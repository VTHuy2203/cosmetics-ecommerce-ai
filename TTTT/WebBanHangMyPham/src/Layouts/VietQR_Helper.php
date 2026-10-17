<?php
// Simple VietQR helper to build QR image URLs for MB Bank via VietQR
// Usage: getVietQRUrl($accountNo, $bankBin, $amountVnd, $description)

function normalizeVietQRDescription($description)
{
	$desc = trim((string) $description);
	$desc = preg_replace('/\s+/u', ' ', $desc) ?? '';
	return function_exists('mb_substr') ? mb_substr($desc, 0, 120, 'UTF-8') : substr($desc, 0, 120);
}

function getVietQRUrl($accountNo, $bankBin, $amountVnd, $description)
{
	$base = 'https://img.vietqr.io/image';
	$template = 'compact'; // compact or default

	$amount = intval($amountVnd);
	if ($amount < 0) {
		$amount = 0;
	}

	$desc = normalizeVietQRDescription($description);

	$query = http_build_query([
		'amount' => $amount,
		'addInfo' => $desc,
	]);

	// Example: https://img.vietqr.io/image/970422-123456789-compact.png?amount=10000&addInfo=Noi%20dung
	$url = $base . '/' . $bankBin . '-' . $accountNo . '-' . $template . '.png?' . $query;
	return $url;
}

?>
