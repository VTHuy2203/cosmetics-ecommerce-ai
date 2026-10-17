<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/uploads.php';
require_once '../Layouts/Admin_Access_Check.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_is_valid((string) ($_GET['csrf_token'] ?? ''))) {
    json_response(['uploaded' => 0, 'error' => ['message' => 'Unauthorized upload.']], 403);
}

try {
    $fileName = store_public_image_upload($_FILES['upload'] ?? [], dirname(__DIR__, 3) . '/Image_upload');
    json_response(['uploaded' => 1, 'fileName' => $fileName, 'url' => '/TTTT/Image_upload/' . rawurlencode($fileName)]);
} catch (Throwable $error) {
    error_log('Editor news upload failed: ' . $error->getMessage());
    json_response(['uploaded' => 0, 'error' => ['message' => 'Invalid image.']], 422);
}
