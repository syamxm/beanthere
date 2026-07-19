<?php
session_start();
require_once __DIR__ . '/../src/dbconn.php';

$featured = [];
$result = $conn->query("SELECT id, name, description, image_path, price, category
                        FROM menu_items
                        WHERE category = 'menu' AND stock > 0
                        ORDER BY sort_order, name LIMIT 3");
while ($row = $result->fetch_assoc()) {
  $featured[] = $row;
}

$pageTitle = 'Bean There - Small-batch coffee';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main id="main">
  <section class="relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 pt-16 pb-20 md:pt-24 md:pb-28 grid md:grid-cols-2 gap-10 items-center">
      <div>
        <p class="text-caramel tracking-[0.3em] text-sm mb-4">SHAH ALAM · SINCE 2023</p>
        <h1 class="text-4xl md:text-6xl font-bold tracking-tight leading-[1.05] mb-6">
          Coffee worth<br>the <span class="text-caramel">detour.</span>
        </h1>
        <p class="text-foam text-lg leading-relaxed mb-8 max-w-md">
          Twelve drinks, three single-origin beans, zero guesswork.
          Tell us how you like your coffee and we'll pick your cup.
        </p>
        <div class="flex flex-wrap gap-4">
          <a href="user_dashboard.php" class="bg-caramel text-espresso font-semibold px-6 py-3 rounded-full hover:bg-crema transition">Browse the menu</a>
          <a href="recommendation.php" class="border border-caramel text-caramel px-6 py-3 rounded-full hover:bg-caramel hover:text-espresso transition">Recommend me a drink</a>
        </div>
      </div>
      <div class="hidden md:block">
        <img src="assets/images/thumbnail.jpg" alt="Coffee at Bean There"
          class="rounded-2xl border border-bean shadow-warm-lg object-cover w-full h-96 md:rotate-1">
      </div>
    </div>
  </section>

  <section class="bg-roast border-y border-bean">
    <div class="max-w-6xl mx-auto px-4 py-14">
      <h2 class="text-2xl md:text-3xl font-bold mb-2">House favourites</h2>
      <p class="text-foam mb-8">What regulars keep coming back for.</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($featured as $item): ?>
          <form action="customize.php" method="post" class="group">
            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
            <input type="hidden" name="from_section" value="<?= htmlspecialchars($item['category']) ?>">
            <div class="bg-espresso border border-bean rounded-2xl overflow-hidden hover:border-caramel hover:shadow-warm transition">
              <img loading="lazy" src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                class="w-full h-48 object-cover group-hover:scale-105 transition duration-300">
              <div class="p-5">
                <div class="flex items-start justify-between gap-2 mb-2">
                  <h3 class="font-semibold text-lg"><?= htmlspecialchars($item['name']) ?></h3>
                  <span class="text-caramel font-semibold whitespace-nowrap tabular-nums">RM<?= number_format($item['price'], 2) ?></span>
                </div>
                <p class="text-foam text-sm mb-4"><?= htmlspecialchars($item['description']) ?></p>
                <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2 rounded-lg hover:bg-crema transition">Order this</button>
              </div>
            </div>
          </form>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="max-w-6xl mx-auto px-4 py-16">
    <div class="bg-roast border border-bean rounded-2xl p-8 md:p-12 grid md:grid-cols-2 gap-8 items-center">
      <div>
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Every ringgit brews the next cup.</h2>
        <p class="text-foam leading-relaxed mb-6">
          Earn 1 point per RM1 spent, swap points for discount vouchers,
          and level up from bronze to silver to gold to earn faster.
        </p>
        <div class="flex flex-wrap gap-4">
          <?php if (isset($_SESSION['current_user'])): ?>
            <a href="rewards.php" class="bg-caramel text-espresso font-semibold px-6 py-3 rounded-full hover:bg-crema transition">See my rewards</a>
          <?php else: ?>
            <a href="user_register.php" class="bg-caramel text-espresso font-semibold px-6 py-3 rounded-full hover:bg-crema transition">Join free &amp; start earning</a>
            <a href="rewards.php" class="border border-caramel text-caramel px-6 py-3 rounded-full hover:bg-caramel hover:text-espresso transition">How it works</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="grid grid-cols-3 gap-4 text-center">
        <div class="bg-espresso border border-bean rounded-2xl px-3 py-6">
          <i class="fa-solid fa-medal text-caramel text-2xl mb-2"></i>
          <p class="font-semibold text-sm">Bronze</p>
          <p class="text-foam text-xs">1x points</p>
        </div>
        <div class="bg-espresso border border-bean rounded-2xl px-3 py-6">
          <i class="fa-solid fa-medal text-foam text-2xl mb-2"></i>
          <p class="font-semibold text-sm">Silver</p>
          <p class="text-foam text-xs">1.25x from 500 pts</p>
        </div>
        <div class="bg-espresso border border-bean rounded-2xl px-3 py-6">
          <i class="fa-solid fa-medal text-crema text-2xl mb-2"></i>
          <p class="font-semibold text-sm">Gold</p>
          <p class="text-foam text-xs">1.5x from 1500 pts</p>
        </div>
      </div>
    </div>
  </section>

  <section class="max-w-6xl mx-auto px-4 py-16">
    <h2 class="text-2xl md:text-3xl font-bold mb-10">From bean to cup in three taps</h2>
    <div class="grid md:grid-cols-3 gap-8 md:gap-10">
      <div class="border-l-2 border-caramel/60 pl-5">
        <i class="fa-solid fa-mug-hot text-caramel text-2xl mb-3"></i>
        <h3 class="font-semibold mb-2">Browse the menu</h3>
        <p class="text-foam text-sm leading-relaxed">Twelve drinks and three single-origin beans, described honestly and priced fairly.</p>
      </div>
      <div class="border-l-2 border-caramel/60 pl-5">
        <i class="fa-solid fa-wand-magic-sparkles text-caramel text-2xl mb-3"></i>
        <h3 class="font-semibold mb-2">Get a recommendation</h3>
        <p class="text-foam text-sm leading-relaxed">Tell us roast, caffeine and flavour, and we'll match a drink from our actual menu.</p>
      </div>
      <div class="border-l-2 border-caramel/60 pl-5">
        <i class="fa-solid fa-bag-shopping text-caramel text-2xl mb-3"></i>
        <h3 class="font-semibold mb-2">Order your way</h3>
        <p class="text-foam text-sm leading-relaxed">Customise sugar, milk and toppings, then pick up in store or get it delivered.</p>
      </div>
    </div>
    <?php if (!isset($_SESSION['current_user'])): ?>
      <div class="text-center mt-12">
        <a href="user_register.php" class="bg-caramel text-espresso font-semibold px-8 py-3 rounded-full hover:bg-crema transition">Create a free account</a>
        <p class="text-foam text-sm mt-3">Members earn vouchers on every order.</p>
      </div>
    <?php endif; ?>
  </section>

  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
