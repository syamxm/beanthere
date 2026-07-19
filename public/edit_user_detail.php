<?php
session_start();

if (!isset($_SESSION["current_user"])) {
  header("Location: user_login.php?return=edit_user_detail.php");
  exit;
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/loyalty.php';
require_once __DIR__ . '/../src/theme.php';

$current_username = $_SESSION["current_user"];

$flash_success = $_SESSION['flash_success'] ?? "";
$flash_errors = $_SESSION['flash_errors'] ?? [];
unset($_SESSION['flash_success'], $_SESSION['flash_errors']);

$sql = "SELECT userID, username, password, phone_number, email, lifetime_points, theme, accent_color FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
  session_destroy();
  header("Location: user_login.php");
  exit;
}
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'theme') {
  csrf_verify();

  $theme = $_POST['theme'] ?? '';
  $accent = strtolower(trim($_POST['accent'] ?? ''));
  $useDefaultAccent = isset($_POST['accent_default']);

  if (!is_string($theme) || !valid_theme($theme)) {
    $_SESSION['flash_errors'] = ["Invalid theme choice."];
  } elseif (!$useDefaultAccent && !valid_accent($accent)) {
    $_SESSION['flash_errors'] = ["Invalid accent colour."];
  } else {
    $accentValue = $useDefaultAccent ? null : $accent;
    $stmt = $conn->prepare("UPDATE users SET theme = ?, accent_color = ? WHERE userID = ?");
    $stmt->bind_param("ssi", $theme, $accentValue, $user['userID']);
    if ($stmt->execute()) {
      $_SESSION['theme'] = $theme;
      $_SESSION['accent'] = $accentValue;
      $_SESSION['flash_success'] = "Theme saved.";
    } else {
      $_SESSION['flash_errors'] = ["Failed to save theme."];
    }
    $stmt->close();
  }
  header("Location: edit_user_detail.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $errors = [];

  $username = trim($_POST['username']);
  $phone = trim($_POST['phone_number']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $current_password = $_POST['current_password'] ?? '';

  if (empty($username)) {
    $errors[] = "Username cannot be empty.";
  } elseif (mb_strlen($username) > 25) {
    $errors[] = "Username must be 25 characters or fewer.";
  }

  if (mb_strlen($phone) > 20) {
    $errors[] = "Phone number must be 20 characters or fewer.";
  }

  if (mb_strlen($email) > 100) {
    $errors[] = "Email must be 100 characters or fewer.";
  }

  if (!empty($password)) {
    if (empty($current_password) || !password_verify($current_password, $user['password'])) {
      $errors[] = "Current password is incorrect.";
    } elseif ($password !== $confirm_password) {
      $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
      $errors[] = "Password must be at least 6 characters.";
    }
  }

  if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
  }

  if ($username !== $user['username']) {
    $stmt = $conn->prepare("SELECT userID FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
      $errors[] = "Username already taken.";
    }
    $stmt->close();
  }

  if (!empty($phone) && $phone !== $user['phone_number']) {
    $stmt = $conn->prepare("SELECT userID FROM users WHERE phone_number = ? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
      $errors[] = "Phone number already in use.";
    }
    $stmt->close();
  }

  if (!empty($email) && $email !== $user['email']) {
    $stmt = $conn->prepare("SELECT userID FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
      $errors[] = "Email already in use.";
    }
    $stmt->close();
  }

  if (empty($errors)) {
    if (!empty($password)) {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $update_sql = "UPDATE users SET username=?, password=?, phone_number=?, email=? WHERE userID=?";
      $stmt = $conn->prepare($update_sql);
      $stmt->bind_param("ssssi", $username, $hashed_password, $phone, $email, $user['userID']);
    } else {
      $update_sql = "UPDATE users SET username=?, phone_number=?, email=? WHERE userID=?";
      $stmt = $conn->prepare($update_sql);
      $stmt->bind_param("sssi", $username, $phone, $email, $user['userID']);
    }

    if ($stmt->execute()) {
      $_SESSION['flash_success'] = "Details updated successfully.";
      $_SESSION["current_user"] = $username;
    } else {
      $_SESSION['flash_errors'] = ["Failed to update details."];
    }
    $stmt->close();
  } else {
    $_SESSION['flash_errors'] = $errors;
  }
  header("Location: edit_user_detail.php");
  exit;
}


$tier = get_tier((int)$user['lifetime_points']);

$themeOptions = theme_options();
$currentTheme = valid_theme((string)$user['theme']) ? $user['theme'] : DEFAULT_THEME;
$currentAccent = ($user['accent_color'] !== null && valid_accent($user['accent_color'])) ? $user['accent_color'] : null;

$pageTitle = 'My profile - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main id="main" class="grow flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md">
      <h1 class="text-2xl font-bold mb-4">My profile</h1>

      <div class="flex items-center gap-3 mb-6">
        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-caramel text-espresso uppercase tracking-widest">
          <i class="fa-solid fa-medal mr-1"></i><?= htmlspecialchars($tier['name']) ?> member
        </span>
        <span class="text-foam text-sm"><?= number_format($user['lifetime_points']) ?> lifetime points · <a href="rewards.php" class="text-caramel underline hover:text-crema">rewards</a></span>
      </div>

      <?php foreach ($flash_errors as $error): ?>
        <p class="text-red-400 text-sm mb-2"><?= htmlspecialchars($error) ?></p>
      <?php endforeach; ?>
      <?php if (!empty($flash_success)): ?>
        <p class="text-green-400 text-sm mb-2"><?= htmlspecialchars($flash_success) ?></p>
      <?php endif; ?>

      <form method="POST" action="edit_user_detail.php" class="bg-roast border border-bean rounded-2xl p-6">
        <?= csrf_field() ?>
        <label for="username" class="block text-sm text-foam mb-1.5">Username</label>
        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($user['username']) ?>"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="phone_number" class="block text-sm text-foam mb-1.5">Phone number</label>
        <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="email" class="block text-sm text-foam mb-1.5">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="password" class="block text-sm text-foam mb-1.5">New password <span class="text-xs">(leave blank to keep current)</span></label>
        <input type="password" id="password" name="password" autocomplete="new-password"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="confirm_password" class="block text-sm text-foam mb-1.5">Confirm new password</label>
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema focus:outline-none focus:border-caramel">

        <label for="current_password" class="block text-sm text-foam mb-1.5">Current password <span class="text-xs">(required to change password)</span></label>
        <input type="password" id="current_password" name="current_password" autocomplete="current-password"
          class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-6 text-crema focus:outline-none focus:border-caramel">

        <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2.5 rounded-lg hover:bg-crema transition">Save changes</button>
      </form>

      <h2 class="text-xl font-bold mt-10 mb-4">Site theme</h2>
      <form method="POST" action="edit_user_detail.php" class="bg-roast border border-bean rounded-2xl p-6">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="theme">

        <div class="grid grid-cols-2 gap-3 mb-6">
          <?php foreach ($themeOptions as $key => $t): ?>
            <label class="cursor-pointer">
              <input type="radio" name="theme" value="<?= htmlspecialchars($key) ?>" data-theme-option
                data-accent="<?= htmlspecialchars($t['accent']) ?>" class="peer sr-only" <?= $key === $currentTheme ? 'checked' : '' ?>>
              <span class="block rounded-xl border-2 border-bean peer-checked:border-caramel p-3"
                style="background: <?= $t['bg'] ?>">
                <span class="flex gap-1.5 mb-2">
                  <span class="w-4 h-4 rounded-full" style="background: <?= $t['surface'] ?>; border: 1px solid <?= $t['border'] ?>"></span>
                  <span class="w-4 h-4 rounded-full" style="background: <?= $t['accent'] ?>"></span>
                  <span class="w-4 h-4 rounded-full" style="background: <?= $t['text'] ?>"></span>
                </span>
                <span class="text-sm font-semibold" style="color: <?= $t['text'] ?>"><?= htmlspecialchars($t['label']) ?></span>
              </span>
            </label>
          <?php endforeach; ?>
        </div>

        <label for="accentColor" class="block text-sm text-foam mb-1.5">Accent colour</label>
        <div class="flex items-center gap-3 mb-6">
          <input type="color" id="accentColor" name="accent"
            value="<?= htmlspecialchars($currentAccent ?? $themeOptions[$currentTheme]['accent']) ?>"
            class="w-10 h-10 rounded cursor-pointer bg-transparent border border-bean">
          <label class="flex items-center gap-2 text-sm text-foam">
            <input type="checkbox" name="accent_default" id="accentDefault" <?= $currentAccent === null ? 'checked' : '' ?>>
            Use theme default
          </label>
        </div>

        <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2.5 rounded-lg hover:bg-crema transition">Save theme</button>
      </form>
    </div>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
  <script src="assets/theme.js"></script>
</body>

</html>
