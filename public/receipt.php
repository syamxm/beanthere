<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php?return=user_order_tracking.php');
  exit();
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/payment.php';

$ref = (string)($_GET['ref'] ?? '');
$username = $_SESSION['current_user'];

$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

// Owner-only: the group must belong to the logged-in user.
$stmt = $conn->prepare("SELECT name, qty, total, delivery, delivery_fee, discount_percent,
                               orderStatus, payment_method, card_last4, paid_at, orderTime,
                               drinkType, milkType, sugarLevel, syrups, toppings
                        FROM orders WHERE checkoutID = ? AND userID = ? ORDER BY orderID");
$stmt->bind_param("si", $ref, $userID);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($rows)) {
  http_response_code(404);
  $notFound = true;
} else {
  $notFound = false;
  $itemsTotal = 0.0;
  foreach ($rows as $r) {
    $itemsTotal += (float)$r['total'];
  }
  $deliveryFee = (float)$rows[0]['delivery_fee'];
  $orderTotal = $itemsTotal + $deliveryFee;
  $discountPercent = (float)$rows[0]['discount_percent'];

  $pointsEarned = 0;
  $pStmt = $conn->prepare("SELECT points FROM loyalty_ledger WHERE userID = ? AND reason = ? LIMIT 1");
  $orderReason = "Order $ref";
  $pStmt->bind_param("is", $userID, $orderReason);
  $pStmt->execute();
  $pStmt->bind_result($pointsEarned);
  $pStmt->fetch();
  $pStmt->close();
}

$pageTitle = 'Receipt - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
  <style>
    @media print {
      body * { visibility: hidden; }
      #receipt, #receipt * { visibility: visible; }
      #receipt { position: absolute; left: 0; top: 0; width: 100%; border: 0 !important; }
      .no-print { display: none !important; }
    }
  </style>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow max-w-lg mx-auto w-full px-4 py-10">
    <?php if ($notFound): ?>
      <div class="bg-roast border border-bean rounded-2xl p-10 text-center">
        <p class="text-lg font-semibold mb-2">Receipt not found</p>
        <p class="text-foam text-sm mb-6">We couldn't find that order under your account.</p>
        <a href="user_order_tracking.php" class="bg-caramel text-espresso font-semibold px-6 py-2.5 rounded-full hover:bg-crema transition">Your orders</a>
      </div>
    <?php else: ?>
      <div class="no-print flex items-center justify-between mb-4">
        <a href="user_order_tracking.php" class="text-foam hover:text-caramel text-sm"><i class="fa-solid fa-arrow-left mr-1"></i> Your orders</a>
        <button onclick="window.print()" class="bg-caramel text-espresso font-semibold px-4 py-2 rounded-lg hover:bg-crema transition text-sm"><i class="fa-solid fa-print mr-1"></i> Print</button>
      </div>

      <div id="receipt" class="bg-roast border border-bean rounded-2xl p-6">
        <div class="text-center mb-5">
          <h1 class="text-xl font-bold tracking-[0.2em] text-caramel">BEANTHERE</h1>
          <p class="text-foam text-xs mt-1">Receipt · #<?= htmlspecialchars($ref) ?></p>
          <p class="text-foam text-xs"><?= htmlspecialchars(date('j M Y, g:ia', strtotime($rows[0]['paid_at'] ?? $rows[0]['orderTime']))) ?></p>
        </div>

        <div class="flex flex-col gap-2 text-sm border-t border-bean pt-4">
          <?php foreach ($rows as $r):
            $opts = array_filter([$r['drinkType'], $r['milkType'], $r['sugarLevel'] ? "Sugar {$r['sugarLevel']}" : '', $r['syrups'], $r['toppings']], 'strlen'); ?>
            <div class="flex justify-between">
              <span>
                <?= htmlspecialchars($r['name']) ?> <span class="text-foam">×<?= (int)$r['qty'] ?></span>
                <?php if ($opts): ?><span class="text-foam text-xs block"><?= htmlspecialchars(implode(' · ', $opts)) ?></span><?php endif; ?>
              </span>
              <span class="text-foam whitespace-nowrap">RM<?= number_format($r['total'], 2) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="border-t border-bean mt-4 pt-4 flex flex-col gap-1.5 text-sm">
          <?php if ($discountPercent > 0): ?>
            <div class="flex justify-between text-foam"><span>Voucher discount</span><span><?= number_format($discountPercent, 0) ?>% applied</span></div>
          <?php endif; ?>
          <div class="flex justify-between text-foam"><span><?= htmlspecialchars($rows[0]['delivery']) ?></span><span>RM<?= number_format($deliveryFee, 2) ?></span></div>
          <div class="flex justify-between font-semibold text-base"><span>Total paid</span><span class="text-caramel">RM<?= number_format($orderTotal, 2) ?></span></div>
        </div>

        <div class="border-t border-bean mt-4 pt-4 text-xs text-foam flex flex-col gap-1">
          <div class="flex justify-between"><span>Payment</span><span><?= htmlspecialchars($rows[0]['payment_method'] ?? '—') ?><?= $rows[0]['card_last4'] ? ' •••• ' . htmlspecialchars($rows[0]['card_last4']) : '' ?></span></div>
          <div class="flex justify-between"><span>Status</span><span><?= htmlspecialchars($rows[0]['orderStatus']) ?></span></div>
          <div class="flex justify-between"><span>Gateway ref</span><span><?= htmlspecialchars($ref) ?></span></div>
          <?php if ($pointsEarned > 0): ?>
            <div class="flex justify-between text-caramel"><span>Points earned</span><span>+<?= (int)$pointsEarned ?></span></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="no-print bg-roast/50 border border-bean rounded-2xl p-5 mt-6">
        <p class="text-xs text-foam uppercase tracking-wide mb-2"><i class="fa-solid fa-envelope mr-1"></i> Email preview — demo only, nothing is sent</p>
        <p class="text-sm">Hi <?= htmlspecialchars($username) ?>, thanks for your order at BeanThere! Your total was
          <span class="text-caramel font-semibold">RM<?= number_format($orderTotal, 2) ?></span>
          paid via <?= htmlspecialchars($rows[0]['payment_method'] ?? 'card') ?>.
          <?php if ($pointsEarned > 0): ?>You earned <?= (int)$pointsEarned ?> loyalty points.<?php endif; ?>
          We'll let you know as your order moves along. ☕</p>
      </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
