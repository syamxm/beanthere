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
  $image_path = $_POST["image_path"];
  $price = floatval($_POST["price"]);
  $old_price = ($_POST["old_price"] === '') ? null : floatval($_POST["old_price"]);
  $category = $_POST["category"];
  $stock = (int)$_POST["stock"];

  if ($category === 'menu') {
    $roast_level = $_POST["roast_level"];
    $caffeine_level = $_POST["caffeine_level"];
    //***** */
    $flavour_tags_input = $_POST["flavour_profile"];
    $flavour_tags_array = array_map('trim', explode(',', $flavour_tags_input));
    $flavour_tags_json = json_encode($flavour_tags_array);
    //***** */
    $origin = $_POST["origin"];
    //***** */
    $bestMood_input = $_POST["bestMood"];
    $bestMood_array = array_map('trim', explode(',', $bestMood_input));
    $bestMood_json = json_encode($bestMood_array);
    //***** */
    $bestWeather_input = $_POST["bestWeather"];
    $bestWeather_array = array_map('trim', explode(',', $bestWeather_input));
    $bestWeather_json = json_encode($bestWeather_array);
    //***** */

    $stmt = $conn->prepare("INSERT INTO menu_items (name, image_path, price, old_price, category, roast_level, caffeine_level, flavour_profile, origin, bestMood, bestWeather, stock)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddsssssssi", $name, $image_path, $price, $old_price, $category, $roast_level, $caffeine_level, $flavour_tags_json, $origin, $bestMood_json, $bestWeather_json, $stock);
  } else {
    $stmt = $conn->prepare("INSERT INTO menu_items (name, image_path, price, old_price, category, stock)
            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddsi", $name, $image_path, $price, $old_price, $category, $stock);
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Add Item</title>
  <link rel="stylesheet" href="../assets/style_scrollbar.css" />
  <style>
    body {
      background-color: #121212;
      color: #ffffff;
      font-family: Arial, sans-serif;
      padding: 120px 20px 20px 20px;
      margin: 0;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background-color: #1f1f1f;
      padding: 20px;
      z-index: 999;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
    }

    .back-link {
      position: absolute;
      top: 20px;
      left: 20px;
      color: #c49b63;
      text-decoration: none;
      font-weight: bold;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    h1 {
      color: #c49b63;
      margin: 0;
    }

    .container {
      max-width: 600px;
      margin: 30px auto;
      background: #1e1e1e;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
    }

    .container h2 {
      color: #c49b63;
      text-align: center;
      margin-bottom: 20px;
    }

    .message {
      text-align: center;
      margin-bottom: 20px;
      font-weight: bold;
      color: #76ff03;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      font-weight: bold;
      margin-bottom: 6px;
    }

    input,
    select {
      padding: 10px;
      margin-bottom: 18px;
      border-radius: 5px;
      border: none;
      background-color: #2a2a2a;
      color: white;
    }

    .menu-section {
      margin-top: 10px;
      padding-top: 10px;
      border-top: 1px solid #444;
      transition: all 0.3s ease;
    }

    .section-title {
      color: #c49b63;
      font-size: 16px;
      margin-bottom: 10px;
      text-align: left;
      border-left: 4px solid #c49b63;
      padding-left: 10px;
    }

    #menu-fields {
      display: none;
      flex-direction: column;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    #menu-fields.show {
      display: flex;
      opacity: 1;
    }


    button {
      padding: 12px;
      background-color: #c49b63;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
      color: black;
      transition: 0.3s;
    }

    button:hover {
      background-color: #fff;
      color: #000;
    }
  </style>
</head>

<body>
  <header>
    <a href="admin_home.php" class="back-link">⬅ Back to Admin Page</a>
    <h1>Add New Item</h1>
  </header>

  <div class="container">
    <h2>Insert Menu/Product</h2>
    <?php if (!empty($flash)): ?>
      <div class="message"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrf_field() ?>
      <label for="name">Item Name:</label>
      <input type="text" name="name" id="name" required>

      <label for="image_path">Image Path:</label>
      <input type="text" name="image_path" id="image_path" required>

      <label for="price">Current Price (RM):</label>
      <input type="number" step="0.01" name="price" id="price" required>

      <label for="old_price">Old Price (RM):</label>
      <input type="number" step="0.01" name="old_price" id="old_price">

      <label for="category">Category:</label>
      <select name="category" id="category" required>
        <option value="menu">Menu</option>
        <option value="product">Product</option>
      </select>

      <!-- Replace your #menu-fields section in the HTML part with the following -->

      <div id="menu-fields" class="menu-section">
        <h3 class="section-title">Menu-Specific Details</h3>

        <label for="roast_level">Roast Level</label>
        <select id="roast_level" name="roast_level">
          <option value="">Select Roast Level</option>
          <option value="Light">Light</option>
          <option value="Medium">Medium</option>
          <option value="Dark">Dark</option>
        </select>



        <label for="caffeine_level">Caffeine Level</label>
        <select id="caffeine_level" name="caffeine_level">
          <option value="">Select Caffeine Level</option>
          <option value="Low">Low</option>
          <option value="Medium">Medium</option>
          <option value="High">High</option>
        </select>

        <label for="flavour_profile">Flavour Profile</label>
        <input type="text" id="flavour_profile" name="flavour_profile" placeholder="e.g. Nutty, Chocolaty">

        <label for="origin">Origin</label>
        <input type="text" id="origin" name="origin" placeholder="e.g. Colombia">

        <label for="bestMood">Best Mood</label>
        <input type="text" id="bestMood" name="bestMood" placeholder="e.g. Moody">

        <label for="bestWeather">Best Weather</label>
        <input type="text" id="bestWeather" name="bestWeather" placeholder="e.g. Raining">

      </div>

      <label for="stock">Stock</label>
      <input type="number" id="stock" name="stock">


      <button type=" submit">Save Item</button>
    </form>
  </div>

  <script>
    const categorySelect = document.getElementById("category");
    const menuFields = document.getElementById("menu-fields");

    function toggleFields() {
      if (categorySelect.value === "menu") {
        menuFields.classList.add("show");
      } else {
        menuFields.classList.remove("show");
      }
    }

    categorySelect.addEventListener("change", toggleFields);
    window.addEventListener("DOMContentLoaded", toggleFields);
  </script>
</body>

</html>