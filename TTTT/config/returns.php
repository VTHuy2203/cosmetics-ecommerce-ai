<?php
declare(strict_types=1);

/**
 * Atomically approve or reject one product return request.
 * Refunds contain only immutable product money paid after voucher allocation;
 * shipping never enters the refundable amount.
 */
function process_return_request(
    mysqli $connection,
    int $requestId,
    string $action,
    int $actorId,
    string $actorRole
): void {
    if ($requestId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
        throw new DomainException('Invalid return action.');
    }
    if ($actorId <= 0 || !in_array($actorRole, ['admin', 'manager', 'employee'], true)) {
        throw new DomainException('Invalid return actor.');
    }

    $connection->begin_transaction();
    try {
        $actor = $connection->prepare('SELECT Role FROM khachhang WHERE ID_KhachHang = ? FOR SHARE');
        $actor->bind_param('i', $actorId);
        $actor->execute();
        if ($actor->get_result()->fetch_column() !== $actorRole) {
            throw new DomainException('Return actor role is no longer valid.');
        }

        // The detail lock serializes separate partial approvals for one line;
        // the request lock makes retries safe.
        $lock = $connection->prepare(
            "SELECT r.ID_Request, r.ID_DonHang, r.ID_KhachHang, r.ID_SanPham,
                    r.SoLuongYeuCau, r.TrangThai,
                    d.GhiChu, d.TrangThai AS TrangThaiDon, d.TrangThaiThanhToan,
                    dc.SoLuong, dc.ThanhTienThucTra,
                    COALESCE(sp.TenSanPham, CONCAT('Sản phẩm #', r.ID_SanPham)) AS TenSanPham
             FROM return_refund_requests r
             INNER JOIN donhang d
                ON d.ID_DonHang = r.ID_DonHang AND d.ID_KhachHang = r.ID_KhachHang
             INNER JOIN donhangchitiet dc
                ON dc.ID_DonHang = r.ID_DonHang AND dc.ID_SanPham = r.ID_SanPham
             LEFT JOIN sanpham sp ON sp.IDSanPham = r.ID_SanPham
             WHERE r.ID_Request = ? FOR UPDATE"
        );
        $lock->bind_param('i', $requestId);
        $lock->execute();
        $request = $lock->get_result()->fetch_assoc();
        if (!$request || $request['TrangThai'] !== 'pending') {
            throw new DomainException('Return request is no longer pending.');
        }

        $refundAmount = 0;
        if ($action === 'approve') {
            if ((int) $request['TrangThaiDon'] !== 3 || $request['TrangThaiThanhToan'] !== 'paid') {
                throw new DomainException('Order is no longer eligible for a refund.');
            }

            $orderedQuantity = (int) $request['SoLuong'];
            $requestedQuantity = (int) $request['SoLuongYeuCau'];
            $linePaid = (int) $request['ThanhTienThucTra'];
            if ($orderedQuantity <= 0 || $requestedQuantity <= 0 || $linePaid <= 0) {
                throw new DomainException('Invalid immutable refund snapshot.');
            }

            $history = $connection->prepare(
                "SELECT COALESCE(SUM(r.SoLuongYeuCau), 0) AS approved_quantity,
                        COALESCE(SUM(t.amount), 0) AS refunded_amount,
                        COALESCE(SUM(CASE WHEN t.id IS NULL THEN 1 ELSE 0 END), 0) AS unreconciled_count
                 FROM return_refund_requests r
                 LEFT JOIN refund_transactions t ON t.request_id = r.ID_Request
                 WHERE r.ID_DonHang = ? AND r.ID_SanPham = ? AND r.TrangThai = 'approved'"
            );
            $orderId = (int) $request['ID_DonHang'];
            $productId = (int) $request['ID_SanPham'];
            $history->bind_param('ii', $orderId, $productId);
            $history->execute();
            $totals = $history->get_result()->fetch_assoc();
            if ((int) ($totals['unreconciled_count'] ?? 0) > 0) {
                throw new DomainException('Legacy refund history requires reconciliation.');
            }

            $approvedQuantity = (int) ($totals['approved_quantity'] ?? 0);
            $alreadyRefunded = (int) ($totals['refunded_amount'] ?? 0);
            $newQuantity = $approvedQuantity + $requestedQuantity;
            if ($newQuantity > $orderedQuantity) {
                throw new DomainException('Refund quantity exceeds the purchased quantity.');
            }

            // Cumulative integer arithmetic gives the final unit all rounding
            // residue and keeps the sum at or below the immutable line payment.
            $cumulativeEntitlement = intdiv($linePaid * $newQuantity, $orderedQuantity);
            $refundAmount = $cumulativeEntitlement - $alreadyRefunded;
            if ($refundAmount <= 0 || $alreadyRefunded + $refundAmount > $linePaid) {
                throw new DomainException('Invalid refund amount.');
            }

            $ledger = $connection->prepare(
                "INSERT INTO refund_transactions
                 (request_id, order_id, customer_id, product_id, quantity, amount, destination, actor_id, actor_role)
                 VALUES (?, ?, ?, ?, ?, ?, 'wallet', ?, ?)"
            );
            $customerId = (int) $request['ID_KhachHang'];
            $ledger->bind_param(
                'iiiiiiis',
                $requestId,
                $orderId,
                $customerId,
                $productId,
                $requestedQuantity,
                $refundAmount,
                $actorId,
                $actorRole
            );
            $ledger->execute();

            $credit = $connection->prepare('UPDATE khachhang SET SoDu = SoDu + ? WHERE ID_KhachHang = ?');
            $credit->bind_param('ii', $refundAmount, $customerId);
            $credit->execute();
            if ($credit->affected_rows !== 1) {
                throw new RuntimeException('Refund customer no longer exists.');
            }

            $note = trim((string) $request['GhiChu']);
            $marker = sprintf(
                '[ĐÃ HOÀN TRẢ #%d] %s x%d: %sđ',
                $requestId,
                (string) $request['TenSanPham'],
                $requestedQuantity,
                number_format($refundAmount, 0, ',', '.')
            );
            $note = mb_substr($note === '' ? $marker : $note . ' | ' . $marker, 0, 4000);
            $updateOrder = $connection->prepare('UPDATE donhang SET GhiChu = ? WHERE ID_DonHang = ?');
            $updateOrder->bind_param('si', $note, $orderId);
            $updateOrder->execute();
        }

        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $processedAt = date('Y-m-d H:i:s');
        $updateRequest = $connection->prepare(
            "UPDATE return_refund_requests
             SET TrangThai = ?, SoTienHoan = ?, XuLyBoi = ?, VaiTroXuLy = ?, ThoiGianXuLy = ?
             WHERE ID_Request = ? AND TrangThai = 'pending'"
        );
        $storedAmount = $action === 'approve' ? $refundAmount : null;
        $updateRequest->bind_param('siissi', $newStatus, $storedAmount, $actorId, $actorRole, $processedAt, $requestId);
        $updateRequest->execute();
        if ($updateRequest->affected_rows !== 1) {
            throw new RuntimeException('Return request was concurrently processed.');
        }

        $payload = json_encode([
            'request_id' => $requestId,
            'order_id' => (int) $request['ID_DonHang'],
            'customer_id' => (int) $request['ID_KhachHang'],
            'product_id' => (int) $request['ID_SanPham'],
            'quantity' => (int) $request['SoLuongYeuCau'],
            'refund_amount' => $refundAmount,
            'shipping_refund_amount' => 0,
            'destination' => $action === 'approve' ? 'wallet' : null,
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $eventType = $action === 'approve' ? 'return.approved' : 'return.rejected';
        $event = $connection->prepare(
            "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
             VALUES (?, 'return_request', ?, ?)"
        );
        $aggregateId = (string) $requestId;
        $event->bind_param('sss', $eventType, $aggregateId, $payload);
        $event->execute();
        $connection->commit();
    } catch (Throwable $error) {
        $connection->rollback();
        throw $error;
    }
}

/** @param array<string, mixed> $row */
function return_refund_preview_amount(array $row): int
{
    if (isset($row['SoTienHoan']) && $row['SoTienHoan'] !== '') {
        return max(0, (int) $row['SoTienHoan']);
    }
    $linePaid = max(0, (int) ($row['ThanhTienThucTra'] ?? 0));
    $orderedQuantity = max(0, (int) ($row['ProductQuantity'] ?? $row['SoLuong'] ?? 0));
    $requestedQuantity = max(0, (int) ($row['SoLuongYeuCau'] ?? 0));
    if ($linePaid === 0 || $orderedQuantity === 0 || $requestedQuantity === 0) {
        return 0;
    }
    return intdiv($linePaid * min($requestedQuantity, $orderedQuantity), $orderedQuantity);
}
