<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

$message = $_SESSION['message'] ?? "";
$success = $_SESSION['success'] ?? false;
unset($_SESSION['message'], $_SESSION['success']);

$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  csrf_verify();
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $confirmPassword = trim($_POST['confirm-password']);

  if (empty($username) || empty($password) || empty($confirmPassword)) {
    $_SESSION['message'] = "All fields are required.";
    $_SESSION['success'] = false;
  } elseif ($password !== $confirmPassword) {
    $_SESSION['message'] = "Passwords do not match.";
    $_SESSION['success'] = false;
  } else {
    $stmt = mysqli_prepare($conn, "SELECT id FROM admins WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
      $_SESSION['message'] = "Username is already taken.";
      $_SESSION['success'] = false;
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $insert = mysqli_prepare($conn, "INSERT INTO admins (username, password) VALUES (?, ?)");
      mysqli_stmt_bind_param($insert, "ss", $username, $hashedPassword);

      if (mysqli_stmt_execute($insert)) {
        $_SESSION['message'] = "Admin '$username' registered successfully.";
        $_SESSION['success'] = true;
        header("Location: admin_register.php");
        exit();
      }

      $_SESSION['message'] = "Error while registering.";
      $_SESSION['success'] = false;
    }
  }

  $_SESSION['old'] = ['username' => $username];
  header("Location: admin_register.php");
  exit();
}

if (isset($_SESSION['old'])) {
  $username = $_SESSION['old']['username'];
  unset($_SESSION['old']);
}

$pageTitle = 'Register admin - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6 text-center">Register new admin</h1>

    <form action="" method="post" class="admin-form">
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <label for="confirm-password">Confirm password</label>
      <input type="password" id="confirm-password" name="confirm-password" required>

      <button type="submit" class="btn-caramel w-full mt-5">Register admin</button>

      <?php if (!empty($message)): ?>
        <p class="mt-4 text-sm text-center <?= $success ? 'text-green-400' : 'text-red-400' ?>"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>
    </form>
  </main>
</body>

</html>
