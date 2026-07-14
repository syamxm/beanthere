<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  $_SESSION['message'] = "Log in to add items to your cart.";
  $_SESSION['success'] = false;
  header("Location: user_login.php?return=user_dashboard.php");
  exit;
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/pricing.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  csrf_verify();

  if (!store_status($conn)['open']) {
    $_SESSION['message'] = "We're closed right now — ordering is paused until we reopen.";
    $_SESSION['success'] = false;
    header("Location: user_dashboard.php");
    exit;
  }

  $username = $_SESSION['current_user'];
  $itemID = $_POST['id'] ?? null;

  if (!is_numeric($itemID)) {
    $_SESSION['message'] = "Invalid item — please try again from the menu.";
    $_SESSION['success'] = false;
    header("Location: user_dashboard.php");
    exit;
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
    $_SESSION['message'] = "We couldn't find your account — please log in again.";
    $_SESSION['success'] = false;
    header("Location: user_login.php?return=user_dashboard.php");
    exit;
  }

  // Get item details, price always from DB
  $categoryQuery = $conn->prepare("SELECT name, category, price, stock FROM menu_items WHERE id = ?");
  $categoryQuery->bind_param("i", $itemID);
  $categoryQuery->execute();
  $categoryQuery->bind_result($name, $category, $basePrice, $stock);
  $found = $categoryQuery->fetch();
  $categoryQuery->close();

  if (!$found) {
    $_SESSION['message'] = "That item is no longer available.";
    $_SESSION['success'] = false;
    header("Location: user_dashboard.php");
    exit;
  }

  $qty = 1;
  if ($stock < $qty) {
    $_SESSION['message'] = "$name is sold out right now.";
    $_SESSION['success'] = false;
    header("Location: user_dashboard.php");
    exit;
  }

  if ($category === "menu") {
    $allowedMilk = ["Dairy", "Oatside", "Almond", "Soy"];
    $allowedSyrups = ["Vanilla", "Caramel", "Hazelnut"];
    $allowedToppings = ["Whipped Cream", "Espresso Jelly"];
    $allowedDrinkTypes = ["Hot", "Iced"];
    $allowedRoastLevels = ["", "light", "medium", "dark"];
    $allowedCaffeineLevels = ["", "low", "medium", "high"];
    $allowedSugarLevels = ["0%", "25%", "50%", "75%", "100%"];

    $drinkType = $_POST['drinkType'] ?? 'Hot';
    $roastLevel = $_POST['roastLevel'] ?? '';
    $caffeineLevel = $_POST['caffeineLevel'] ?? '';
    $milkType = $_POST['milkType'] ?? 'Dairy';
    $sugarLevel = $_POST['sugarLevel'] ?? '0%';

    $optionsValid = in_array($drinkType, $allowedDrinkTypes, true)
      && in_array($roastLevel, $allowedRoastLevels, true)
      && in_array($caffeineLevel, $allowedCaffeineLevels, true)
      && in_array($milkType, $allowedMilk, true)
      && in_array($sugarLevel, $allowedSugarLevels, true);

    if (!$optionsValid) {
      $_SESSION['message'] = "Those drink options aren't available — please pick from the menu.";
      $_SESSION['success'] = false;
      header("Location: user_dashboard.php");
      exit;
    }

    $syrups = array_intersect((array)($_POST['syrups'] ?? []), $allowedSyrups);
    $toppings = array_intersect((array)($_POST['toppings'] ?? []), $allowedToppings);

    $syrups_text = implode(", ", $syrups);
    $toppings_text = implode(", ", $toppings);

    $unitPrice = drink_unit_price((float)$basePrice, $milkType, $syrups_text, $toppings_text);
    $total = cart_line_total($unitPrice, $qty);

    // Insert into cart
    $stmt = $conn->prepare("INSERT INTO cart (userID, name, drinkType, roastLevel, caffeineLevel, milkType, sugarLevel, syrups, toppings, total, qty, itemID)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssdii", $userID, $name, $drinkType, $roastLevel, $caffeineLevel, $milkType, $sugarLevel, $syrups_text, $toppings_text, $total, $qty, $itemID);
  } else if ($category === "product") {
    $total = cart_line_total((float)$basePrice, $qty);
    $noDrinkOption = '';

    // The drink columns are NOT NULL without a default, so a bean bag stores
    // empty strings rather than omitting them.
    $stmt = $conn->prepare("INSERT INTO cart (userID, name, drinkType, roastLevel, caffeineLevel, milkType, sugarLevel, total, qty, itemID)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
      "sssssssdii",
      $userID,
      $name,
      $noDrinkOption,
      $noDrinkOption,
      $noDrinkOption,
      $noDrinkOption,
      $noDrinkOption,
      $total,
      $qty,
      $itemID
    );
  } else {
    $_SESSION['message'] = "That item can't be added to the cart.";
    $_SESSION['success'] = false;
    header("Location: user_dashboard.php");
    exit;
  }

  try {
    $stmt->execute();
    $stmt->close();
    header("Location: cart.php");
    exit;
  } catch (mysqli_sql_exception $e) {
    $_SESSION['message'] = "Something went wrong adding that to your cart — please try again.";
    $_SESSION['success'] = false;
    header("Location: user_dashboard.php");
    exit;
  }
}

$conn->close();
