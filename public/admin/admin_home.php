<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/settings.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $storeOpen = isset($_POST['store_open']) ? '1' : '0';
  $closedMessage = trim($_POST['closed_message'] ?? '');
  if ($closedMessage === '') {
    $closedMessage = 'We are closed at the moment — see you soon for your next cup!';
  }
  set_setting($conn, 'store_open', $storeOpen);
  set_setting($conn, 'closed_message', mb_substr($closedMessage, 0, 255));
  $_SESSION['message'] = $storeOpen === '1' ? 'Store is now open.' : 'Store is now closed.';
  header('Location: admin_home.php');
  exit();
}

$storeOpen = get_setting($conn, 'store_open') !== '0';
$closedMessage = get_setting($conn, 'closed_message') ?? '';
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

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-1">Dashboard</h1>
    <p class="text-foam text-sm mb-8">Signed in as <?= htmlspecialchars($_SESSION['current_admin']) ?></p>

    <?php if ($flash): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <div class="bg-roast border border-bean rounded-2xl p-6 mb-6">
      <h2 class="text-caramel font-semibold tracking-widest text-sm mb-4">STORE STATUS</h2>
      <form method="POST" class="flex flex-col gap-4">
        <?= csrf_field() ?>
        <label class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" name="store_open" value="1" <?= $storeOpen ? 'checked' : '' ?> class="accent-[#c49b63] w-4 h-4">
          <span>Store is open — customers can order</span>
        </label>
        <div>
          <label for="closed_message" class="block text-sm text-foam mb-1.5">Message shown when closed</label>
          <textarea name="closed_message" id="closed_message" rows="2" maxlength="255"
            class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 text-crema focus:outline-none focus:border-caramel"><?= htmlspecialchars($closedMessage) ?></textarea>
        </div>
        <button type="submit" class="btn-caramel self-start">Save store status</button>
      </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php foreach ($sections as $title => $links): ?>
        <div class="bg-roast border border-bean rounded-2xl p-6">
          <h2 class="text-caramel font-semibold tracking-widest text-sm mb-4"><?= htmlspecialchars(strtoupper($title)) ?></h2>
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
