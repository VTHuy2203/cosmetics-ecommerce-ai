<?php
declare(strict_types=1);

require_once __DIR__ . '/support/environment.php';
require_once dirname(__DIR__) . '/TTTT/config/security.php';
require_once dirname(__DIR__) . '/TTTT/WebBanHangMyPham/src/Services/ChatSessionService.php';

$pdo = chat_session_pdo();
$tableExists = static function (string $table) use ($pdo): bool {
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $statement->execute([$table]);
    return (int) $statement->fetchColumn() === 1;
};
$createdSessions = !$tableExists('chat_sessions');
$createdMessages = !$tableExists('chat_messages');
$pdo->exec("CREATE TABLE IF NOT EXISTS chat_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, session_id VARCHAR(128) NOT NULL UNIQUE,
    user_id INT NULL, title VARCHAR(160) NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, last_message_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, session_id VARCHAR(128) NOT NULL,
    message TEXT NOT NULL, direction ENUM('user','bot') NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$cleanupTables = static function () use ($pdo, $createdMessages, $createdSessions): void {
    if ($createdMessages) { $pdo->exec('DROP TABLE chat_messages'); }
    if ($createdSessions) { $pdo->exec('DROP TABLE chat_sessions'); }
};
$pdo->beginTransaction();
try {
    $fixture = $pdo->prepare(
        "INSERT INTO khachhang(ID_KhachHang,TenDangNhap,HoVaTen,Role)
         VALUES(?,?,?,'customer') ON DUPLICATE KEY UPDATE Role='customer'"
    );
    foreach ([9801, 9802] as $id) {
        $fixture->execute([$id, 'chat-test-' . $id, 'Chat test']);
    }
    $users = [9801, 9802];
    $_SESSION['IDNguoiDung'] = (int) $users[0];
    unset($_SESSION['chat_current_session_id']);
    $firstSession = chat_session_create($pdo, (int) $users[0]);
    if (chat_session_resolve($pdo, (int) $users[0], $firstSession) !== $firstSession) {
        throw new RuntimeException('The owner could not reopen the chat session.');
    }
    $historyInsert = $pdo->prepare(
        'INSERT INTO chat_messages(user_id,session_id,message,direction) VALUES(?,?,?,?)'
    );
    foreach ([
        ['Tôi cần kem dưỡng', 'user'], ['Bạn dùng loại da nào?', 'bot'],
        ['Da khô', 'user'], ['Tôi sẽ tìm sản phẩm phù hợp.', 'bot'],
        ['Ưu tiên không mùi', 'user'], ['Đã hiểu yêu cầu.', 'bot'],
        ['Tin nhắn hiện tại', 'user'],
    ] as [$content, $direction]) {
        $historyInsert->execute([(int) $users[0], $firstSession, $content, $direction]);
    }
    $history = chat_session_recent_history($pdo, $firstSession, (int) $users[0], 'Tin nhắn hiện tại');
    if (count($history) !== 6 || end($history)['content'] !== 'Đã hiểu yêu cầu.') {
        throw new RuntimeException('Recent AI history is not bounded or did not exclude the current message.');
    }
    if (chat_session_recent_history($pdo, $firstSession, (int) $users[1], 'x') !== []) {
        throw new RuntimeException('AI history leaked across customer ownership.');
    }

    $secondSession = chat_session_create($pdo, (int) $users[0]);
    if (chat_session_resolve($pdo, (int) $users[0], $firstSession) !== $firstSession) {
        throw new RuntimeException('An older owned session could not be reopened.');
    }
    if (chat_session_resolve($pdo, (int) $users[0], null) !== $firstSession) {
        throw new RuntimeException('Reload did not retain the currently selected session.');
    }

    $insertMessage = $pdo->prepare(
        "INSERT INTO chat_messages (user_id, session_id, message, direction) VALUES (?, ?, 'delete test', 'user')"
    );
    $insertMessage->execute([(int) $users[0], $secondSession]);
    $insertAppointment = $pdo->prepare(
        "INSERT INTO chat_appointments (user_id, session_id, appt_at, note) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY), 'keep appointment')"
    );
    $insertAppointment->execute([(int) $users[0], $secondSession]);

    if (!chat_session_delete($pdo, $secondSession, (int) $users[0])) {
        throw new RuntimeException('The owner could not delete the chat session.');
    }
    $count = $pdo->prepare('SELECT COUNT(*) FROM chat_sessions WHERE session_id = ?');
    $count->execute([$secondSession]);
    if ((int) $count->fetchColumn() !== 0) {
        throw new RuntimeException('Deleted chat session is still visible in history.');
    }
    $count = $pdo->prepare('SELECT COUNT(*) FROM chat_messages WHERE session_id = ?');
    $count->execute([$secondSession]);
    if ((int) $count->fetchColumn() !== 0) {
        throw new RuntimeException('Messages of the deleted session were retained.');
    }
    $count = $pdo->prepare('SELECT COUNT(*) FROM chat_appointments WHERE session_id = ?');
    $count->execute([$secondSession]);
    if ((int) $count->fetchColumn() !== 1) {
        throw new RuntimeException('Deleting chat unexpectedly removed the appointment.');
    }

    $_SESSION['IDNguoiDung'] = (int) $users[1];
    unset($_SESSION['chat_current_session_id']);
    $isolatedSession = chat_session_resolve($pdo, (int) $users[1], $firstSession);
    if ($isolatedSession === $firstSession) {
        throw new RuntimeException('A customer accessed another customer chat session.');
    }

    $pdo->rollBack();
    $cleanupTables();
    echo "chat_sessions_integration=ok\n";
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $cleanupTables();
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
