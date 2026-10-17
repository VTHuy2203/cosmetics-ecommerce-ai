<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';
require_once __DIR__ . '/CustomerEventService.php';
require_once __DIR__ . '/CustomerScoringService.php';
require_once __DIR__ . '/VoucherService.php';
require_once __DIR__ . '/MoneyAllocationService.php';
require_once __DIR__ . '/ShippingService.php';

final class CheckoutException extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}

final class CheckoutService
{
    public function __construct(private readonly mysqli $db)
    {
    }

    /**
     * @param array<int, array<int, mixed>> $cart
     * @param array{HoVaTen?:string,Email?:string,Sdt?:string,DiaChi?:string,ShippingRegion?:string} $contact
     * @return array<string, mixed>
     */
    public function create(
        string $username,
        array $cart,
        string $paymentMethod,
        array $contact = [],
        string $note = '',
        ?string $voucherCode = null
    ): array {
        if (!in_array($paymentMethod, ['cod', 'qr', 'balance', 'sepay_pg'], true)) {
            throw new CheckoutException('invalid_payment_method', 'Phương thức thanh toán không hợp lệ.');
        }
        if ($cart === []) {
            throw new CheckoutException('empty_cart', 'Giỏ hàng đang trống.');
        }

        $quantities = $this->normalizeCart($cart);
        $this->db->begin_transaction();

        try {
            $customer = $this->lockCustomer($username);
            $isGuest = (int) ($customer['is_guest'] ?? 0) === 1;
            if ($isGuest && $paymentMethod !== 'cod') {
                throw new CheckoutException('guest_payment_method', 'Khách chưa đăng nhập chỉ thanh toán khi nhận hàng (COD).');
            }
            if ($isGuest) {
                // Vouchers are claimed per account; a guest account has none.
                $voucherCode = null;
            }
            $items = [];
            $subtotal = 0;
            $totalWeightGrams = 0;

            $productStmt = $this->db->prepare(
                'SELECT IDSanPham, TenSanPham, GiaTien, SoLuong, TrongLuongGram FROM sanpham WHERE IDSanPham = ? FOR UPDATE'
            );
            foreach ($quantities as $productId => $quantity) {
                $productStmt->bind_param('i', $productId);
                $productStmt->execute();
                $product = $productStmt->get_result()->fetch_assoc();
                if (!$product) {
                    throw new CheckoutException('product_not_found', 'Một sản phẩm không còn tồn tại.');
                }
                if ((int) $product['SoLuong'] < $quantity) {
                    throw new CheckoutException(
                        'insufficient_stock',
                        sprintf('Sản phẩm "%s" chỉ còn %d sản phẩm.', $product['TenSanPham'], $product['SoLuong'])
                    );
                }

                $unitPrice = (int) $product['GiaTien'];
                if ($unitPrice < 0) {
                    throw new CheckoutException('invalid_price', 'Giá sản phẩm không hợp lệ.');
                }
                $lineTotal = $unitPrice * $quantity;
                // Missing weight is allowed: ShippingService rejects it only for weight-tiered rates.
                $unitWeight = max(0, (int) ($product['TrongLuongGram'] ?? 0));
                $subtotal += $lineTotal;
                $totalWeightGrams += $unitWeight * $quantity;
                $items[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            if ($subtotal <= 0) {
                throw new CheckoutException('invalid_total', 'Tổng tiền không hợp lệ.');
            }

            $voucher = null;
            $discountAmount = 0;
            if ($voucherCode !== null && trim($voucherCode) !== '') {
                $voucher = (new VoucherService($this->db))->validate(
                    $voucherCode,
                    (int) $customer['ID_KhachHang'],
                    $subtotal,
                    true
                );
                $discountAmount = (int) $voucher['discount_amount'];
            }

            $lineDiscounts = allocate_order_discount(
                array_column($items, 'line_total'),
                $discountAmount
            );

            try {
                $shipping = (new ShippingService($this->db))->quote(
                    (string) ($contact['ShippingRegion'] ?? ''),
                    $totalWeightGrams,
                    $subtotal,
                    true
                );
            } catch (ShippingException $error) {
                throw new CheckoutException($error->reason, $error->getMessage());
            }
            $shippingFee = $shipping['fee'];
            $amount = max(0, $subtotal - $discountAmount) + $shippingFee;
            $paid = 0;
            $paymentStatus = $paymentMethod === 'cod' ? 'cod_pending' : 'pending';
            $paymentCode = $paymentMethod === 'qr' ? $this->newPaymentCode() : null;
            $sepayInvoice = $paymentMethod === 'sepay_pg' ? $this->newGatewayInvoiceNumber() : null;
            $ttl = max(5, min(1440, (int) app_env('PAYMENT_TTL_MINUTES', '15')));
            $expiresAt = in_array($paymentMethod, ['qr', 'sepay_pg'], true)
                ? date('Y-m-d H:i:s', time() + ($ttl * 60))
                : null;

            if ($paymentMethod === 'balance') {
                $balance = (int) ($customer['SoDu'] ?? 0);
                if ($balance < $amount) {
                    throw new CheckoutException('insufficient_balance', 'Số dư tài khoản không đủ.');
                }
                $newBalance = $balance - $amount;
                $balanceStmt = $this->db->prepare('UPDATE khachhang SET SoDu = ? WHERE ID_KhachHang = ?');
                $customerId = (int) $customer['ID_KhachHang'];
                $balanceStmt->bind_param('ii', $newBalance, $customerId);
                $balanceStmt->execute();
                $paid = 1;
                $paymentStatus = 'paid';
            }

            $name = $this->contactValue($contact['HoVaTen'] ?? $customer['HoVaTen'], 50, 'Họ tên');
            if ($isGuest) {
                // Email is optional for guests; staff confirm the order by phone.
                $email = mb_strtolower(trim((string) ($contact['Email'] ?? '')));
                if ($email !== '' && (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                    throw new CheckoutException('invalid_email', 'Email không hợp lệ.');
                }
            } else {
                $email = $this->contactValue($contact['Email'] ?? $customer['Email'], 100, 'Email');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new CheckoutException('invalid_email', 'Email không hợp lệ.');
                }
            }
            $phone = $this->contactValue($contact['Sdt'] ?? $customer['SDT'], 20, 'Số điện thoại');
            if ($isGuest && ($phone = self::normalizePhone($phone)) === null) {
                throw new CheckoutException('invalid_phone', 'Số điện thoại không hợp lệ.');
            }
            $address = $this->contactValue($contact['DiaChi'] ?? $customer['DiaChi'], 255, 'Địa chỉ');
            $note = mb_substr(trim($note), 0, 255);
            $customerId = (int) $customer['ID_KhachHang'];

            $orderStmt = $this->db->prepare(
                'INSERT INTO donhang
                (ID_KhachHang, HoVaTen, Email, SDT, DiaChi, TongTien, TrangThai, DaThanhToan, GhiChu,
                 PhuongThucThanhToan, TrangThaiThanhToan, MaThanhToan, SePayInvoiceNumber,
                 SoTienHang, PhiVanChuyen, ShippingConfigVersionID, ShippingRegionCode, ShippingRegionName,
                 TongKhoiLuongGram, VoucherID, MaVoucher, SoTienGiam,
                 TonKhoDaGiu, HanThanhToan, ThanhToanLuc)
                VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)'
            );
            $paidAt = $paid ? date('Y-m-d H:i:s') : null;
            $voucherId = $voucher === null ? null : (int) $voucher['id'];
            $appliedVoucherCode = $voucher === null ? null : (string) $voucher['code'];
            $shippingVersionId = $shipping['version_id'];
            $shippingRegionCode = $shipping['region_code'];
            $shippingRegionName = $shipping['region_name'];
            $shippingWeight = $shipping['total_weight_grams'];
            $orderStmt->bind_param(
                'issssiisssssiiissiisiss',
                $customerId,
                $name,
                $email,
                $phone,
                $address,
                $amount,
                $paid,
                $note,
                $paymentMethod,
                $paymentStatus,
                $paymentCode,
                $sepayInvoice,
                $subtotal,
                $shippingFee,
                $shippingVersionId,
                $shippingRegionCode,
                $shippingRegionName,
                $shippingWeight,
                $voucherId,
                $appliedVoucherCode,
                $discountAmount,
                $expiresAt,
                $paidAt
            );
            $orderStmt->execute();
            $orderId = (int) $this->db->insert_id;

            if ($voucher !== null) {
                (new VoucherService($this->db))->redeem($voucher, $customerId, $orderId);
            }

            $detailStmt = $this->db->prepare(
                'INSERT INTO donhangchitiet
                 (ID_DonHang, ID_SanPham, SoLuong, DonGia, ThanhTien, SoTienGiamPhanBo, ThanhTienThucTra)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stockStmt = $this->db->prepare(
                'UPDATE sanpham SET SoLuong = SoLuong - ? WHERE IDSanPham = ? AND SoLuong >= ?'
            );
            foreach ($items as $index => $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $lineTotal = $item['line_total'];
                $lineDiscount = $lineDiscounts[$index];
                $linePaid = $lineTotal - $lineDiscount;
                $detailStmt->bind_param(
                    'iiiiiii',
                    $orderId,
                    $productId,
                    $quantity,
                    $unitPrice,
                    $lineTotal,
                    $lineDiscount,
                    $linePaid
                );
                $detailStmt->execute();
                $stockStmt->bind_param('iii', $quantity, $productId, $quantity);
                $stockStmt->execute();
                if ($stockStmt->affected_rows !== 1) {
                    throw new CheckoutException('stock_race', 'Tồn kho vừa thay đổi. Vui lòng thử lại.');
                }
            }

            $eventPayload = json_encode([
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $eventStmt = $this->db->prepare(
                "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload) VALUES ('order.created', 'order', ?, ?)"
            );
            $aggregateId = (string) $orderId;
            $eventStmt->bind_param('ss', $aggregateId, $eventPayload);
            $eventStmt->execute();

            CustomerEventService::recordMysqli(
                $this->db,
                $customerId,
                CustomerEventService::sessionId(),
                'order_created',
                ['order_id' => $orderId, 'amount' => $amount, 'payment_method' => $paymentMethod, 'item_count' => count($items)]
            );

            $this->db->commit();

            CustomerScoringService::refreshQuietly($customerId, CustomerEventService::sessionId());
            $this->notifyNewOrder($orderId, $name, $amount, $paymentMethod);

            return [
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'payment_code' => $paymentCode,
                'sepay_invoice' => $sepayInvoice,
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'shipping_region_code' => $shipping['region_code'],
                'shipping_region_name' => $shipping['region_name'],
                'total_weight_grams' => $shipping['total_weight_grams'],
                'voucher_code' => $appliedVoucherCode,
                'discount_amount' => $discountAmount,
                'amount' => $amount,
                'expires_at' => $expiresAt,
            ];
        } catch (VoucherException $error) {
            $this->db->rollback();
            throw new CheckoutException($error->reason, $error->getMessage());
        } catch (Throwable $error) {
            $this->db->rollback();
            throw $error;
        }
    }

    /** @param array<int, array<int, mixed>> $cart @return array<int, int> */
    private function normalizeCart(array $cart): array
    {
        $result = [];
        foreach ($cart as $row) {
            $productId = filter_var($row[0] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($row[3] ?? null, FILTER_VALIDATE_INT);
            if (!$productId || !$quantity || $quantity < 1 || $quantity > 100) {
                throw new CheckoutException('invalid_cart', 'Dữ liệu giỏ hàng không hợp lệ.');
            }
            $result[$productId] = ($result[$productId] ?? 0) + $quantity;
            if ($result[$productId] > 100) {
                throw new CheckoutException('invalid_quantity', 'Số lượng sản phẩm vượt giới hạn.');
            }
        }
        return $result;
    }

    /** @return array<string, mixed> */
    private function lockCustomer(string $username): array
    {
        $stmt = $this->db->prepare(
            'SELECT ID_KhachHang, HoVaTen, Email, SDT, DiaChi, SoDu, is_guest FROM khachhang WHERE TenDangNhap = ? FOR UPDATE'
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        if (!$customer) {
            throw new CheckoutException('customer_not_found', 'Không tìm thấy khách hàng.');
        }
        return $customer;
    }

    private function newPaymentCode(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', app_env('SEPAY_PAYMENT_PREFIX', 'MO') ?? 'MO'));
        $prefix = substr($prefix ?: 'MO', 0, 5);
        return $prefix . strtoupper(bin2hex(random_bytes(5)));
    }

    private function newGatewayInvoiceNumber(): string
    {
        return 'MOSHOP_' . gmdate('YmdHis') . '_' . strtoupper(bin2hex(random_bytes(6)));
    }

    private function notifyNewOrder(int $orderId, string $customerName, int $amount, string $paymentMethod): void
    {
        // CLI is used by integration tests and background maintenance; never emit
        // customer-facing realtime events from those processes.
        if (PHP_SAPI === 'cli') {
            return;
        }

        $pushFile = dirname(__DIR__, 3) . '/WebSocketServer/WebSocket_Push.php';
        try {
            if (!function_exists('websocket_push_new_order') && !function_exists('websocket_push') && is_file($pushFile)) {
                require_once $pushFile;
            }
            if (function_exists('websocket_push_new_order')) {
                websocket_push_new_order($orderId, $customerName, $amount);
            } elseif (function_exists('websocket_notify_new_order')) {
                websocket_notify_new_order($orderId, $customerName, $amount);
            } else {
                error_log("New order realtime helper is unavailable for order #{$orderId}.");
            }
        } catch (Throwable $error) {
            // The order is already committed. Realtime delivery must not turn a
            // successful purchase into an error shown to the customer.
            error_log(
                "New order realtime notification failed for order #{$orderId} ({$paymentMethod}): "
                . $error->getMessage()
            );
        }
    }

    /** Vietnamese mobile number in its 0xxxxxxxxx form, or null when it is not one. */
    public static function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/[\s.\-()]/', '', $phone) ?? '';
        if (preg_match('/^(?:0|\+84|84)((?:3|5|7|8|9)\d{8})$/', $phone, $match) !== 1) {
            return null;
        }
        return '0' . $match[1];
    }

    /**
     * Passwordless account that owns a guest's orders, one per phone number (orders require a
     * khachhang row). Login refuses it; the latest name and address are kept for staff.
     * @return string the account's username, to pass to create()
     */
    public function ensureGuestCustomer(string $name, string $phone, string $address): string
    {
        $name = $this->contactValue($name, 50, 'Họ tên');
        $address = $this->contactValue($address, 255, 'Địa chỉ');
        $phone = self::normalizePhone($phone) ?? throw new CheckoutException('invalid_phone', 'Số điện thoại không hợp lệ.');
        $username = 'guest_' . $phone;
        // Unique, never delivered: guest accounts do not receive account email.
        $placeholderEmail = $username . '@guest.moshop.invalid';
        $unusableHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "INSERT INTO khachhang (TenDangNhap, MatKhau, HoVaTen, Email, SDT, DiaChi, Role, is_guest)
             VALUES (?, ?, ?, ?, ?, ?, 'customer', 1)
             ON DUPLICATE KEY UPDATE HoVaTen = IF(is_guest = 1, VALUES(HoVaTen), HoVaTen),
                                     DiaChi = IF(is_guest = 1, VALUES(DiaChi), DiaChi)"
        );
        $stmt->bind_param('ssssss', $username, $unusableHash, $name, $placeholderEmail, $phone, $address);
        $stmt->execute();

        $check = $this->db->prepare('SELECT is_guest FROM khachhang WHERE TenDangNhap = ? LIMIT 1');
        $check->bind_param('s', $username);
        $check->execute();
        if ((int) ($check->get_result()->fetch_row()[0] ?? 0) !== 1) {
            // A real account holds the reserved name: never attach a guest order to it.
            throw new CheckoutException('guest_account_conflict', 'Không thể tạo đơn cho số điện thoại này. Vui lòng đăng nhập.');
        }
        return $username;
    }

    private function contactValue(mixed $value, int $maxLength, string $label): string
    {
        $value = trim((string) $value);
        if ($value === '' || mb_strlen($value) > $maxLength) {
            throw new CheckoutException('invalid_contact', $label . ' không hợp lệ.');
        }
        return $value;
    }
}
