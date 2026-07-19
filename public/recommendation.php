<?php

session_start();

require_once __DIR__ . '/../src/csrf.php';

$quickReplies = [
  'Taste' => ['Something bitter', 'Something sweet', 'Something fruity'],
  'Milk' => ['With milk', 'Black, no milk'],
  'Temperature' => ['Hot', 'Iced'],
  'Caffeine' => ['Wake me up', 'Low caffeine'],
  'Budget' => ['Under RM10'],
];

$pageTitle = 'Recommendation - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main id="main" class="grow max-w-3xl mx-auto w-full px-4 py-10">
    <h1 class="text-3xl font-bold mb-2">Ask our barista</h1>
    <p class="text-foam text-sm mb-8">Tell me what you feel like and I'll pick something off the menu.</p>

    <div class="bg-roast/40 border border-bean rounded-2xl p-4 sm:p-6 shadow-warm">
      <div id="chatLog" class="flex flex-col gap-3 h-[26rem] overflow-y-auto pr-1 mb-4">
        <div class="flex justify-start">
          <div class="max-w-[80%] bg-roast border border-bean rounded-2xl rounded-bl-sm px-4 py-2.5 text-sm">
            Hi, I'm the Bean There barista. Sweet or bitter? Hot or iced? Tell me anything and I'll find your cup.
          </div>
        </div>
      </div>

      <div class="flex flex-wrap gap-2 mb-4">
        <?php foreach ($quickReplies as $group => $replies): ?>
          <?php foreach ($replies as $reply): ?>
            <button type="button" data-quick-reply="<?= htmlspecialchars($reply) ?>"
              aria-label="<?= htmlspecialchars("$group: $reply") ?>"
              class="border border-bean bg-espresso text-crema text-xs sm:text-sm px-3 py-1.5 rounded-full hover:border-caramel hover:text-caramel transition">
              <?= htmlspecialchars($reply) ?>
            </button>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>

      <form id="chatForm" data-csrf="<?= htmlspecialchars(csrf_token()) ?>" class="flex gap-2">
        <label for="chatInput" class="sr-only">Message the barista</label>
        <input type="text" id="chatInput" name="message" maxlength="300" autocomplete="off"
          placeholder="e.g. something iced and not too sweet"
          class="grow bg-espresso border border-bean rounded-lg px-3.5 py-2.5 text-crema placeholder-foam focus:outline-none focus:border-caramel">
        <button type="submit" aria-label="Send message" class="bg-caramel text-espresso font-semibold px-4 sm:px-5 py-2.5 rounded-lg hover:bg-crema transition">
          <i class="fa-solid fa-paper-plane"></i>
        </button>
      </form>
    </div>

    <p class="text-foam text-xs mt-4">
      Prefer to browse? <a href="user_dashboard.php" class="text-caramel underline hover:text-crema">See the full menu</a>.
    </p>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
  <script src="assets/chat.js?v=<?= filemtime(__DIR__ . '/assets/chat.js') ?>"></script>
</body>

</html>
