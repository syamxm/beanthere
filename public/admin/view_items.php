<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/dbconn.php';

$flash = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$items = [];
$result = mysqli_query($conn, "SELECT * FROM menu_items ORDER BY sort_order, name");
while ($row = mysqli_fetch_assoc($result)) {
  $items[] = $row;
}
mysqli_close($conn);

function json_list(?string $json): string
{
  $decoded = json_decode((string)$json, true);
  return is_array($decoded) ? implode(', ', $decoded) : '';
}

$pageTitle = 'Items - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold">Items</h1>
      <a href="add_item.php" class="btn-caramel"><i class="fa-solid fa-plus mr-1"></i> Add item</a>
    </div>

    <?php if ($flash): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <p class="text-foam text-sm mb-4"><i class="fa-solid fa-up-down text-caramel mr-1"></i> Drag cards to change the menu order shown to customers. <span id="reorderStatus" class="text-caramel"></span></p>

    <?php if (empty($items)): ?>
      <p class="text-foam">No menu items found.</p>
    <?php else: ?>
      <div id="itemList" class="flex flex-col gap-3">
        <?php foreach ($items as $row):
          $details = array_filter([
            $row['roast_level'] ? 'Roast: ' . $row['roast_level'] : null,
            $row['caffeine_level'] ? 'Caffeine: ' . $row['caffeine_level'] : null,
            $row['drink_type'] ? 'Type: ' . $row['drink_type'] : null,
            json_list($row['flavour_profile']) !== '' ? 'Flavours: ' . json_list($row['flavour_profile']) : null,
          ]);
        ?>
          <div draggable="true" data-id="<?= (int)$row['id'] ?>" class="item-row bg-roast border border-bean rounded-2xl p-4 flex flex-wrap items-start gap-4">
            <span class="cursor-grab text-foam pt-1"><i class="fa-solid fa-grip-vertical"></i></span>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span class="font-semibold"><?= htmlspecialchars($row['name']) ?></span>
                <span class="text-foam text-xs">#<?= (int)$row['id'] ?> · <?= htmlspecialchars($row['category']) ?></span>
                <span class="text-caramel font-semibold">RM<?= number_format($row['price'], 2) ?></span>
                <?php if ($row['old_price']): ?>
                  <span class="text-foam text-sm line-through">RM<?= number_format($row['old_price'], 2) ?></span>
                <?php endif; ?>
                <span class="text-xs px-2 py-0.5 rounded-full <?= (int)$row['stock'] > 0 ? 'bg-caramel/20 text-caramel' : 'bg-red-400/20 text-red-300' ?>">
                  Stock: <?= (int)$row['stock'] ?>
                </span>
              </div>
              <?php if ($row['description']): ?>
                <p class="text-foam text-sm mt-1"><?= htmlspecialchars($row['description']) ?></p>
              <?php endif; ?>
              <?php if ($details): ?>
                <p class="text-foam text-xs mt-1"><?= htmlspecialchars(implode(' · ', $details)) ?></p>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 ml-auto">
              <a href="edit_item.php?id=<?= (int)$row['id'] ?>" class="btn-outline">Edit</a>
              <form method="POST" action="delete_item.php" onsubmit="return confirm('Are you sure you want to delete this item?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" class="btn-danger">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <script>
    const rows = document.querySelectorAll('.item-row');
    const statusEl = document.getElementById('reorderStatus');
    let dragged = null;

    rows.forEach(row => {
      row.addEventListener('dragstart', () => {
        dragged = row;
        row.classList.add('opacity-50');
      });
      row.addEventListener('dragend', () => {
        row.classList.remove('opacity-50');
        dragged = null;
      });
      row.addEventListener('dragover', e => {
        e.preventDefault();
        if (!dragged || dragged === row) return;
        const rect = row.getBoundingClientRect();
        const after = e.clientY > rect.top + rect.height / 2;
        row.parentNode.insertBefore(dragged, after ? row.nextSibling : row);
      });
      row.addEventListener('drop', e => {
        e.preventDefault();
        saveOrder();
      });
    });

    function saveOrder() {
      const data = new FormData();
      data.append('csrf_token', <?= json_encode(csrf_token()) ?>);
      document.querySelectorAll('.item-row').forEach(row => data.append('order[]', row.dataset.id));
      statusEl.textContent = 'Saving...';
      fetch('reorder_items.php', { method: 'POST', body: data })
        .then(res => res.ok ? res.json() : Promise.reject())
        .then(() => { statusEl.textContent = 'Order saved.'; })
        .catch(() => { statusEl.textContent = 'Could not save the order — refresh and try again.'; });
    }
  </script>
</body>

</html>
