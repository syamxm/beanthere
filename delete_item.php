<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

// Database connection
include "dbconn.php";

// Check if 'id' is set and is a number
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
  $id = (int)$_GET['id'];

  // Delete query
  $sql = "DELETE FROM menu_items WHERE id = $id";

  if (mysqli_query($conn, $sql)) {
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
