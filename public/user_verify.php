<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php?return=user_verify.php');
  exit;
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';

$username = $_SESSION['current_user'];
$phone_number = "";
$email = "";
$authentication_status = false;

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$sql = "SELECT phone_number, email, authentication_status FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "s", $username);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $phone_number, $email, $authentication_status);
  if (!mysqli_stmt_fetch($stmt)) {
    $_SESSION['flash_error'] = "User record not found.";
  }
  mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$authentication_status) {
  csrf_verify();

  if (isset($_POST['verify_method']) && in_array($_POST['verify_method'], ['phone', 'email'])) {
    $update_sql = "UPDATE users SET authentication_status = TRUE WHERE username = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    if ($update_stmt) {
      mysqli_stmt_bind_param($update_stmt, "s", $username);
      if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['flash_success'] = "Account verified successfully!";
        header("Location: user_verify.php");
        exit;
      }
      $_SESSION['flash_error'] = "Failed to update verification.";
      mysqli_stmt_close($update_stmt);
    } else {
      $_SESSION['flash_error'] = "Something went wrong. Please try again.";
    }
    header("Location: user_verify.php");
    exit;
  } else {
    $_SESSION['flash_error'] = "Please select a verification method.";
    header("Location: user_verify.php");
    exit;
  }
}


$hasPhone = !empty($phone_number);
$hasEmail = !empty($email);

$pageTitle = 'Verify account - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md">
      <h1 class="text-2xl font-bold mb-2">Verify your account</h1>
      <p class="text-foam text-sm mb-2">Verified accounts can apply for membership and earn vouchers.</p>
      <p class="inline-flex items-center gap-1.5 text-xs text-foam bg-bean/40 border border-bean rounded-full px-3 py-1 mb-6">
        <i class="fa-solid fa-flask text-caramel"></i> Demo — no OTP or email is actually sent, verification is instant.
      </p>

      <?php if (!empty($flash_success)): ?>
        <p class="text-green-400 text-sm mb-4"><?= htmlspecialchars($flash_success) ?></p>
      <?php elseif (!empty($flash_error)): ?>
        <p class="text-red-400 text-sm mb-4"><?= htmlspecialchars($flash_error) ?></p>
      <?php endif; ?>

      <form method="POST" action="user_verify.php" class="bg-roast border border-bean rounded-2xl p-6">
        <?= csrf_field() ?>
        <?php if ($authentication_status): ?>
          <p class="text-green-400 font-semibold text-center">
            <i class="fa-solid fa-circle-check mr-1"></i> Your account is verified.
          </p>
        <?php else: ?>
          <?php if ($hasPhone): ?>
            <label class="flex items-center gap-3 border border-bean rounded-lg px-4 py-3 mb-3 cursor-pointer hover:border-caramel">
              <input type="radio" name="verify_method" value="phone" class="accent-[#c49b63]">
              <span>Verify via phone <span class="text-foam text-sm">(<?= htmlspecialchars($phone_number) ?>)</span></span>
            </label>
          <?php endif; ?>

          <?php if ($hasEmail): ?>
            <label class="flex items-center gap-3 border border-bean rounded-lg px-4 py-3 mb-3 cursor-pointer hover:border-caramel">
              <input type="radio" name="verify_method" value="email" class="accent-[#c49b63]">
              <span>Verify via email <span class="text-foam text-sm">(<?= htmlspecialchars($email) ?>)</span></span>
            </label>
          <?php endif; ?>

          <?php if (!$hasPhone && !$hasEmail): ?>
            <p class="text-red-400 text-sm text-center mb-3">
              No verification methods available. <a href="edit_user_detail.php" class="underline text-caramel">Add a phone number or email</a> first.
            </p>
          <?php endif; ?>

          <button type="submit" <?= (!$hasPhone && !$hasEmail) ? 'disabled' : '' ?>
            class="w-full bg-caramel text-espresso font-semibold py-2.5 rounded-lg hover:bg-crema transition mt-2 disabled:bg-bean disabled:text-foam disabled:cursor-not-allowed">
            Verify
          </button>
        <?php endif; ?>
      </form>
    </div>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
