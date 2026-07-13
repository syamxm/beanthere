<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php foreach ($sections as $title => $links): ?>
        <div class="bg-roast border border-bean rounded-2xl p-6">
          <h2 class="text-caramel font-semibold tracking-widest text-sm mb-4"><?= strtoupper($title) ?></h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($links as [$href, $label, $icon]): ?>
              <a href="<?= $href ?>" class="flex items-center gap-3 bg-espresso border border-bean rounded-xl px-4 py-3 hover:border-caramel transition">
                <i class="fa-solid <?= $icon ?> text-caramel"></i>
                <span class="text-sm"><?= $label ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>

</html>
