<?php
session_start();
require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';

$sqlAllItem = "SELECT id, name, description, image_path, price, category, stock FROM menu_items ORDER BY sort_order, name";
$resultAllItem = mysqli_query($conn, $sqlAllItem);

$items = [];
while ($row = mysqli_fetch_assoc($resultAllItem)) {
  $items[] = [
    'id' => (int)$row['id'],
    'name' => $row['name'],
    'description' => $row['description'],
    'image' => $row['image_path'],
    'price' => $row['price'],
    'category' => $row['category'],
    'stock' => (int)$row['stock'],
  ];
}

$pageTitle = 'Search - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow max-w-6xl mx-auto w-full px-4 py-10">
    <h1 class="text-3xl font-bold mb-6">Search the menu</h1>
    <div class="relative max-w-xl mb-10">
      <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-foam"></i>
      <input type="text" id="searchInput" placeholder="Try &quot;latte&quot; or &quot;beans&quot;..."
        class="w-full bg-roast border border-bean rounded-full pl-11 pr-4 py-3 text-crema placeholder-foam focus:outline-none focus:border-caramel">
    </div>
    <div id="results" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>
    <p id="noResults" class="hidden text-foam text-center py-12">No matches. Try another name, or <a href="recommendation.php" class="text-caramel underline">ask for a recommendation</a>.</p>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>

  <script>
    const allItems = <?php echo json_encode($items); ?>;
    const csrfToken = <?php echo json_encode(csrf_token()); ?>;
    const input = document.getElementById('searchInput');
    const results = document.getElementById('results');
    const noResults = document.getElementById('noResults');

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text ?? '';
      return div.innerHTML;
    }

    function displayItems(items) {
      noResults.classList.toggle('hidden', items.length > 0);
      results.innerHTML = items.map(item => {
        const fromSection = item.category === "menu" ? "menu" : "products";
        const action = item.category === "menu" ? "customize.php" : "add_to_cart.php";
        const label = item.category === "menu" ? "Customise &amp; order" : "Add to cart";
        const button = item.stock > 0
          ? `<button type="submit" class="w-full bg-caramel text-espresso font-semibold py-2 rounded-lg hover:bg-crema transition">${label}</button>`
          : `<button type="button" disabled class="w-full bg-bean text-foam font-semibold py-2 rounded-lg cursor-not-allowed">Out of stock</button>`;
        return `
      <form action="${action}" method="post">
        <input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
        <input type="hidden" name="id" value="${item.id}">
        <input type="hidden" name="from_section" value="${fromSection}">
        <div class="h-full flex flex-col bg-roast border border-bean rounded-2xl overflow-hidden hover:border-caramel transition">
          <img loading="lazy" src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="w-full h-44 object-cover">
          <div class="p-5 flex flex-col grow">
            <div class="flex items-start justify-between gap-2 mb-1">
              <h3 class="font-semibold">${escapeHtml(item.name)}</h3>
              <span class="text-caramel font-semibold whitespace-nowrap">RM${parseFloat(item.price).toFixed(2)}</span>
            </div>
            <p class="text-foam text-sm mb-4 grow">${escapeHtml(item.description)}</p>
            ${button}
          </div>
        </div>
      </form>`;
      }).join('');
    }

    displayItems(allItems);

    input.addEventListener('input', () => {
      const query = input.value.trim().toLowerCase();
      displayItems(allItems.filter(item =>
        item.name.toLowerCase().includes(query) ||
        (item.description || '').toLowerCase().includes(query)
      ));
    });
  </script>
</body>

</html>
