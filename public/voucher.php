<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php?return=voucher.php');
  exit();
}

require_once __DIR__ . '/../src/dbconn.php';

$username = $_SESSION['current_user'];

$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT membershipID FROM membership WHERE userID = ? AND status = 'active'");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($membershipID);
$stmt->fetch();
$stmt->close();

$vouchers = [];
if ($membershipID) {
  $stmt = $conn->prepare("
    SELECT v.code, v.discount_value, v.valid_from, v.valid_until, v.status, mv.used
    FROM member_vouchers mv
    JOIN vouchers v ON mv.voucherID = v.voucherID
    WHERE mv.membershipID = ? AND v.status = 'active'
    ORDER BY mv.assigned_at DESC
  ");
  $stmt->bind_param("i", $membershipID);
  $stmt->execute();
  $result = $stmt->get_result();
  $vouchers = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

$conn->close();

$pageTitle = 'My vouchers - Bean There';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow max-w-3xl mx-auto w-full px-4 py-10">
    <h1 class="text-3xl font-bold mb-2">My vouchers</h1>
    <p class="text-foam text-sm mb-8">Apply them at checkout for a discount on your order.</p>

    <?php if (empty($vouchers)): ?>
      <div class="bg-roast border border-bean rounded-2xl p-10 text-center">
        <i class="fa-solid fa-ticket text-caramel text-4xl mb-4"></i>
        <p class="text-lg font-semibold mb-2">No vouchers yet</p>
        <p class="text-foam text-sm mb-6">Active members receive vouchers automatically. <a href="membership.php" class="text-caramel underline hover:text-crema">Check your membership</a>.</p>
      </div>
    <?php else: ?>
      <div class="flex flex-col gap-4">
        <?php foreach ($vouchers as $v): ?>
          <div class="bg-roast border border-bean rounded-2xl p-5 flex flex-wrap items-center justify-between gap-4">
            <div>
              <p class="font-semibold text-lg tracking-widest text-caramel"><?= htmlspecialchars($v['code']) ?></p>
              <p class="text-foam text-sm">
                <?= number_format($v['discount_value'], 0) ?>% off ·
                valid <?= htmlspecialchars($v['valid_from']) ?> to <?= htmlspecialchars($v['valid_until']) ?>
              </p>
            </div>
            <span class="text-sm font-semibold px-3 py-1 rounded-full <?= $v['used'] ? 'bg-bean text-foam' : 'bg-caramel text-espresso' ?>">
              <?= $v['used'] ? 'Used' : 'Ready to use' ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
