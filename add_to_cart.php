<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  die("Unauthorized. Please log in.");
}

require_once 'dbconn.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = $_SESSION['current_user'];
  $itemID = $_POST['id'] ?? null;

  if (!is_numeric($itemID)) {
    die("Invalid item ID.");
  }

  // Get user ID from username
  $userID = null;
  $userQuery = $conn->prepare("SELECT userID FROM users WHERE username = ?");
  $userQuery->bind_param("s", $username);
  $userQuery->execute();
  $userQuery->bind_result($userID);
  $userQuery->fetch();
  $userQuery->close();

  if (!$userID) {
    die("User not found.");
  }

  // Get item details, price always from DB
  $categoryQuery = $conn->prepare("SELECT name, category, price FROM menu_items WHERE id = ?");
  $categoryQuery->bind_param("i", $itemID);
  $categoryQuery->execute();
  $categoryQuery->bind_result($name, $category, $basePrice);
  $found = $categoryQuery->fetch();
  $categoryQuery->close();

  if (!$found) {
    die("Item not found.");
  }

  if ($category === "menu") {
    $allowedMilk = ["Dairy", "Oatside", "Almond", "Soy"];
    $allowedSyrups = ["Vanilla", "Caramel", "Hazelnut"];
    $allowedToppings = ["Whipped Cream", "Expresso Jelly"];

    $drinkType = $_POST['drinkType'] ?? '';
    $roastLevel = $_POST['roastLevel'] ?? '';
    $caffeineLevel = $_POST['caffeineLevel'] ?? '';
    $milkType = in_array($_POST['milkType'] ?? '', $allowedMilk, true) ? $_POST['milkType'] : 'Dairy';
    $sugarLevel = $_POST['sugarLevel'] ?? '';
    $syrups = array_intersect((array)($_POST['syrups'] ?? []), $allowedSyrups);
    $toppings = array_intersect((array)($_POST['toppings'] ?? []), $allowedToppings);
    $qty = 1;

    $total = $basePrice
      + ($milkType !== 'Dairy' ? 1.00 : 0)
      + count($syrups) * 0.50
      + count($toppings) * 1.00;

    $syrups_text = implode(", ", $syrups);
    $toppings_text = implode(", ", $toppings);

    // Insert into cart
    $stmt = $conn->prepare("INSERT INTO cart (userID, name, drinkType, roastLevel, caffeineLevel, milkType, sugarLevel, syrups, toppings, total, qty, itemID)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssdii", $userID, $name, $drinkType, $roastLevel, $caffeineLevel, $milkType, $sugarLevel, $syrups_text, $toppings_text, $total, $qty, $itemID);
  } else if ($category === "product") {
    $qty = 1;

    $stmt = $conn->prepare("INSERT INTO cart (userID, name, total, qty, itemID)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdii", $userID, $name, $basePrice, $qty, $itemID);
  } else {
    die("Invalid category.");
  }

  // Execute insert
  if ($stmt->execute()) {
    header("Location: cart.php");
    exit;
  } else {
    echo "Error adding to cart.";
  }

  $stmt->close();
}

$conn->close();
