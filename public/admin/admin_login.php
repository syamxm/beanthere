<?php
session_start();

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/rate_limit.php';

$message = $_SESSION['message'] ?? "";
$success = $_SESSION['success'] ?? false;
unset($_SESSION['message'], $_SESSION['success']);

$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  if (empty($username) || empty($password)) {
    $_SESSION['message'] = "All fields are required.";
    $_SESSION['success'] = false;
  } else {
    $identifier = rate_limit_identifier($username);
    $lockedFor = rate_limit_check($conn, $identifier);

    if ($lockedFor !== null) {
      $_SESSION['message'] = "Too many attempts. Try again in " . ceil($lockedFor / 60) . " minute(s).";
      $_SESSION['success'] = false;
    } else {
      $stmt = mysqli_prepare($conn, "SELECT username, password FROM admins WHERE username = ?");
      mysqli_stmt_bind_param($stmt, "s", $username);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_bind_result($stmt, $current_admin, $storedPassword);
      $found = mysqli_stmt_fetch($stmt);
      mysqli_stmt_close($stmt);

      $valid = false;
      $needsRehash = false;
      if ($found) {
        if (password_verify($password, $storedPassword)) {
          $valid = true;
          $needsRehash = password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
        } elseif (hash_equals($storedPassword, $password)) {
          $valid = true;
          $needsRehash = true;
        }
      }

      if ($valid) {
        if ($needsRehash) {
          $newHash = password_hash($password, PASSWORD_DEFAULT);
          $update = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE username = ?");
          mysqli_stmt_bind_param($update, "ss", $newHash, $current_admin);
          mysqli_stmt_execute($update);
          mysqli_stmt_close($update);
        }

        rate_limit_clear($conn, $identifier);
        session_regenerate_id(true);
        $_SESSION['current_admin'] = $current_admin;

        header("Location: admin_home.php");
        exit();
      }

      rate_limit_record_failure($conn, $identifier);
      $_SESSION['message'] = "Invalid credentials, please try again.";
      $_SESSION['success'] = false;
    }
  }

  $_SESSION['old'] = ['username' => $username];
  header("Location: admin_login.php");
  exit();
}

if (isset($_SESSION['old'])) {
  $username = $_SESSION['old']['username'];
  unset($_SESSION['old']);
}

$pageTitle = 'Admin login - Bean There';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php $assetPrefix = '../assets'; include __DIR__ . '/../../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex items-center justify-center px-4">
  <div class="w-full max-w-sm">
    <p class="text-caramel font-bold tracking-[0.25em] text-center mb-6">BEANTHERE <span class="text-foam text-xs font-normal tracking-normal">ADMIN</span></p>

    <form action="" method="post" class="admin-form">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required>

      <label for="password">Password</label>
      <div class="relative">
        <input type="password" id="password" name="password" required>
        <button type="button" id="togglePassword" aria-label="Show password"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-foam hover:text-caramel bg-transparent border-0 cursor-pointer">
          <i class="fa-solid fa-eye"></i>
        </button>
      </div>

      <button type="submit" class="btn-caramel w-full mt-5">Log in</button>

      <?php if (!empty($message)): ?>
        <p class="mt-4 text-sm text-center <?= $success ? 'text-green-400' : 'text-red-400' ?>"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>
    </form>
  </div>

  <script>
    document.getElementById('togglePassword').addEventListener('click', function () {
      var input = document.getElementById('password');
      input.type = input.type === 'password' ? 'text' : 'password';
      this.querySelector('i').classList.toggle('fa-eye');
      this.querySelector('i').classList.toggle('fa-eye-slash');
    });
  </script>
</body>

</html>
