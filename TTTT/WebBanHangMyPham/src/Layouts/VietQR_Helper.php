<?php
// Simple VietQR helper to build QR image URLs for MB Bank via VietQR
// Usage: getVietQRUrl($accountNo, $bankBin, $amountVnd, $description)

function getVietQRUrl($accountNo, $bankBin, $amountVnd, $description)
{
	$base = 'https://img.vietqr.io/image';
	$template = 'compact'; // compact or default

	$amount = intval($amountVnd);
	if ($amount < 0) {
		$amount = 0;
	}

	$desc = trim($description);
	// VietQR recommends no special characters; sanitize lightly
	$desc = preg_replace('/\s+/', ' ', $desc);
	$desc = substr($desc, 0, 120);

	$query = http_build_query([
		'amount' => $amount,
		'addInfo' => $desc,
	]);

	// Example: https://img.vietqr.io/image/970422-123456789-compact.png?amount=10000&addInfo=Noi%20dung
	$url = $base . '/' . $bankBin . '-' . $accountNo . '-' . $template . '.png?' . $query;
	return $url;
}

?>
