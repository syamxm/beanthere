<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

$flash = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  csrf_verify();
  $name = $_POST["name"];
  $description = trim($_POST["description"] ?? '');
  $image_path = $_POST["image_path"];
  $price = floatval($_POST["price"]);
  $old_price = ($_POST["old_price"] === '') ? null : floatval($_POST["old_price"]);
  $category = $_POST["category"];
  $stock = (int)$_POST["stock"];

  if ($category === 'menu') {
    $roast_level = $_POST["roast_level"];
    $caffeine_level = $_POST["caffeine_level"];
    $drink_type = $_POST["drink_type"] ?? null;
    $sugar_level = $_POST["sugar_level"] ?? null;

    $flavour_tags_array = array_map('trim', explode(',', $_POST["flavour_profile"]));
    $flavour_tags_json = json_encode($flavour_tags_array);

    $origin = $_POST["origin"];

    $bestMood_array = array_map('trim', explode(',', $_POST["bestMood"]));
    $bestMood_json = json_encode($bestMood_array);

    $bestWeather_array = array_map('trim', explode(',', $_POST["bestWeather"]));
    $bestWeather_json = json_encode($bestWeather_array);

    $stmt = $conn->prepare("INSERT INTO menu_items (name, description, image_path, price, old_price, category, roast_level, caffeine_level, flavour_profile, origin, drink_type, sugar_level, bestMood, bestWeather, stock)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssddsssssssssi", $name, $description, $image_path, $price, $old_price, $category, $roast_level, $caffeine_level, $flavour_tags_json, $origin, $drink_type, $sugar_level, $bestMood_json, $bestWeather_json, $stock);
  } else {
    $stmt = $conn->prepare("INSERT INTO menu_items (name, description, image_path, price, old_price, category, stock)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssddsi", $name, $description, $image_path, $price, $old_price, $category, $stock);
  }

  if ($stmt->execute()) {
    $_SESSION['message'] = "Item '$name' added successfully.";
  } else {
    $_SESSION['message'] = "Error: item could not be added.";
  }
  $stmt->close();

  header("Location: add_item.php");
  exit();
}

$pageTitle = 'Add item - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6 text-center">Add new item</h1>

    <?php if (!empty($flash)): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <form method="POST" class="admin-form">
      <?= csrf_field() ?>
      <label for="name">Item name</label>
      <input type="text" name="name" id="name" required>

      <label for="description">Description</label>
      <textarea name="description" id="description" rows="2" placeholder="One short sentence shown on the menu card"></textarea>

      <label for="image_path">Image path</label>
      <input type="text" name="image_path" id="image_path" placeholder="assets/images/example.jpg" required>

      <label for="price">Current price (RM)</label>
      <input type="number" step="0.01" name="price" id="price" required>

      <label for="old_price">Old price (RM, optional)</label>
      <input type="number" step="0.01" name="old_price" id="old_price">

      <label for="category">Category</label>
      <select name="category" id="category" required>
        <option value="menu">Menu (drink)</option>
        <option value="product">Product (beans)</option>
      </select>

      <div id="menu-fields">
        <label for="roast_level">Roast level</label>
        <select id="roast_level" name="roast_level">
          <option value="">No roast level</option>
          <option value="light">Light</option>
          <option value="medium">Medium</option>
          <option value="dark">Dark</option>
        </select>

        <label for="caffeine_level">Caffeine level</label>
        <select id="caffeine_level" name="caffeine_level">
          <option value="">No caffeine level</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
        </select>

        <label for="drink_type">Default serve</label>
        <select id="drink_type" name="drink_type">
          <option value="Hot">Hot</option>
          <option value="Iced">Iced</option>
        </select>

        <label for="sugar_level">Default sugar</label>
        <select id="sugar_level" name="sugar_level">
          <option value="0%">0%</option>
          <option value="25%">25%</option>
          <option value="50%" selected>50%</option>
          <option value="75%">75%</option>
          <option value="100%">100%</option>
        </select>

        <label for="flavour_profile">Flavour profile (comma-separated)</label>
        <input type="text" id="flavour_profile" name="flavour_profile" placeholder="e.g. Nutty, Chocolatey">

        <label for="origin">Origin</label>
        <input type="text" id="origin" name="origin" placeholder="e.g. Colombia">

        <label for="bestMood">Best mood (comma-separated)</label>
        <input type="text" id="bestMood" name="bestMood" placeholder="e.g. focused, relaxed">

        <label for="bestWeather">Best weather (comma-separated)</label>
        <input type="text" id="bestWeather" name="bestWeather" placeholder="e.g. rainy, cold">
      </div>

      <label for="stock">Stock</label>
      <input type="number" id="stock" name="stock" required>

      <button type="submit" class="btn-caramel w-full mt-5">Save item</button>
    </form>
  </main>

  <script>
    const categorySelect = document.getElementById("category");
    const menuFields = document.getElementById("menu-fields");

    function toggleFields() {
      menuFields.style.display = categorySelect.value === "menu" ? "block" : "none";
    }

    categorySelect.addEventListener("change", toggleFields);
    toggleFields();
  </script>
</body>

</html>
