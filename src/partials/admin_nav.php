<?php
$adminLinks = [
  'admin_home.php' => 'Dashboard',
  'view_items.php' => 'Items',
  'adminOrderManagement.php' => 'Orders',
  'order_board.php' => 'Board',
  'manage_voucher.php' => 'Vouchers',
  'admin_membership_manage.php' => 'Memberships',
  'view_users.php' => 'Users',
  'view_admins.php' => 'Admins',
  'admin_report.php' => 'Report',
];
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="sticky top-0 z-50 bg-espresso/95 backdrop-blur border-b border-bean">
  <div class="max-w-6xl mx-auto flex items-center justify-between px-4 py-3">
    <a href="admin_home.php" class="text-lg font-bold tracking-[0.25em] text-caramel">BEANTHERE <span class="text-foam text-xs font-normal tracking-normal ml-1">ADMIN</span></a>
    <button id="adminNavToggle" class="lg:hidden text-crema text-2xl w-11 h-11 -mr-2" aria-label="Toggle menu" aria-expanded="false" aria-controls="adminNavLinks">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div id="adminNavLinks" class="hidden lg:flex absolute lg:static top-full left-0 w-full lg:w-auto
        flex-col lg:flex-row items-start lg:items-center gap-3 lg:gap-5
        bg-espresso lg:bg-transparent border-b border-bean lg:border-0 px-4 py-4 lg:p-0 text-sm">
      <?php foreach ($adminLinks as $href => $label): ?>
        <a href="<?= htmlspecialchars($href) ?>" class="<?= $currentPage === $href ? 'text-caramel font-semibold' : 'text-crema' ?> hover:text-caramel"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
      <a href="admin_logout.php" class="text-foam hover:text-caramel">Log out</a>
    </div>
  </div>
</nav>
<script>
  document.getElementById('adminNavToggle').addEventListener('click', function () {
    var links = document.getElementById('adminNavLinks');
    links.classList.toggle('hidden');
    links.classList.toggle('flex');
    this.setAttribute('aria-expanded', String(!links.classList.contains('hidden')));
  });
</script>
