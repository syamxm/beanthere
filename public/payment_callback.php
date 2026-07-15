<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/payment.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed.');
}

$ref = (string)($_POST['ref'] ?? '');
$amt = (string)($_POST['amt'] ?? '');
$sig = (string)($_POST['sig'] ?? '');
$result = (string)($_POST['result'] ?? '');
$method = in_array($_POST['method'] ?? '', ['Card', 'E-Wallet', 'Online Banking'], true) ? $_POST['method'] : 'Card';
$last4 = preg_match('/^\d{4}$/', $_POST['last4'] ?? '') ? $_POST['last4'] : null;

// HMAC proves the amount and checkout reference were not tampered with in
// transit; the recompute proves the signed amount still matches the order.
if ($ref === '' || !payment_verify($ref, $amt, $sig)) {
  http_response_code(400);
  exit('Payment verification failed.');
}
$expected = payment_group_amount($conn, $ref);
if ($expected === null || $expected !== $amt) {
  http_response_code(400);
  exit('Payment amount mismatch.');
}

if ($result === 'success') {
  payment_mark_paid($conn, $ref, $method, $last4);
  $conn->close();
  header("Location: receipt.php?ref=" . urlencode($ref));
  exit;
}

payment_mark_failed($conn, $ref);
$conn->close();
$_SESSION['message'] = "Payment didn't go through — your order wasn't placed. Any voucher and stock have been released.";
$_SESSION['success'] = false;
header("Location: user_order_tracking.php");
exit;
