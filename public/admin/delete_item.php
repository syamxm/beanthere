<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

// Database connection
require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/image_upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: view_items.php");
  exit;
}
csrf_verify();

// Check if 'id' is set and is a number
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
  $id = (int)$_POST['id'];

  $imageStmt = mysqli_prepare($conn, "SELECT image_path FROM menu_items WHERE id = ?");
  mysqli_stmt_bind_param($imageStmt, "i", $id);
  mysqli_stmt_execute($imageStmt);
  mysqli_stmt_bind_result($imageStmt, $imagePath);
  mysqli_stmt_fetch($imageStmt);
  mysqli_stmt_close($imageStmt);

  // Delete query
  $stmt = mysqli_prepare($conn, "DELETE FROM menu_items WHERE id = ?");
  mysqli_stmt_bind_param($stmt, "i", $id);

  if (mysqli_stmt_execute($stmt)) {
    delete_menu_image($imagePath);
    $_SESSION['message'] = "✅ Item deleted successfully.";
  } else {
    $_SESSION['message'] = "❌ Error deleting item.";
  }
} else {
  $_SESSION['message'] = "⚠️ Invalid ID.";
}

// Redirect back to view page
header("Location: view_items.php");
exit;
