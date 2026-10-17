<?php
declare(strict_types=1);

function catalog_enqueue_rag_event(mysqli $conn, int $productId, string $action): void
{
    if ($productId < 1 || !in_array($action, ['upsert', 'delete'], true)) {
        throw new InvalidArgumentException('Invalid catalog event.');
    }
    $eventKey = 'catalog:' . $productId . ':' . bin2hex(random_bytes(16));
    $payload = json_encode([
        'product_id' => $productId,
        'action' => $action,
        'index_version' => 'catalog-v2-hash-v1',
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $statement = $conn->prepare(
        "INSERT INTO outbox_events(event_type,aggregate_type,aggregate_id,event_key,payload)
         VALUES ('catalog.changed','product',?,?,?)"
    );
    $aggregateId = (string) $productId;
    $statement->bind_param('sss', $aggregateId, $eventKey, $payload);
    $statement->execute();
    $statement->close();
}

/**
 * Delete a catalog row only when no business record depends on it.
 * Database RESTRICT constraints remain the final concurrency guard.
 *
 * @return 'deleted'|'invalid'|'missing'|'in_use'|'error'
 */
function catalog_delete(mysqli $conn, string $entity, int $id): string
{
    if ($id < 1) {
        return 'invalid';
    }

    $contracts = [
        'category' => [
            'exists' => 'SELECT 1 FROM danhmuc WHERE IDDanhMuc = ? LIMIT 1',
            'used' => 'SELECT 1 FROM sanpham WHERE IDDanhMuc = ? LIMIT 1',
            'delete' => 'DELETE FROM danhmuc WHERE IDDanhMuc = ?',
        ],
        'brand' => [
            'exists' => 'SELECT 1 FROM thuonghieu WHERE IDThuongHieu = ? LIMIT 1',
            'used' => 'SELECT 1 FROM sanpham WHERE IDThuongHieu = ? LIMIT 1',
            'delete' => 'DELETE FROM thuonghieu WHERE IDThuongHieu = ?',
        ],
        'product' => [
            'exists' => 'SELECT 1 FROM sanpham WHERE IDSanPham = ? LIMIT 1',
            'used' => 'SELECT 1 FROM donhangchitiet WHERE ID_SanPham = ? LIMIT 1',
            'delete' => 'DELETE FROM sanpham WHERE IDSanPham = ?',
        ],
    ];
    if (!isset($contracts[$entity])) {
        return 'invalid';
    }

    try {
        $contract = $contracts[$entity];
        foreach (['exists', 'used'] as $query) {
            $statement = $conn->prepare($contract[$query]);
            $statement->bind_param('i', $id);
            $statement->execute();
            $found = $statement->get_result()->fetch_row() !== null;
            $statement->close();
            if ($query === 'exists' && !$found) {
                return 'missing';
            }
            if ($query === 'used' && $found) {
                return 'in_use';
            }
        }

        if ($entity === 'product') {
            $conn->begin_transaction();
        }
        $statement = $conn->prepare($contract['delete']);
        $statement->bind_param('i', $id);
        $ok = $statement->execute();
        $affected = $statement->affected_rows;
        $errno = $statement->errno;
        $statement->close();
        if (!$ok && $errno === 1451) {
            return 'in_use';
        }
        if ($ok && $affected === 1 && $entity === 'product') {
            catalog_enqueue_rag_event($conn, $id, 'delete');
            $conn->commit();
        } elseif ($entity === 'product') {
            $conn->rollback();
        }
        return $ok && $affected === 1 ? 'deleted' : 'error';
    } catch (mysqli_sql_exception $error) {
        if ($entity === 'product') {
            try { $conn->rollback(); } catch (Throwable) {}
        }
        return (int) $error->getCode() === 1451 ? 'in_use' : 'error';
    }
}

function catalog_delete_redirect(string $page, string $result): never
{
    $allowed = ['deleted', 'invalid', 'missing', 'in_use', 'error'];
    $status = in_array($result, $allowed, true) ? $result : 'error';
    header('Location: ' . $page . '?delete_status=' . rawurlencode($status), true, 303);
    exit;
}

function catalog_delete_alert(): string
{
    $status = is_string($_GET['delete_status'] ?? null) ? $_GET['delete_status'] : '';
    $messages = [
        'deleted' => ['success', 'Đã xóa mục không còn được sử dụng.'],
        'invalid' => ['danger', 'Mã dữ liệu không hợp lệ.'],
        'missing' => ['warning', 'Dữ liệu không tồn tại hoặc đã được xử lý.'],
        'in_use' => ['warning', 'Không thể xóa vì dữ liệu đang được sản phẩm hoặc đơn hàng sử dụng.'],
        'error' => ['danger', 'Không thể xóa dữ liệu. Vui lòng thử lại hoặc kiểm tra nhật ký hệ thống.'],
    ];
    if (!isset($messages[$status])) {
        return '';
    }
    [$type, $message] = $messages[$status];
    return '<div class="alert alert-' . $type . '" role="alert">'
        . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
}
