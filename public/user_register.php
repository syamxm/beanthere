<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/theme.php';
require_once __DIR__ . '/../src/rate_limit.php';
require_once __DIR__ . '/../src/session_role.php';

const USERNAME_MAX_LENGTH = 25;
const PASSWORD_MIN_LENGTH = 6;
const REGISTER_MAX_ATTEMPTS = 10;

$message = $_SESSION['message'] ?? "";
$success = $_SESSION['success'] ?? false;
unset($_SESSION['message'], $_SESSION['success']);

$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  csrf_verify();

  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $confirmPassword = trim($_POST['confirm-password']);

  $ipIdentifier = rate_limit_ip_identifier('register');
  $lockedFor = rate_limit_check($conn, $ipIdentifier);

  if ($lockedFor !== null) {
    $_SESSION['message'] = "Too many sign-up attempts. Try again in " . ceil($lockedFor / 60) . " minute(s).";
    $_SESSION['success'] = false;
  } elseif (empty($username) || empty($password) || empty($confirmPassword)) {
    $_SESSION['message'] = "All fields are required.";
    $_SESSION['success'] = false;
  } elseif (mb_strlen($username) > USERNAME_MAX_LENGTH) {
    $_SESSION['message'] = "Username must be " . USERNAME_MAX_LENGTH . " characters or fewer.";
    $_SESSION['success'] = false;
  } elseif (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
    $_SESSION['message'] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters.";
    $_SESSION['success'] = false;
  } elseif ($password !== $confirmPassword) {
    $_SESSION['message'] = "Passwords do not match.";
    $_SESSION['success'] = false;
  } else {
    rate_limit_record($conn, $ipIdentifier, REGISTER_MAX_ATTEMPTS);

    $stmt = mysqli_prepare($conn, "SELECT userID FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
      $_SESSION['message'] = "Username is already taken.";
      $_SESSION['success'] = false;
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $previewTheme = current_theme();
      $insert = mysqli_prepare($conn, "INSERT INTO users (username, password, theme) VALUES (?, ?, ?)");
      mysqli_stmt_bind_param($insert, "sss", $username, $hashedPassword, $previewTheme);

      if (mysqli_stmt_execute($insert)) {
        rate_limit_clear($conn, $ipIdentifier);
        session_regenerate_id(true);
        clear_admin_session();
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
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

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
          maxlength="<?= USERNAME_MAX_LENGTH ?>"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="password" class="block text-sm text-foam mb-1.5">Password <span class="text-xs">(min <?= PASSWORD_MIN_LENGTH ?> characters)</span></label>
        <input type="password" id="password" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="confirm-password" class="block text-sm text-foam mb-1.5">Confirm password</label>
        <input type="password" id="confirm-password" name="confirm-password" required minlength="<?= PASSWORD_MIN_LENGTH ?>"
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
