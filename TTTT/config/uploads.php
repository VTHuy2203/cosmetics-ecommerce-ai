<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';

/**
 * @param array<string, mixed> $file
 * @return string generated filename (without directory)
 */
function store_public_image_upload(array $file, string $directory, int $maxBytes = 5_242_880): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
        || (int) ($file['size'] ?? 0) < 1
        || (int) $file['size'] > $maxBytes) {
        throw new RuntimeException('Tệp tải lên không hợp lệ hoặc vượt quá dung lượng cho phép.');
    }

    $mimeMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $temporaryPath = (string) $file['tmp_name'];
    $mime = $finfo->file($temporaryPath);
    if (!is_string($mime) || !isset($mimeMap[$mime]) || @getimagesize($temporaryPath) === false) {
        throw new RuntimeException('Chỉ chấp nhận ảnh JPEG, PNG, WebP hoặc GIF hợp lệ.');
    }

    $resolvedDirectory = rtrim($directory, '/\\');
    if (!is_dir($resolvedDirectory) && !mkdir($resolvedDirectory, 0750, true) && !is_dir($resolvedDirectory)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $mimeMap[$mime];
    $destination = $resolvedDirectory . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('Không thể lưu ảnh tải lên.');
    }
    chmod($destination, 0640);
    return $fileName;
}
