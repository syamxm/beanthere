<?php
session_start();
if (isset($_SESSION['current_admin'])) {
  unset($_SESSION['current_admin']);
  header("Location: admin_login.php");
}


// Redirect to main page
exit;
