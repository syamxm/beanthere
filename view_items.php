<?php

session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="ISO-8859-1">
  <title>Menu Items</title>
  <link rel="stylesheet" href="style_scrollbar.css" />
  <style>
    body {
      background-color: #121212;
      color: #ffffff;
      font-family: Arial, sans-serif;
      padding: 120px 20px 20px 20px;
      /* add top padding to avoid hidden content */
      margin: 0;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background-color: #1f1f1f;
      padding: 20px 20px 10px 20px;
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

    table {
      width: 95%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }

    th,
    td {
      border: 1px solid #333;
      padding: 12px;
      text-align: center;
    }

    th {
      background-color: #1f1f1f;
      color: #c49b63;
    }

    tr:nth-child(even) {
      background-color: #1a1a1a;
    }

    tr:nth-child(odd) {
      background-color: #222;
    }

    a.button {
      display: inline-block;
      padding: 6px 12px;
      margin: 2px;
      background-color: #c49b63;
      color: #000;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
    }

    a.button:hover {
      background-color: #fff;
    }
  </style>
</head>

<body>

  <header>
    <a href="admin%20page.php" class="back-link">⬅ Back to Admin Page</a>
    <h1>Menu Items</h1>
  </header>

  <?php
  if (isset($_SESSION['message'])) {
    echo "<p style='text-align:center; font-weight:bold; color:#f8b400;'>" . htmlspecialchars($_SESSION['message']) . "</p>";
    unset($_SESSION['message']);
  }
  ?>


  <table border="1" cellspacing="4" cellpadding="4">
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Price</th>
      <th>Old Price</th>
      <th>Category</th>
      <th>Roast Level</th>
      <th>Caffeine Level</th>
      <th>Flavor Profile</th>
      <th>Drink Type</th>
      <th>Sugar Level</th>
      <th>Best Mood</th>
      <th>Best Weather</th>
      <th>Stock</th>
      <th>EDIT</th>
      <th>DELETE</th>
    </tr>
    <?php
    include "dbconn.php";
    $sql = "SELECT * FROM menu_items";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id'];
        $name = $row['name'];
        $price = $row['price'];
        $old_price = $row['old_price'];
        $category = $row['category'];
        $roast_level = $row['roast_level'];
        $caffeine_level = $row['caffeine_level'];
        // *****
        $flavour_input = $row["flavour_profile"];
        $flavour_array = json_decode($flavour_input, true);
        $flavour_profile = is_array($flavour_array) ? implode(', ', $flavour_array) : '';
        // *****
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
    ?>
        <tr>
          <td><?php echo $id ?></td>
          <td><?php echo htmlspecialchars($name) ?></td>

          <td><?php echo $price ?></td>
          <td><?php echo $old_price ?></td>
          <td><?php echo htmlspecialchars($category) ?></td>
          <td><?php echo htmlspecialchars($roast_level) ?></td>
          <td><?php echo htmlspecialchars($caffeine_level) ?></td>
          <td><?php echo htmlspecialchars($flavour_profile) ?></td>

          <td><?php echo htmlspecialchars($drink_type) ?></td>
          <td><?php echo htmlspecialchars($sugar_level) ?></td>
          <td><?php echo htmlspecialchars($bestMood) ?></td>
          <td><?php echo htmlspecialchars($bestWeather) ?></td>
          <td><?php echo $stock ?></td>
          <td><a href='edit_item.php?id=<?php echo $id ?>' class='button'>Edit</a></td>
          <td><a href='delete_item.php?id=<?php echo $id ?>' class='button' onclick="return confirm('Are you sure you want to delete this item?');">Delete</a></td>
        </tr>
      <?php
      }
    } else {
      ?>
      <tr>
        <td colspan="8">
          <h2>No menu items found.</h2>
        </td>
      </tr>
    <?php
    }
    mysqli_close($conn);
    ?>
  </table>

</body>

</html>