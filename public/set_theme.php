<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/theme.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: index.php");
  exit;
}

csrf_verify();

$theme = $_POST['theme'] ?? '';
if (!is_string($theme) || !valid_theme($theme)) {
  http_response_code(400);
  die("Invalid theme.");
}

$_SESSION['theme'] = $theme;

if (isset($_SESSION['current_user'])) {
  $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE username = ?");
  $stmt->bind_param("ss", $theme, $_SESSION['current_user']);
  $stmt->execute();
  $stmt->close();
}

$return = basename($_POST['return'] ?? '');
if (!preg_match('/^[a-zA-Z_]+\.php$/', $return)) {
  $return = 'index.php';
}
header("Location: " . $return);
exit;
