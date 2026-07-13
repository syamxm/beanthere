<?php
require_once __DIR__ . '/../settings.php';
$isLoggedIn = isset($_SESSION['current_user']);
$storeStatus = store_status();
?>
<?php if (!$storeStatus['open']): ?>
  <div class="bg-caramel text-espresso text-sm font-semibold text-center px-4 py-2">
    <i class="fa-solid fa-store-slash mr-1"></i><?= htmlspecialchars($storeStatus['message'] ?: 'We are closed at the moment.') ?>
  </div>
<?php endif; ?>
<nav class="sticky top-0 z-50 bg-espresso/95 backdrop-blur border-b border-bean">
  <div class="max-w-6xl mx-auto flex items-center justify-between px-4 py-3">
    <a href="index.php" class="text-xl font-bold tracking-[0.25em] text-caramel">BEAN<span class="text-crema">THERE</span></a>

    <button id="navToggle" class="md:hidden text-crema text-2xl" aria-label="Toggle menu">
      <i class="fa-solid fa-bars"></i>
    </button>

    <div id="navLinks" class="hidden md:flex absolute md:static top-full left-0 w-full md:w-auto
        flex-col md:flex-row items-start md:items-center gap-4 md:gap-6
        bg-espresso md:bg-transparent border-b border-bean md:border-0 px-4 py-4 md:p-0">
      <a href="user_dashboard.php" class="text-crema hover:text-caramel">Menu</a>
      <a href="recommendation.php" class="text-crema hover:text-caramel">Recommend Me</a>
      <a href="search.php" class="text-crema hover:text-caramel">Search</a>

      <?php if ($isLoggedIn): ?>
        <a href="cart.php" class="text-crema hover:text-caramel"><i class="fa-solid fa-cart-shopping mr-1"></i>Cart</a>
        <a href="user_order_tracking.php" class="text-crema hover:text-caramel">Orders</a>
        <div class="relative">
          <button id="accountToggle" class="text-crema hover:text-caramel flex items-center gap-1">
            <i class="fa-solid fa-user mr-1"></i><?= htmlspecialchars($_SESSION['current_user']) ?>
            <i class="fa-solid fa-chevron-down text-xs"></i>
          </button>
          <div id="accountMenu" class="hidden md:absolute md:right-0 md:top-full md:mt-2 flex flex-col
              bg-roast border border-bean rounded-lg py-2 mt-2 md:mt-2 min-w-44 md:shadow-lg">
            <a href="edit_user_detail.php" class="px-4 py-2 text-crema hover:text-caramel">My profile</a>
            <a href="membership.php" class="px-4 py-2 text-crema hover:text-caramel">Membership</a>
            <a href="voucher.php" class="px-4 py-2 text-crema hover:text-caramel">My vouchers</a>
            <a href="user_verify.php" class="px-4 py-2 text-crema hover:text-caramel">Verify account</a>
            <a href="user_logout.php" class="px-4 py-2 text-foam hover:text-caramel border-t border-bean mt-1 pt-3">Log out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="user_login.php" class="text-crema hover:text-caramel">Log in</a>
        <a href="user_register.php" class="bg-caramel text-espresso font-semibold px-4 py-1.5 rounded-full hover:bg-crema transition">Sign up</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
<script>
  document.getElementById('navToggle').addEventListener('click', function () {
    document.getElementById('navLinks').classList.toggle('hidden');
    document.getElementById('navLinks').classList.toggle('flex');
  });
  var accountToggle = document.getElementById('accountToggle');
  if (accountToggle) {
    accountToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      document.getElementById('accountMenu').classList.toggle('hidden');
    });
    document.addEventListener('click', function () {
      document.getElementById('accountMenu').classList.add('hidden');
    });
  }
</script>
