<?php
require_once __DIR__ . '/../dbconn.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../loyalty.php';
require_once __DIR__ . '/../csrf.php';
require_once __DIR__ . '/../theme.php';
$isLoggedIn = isset($_SESSION['current_user']);
$navThemeKeys = array_keys(theme_options());
$navNextTheme = $navThemeKeys[(array_search(current_theme(), $navThemeKeys, true) + 1) % count($navThemeKeys)];
$navNextThemeLabel = theme_options()[$navNextTheme]['label'];
$storeStatus = store_status($conn);
$navPoints = $isLoggedIn ? get_balance_for_nav($conn, $_SESSION['current_user']) : null;
$navCurrentPage = basename($_SERVER['PHP_SELF']);
function nav_link_class(string $href, string $current): string
{
  return $href === $current ? 'text-caramel font-semibold' : 'text-crema hover:text-caramel';
}
?>
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[60] bg-caramel text-espresso font-semibold px-4 py-2 rounded-lg">Skip to content</a>
<?php if (!$storeStatus['open']): ?>
  <div class="bg-caramel text-espresso text-sm font-semibold text-center px-4 py-2">
    <i class="fa-solid fa-store-slash mr-1"></i><?= htmlspecialchars($storeStatus['message'] ?: 'We are closed at the moment.') ?>
  </div>
<?php elseif ($storeStatus['today']['open'] !== '' && $storeStatus['today']['close'] !== ''): ?>
  <div class="bg-roast text-foam text-xs text-center px-4 py-1.5 border-b border-bean">
    <i class="fa-solid fa-clock mr-1"></i>Open today <?= htmlspecialchars($storeStatus['today']['open']) ?>-<?= htmlspecialchars($storeStatus['today']['close']) ?>
  </div>
<?php endif; ?>
<nav class="sticky top-0 z-50 bg-espresso/95 backdrop-blur border-b border-bean">
  <div class="max-w-6xl mx-auto flex items-center justify-between px-4 py-3">
    <a href="index.php" class="text-xl font-bold tracking-[0.25em] text-caramel">BEAN<span class="text-crema">THERE</span></a>

    <button id="navToggle" class="md:hidden text-crema text-2xl w-11 h-11 -mr-2" aria-label="Toggle menu" aria-expanded="false" aria-controls="navLinks">
      <i class="fa-solid fa-bars"></i>
    </button>

    <div id="navLinks" class="hidden md:flex absolute md:static top-full left-0 w-full md:w-auto
        flex-col md:flex-row items-start md:items-center gap-4 md:gap-6
        bg-espresso md:bg-transparent border-b border-bean md:border-0 px-4 py-4 md:p-0">
      <a href="user_dashboard.php" class="<?= nav_link_class('user_dashboard.php', $navCurrentPage) ?>">Menu</a>
      <a href="recommendation.php" class="<?= nav_link_class('recommendation.php', $navCurrentPage) ?>">Recommend Me</a>
      <a href="search.php" class="<?= nav_link_class('search.php', $navCurrentPage) ?>">Search</a>

      <form method="post" action="set_theme.php">
        <?= csrf_field() ?>
        <input type="hidden" name="theme" value="<?= htmlspecialchars($navNextTheme) ?>">
        <input type="hidden" name="return" value="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'])) ?>">
        <button type="submit" class="text-crema hover:text-caramel" title="Switch theme: <?= htmlspecialchars($navNextThemeLabel) ?>" aria-label="Switch theme">
          <i class="fa-solid fa-palette"></i>
        </button>
      </form>

      <?php if ($isLoggedIn): ?>
        <a href="cart.php" class="<?= nav_link_class('cart.php', $navCurrentPage) ?>"><i class="fa-solid fa-cart-shopping mr-1"></i>Cart</a>
        <a href="user_order_tracking.php" class="<?= nav_link_class('user_order_tracking.php', $navCurrentPage) ?>">Orders</a>
        <a href="rewards.php" class="<?= nav_link_class('rewards.php', $navCurrentPage) ?>">
          Rewards<?php if ($navPoints !== null): ?><span class="ml-1.5 bg-caramel text-espresso text-xs font-semibold px-2 py-0.5 rounded-full"><?= number_format($navPoints) ?> pts</span><?php endif; ?>
        </a>
        <div class="relative">
          <button id="accountToggle" class="text-crema hover:text-caramel flex items-center gap-1 py-1" aria-expanded="false" aria-controls="accountMenu">
            <i class="fa-solid fa-user mr-1"></i><?= htmlspecialchars($_SESSION['current_user']) ?>
            <i class="fa-solid fa-chevron-down text-xs"></i>
          </button>
          <div id="accountMenu" class="hidden md:absolute md:right-0 md:top-full md:mt-2 flex flex-col
              bg-roast border border-bean rounded-lg py-2 mt-2 md:mt-2 min-w-44 md:shadow-warm">
            <a href="edit_user_detail.php" class="px-4 py-2.5 text-crema hover:text-caramel hover:bg-espresso/50">My profile</a>
            <a href="membership.php" class="px-4 py-2.5 text-crema hover:text-caramel hover:bg-espresso/50">Membership</a>
            <a href="voucher.php" class="px-4 py-2.5 text-crema hover:text-caramel hover:bg-espresso/50">My vouchers</a>
            <a href="user_verify.php" class="px-4 py-2.5 text-crema hover:text-caramel hover:bg-espresso/50">Verify account</a>
            <a href="user_logout.php" class="px-4 py-2.5 text-foam hover:text-caramel hover:bg-espresso/50 border-t border-bean mt-1 pt-3">Log out</a>
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
    var links = document.getElementById('navLinks');
    links.classList.toggle('hidden');
    links.classList.toggle('flex');
    this.setAttribute('aria-expanded', String(!links.classList.contains('hidden')));
  });
  var accountToggle = document.getElementById('accountToggle');
  if (accountToggle) {
    accountToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var menu = document.getElementById('accountMenu');
      menu.classList.toggle('hidden');
      this.setAttribute('aria-expanded', String(!menu.classList.contains('hidden')));
    });
    document.addEventListener('click', function () {
      document.getElementById('accountMenu').classList.add('hidden');
      accountToggle.setAttribute('aria-expanded', 'false');
    });
  }
</script>
