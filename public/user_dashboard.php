<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';

$flashMessage = $_SESSION['message'] ?? '';
$flashSuccess = $_SESSION['success'] ?? false;
unset($_SESSION['message'], $_SESSION['success']);

$drinks = [];
$result = $conn->query("SELECT id, name, description, image_path, price, old_price, stock, roast_level, caffeine_level, drink_type
                        FROM menu_items WHERE category = 'menu' ORDER BY sort_order, name");
while ($row = $result->fetch_assoc()) {
  $drinks[] = $row;
}

$beans = [];
$result = $conn->query("SELECT id, name, description, image_path, price, stock
                        FROM menu_items WHERE category = 'product' ORDER BY sort_order, name");
while ($row = $result->fetch_assoc()) {
  $beans[] = $row;
}

function level_dots(?string $level): string
{
  $levels = ['light' => 1, 'low' => 1, 'medium' => 2, 'dark' => 3, 'high' => 3];
  $filled = $levels[strtolower((string)$level)] ?? 0;
  if ($filled === 0) {
    return '';
  }
  $dots = '';
  for ($i = 1; $i <= 3; $i++) {
    $dots .= '<span class="inline-block w-2 h-2 rounded-full ' . ($i <= $filled ? 'bg-caramel' : 'bg-bean') . '"></span>';
  }
  return $dots;
}

$pageTitle = 'Menu - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main id="main">
  <div class="max-w-6xl mx-auto px-4 pt-12">
    <h1 class="text-3xl md:text-4xl font-bold mb-2">The menu</h1>
    <p class="text-foam mb-4">Every drink made to order. Not sure? <a href="recommendation.php" class="text-caramel underline hover:text-crema">Let us recommend one</a>.</p>
    <?php if (!empty($flashMessage)): ?>
      <p class="text-sm mb-4 <?= $flashSuccess ? 'text-green-400' : 'text-red-400' ?>"><?= htmlspecialchars($flashMessage) ?></p>
    <?php endif; ?>
  </div>

  <section id="menu" class="max-w-6xl mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold mb-6">Drinks</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($drinks as $row): ?>
        <form action="customize.php" method="post" class="group">
          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
          <input type="hidden" name="from_section" value="menu">
          <div class="h-full flex flex-col bg-roast border border-bean rounded-2xl overflow-hidden hover:border-caramel hover:shadow-warm transition">
            <div class="relative">
              <img loading="lazy" src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>"
                class="w-full h-44 object-cover">
              <?php if (!empty($row['drink_type'])): ?>
                <span class="absolute top-3 right-3 bg-espresso/85 text-crema text-xs px-2.5 py-1 rounded-full border border-bean">
                  <i class="fa-solid <?= strtolower($row['drink_type']) === 'iced' ? 'fa-snowflake' : 'fa-mug-hot' ?> mr-1 text-caramel"></i><?= htmlspecialchars($row['drink_type']) ?>
                </span>
              <?php endif; ?>
            </div>
            <div class="p-5 flex flex-col grow">
              <div class="flex items-start justify-between gap-2 mb-1">
                <h3 class="font-semibold"><?= htmlspecialchars($row['name']) ?></h3>
                <div class="text-right whitespace-nowrap">
                  <span class="text-caramel font-semibold tabular-nums">RM<?= number_format($row['price'], 2) ?></span>
                  <?php if (!empty($row['old_price']) && $row['old_price'] > $row['price']): ?>
                    <span class="text-foam line-through text-sm ml-1 tabular-nums">RM<?= number_format($row['old_price'], 2) ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <p class="text-foam text-sm mb-3 grow"><?= htmlspecialchars($row['description'] ?? '') ?></p>
              <div class="flex items-center gap-4 text-xs text-foam mb-4">
                <?php if (level_dots($row['roast_level'])): ?>
                  <span class="flex items-center gap-1.5">Roast <?= level_dots($row['roast_level']) ?></span>
                <?php endif; ?>
                <?php if (level_dots($row['caffeine_level'])): ?>
                  <span class="flex items-center gap-1.5">Caffeine <?= level_dots($row['caffeine_level']) ?></span>
                <?php endif; ?>
              </div>
              <?php if ((int)$row['stock'] > 0): ?>
                <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2 rounded-lg hover:bg-crema transition">Customise &amp; order</button>
              <?php else: ?>
                <button type="button" disabled class="w-full bg-bean text-foam font-semibold py-2 rounded-lg cursor-not-allowed">Out of stock</button>
              <?php endif; ?>
            </div>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="products" class="max-w-6xl mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold mb-6">Beans for home</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($beans as $row): ?>
        <form action="add_to_cart.php" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
          <input type="hidden" name="from_section" value="products">
          <div class="h-full flex flex-col bg-roast border border-bean rounded-2xl overflow-hidden hover:border-caramel hover:shadow-warm transition">
            <img loading="lazy" src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>"
              class="w-full h-44 object-cover">
            <div class="p-5 flex flex-col grow">
              <div class="flex items-start justify-between gap-2 mb-1">
                <h3 class="font-semibold"><?= htmlspecialchars($row['name']) ?></h3>
                <span class="text-caramel font-semibold whitespace-nowrap tabular-nums">RM<?= number_format($row['price'], 2) ?></span>
              </div>
              <p class="text-foam text-sm mb-4 grow"><?= htmlspecialchars($row['description'] ?? '') ?></p>
              <?php if ((int)$row['stock'] > 0): ?>
                <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2 rounded-lg hover:bg-crema transition">Add to cart</button>
              <?php else: ?>
                <button type="button" disabled class="w-full bg-bean text-foam font-semibold py-2 rounded-lg cursor-not-allowed">Out of stock</button>
              <?php endif; ?>
            </div>
          </div>
        </form>
      <?php endforeach; ?>
    </div>
  </section>

  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
