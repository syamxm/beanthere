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
$result = mysqli_query($conn, "SELECT * FROM menu_items ORDER BY category, id");
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

    <?php if (empty($items)): ?>
      <p class="text-foam">No menu items found.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="admin-table">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Price</th>
            <th>Old price</th>
            <th>Category</th>
            <th>Roast</th>
            <th>Caffeine</th>
            <th>Flavours</th>
            <th>Type</th>
            <th>Stock</th>
            <th colspan="2">Actions</th>
          </tr>
          <?php foreach ($items as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td class="whitespace-nowrap"><?= htmlspecialchars($row['name']) ?></td>
              <td class="max-w-56"><?= htmlspecialchars($row['description'] ?? '') ?></td>
              <td>RM<?= number_format($row['price'], 2) ?></td>
              <td><?= $row['old_price'] ? 'RM' . number_format($row['old_price'], 2) : '—' ?></td>
              <td><?= htmlspecialchars($row['category']) ?></td>
              <td><?= htmlspecialchars($row['roast_level'] ?? '—') ?></td>
              <td><?= htmlspecialchars($row['caffeine_level'] ?? '—') ?></td>
              <td class="max-w-40"><?= htmlspecialchars(json_list($row['flavour_profile'])) ?></td>
              <td><?= htmlspecialchars($row['drink_type'] ?? '—') ?></td>
              <td><?= (int)$row['stock'] ?></td>
              <td><a href="edit_item.php?id=<?= (int)$row['id'] ?>" class="btn-outline">Edit</a></td>
              <td>
                <form method="POST" action="delete_item.php" onsubmit="return confirm('Are you sure you want to delete this item?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button type="submit" class="btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
