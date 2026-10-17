<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';
require_once dirname(__DIR__, 3) . '/WebSocketServer/WebSocket_Push.php';

final class MarketingService
{
    public function __construct(private readonly mysqli $db)
    {
    }

    /** @return array{vouchers:int,broadcasts:int,expired:int} */
    public function publishDue(int $limit = 100): array
    {
        $this->db->query(
            "UPDATE vouchers SET status = 'expired' WHERE status IN ('active','scheduled') AND ends_at <= NOW()"
        );
        $expired = $this->db->affected_rows;
        $limit = max(1, min(500, $limit));

        $voucherIds = [];
        $result = $this->db->query(
            "SELECT id FROM vouchers
             WHERE status IN ('scheduled','active') AND starts_at <= NOW() AND ends_at > NOW() AND published_at IS NULL
             ORDER BY starts_at, id LIMIT {$limit}"
        );
        while ($row = $result->fetch_assoc()) {
            $voucherIds[] = (int) $row['id'];
        }
        $voucherCount = 0;
        foreach ($voucherIds as $voucherId) {
            $voucherCount += $this->publishVoucher($voucherId) ? 1 : 0;
        }

        $broadcastIds = [];
        $result = $this->db->query(
            "SELECT id FROM broadcast_notifications
             WHERE status = 'scheduled' AND scheduled_at <= NOW()
             ORDER BY scheduled_at, id LIMIT {$limit}"
        );
        while ($row = $result->fetch_assoc()) {
            $broadcastIds[] = (int) $row['id'];
        }
        $broadcastCount = 0;
        foreach ($broadcastIds as $broadcastId) {
            $broadcastCount += $this->publishBroadcast($broadcastId) ? 1 : 0;
        }

        return ['vouchers' => $voucherCount, 'broadcasts' => $broadcastCount, 'expired' => $expired];
    }

    public function publishVoucher(int $voucherId): bool
    {
        $payload = null;
        $this->db->begin_transaction();
        try {
            $select = $this->db->prepare(
                "SELECT id, code, name, discount_percent, ends_at, announce_on_publish,
                        announcement_title, announcement_message, status, starts_at, published_at
                 FROM vouchers WHERE id = ? FOR UPDATE"
            );
            $select->bind_param('i', $voucherId);
            $select->execute();
            $voucher = $select->get_result()->fetch_assoc();
            if (!$voucher || $voucher['published_at'] !== null || !in_array($voucher['status'], ['scheduled', 'active'], true)
                || strtotime((string) $voucher['starts_at']) > time() || strtotime((string) $voucher['ends_at']) <= time()) {
                $this->db->commit();
                return false;
            }

            $update = $this->db->prepare("UPDATE vouchers SET status = 'active', published_at = NOW() WHERE id = ?");
            $update->bind_param('i', $voucherId);
            $update->execute();

            if ((int) $voucher['announce_on_publish'] === 1) {
                $title = trim((string) ($voucher['announcement_title'] ?? ''));
                $message = trim((string) ($voucher['announcement_message'] ?? ''));
                if ($title === '') {
                    $title = 'Voucher mới: ' . $voucher['code'];
                }
                if ($message === '') {
                    $message = sprintf(
                        '%s giảm %d%%, dùng mã %s trước %s.',
                        $voucher['name'],
                        $voucher['discount_percent'],
                        $voucher['code'],
                        date('d/m/Y H:i', strtotime((string) $voucher['ends_at']))
                    );
                }
                $this->insertCustomerNotifications('voucher', $title, $message);
                $payload = ['type' => 'voucher_published', 'title' => $title, 'message' => $message, 'sound' => 'notification'];
            }
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }

        if ($payload !== null) {
            websocket_push(['user_channel'], $payload);
        }
        return true;
    }

    public function publishBroadcast(int $broadcastId): bool
    {
        $payload = null;
        $this->db->begin_transaction();
        try {
            $select = $this->db->prepare(
                "SELECT id, title, message, type, status, scheduled_at
                 FROM broadcast_notifications WHERE id = ? FOR UPDATE"
            );
            $select->bind_param('i', $broadcastId);
            $select->execute();
            $broadcast = $select->get_result()->fetch_assoc();
            if (!$broadcast || $broadcast['status'] !== 'scheduled'
                || strtotime((string) $broadcast['scheduled_at']) > time()) {
                $this->db->commit();
                return false;
            }
            $update = $this->db->prepare(
                "UPDATE broadcast_notifications SET status = 'published', published_at = NOW() WHERE id = ?"
            );
            $update->bind_param('i', $broadcastId);
            $update->execute();
            $this->insertCustomerNotifications((string) $broadcast['type'], (string) $broadcast['title'], (string) $broadcast['message']);
            $payload = [
                'type' => 'marketing_broadcast',
                'title' => (string) $broadcast['title'],
                'message' => (string) $broadcast['message'],
                'sound' => 'notification',
            ];
            $this->db->commit();
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
        websocket_push(['user_channel'], $payload);
        return true;
    }

    private function insertCustomerNotifications(string $type, string $title, string $message): void
    {
        $insert = $this->db->prepare(
            "INSERT INTO customer_notifications(user_id, type, title, message)
             SELECT ID_KhachHang, ?, ?, ? FROM khachhang
             WHERE Role IS NULL OR Role = '' OR Role = 'customer'"
        );
        $insert->bind_param('sss', $type, $title, $message);
        $insert->execute();
    }
}
