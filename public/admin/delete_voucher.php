<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: manage_voucher.php");
  exit;
}
csrf_verify();

// Check if 'id' is set and is a number
if (isset($_POST['voucherID']) && is_numeric($_POST['voucherID'])) {
  $voucherID = (int)$_POST['voucherID'];

  // Delete query
  $sql = "DELETE FROM vouchers WHERE voucherID = $voucherID";

  if (mysqli_query($conn, $sql)) {
    $_SESSION['message'] = "✅ Item deleted successfully.";
  } else {
    $_SESSION['message'] = "❌ Error deleting item.";
  }
} else {
  $_SESSION['message'] = "⚠️ Invalid ID.";
}

// Redirect back to view page
header("Location: manage_voucher.php");
exit;
