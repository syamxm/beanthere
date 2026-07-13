<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  $_SESSION['message'] = "Invalid ID.";
  header("Location: view_items.php");
  exit;
}

$id = (int)$_GET['id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM menu_items WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
  $_SESSION['message'] = "Item not found.";
  header("Location: view_items.php");
  exit;
}

function json_to_csv(?string $json): string
{
  $decoded = json_decode((string)$json, true);
  return is_array($decoded) ? implode(', ', $decoded) : '';
}

$flavour_profile = json_to_csv($row['flavour_profile']);
$bestMood = json_to_csv($row['bestMood']);
$bestWeather = json_to_csv($row['bestWeather']);

$pageTitle = 'Edit item - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6 text-center">Edit item</h1>

    <form action="update_item.php" method="post" enctype="multipart/form-data" class="admin-form">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">

      <label for="name">Item name</label>
      <input type="text" id="name" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>

      <label for="description">Description</label>
      <textarea name="description" id="description" rows="2"><?= htmlspecialchars($row['description'] ?? '') ?></textarea>

      <label for="image">Photo (leave empty to keep the current one)</label>
      <img src="../<?= htmlspecialchars($row['image_path']) ?>" alt="Current photo" class="w-24 h-24 rounded-xl object-cover border border-bean mb-2">
      <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">

      <label for="price">Current price (RM)</label>
      <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars($row['price']) ?>" required>

      <label for="old_price">Old price (RM, optional)</label>
      <input type="number" step="0.01" id="old_price" name="old_price" value="<?= htmlspecialchars($row['old_price'] ?? '') ?>">

      <label for="category">Category</label>
      <select id="category" name="category" onchange="toggleAttributes()">
        <option value="menu" <?= $row['category'] == 'menu' ? 'selected' : '' ?>>Menu (drink)</option>
        <option value="product" <?= $row['category'] == 'product' ? 'selected' : '' ?>>Product (beans)</option>
      </select>

      <div id="menu-attributes">
        <label for="roast_level">Roast level</label>
        <select id="roast_level" name="roast_level">
          <option value="" <?= empty($row['roast_level']) ? 'selected' : '' ?>>None</option>
          <option value="light" <?= $row['roast_level'] == 'light' ? 'selected' : '' ?>>Light</option>
          <option value="medium" <?= $row['roast_level'] == 'medium' ? 'selected' : '' ?>>Medium</option>
          <option value="dark" <?= $row['roast_level'] == 'dark' ? 'selected' : '' ?>>Dark</option>
        </select>

        <label for="caffeine_level">Caffeine level</label>
        <select id="caffeine_level" name="caffeine_level">
          <option value="" <?= empty($row['caffeine_level']) ? 'selected' : '' ?>>None</option>
          <option value="low" <?= $row['caffeine_level'] == 'low' ? 'selected' : '' ?>>Low</option>
          <option value="medium" <?= $row['caffeine_level'] == 'medium' ? 'selected' : '' ?>>Medium</option>
          <option value="high" <?= $row['caffeine_level'] == 'high' ? 'selected' : '' ?>>High</option>
        </select>

        <label for="flavour_profile">Flavour profile (comma-separated)</label>
        <input type="text" id="flavour_profile" name="flavour_profile" value="<?= htmlspecialchars($flavour_profile) ?>">

        <label for="origin">Origin</label>
        <input type="text" id="origin" name="origin" value="<?= htmlspecialchars($row['origin'] ?? '') ?>">

        <label for="drinkType">Default serve</label>
        <select id="drinkType" name="drinkType">
          <option value="Hot" <?= $row['drink_type'] == 'Hot' ? 'selected' : '' ?>>Hot</option>
          <option value="Iced" <?= $row['drink_type'] == 'Iced' ? 'selected' : '' ?>>Iced</option>
        </select>

        <label for="sugar_level">Default sugar</label>
        <select id="sugar_level" name="sugar_level">
          <?php foreach (['0%', '25%', '50%', '75%', '100%'] as $level): ?>
            <option value="<?= $level ?>" <?= $row['sugar_level'] == $level ? 'selected' : '' ?>><?= $level ?></option>
          <?php endforeach; ?>
        </select>

        <label for="bestMood">Best mood (comma-separated)</label>
        <input type="text" id="bestMood" name="bestMood" value="<?= htmlspecialchars($bestMood) ?>">

        <label for="bestWeather">Best weather (comma-separated)</label>
        <input type="text" id="bestWeather" name="bestWeather" value="<?= htmlspecialchars($bestWeather) ?>">
      </div>

      <label for="stock">Stock</label>
      <input type="number" id="stock" name="stock" value="<?= (int)$row['stock'] ?>">

      <button type="submit" class="btn-caramel w-full mt-5">Update item</button>
      <a href="view_items.php" class="block text-center text-foam hover:text-caramel text-sm mt-4">Back to item list</a>
    </form>
  </main>

  <script>
    function toggleAttributes() {
      const category = document.getElementById('category').value;
      document.getElementById('menu-attributes').style.display = category === 'menu' ? 'block' : 'none';
    }
    toggleAttributes();
  </script>
</body>

</html>
