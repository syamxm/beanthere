<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/get_recommendation.php';

$recommendations = [];
$error = "";
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $_SESSION['form_data'] = $_POST;
  header("Location: recommendation.php#recommended");
  exit;
}

if (isset($_SESSION['form_data'])) {
  $answers = [
    'roast' => trim($_SESSION['form_data']['roast'] ?? ''),
    'caffeine' => trim($_SESSION['form_data']['caffeine'] ?? ''),
    'flavour' => trim($_SESSION['form_data']['flavour'] ?? ''),
    'currentMood' => trim($_SESSION['form_data']['currentMood'] ?? ''),
    'currentWeather' => trim($_SESSION['form_data']['currentWeather'] ?? ''),
  ];
  unset($_SESSION['form_data']);

  if (implode('', $answers) === '') {
    $error = "Fill in at least one field to get a recommendation.";
  } else {
    $searched = true;
    $recommendations = get_recommendation($answers);
  }
}

$pageTitle = 'Recommendation - Bean There';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow max-w-6xl mx-auto w-full px-4 py-10">
    <h1 class="text-3xl font-bold mb-2">Find your cup</h1>
    <p class="text-foam text-sm mb-8">Tell us what you're in the mood for and we'll match it against our menu.</p>

    <form action="recommendation.php" method="post" class="bg-roast border border-bean rounded-2xl p-6 max-w-xl">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="roast" class="block text-sm text-foam mb-1.5">Roast level</label>
          <select name="roast" id="roast" class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 text-crema focus:outline-none focus:border-caramel">
            <option value="">No preference</option>
            <option value="light">Light</option>
            <option value="medium">Medium</option>
            <option value="dark">Dark</option>
          </select>
        </div>
        <div>
          <label for="caffeine" class="block text-sm text-foam mb-1.5">Caffeine level</label>
          <select name="caffeine" id="caffeine" class="w-full bg-espresso border border-bean rounded-lg px-3 py-2.5 text-crema focus:outline-none focus:border-caramel">
            <option value="">No preference</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
      </div>

      <label for="flavour" class="block text-sm text-foam mb-1.5">Flavour</label>
      <input type="text" name="flavour" id="flavour" placeholder="e.g. Nutty, Sweet, Bold"
        class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema placeholder-foam focus:outline-none focus:border-caramel">

      <label for="currentMood" class="block text-sm text-foam mb-1.5">Current mood</label>
      <input type="text" name="currentMood" id="currentMood" placeholder="e.g. focused, relaxed"
        class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-4 text-crema placeholder-foam focus:outline-none focus:border-caramel">

      <label for="currentWeather" class="block text-sm text-foam mb-1.5">Current weather</label>
      <input type="text" name="currentWeather" id="currentWeather" placeholder="e.g. sunny, rainy"
        class="w-full bg-espresso border border-bean rounded-lg px-3.5 py-2.5 mb-6 text-crema placeholder-foam focus:outline-none focus:border-caramel">

      <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2.5 rounded-lg hover:bg-crema transition">Get recommendation</button>

      <?php if (!empty($error)): ?>
        <p class="text-red-400 text-sm text-center mt-4"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
    </form>

    <?php if ($searched && count($recommendations) === 0): ?>
      <div id="recommended" class="mt-10 bg-roast border border-bean rounded-2xl p-8 text-center max-w-xl">
        <p class="font-semibold mb-2">No exact match this time</p>
        <p class="text-foam text-sm">Try fewer filters, or <a href="user_dashboard.php" class="text-caramel underline hover:text-crema">browse the full menu</a>.</p>
      </div>
    <?php elseif (count($recommendations) > 0): ?>
      <section id="recommended" class="mt-12">
        <h2 class="text-xl font-semibold text-caramel tracking-widest mb-6">YOUR MATCHES</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($recommendations as $item): ?>
            <form action="customize.php" method="post">
              <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
              <input type="hidden" name="from_section" value="<?= htmlspecialchars($item['category']) ?>">
              <div class="h-full flex flex-col bg-roast border border-bean rounded-2xl overflow-hidden hover:border-caramel transition">
                <img loading="lazy" src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                  class="w-full h-44 object-cover">
                <div class="p-5 flex flex-col grow">
                  <div class="flex items-start justify-between gap-2 mb-1">
                    <h3 class="font-semibold"><?= htmlspecialchars($item['name']) ?></h3>
                    <span class="text-caramel font-semibold whitespace-nowrap">RM<?= number_format($item['price'], 2) ?></span>
                  </div>
                  <p class="text-foam text-sm mb-4 grow"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                  <button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2 rounded-lg hover:bg-crema transition">Customise &amp; order</button>
                </div>
              </div>
            </form>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
