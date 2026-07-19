<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/settings.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  header("Location: user_dashboard.php");
  exit;
}

if (!store_status($conn)['open']) {
  $_SESSION['message'] = "We're closed right now — ordering is paused until we reopen.";
  $_SESSION['success'] = false;
  header("Location: user_dashboard.php");
  exit;
}

$itemID = (int)($_POST['id'] ?? 0);
$fromSection = ($_POST['from_section'] ?? '') === 'products' ? 'products' : 'menu';

$item = null;
if ($itemID > 0) {
  $stmt = $conn->prepare("SELECT id, name, description, image_path, price, category, drink_type, sugar_level, roast_level, caffeine_level, stock
                          FROM menu_items WHERE id = ?");
  $stmt->bind_param("i", $itemID);
  $stmt->execute();
  $item = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

if (!$item) {
  header("Location: user_dashboard.php");
  exit;
}

if ((int)$item['stock'] < 1) {
  $_SESSION['message'] = $item['name'] . " is sold out right now.";
  $_SESSION['success'] = false;
  header("Location: user_dashboard.php");
  exit;
}

$isDrink = $item['category'] === 'menu';
$pageTitle = 'Customise - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main id="main" class="grow max-w-2xl mx-auto w-full px-4 py-10">
    <a href="user_dashboard.php#<?= $fromSection ?>" class="text-foam hover:text-caramel text-sm mb-6 inline-block">
      <i class="fa-solid fa-arrow-left mr-1"></i> Back to menu
    </a>

    <?php if (!isset($_SESSION['current_user'])): ?>
      <div class="bg-roast border border-caramel/40 rounded-xl px-4 py-3 mb-6 text-sm text-foam">
        <i class="fa-solid fa-circle-info text-caramel mr-1"></i>
        You can customise now — we'll ask you to log in when you add it to your cart.
      </div>
    <?php endif; ?>

    <div class="bg-roast border border-bean rounded-2xl overflow-hidden mb-6">
      <div class="flex items-center gap-4 p-5">
        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
          class="w-20 h-20 rounded-xl object-cover border border-bean">
        <div>
          <h1 class="text-xl font-bold"><?= htmlspecialchars($item['name']) ?></h1>
          <p class="text-foam text-sm"><?= htmlspecialchars($item['description'] ?? '') ?></p>
        </div>
      </div>
    </div>

    <form action="add_to_cart.php" method="post" class="bg-roast border border-bean rounded-2xl p-6">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

      <?php if ($isDrink): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
          <div>
            <label for="drinkType" class="block text-sm text-foam mb-1.5">Serve it</label>
            <select name="drinkType" id="drinkType" class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 text-crema focus:outline-none focus:border-caramel">
              <option value="Hot" <?= $item['drink_type'] === 'Hot' ? 'selected' : '' ?>>Hot</option>
              <option value="Iced" <?= $item['drink_type'] === 'Iced' ? 'selected' : '' ?>>Iced</option>
            </select>
          </div>
          <div>
            <label for="milkType" class="block text-sm text-foam mb-1.5">Milk</label>
            <select name="milkType" id="milkType" class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 text-crema focus:outline-none focus:border-caramel">
              <option value="Dairy">Dairy</option>
              <option value="Oatside">Oatside (+RM1)</option>
              <option value="Almond">Almond (+RM1)</option>
              <option value="Soy">Soy (+RM1)</option>
            </select>
          </div>
          <div>
            <label for="roastLevel" class="block text-sm text-foam mb-1.5">Roast</label>
            <select name="roastLevel" id="roastLevel" class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 text-crema focus:outline-none focus:border-caramel">
              <option value="" <?= empty($item['roast_level']) ? 'selected' : '' ?>>No preference</option>
              <option value="light" <?= $item['roast_level'] === 'light' ? 'selected' : '' ?>>Light</option>
              <option value="medium" <?= $item['roast_level'] === 'medium' ? 'selected' : '' ?>>Medium</option>
              <option value="dark" <?= $item['roast_level'] === 'dark' ? 'selected' : '' ?>>Dark</option>
            </select>
          </div>
          <div>
            <label for="sugarLevel" class="block text-sm text-foam mb-1.5">Sugar</label>
            <select name="sugarLevel" id="sugarLevel" class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 text-crema focus:outline-none focus:border-caramel">
              <?php foreach (['0%', '25%', '50%', '75%', '100%'] as $level): ?>
                <option value="<?= $level ?>" <?= $item['sugar_level'] === $level ? 'selected' : '' ?>><?= $level ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <input type="hidden" name="caffeineLevel" value="<?= htmlspecialchars($item['caffeine_level'] ?? '') ?>">

        <p class="text-sm text-foam mb-2">Syrups <span class="text-xs">(RM0.50 each)</span></p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
          <?php foreach (['Vanilla', 'Caramel', 'Hazelnut'] as $syrup): ?>
            <label class="flex justify-between items-center border border-bean rounded-lg px-4 py-3 bg-espresso cursor-pointer hover:border-caramel has-[:checked]:border-caramel has-[:checked]:bg-caramel/10 transition">
              <span><?= $syrup ?></span>
              <input type="checkbox" name="syrups[]" value="<?= $syrup ?>" class="syrup w-4 h-4">
            </label>
          <?php endforeach; ?>
        </div>

        <p class="text-sm text-foam mb-2">Toppings <span class="text-xs">(RM1.00 each)</span></p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
          <label class="flex justify-between items-center border border-bean rounded-lg px-4 py-3 bg-espresso cursor-pointer hover:border-caramel has-[:checked]:border-caramel has-[:checked]:bg-caramel/10 transition">
            <span>Whipped Cream</span>
            <input type="checkbox" name="toppings[]" value="Whipped Cream" class="topping w-4 h-4">
          </label>
          <label class="flex justify-between items-center border border-bean rounded-lg px-4 py-3 bg-espresso cursor-pointer hover:border-caramel has-[:checked]:border-caramel has-[:checked]:bg-caramel/10 transition">
            <span>Espresso Jelly</span>
            <input type="checkbox" name="toppings[]" value="Espresso Jelly" class="topping w-4 h-4">
          </label>
        </div>
      <?php endif; ?>

      <div class="flex items-center justify-between border-t border-bean pt-4">
        <span class="text-foam">Total</span>
        <span class="text-xl font-bold text-caramel tabular-nums">RM <span id="totalPrice"><?= number_format($item['price'], 2) ?></span></span>
      </div>

      <button type="submit" id="addToCartBtn" class="w-full bg-caramel text-espresso font-semibold py-3 rounded-lg hover:bg-crema transition mt-4 disabled:opacity-60 disabled:cursor-not-allowed">Add to cart</button>
    </form>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>

  <script>
    document.querySelector('form[action="add_to_cart.php"]').addEventListener('submit', function () {
      var btn = document.getElementById('addToCartBtn');
      btn.disabled = true;
      btn.textContent = 'Adding...';
    });

    document.addEventListener("DOMContentLoaded", function () {
      const basePrice = <?= json_encode((float)$item['price']) ?>;
      const totalPriceElem = document.getElementById("totalPrice");
      const milkSelect = document.querySelector("select[name='milkType']");
      const extras = document.querySelectorAll("input[name='syrups[]'], input[name='toppings[]']");

      function calculateTotal() {
        let total = basePrice;
        if (milkSelect && milkSelect.value !== "Dairy") total += 1;
        document.querySelectorAll("input[name='syrups[]']:checked").forEach(() => total += 0.5);
        document.querySelectorAll("input[name='toppings[]']:checked").forEach(() => total += 1);
        totalPriceElem.textContent = total.toFixed(2);
      }

      if (milkSelect) milkSelect.addEventListener("change", calculateTotal);
      extras.forEach(el => el.addEventListener("change", calculateTotal));
      calculateTotal();
    });
  </script>
</body>

</html>
