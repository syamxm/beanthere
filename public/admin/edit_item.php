<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

// Database connection
require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

// Check if 'id' is set
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  $_SESSION['message'] = "⚠️ Invalid ID.";
  header("Location: view_items.php");
  exit;
}

$id = (int)$_GET['id'];

// Fetch the item
$sql = "SELECT * FROM menu_items WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
  mysqli_stmt_bind_param($stmt, "i", $id);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);

  if ($row = mysqli_fetch_assoc($result)) {
    // Assign all columns
    $name = $row['name'];
    $image_path = $row['image_path'];
    $price = $row['price'];
    $old_price = $row['old_price'];
    $category = $row['category'];
    $roast_level = $row['roast_level'];
    $caffeine_level = $row['caffeine_level'];
    // *****
    $flavour_input = $row["flavour_profile"];
    $flavour_array = json_decode($flavour_input, true);
    $flavour_profile = is_array($flavour_array) ? implode(', ', $flavour_array) : '';
    //***** 
    $origin = $row['origin'];
    $drink_type = $row['drink_type'];
    $sugar_level = $row['sugar_level'];
    //***** */
    $bestMood_json = $row["bestMood"];
    $bestMood_array = json_decode($bestMood_json, true);
    $bestMood = is_array($bestMood_array) ? implode(', ', $bestMood_array) : '';
    //***** */
    $bestWeather_json = $row["bestWeather"];
    $bestWeather_array = json_decode($bestWeather_json, true);
    $bestWeather = is_array($bestWeather_array) ? implode(', ', $bestWeather_array) : '';
    //***** */

    $stock = $row['stock'];
  } else {
    $_SESSION['message'] = "❌ Item not found.";
    header("Location: view_items.php");
    exit;
  }

  mysqli_stmt_close($stmt);
} else {
  echo "Error loading item.";
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Menu Item</title>
  <link rel="stylesheet" href="../assets/style_scrollbar.css" />
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      background-color: #121212;
      font-family: Arial, sans-serif;
      color: #fff;
    }

    .container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .admin-form {
      width: 400px;
      background-color: #1f1f1f;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(255, 255, 255, 0.05);
      text-align: center;
    }

    h2 {
      color: #c49b63;
      margin-bottom: 25px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    #menu-attributes {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }


    .form-group {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    label {
      margin-bottom: 5px;
      font-weight: bold;
    }

    input,
    select {
      width: 100%;
      padding: 10px;
      border: none;
      border-radius: 8px;
      background-color: #2c2c2c;
      color: #fff;
      font-size: 14px;
    }

    input:focus,
    select:focus {
      outline: 2px solid #c49b63;
    }

    button {
      margin-top: 10px;
      padding: 12px;
      width: 100%;
      border: none;
      border-radius: 10px;
      background-color: #c49b63;
      color: #000;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 16px;
    }

    button:hover {
      background-color: #fff;
    }

    .button-link {
      display: inline-block;
      margin-top: 10px;
      color: #c49b63;
      text-decoration: none;
      font-weight: bold;
    }

    .button-link:hover {
      text-decoration: underline;
    }

    .message {
      margin-top: 20px;
      font-weight: bold;
      color: #76ff03;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="admin-form">
      <h1>Edit Menu Item</h1>
      <form action="update_item.php" method="post">
        <?php echo csrf_field() ?>
        <input type="hidden" name="id" value="<?php echo $id ?>" />

        <div class="form-group">
          <label for="name">Item Name</label>
          <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name) ?>" required>
        </div>

        <div class="form-group">
          <label for="image_path">Image Path</label>
          <input type="text" id="image_path" name="image_path" value="<?php echo htmlspecialchars($image_path) ?>" required>
        </div>

        <div class="form-group">
          <label for="price">Current Price (RM)</label>
          <input type="number" step="0.01" id="price" name="price" value="<?php echo $price ?>" required>
        </div>

        <div class="form-group">
          <label for="old_price">Old Price (RM)</label>
          <input type="number" step="0.01" id="old_price" name="old_price" value="<?php echo $old_price ?>">
        </div>

        <div class="form-group">
          <label for="category">Category</label>
          <select id="category" name="category" onchange="toggleAttributes()">
            <option value="menu" <?php if ($category == 'menu') echo 'selected'; ?>>Menu</option>
            <option value="product" <?php if ($category == 'product') echo 'selected'; ?>>Product</option>
          </select>
        </div>

        <div id="menu-attributes">
          <div class="form-group">
            <label for="roast_level">Roast Level</label>
            <select id="roast_level" name="roast_level">
              <option value="" <?php if (empty($roast_level)) echo 'selected'; ?>>None</option>
              <option value="light" <?php if ($roast_level == 'light') echo 'selected'; ?>>Light</option>
              <option value="medium" <?php if ($roast_level == 'medium') echo 'selected'; ?>>Medium</option>
              <option value="dark" <?php if ($roast_level == 'dark') echo 'selected'; ?>>Dark</option>
            </select>
          </div>

          <div class="form-group">
            <label for="caffeine_level">Caffeine Level</label>
            <select id="caffeine_level" name="caffeine_level">
              <option value="" <?php if (empty($caffeine_level)) echo 'selected'; ?>>None</option>
              <option value="low" <?php if ($caffeine_level == 'low') echo 'selected'; ?>>Low</option>
              <option value="medium" <?php if ($caffeine_level == 'medium') echo 'selected'; ?>>Medium</option>
              <option value="high" <?php if ($caffeine_level == 'high') echo 'selected'; ?>>High</option>
            </select>
          </div>

          <div class="form-group">
            <label for="flavour_profile">Flavour Profile</label>
            <input type="text" id="flavour_profile" name="flavour_profile" value="<?php echo htmlspecialchars($flavour_profile) ?>">
          </div>

          <div class="form-group">
            <label for="origin">Origin</label>
            <input type="text" id="origin" name="origin" value="<?php echo htmlspecialchars($origin) ?>">
          </div>

          <div class="form-group">
            <label for="drinkType">Drink Type</label>
            <select id="drinkType" name="drinkType">
              <option value="Iced" <?php if ($drink_type == 'Iced') echo 'selected'; ?>>Iced</option>
              <option value="Hot" <?php if ($drink_type == 'Hot') echo 'selected'; ?>>Hot</option>
            </select>
          </div>

          <div class="form-group">
            <label for="sugar_level">Sugar Level</label>
            <select id="sugar_level" name="sugar_level">
              <option value="0%" <?php if ($sugar_level == '0%') echo 'selected'; ?>>0%</option>
              <option value="25%" <?php if ($sugar_level == '25%') echo 'selected'; ?>>25%</option>
              <option value="50%" <?php if ($sugar_level == '50%') echo 'selected'; ?>>50%</option>
              <option value="75%" <?php if ($sugar_level == '75%') echo 'selected'; ?>>75%</option>
              <option value="100%" <?php if ($sugar_level == '100%') echo 'selected'; ?>>100%</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="bestMood">Best Mood</label>
          <input type="text" id="bestMood" name="bestMood" value="<?php echo htmlspecialchars($bestMood) ?>">
        </div>


        <div class="form-group">
          <label for="bestWeather">Best Weather</label>
          <input type="text" id="bestWeather" name="bestWeather" value="<?php echo htmlspecialchars($bestWeather) ?>">
        </div>

        <div class="form-group">
          <label for="stock">Stock</label>
          <input type="number" id="stock" name="stock" value="<?php echo $stock ?>">
        </div>


        <button type="submit">Update</button>

        <div class="form-group">
          <a href="view_items.php" class="button-link">⬅ Back to Item List</a>
        </div>
      </form>
    </div>
  </div>
  <script>
    function toggleAttributes() {
      const category = document.getElementById('category').value;
      const menuAttributes = document.getElementById('menu-attributes');
      menuAttributes.style.display = category === 'menu' ? 'flex' : 'none';
    }

    // Run on page load
    window.addEventListener('DOMContentLoaded', toggleAttributes);
  </script>

</body>

</html>