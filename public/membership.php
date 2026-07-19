<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php?return=membership.php');
  exit();
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';

$username = $_SESSION['current_user'];

$stmt = $conn->prepare("SELECT userID, authentication_status FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID, $verified);
$stmt->fetch();
$stmt->close();

$status = null;

$stmt = $conn->prepare("SELECT status FROM membership WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $verified) {
  csrf_verify();

  if ($status === null) {
    // UNIQUE(userID) is the real guard against a double-submitted application;
    // the status check above can be passed twice by two fast clicks.
    try {
      $stmt = $conn->prepare("INSERT INTO membership (userID) VALUES (?)");
      $stmt->bind_param("i", $userID);
      $stmt->execute();
      $stmt->close();

      $success = "Membership application submitted! Please wait for admin approval.";
      $status = "pending";
    } catch (mysqli_sql_exception $e) {
      if ($e->getCode() == 1062) {
        $error = "You have already applied or are a member.";
        $status = "pending";
      } else {
        $error = "Failed to apply for membership. Please try again.";
      }
    }
  } elseif ($status === "rejected" || $status === "revoked") {
    $status = "pending";
    $update = $conn->prepare("UPDATE membership SET status = ? WHERE userID = ?");
    $update->bind_param("si", $status, $userID);
    if ($update->execute()) {
      $success = "Reapplication submitted! Please wait for admin approval.";
    } else {
      $error = "Failed to apply for membership. Please try again.";
    }
    $update->close();
  } else {
    $error = "You have already applied or are a member.";
  }
}

$pageTitle = 'Membership - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main id="main" class="grow flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md bg-roast border border-bean rounded-2xl p-8">
      <h1 class="text-2xl font-bold mb-2">Membership</h1>
      <p class="text-foam text-sm mb-6">Members get vouchers on active accounts, applied automatically every month.</p>

      <?php if (!empty($success)): ?>
        <p class="text-green-400 text-sm mb-4"><?= htmlspecialchars($success) ?></p>
      <?php elseif (!empty($error)): ?>
        <p class="text-red-400 text-sm mb-4"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <?php if (!$verified): ?>
        <p class="text-foam mb-4">You need a verified account to apply.</p>
        <a href="user_verify.php" class="inline-block bg-caramel text-espresso font-semibold px-5 py-2.5 rounded-lg hover:bg-crema transition">Verify my account</a>

      <?php elseif ($status === 'active'): ?>
        <p class="text-green-400 font-semibold"><i class="fa-solid fa-circle-check mr-1"></i> You're an active Bean There member.</p>
        <a href="voucher.php" class="inline-block mt-4 text-caramel underline hover:text-crema">See my vouchers</a>

      <?php elseif ($status === 'pending'): ?>
        <p class="text-caramel font-medium"><i class="fa-solid fa-hourglass-half mr-1"></i> Your application is pending approval.</p>

      <?php elseif ($status === 'revoked' || $status === 'rejected'): ?>
        <p class="text-red-400 mb-4">Your membership was <?= htmlspecialchars($status) ?>. You can reapply below.</p>
        <form method="post">
          <?= csrf_field() ?>
          <button type="submit" class="bg-caramel text-espresso font-semibold px-5 py-2.5 rounded-lg hover:bg-crema transition">Reapply for membership</button>
        </form>

      <?php elseif (empty($success)): ?>
        <form method="post">
          <?= csrf_field() ?>
          <button type="submit" class="bg-caramel text-espresso font-semibold px-5 py-2.5 rounded-lg hover:bg-crema transition">Apply for membership</button>
        </form>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
