<?php
declare(strict_types=1);

ini_set('display_errors', '0');

require_once __DIR__ . '/../DB_Connect.php';
require_once dirname(__DIR__, 3) . '/config/security.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo 'error';
    exit;
}

require_csrf();
require_turnstile('register');

/** @return never */
function registration_response(string $value, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $value;
    exit;
}

/** @param list<string> $paths */
function remove_registration_uploads(array $paths): void
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

$name = trim((string) ($_POST['HoVaTen'] ?? ''));
$email = strtolower(trim((string) ($_POST['Email'] ?? '')));
$phone = trim((string) ($_POST['Sdt'] ?? ''));
$address = trim((string) ($_POST['DiaChi'] ?? ''));
$username = trim((string) ($_POST['TenDangNhap'] ?? ''));
$plainPassword = (string) ($_POST['MatKhau'] ?? '');
$verificationCode = trim((string) ($_POST['verification_code'] ?? ''));
$requestedRole = strtolower(trim((string) ($_POST['register_role'] ?? 'customer')));

if (!in_array($requestedRole, ['customer', 'shipper', 'employee'], true)) {
    registration_response('error', 422);
}
// Phone and address are stored on the account and prefill every checkout: both are required,
// the phone as a normalized Vietnamese mobile number (0xxxxxxxxx).
$phone = preg_replace('/[\s.\-()]/', '', $phone) ?? '';
if (preg_match('/^(?:0|\+84|84)((?:3|5|7|8|9)\d{8})$/', $phone, $phoneMatch) === 1) {
    $phone = '0' . $phoneMatch[1];
} else {
    registration_response('invalid_phone', 422);
}
if (mb_strlen($address) < 10 || mb_strlen($address) > 255) {
    registration_response('invalid_address', 422);
}
if ($name === '' || mb_strlen($name) > 50
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100
    || !preg_match('/^[A-Za-z0-9._-]{4,50}$/', $username)
    // "guest_" names belong to guest checkout accounts (CheckoutService::ensureGuestCustomer).
    || str_starts_with(strtolower($username), 'guest_')) {
    registration_response('error', 422);
}
if (strlen($plainPassword) < 10 || strlen($plainPassword) > 255) {
    registration_response('weak_password', 422);
}
if ($verificationCode === '' || !preg_match('/^[0-9]{6}$/', $verificationCode)) {
    registration_response('missing_code', 422);
}

$isStaffRequest = $requestedRole !== 'customer';
$license = trim((string) ($_POST['license'] ?? ''));
$vehicle = mb_substr(trim((string) ($_POST['vehicle'] ?? '')), 0, 50);
$hometown = mb_substr(trim((string) ($_POST['hometown'] ?? '')), 0, 255);
$permanentAddress = mb_substr(trim((string) ($_POST['permanent_address'] ?? $address)), 0, 1000);
$temporaryAddress = mb_substr(trim((string) ($_POST['temporary_address'] ?? '')), 0, 1000);
$note = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 2000);
$workLocation = trim((string) ($_POST['work_location'] ?? ''));
if (!in_array($workLocation, ['', 'in-store', 'remote'], true)) {
    registration_response('error', 422);
}
if ($isStaffRequest && ($license === '' || mb_strlen($license) > 50 || $hometown === '' || $temporaryAddress === '')) {
    registration_response('invalid_staff_data', 422);
}

$storedFiles = [];
$absoluteFiles = [];
if ($isStaffRequest) {
    $uploads = $_FILES['id_card_images'] ?? null;
    if (!is_array($uploads) || !isset($uploads['name'], $uploads['tmp_name'], $uploads['error'], $uploads['size'])
        || !is_array($uploads['name']) || count($uploads['name']) !== 2) {
        registration_response('invalid_upload', 422);
    }

    $uploadDirectory = dirname(__DIR__, 3) . '/storage/private/shipper_id_cards';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
        registration_response('error', 500);
    }

    $allowedMimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($uploads['name'] as $index => $_originalName) {
        if (($uploads['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || (int) ($uploads['size'][$index] ?? 0) < 1
            || (int) $uploads['size'][$index] > 5 * 1024 * 1024
            || !is_uploaded_file((string) ($uploads['tmp_name'][$index] ?? ''))) {
            remove_registration_uploads($absoluteFiles);
            registration_response('invalid_upload', 422);
        }

        $temporaryFile = (string) $uploads['tmp_name'][$index];
        $mime = $finfo->file($temporaryFile);
        if (!is_string($mime) || !isset($allowedMimeTypes[$mime])) {
            remove_registration_uploads($absoluteFiles);
            registration_response('invalid_upload', 422);
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedMimeTypes[$mime];
        $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($temporaryFile, $absolutePath)) {
            remove_registration_uploads($absoluteFiles);
            registration_response('invalid_upload', 500);
        }
        chmod($absolutePath, 0640);
        $absoluteFiles[] = $absolutePath;
        $storedFiles[] = 'shipper_id_cards/' . $fileName;
    }
}

$conn->begin_transaction();
try {
    $otp = $conn->prepare(
        'SELECT id, used, expires_at FROM email_verifications
         WHERE email = ? AND code = ? ORDER BY created_at DESC LIMIT 1 FOR UPDATE'
    );
    $otp->bind_param('ss', $email, $verificationCode);
    $otp->execute();
    $verification = $otp->get_result()->fetch_assoc();
    if (!$verification) {
        $conn->rollback();
        remove_registration_uploads($absoluteFiles);
        registration_response('invalid_code', 422);
    }
    if ((int) $verification['used'] === 1) {
        $conn->rollback();
        remove_registration_uploads($absoluteFiles);
        registration_response('code_used', 422);
    }
    if (strtotime((string) $verification['expires_at']) <= time()) {
        $conn->rollback();
        remove_registration_uploads($absoluteFiles);
        registration_response('code_expired', 422);
    }

    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $initialRole = 'customer';
    $insertUser = $conn->prepare(
        'INSERT INTO khachhang (TenDangNhap, MatKhau, HoVaTen, Email, SDT, DiaChi, Role)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $insertUser->bind_param('sssssss', $username, $passwordHash, $name, $email, $phone, $address, $initialRole);
    $insertUser->execute();
    $userId = (int) $conn->insert_id;

    if ($isStaffRequest) {
        $front = $storedFiles[0];
        $back = $storedFiles[1];
        $insertRequest = $conn->prepare(
            "INSERT INTO shipper_requests
             (user_id, license, vehicle, hometown, permanent_address, temporary_address,
              id_card_front, id_card_back, note, work_location, requested_role, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, 'pending')"
        );
        $insertRequest->bind_param(
            'issssssssss',
            $userId,
            $license,
            $vehicle,
            $hometown,
            $permanentAddress,
            $temporaryAddress,
            $front,
            $back,
            $note,
            $workLocation,
            $requestedRole
        );
        $insertRequest->execute();
    }

    $markUsed = $conn->prepare('UPDATE email_verifications SET used = 1 WHERE id = ? AND used = 0');
    $verificationId = (int) $verification['id'];
    $markUsed->bind_param('i', $verificationId);
    $markUsed->execute();
    if ($markUsed->affected_rows !== 1) {
        throw new RuntimeException('OTP was consumed concurrently.');
    }

    $payload = json_encode(
        ['user_id' => $userId, 'requested_role' => $requestedRole],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    $event = $conn->prepare(
        "INSERT INTO outbox_events(event_type, aggregate_type, aggregate_id, payload)
         VALUES ('user.registered', 'user', ?, ?)"
    );
    $aggregateId = (string) $userId;
    $event->bind_param('ss', $aggregateId, $payload);
    $event->execute();

    $conn->commit();
    registration_response($requestedRole === 'customer' ? 'success' : $requestedRole . '_pending');
} catch (mysqli_sql_exception $error) {
    $conn->rollback();
    remove_registration_uploads($absoluteFiles);
    if ((int) $error->getCode() === 1062) {
        registration_response('tontai', 409);
    }
    error_log('Registration failed: ' . $error->getMessage());
    registration_response('error', 500);
} catch (Throwable $error) {
    $conn->rollback();
    remove_registration_uploads($absoluteFiles);
    error_log('Registration failed: ' . $error->getMessage());
    registration_response('error', 500);
}
