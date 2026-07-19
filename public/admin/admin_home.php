<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/settings.php';
require_once __DIR__ . '/../../src/loyalty.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'store_status') {
  csrf_verify();
  $storeOpen = isset($_POST['store_open']) ? '1' : '0';
  $closedMessage = trim($_POST['closed_message'] ?? '');
  if ($closedMessage === '') {
    $closedMessage = 'We are closed at the moment — see you soon for your next cup!';
  }
  set_setting($conn, 'store_open', $storeOpen);
  set_setting($conn, 'hours_override', isset($_POST['hours_override']) ? '1' : '0');
  set_setting($conn, 'closed_message', mb_substr($closedMessage, 0, 255));
  $_SESSION['message'] = 'Store status saved.';
  header('Location: admin_home.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'opening_hours') {
  csrf_verify();
  foreach (HOURS_DAYS as $day) {
    $open = trim($_POST["open_$day"] ?? '');
    $close = trim($_POST["close_$day"] ?? '');
    $validPair = preg_match('/^\d{2}:\d{2}$/', $open) && preg_match('/^\d{2}:\d{2}$/', $close) && $open < $close;
    if (!$validPair) {
      $open = '';
      $close = '';
    }
    set_setting($conn, "hours_{$day}_open", $open);
    set_setting($conn, "hours_{$day}_close", $close);
  }
  set_setting($conn, 'hours_configured', '1');
  $_SESSION['message'] = 'Opening hours saved. Days with missing or invalid times are treated as closed.';
  header('Location: admin_home.php');
  exit();
}

$storeOpen = get_setting($conn, 'store_open') !== '0';
$hoursOverride = get_setting($conn, 'hours_override') === '1';
$closedMessage = get_setting($conn, 'closed_message') ?? '';
$schedule = store_schedule($conn);
$storeStatus = store_status($conn);

$result = $conn->query("SELECT
    COALESCE(SUM(CASE WHEN points > 0 THEN points END), 0) AS issued,
    COALESCE(SUM(CASE WHEN points < 0 THEN -points END), 0) AS redeemed
  FROM loyalty_ledger");
$loyaltyTotals = $result->fetch_assoc();

$tierCounts = [];
foreach (array_reverse(LOYALTY_TIERS) as $tier) {
  $tierCounts[$tier['name']] = 0;
}
$result = $conn->query("SELECT lifetime_points FROM users");
while ($row = $result->fetch_assoc()) {
  $tierCounts[get_tier((int)$row['lifetime_points'])['name']]++;
}
mysqli_close($conn);

$flash = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$pageTitle = 'Admin dashboard - Bean There';

$sections = [
  'Items' => [
    ['add_item.php', 'Add new item', 'fa-plus'],
    ['view_items.php', 'View items', 'fa-mug-hot'],
  ],
  'Orders' => [
    ['adminOrderManagement.php', 'Manage order status', 'fa-list-check'],
    ['admin_report.php', 'Order report', 'fa-chart-line'],
    ['admin_order_tracking.php', 'User order status', 'fa-truck'],
  ],
  'Vouchers' => [
    ['add_voucher.php', 'Add voucher', 'fa-ticket'],
    ['manage_voucher.php', 'Manage vouchers', 'fa-tags'],
  ],
  'People' => [
    ['view_users.php', 'View users', 'fa-users'],
    ['admin_membership_manage.php', 'Manage memberships', 'fa-id-card'],
    ['view_admins.php', 'View admins', 'fa-user-shield'],
    ['admin_register.php', 'Register admin', 'fa-user-plus'],
  ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main id="main" class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-1">Dashboard</h1>
    <p class="text-foam text-sm mb-8">Signed in as <?= htmlspecialchars($_SESSION['current_admin']) ?></p>

    <?php if ($flash): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <div class="bg-roast border border-bean rounded-2xl p-6 mb-6">
      <h2 class="text-lg font-bold mb-4">Store status</h2>
      <p class="text-sm mb-4">
        Right now the store is
        <span class="font-semibold <?= $storeStatus['open'] ? 'text-green-300' : 'text-red-300' ?>"><?= $storeStatus['open'] ? 'open' : 'closed' ?></span>
        <span class="text-foam">(<?= $hoursOverride ? 'manual override' : 'following the schedule' ?>)</span>
      </p>
      <form method="POST" class="flex flex-col gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="store_status">
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" name="hours_override" value="1" <?= $hoursOverride ? 'checked' : '' ?> class="w-4 h-4">
          <span>Manual override — ignore the schedule and use the toggle below</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" name="store_open" value="1" <?= $storeOpen ? 'checked' : '' ?> class="w-4 h-4">
          <span>Store is open — customers can order</span>
        </label>
        <div>
          <label for="closed_message" class="block text-sm text-foam mb-1.5">Message shown when closed manually</label>
          <textarea name="closed_message" id="closed_message" rows="2" maxlength="255"
            class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 text-crema focus:outline-none focus:border-caramel"><?= htmlspecialchars($closedMessage) ?></textarea>
        </div>
        <button type="submit" class="btn-caramel self-start">Save store status</button>
      </form>
    </div>

    <div class="bg-roast border border-bean rounded-2xl p-6 mb-6">
      <h2 class="text-lg font-bold mb-4">Opening hours</h2>
      <p class="text-foam text-sm mb-4">Leave both fields blank to mark a day closed. The schedule controls ordering unless manual override is on.</p>
      <form method="POST" class="flex flex-col gap-3">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="opening_hours">
        <?php foreach (HOURS_DAYS as $day): ?>
          <div class="flex items-center gap-3">
            <span class="w-28 text-sm"><?= htmlspecialchars(HOURS_DAY_LABELS[$day]) ?></span>
            <input type="time" name="open_<?= $day ?>" value="<?= htmlspecialchars($schedule[$day]['open']) ?>"
              class="bg-espresso border border-bean rounded-lg px-3 py-1.5 text-crema focus:outline-none focus:border-caramel">
            <span class="text-foam text-sm">to</span>
            <input type="time" name="close_<?= $day ?>" value="<?= htmlspecialchars($schedule[$day]['close']) ?>"
              class="bg-espresso border border-bean rounded-lg px-3 py-1.5 text-crema focus:outline-none focus:border-caramel">
          </div>
        <?php endforeach; ?>
        <button type="submit" class="btn-caramel self-start mt-2">Save opening hours</button>
      </form>
    </div>

    <div class="bg-roast border border-bean rounded-2xl p-6 mb-6">
      <h2 class="text-lg font-bold mb-4">Loyalty</h2>
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-center">
        <div class="bg-espresso border border-bean rounded-xl px-4 py-3">
          <p class="text-2xl font-bold text-caramel tabular-nums"><?= number_format($loyaltyTotals['issued']) ?></p>
          <p class="text-foam text-xs">points issued</p>
        </div>
        <div class="bg-espresso border border-bean rounded-xl px-4 py-3">
          <p class="text-2xl font-bold text-caramel tabular-nums"><?= number_format($loyaltyTotals['redeemed']) ?></p>
          <p class="text-foam text-xs">points redeemed</p>
        </div>
        <?php foreach ($tierCounts as $tierName => $count): ?>
          <div class="bg-espresso border border-bean rounded-xl px-4 py-3">
            <p class="text-2xl font-bold text-caramel tabular-nums"><?= number_format($count) ?></p>
            <p class="text-foam text-xs"><?= htmlspecialchars($tierName) ?> users</p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php foreach ($sections as $title => $links): ?>
        <div class="bg-roast border border-bean rounded-2xl p-6">
          <h2 class="text-lg font-bold mb-4"><?= htmlspecialchars($title) ?></h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($links as [$href, $label, $icon]): ?>
              <a href="<?= htmlspecialchars($href) ?>" class="flex items-center gap-3 bg-espresso border border-bean rounded-xl px-4 py-3 hover:border-caramel transition">
                <i class="fa-solid <?= htmlspecialchars($icon) ?> text-caramel"></i>
                <span class="text-sm"><?= htmlspecialchars($label) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>

</html>
