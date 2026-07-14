<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/rate_limit.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/theme.php';
require_once __DIR__ . '/../src/session_role.php';

$allowedReturns = ['cart.php', 'user_dashboard.php', 'recommendation.php', 'membership.php', 'voucher.php', 'user_order_tracking.php', 'edit_user_detail.php', 'user_verify.php'];

$return = $_GET['return'] ?? $_POST['return'] ?? '';
if (!in_array($return, $allowedReturns, true)) {
  $return = 'user_dashboard.php';
}

$message = $_SESSION['message'] ?? "";
$success = $_SESSION['success'] ?? false;
unset($_SESSION['message'], $_SESSION['success']);

$username = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  csrf_verify();

  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  if (empty($username) || empty($password)) {
    $_SESSION['message'] = "All fields are required.";
    $_SESSION['success'] = false;
  } else {
    $identifier = rate_limit_identifier($username);
    $ipIdentifier = rate_limit_ip_identifier('login');
    $lockedFor = rate_limit_check($conn, $identifier) ?? rate_limit_check($conn, $ipIdentifier);

    if ($lockedFor !== null) {
      $_SESSION['message'] = "Too many attempts. Try again in " . ceil($lockedFor / 60) . " minute(s).";
      $_SESSION['success'] = false;
    } else {
      $stmt = mysqli_prepare($conn, "SELECT username, password, theme, accent_color FROM users WHERE username = ?");
      mysqli_stmt_bind_param($stmt, "s", $username);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_bind_result($stmt, $current_user, $storedPassword, $userTheme, $userAccent);
      $found = mysqli_stmt_fetch($stmt);
      mysqli_stmt_close($stmt);

      $valid = false;
      $needsRehash = false;
      if ($found) {
        if (password_verify($password, $storedPassword)) {
          $valid = true;
          $needsRehash = password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
        }
      }

      if ($valid) {
        if ($needsRehash) {
          $newHash = password_hash($password, PASSWORD_DEFAULT);
          $update = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE username = ?");
          mysqli_stmt_bind_param($update, "ss", $newHash, $current_user);
          mysqli_stmt_execute($update);
          mysqli_stmt_close($update);
        }

        rate_limit_clear($conn, $identifier);
        session_regenerate_id(true);
        clear_admin_session();
        $_SESSION['current_user'] = $current_user;
        $_SESSION['theme'] = valid_theme((string)$userTheme) ? $userTheme : DEFAULT_THEME;
        $_SESSION['accent'] = ($userAccent !== null && valid_accent($userAccent)) ? $userAccent : null;

        header("Location: " . $return);
        exit();
      }

      rate_limit_record($conn, $identifier);
      rate_limit_record($conn, $ipIdentifier, RATE_LIMIT_IP_MAX_ATTEMPTS);
      $_SESSION['message'] = "Invalid credentials, please try again.";
      $_SESSION['success'] = false;
    }
  }

  $_SESSION['old'] = ['username' => $username];
  header("Location: user_login.php" . ($return !== 'user_dashboard.php' ? "?return=" . urlencode($return) : ""));
  exit();
}

if (isset($_SESSION['old'])) {
  $username = $_SESSION['old']['username'];
  unset($_SESSION['old']);
}

$pageTitle = 'Log in - Bean There';
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
      <h1 class="text-2xl font-bold mb-1">Welcome back</h1>
      <p class="text-foam text-sm mb-6">Log in to order and track deliveries.</p>

      <form action="" method="post" class="bg-roast border border-bean rounded-2xl p-6">
        <?= csrf_field() ?>
        <input type="hidden" name="return" value="<?= htmlspecialchars($return) ?>">

        <label for="username" class="block text-sm text-foam mb-1.5">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" required
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="password" class="block text-sm text-foam mb-1.5">Password</label>
        <div class="relative mb-6">
          <input type="password" id="password" name="password" required
            class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 pr-11 text-crema focus:outline-none focus:border-caramel">
          <button type="button" id="togglePassword" aria-label="Show password"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-foam hover:text-caramel">
            <i class="fa-solid fa-eye"></i>
          </button>
        </div>

        <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2.5 rounded-lg hover:bg-crema transition">Log in</button>

        <?php if (!empty($message)): ?>
          <p class="mt-4 text-sm text-center <?= $success ? 'text-green-400' : 'text-red-400' ?>"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
      </form>

      <p class="text-foam text-sm text-center mt-4">
        New here? <a href="user_register.php" class="text-caramel hover:text-crema underline">Create an account</a>
      </p>
    </div>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>

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
