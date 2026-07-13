<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';

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
    $stmt = mysqli_prepare($conn, "SELECT userID FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
      $_SESSION['message'] = "Username is already taken.";
      $_SESSION['success'] = false;
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $insert = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
      mysqli_stmt_bind_param($insert, "ss", $username, $hashedPassword);

      if (mysqli_stmt_execute($insert)) {
        session_regenerate_id(true);
        $_SESSION['current_user'] = $username;
        header("Location: user_dashboard.php");
        exit();
      }

      $_SESSION['message'] = "Error while registering.";
      $_SESSION['success'] = false;
    }
  }

  $_SESSION['old'] = ['username' => $username];
  header("Location: user_register.php");
  exit();
}

if (isset($_SESSION['old'])) {
  $username = $_SESSION['old']['username'];
  unset($_SESSION['old']);
}

$pageTitle = 'Sign up - Bean There';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-sm">
      <h1 class="text-2xl font-bold mb-1">Create your account</h1>
      <p class="text-foam text-sm mb-6">Order ahead and earn member vouchers.</p>

      <form action="" method="post" class="bg-roast border border-bean rounded-2xl p-6">
        <?= csrf_field() ?>
        <label for="username" class="block text-sm text-foam mb-1.5">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="password" class="block text-sm text-foam mb-1.5">Password</label>
        <input type="password" id="password" name="password" required
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="confirm-password" class="block text-sm text-foam mb-1.5">Confirm password</label>
        <input type="password" id="confirm-password" name="confirm-password" required
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-6 text-crema focus:outline-none focus:border-caramel">

        <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2.5 rounded-lg hover:bg-crema transition">Sign up</button>

        <?php if (!empty($message)): ?>
          <p class="mt-4 text-sm text-center <?= $success ? 'text-green-400' : 'text-red-400' ?>"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
      </form>

      <p class="text-foam text-sm text-center mt-4">
        Already have an account? <a href="user_login.php" class="text-caramel hover:text-crema underline">Log in</a>
      </p>
    </div>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
