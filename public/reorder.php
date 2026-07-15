<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php?return=user_order_tracking.php');
  exit();
}

require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/pricing.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: user_order_tracking.php');
  exit();
}
csrf_verify();

if (!store_status($conn)['open']) {
  $_SESSION['message'] = "We're closed right now — ordering is paused until we reopen.";
  header('Location: user_order_tracking.php');
  exit();
}

$checkoutID = $_POST['checkoutID'] ?? '';
if (!preg_match('/^[A-Za-z0-9]{1,12}$/', $checkoutID)) {
  $_SESSION['message'] = 'That order could not be found.';
  header('Location: user_order_tracking.php');
  exit();
}

$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['current_user']);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT itemID, name, drinkType, roastLevel, caffeineLevel, milkType, sugarLevel,
                               syrups, toppings, qty, total
                        FROM orders
                        WHERE checkoutID = ? AND userID = ?");
$stmt->bind_param("si", $checkoutID, $userID);
$stmt->execute();
$orderRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($orderRows)) {
  $_SESSION['message'] = 'That order could not be found.';
  header('Location: user_order_tracking.php');
  exit();
}

$added = 0;
$skipped = [];
$priceChanged = [];

$itemStmt = $conn->prepare("SELECT name, category, price, stock FROM menu_items WHERE id = ?");
$cartStmt = $conn->prepare("INSERT INTO cart (userID, name, drinkType, roastLevel, caffeineLevel, milkType, sugarLevel, syrups, toppings, total, qty, itemID)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($orderRows as $row) {
  $itemID = $row['itemID'];
  $found = false;
  if ($itemID !== null) {
    $itemStmt->bind_param("i", $itemID);
    $itemStmt->execute();
    $itemStmt->bind_result($menuName, $category, $basePrice, $stock);
    $found = (bool)$itemStmt->fetch();
    $itemStmt->free_result();
  }

  if (!$found) {
    $skipped[] = "{$row['name']} is no longer on the menu";
    continue;
  }

  $qty = max(1, (int)$row['qty']);
  if ($stock < $qty) {
    $skipped[] = "$menuName is sold out right now";
    continue;
  }

  if ($category === 'menu') {
    $unitPrice = drink_unit_price((float)$basePrice, $row['milkType'], $row['syrups'], $row['toppings']);
  } else {
    $unitPrice = (float)$basePrice;
  }
  $total = cart_line_total($unitPrice, $qty);

  if (abs($total - (float)$row['total']) >= 0.01) {
    $priceChanged[] = sprintf('%s is now RM%s', $menuName, number_format($total, 2));
  }

  $drinkType = $row['drinkType'] ?? '';
  $roastLevel = $row['roastLevel'] ?? '';
  $caffeineLevel = $row['caffeineLevel'] ?? '';
  $milkType = $row['milkType'] ?? '';
  $sugarLevel = $row['sugarLevel'] ?? '';
  $syrups = $row['syrups'] ?? '';
  $toppings = $row['toppings'] ?? '';
  $cartStmt->bind_param(
    "issssssssdii",
    $userID,
    $menuName,
    $drinkType,
    $roastLevel,
    $caffeineLevel,
    $milkType,
    $sugarLevel,
    $syrups,
    $toppings,
    $total,
    $qty,
    $itemID
  );
  $cartStmt->execute();
  $added++;
}
$itemStmt->close();
$cartStmt->close();

$notes = array_merge($skipped, $priceChanged);
if ($added === 0) {
  $_SESSION['message'] = 'Nothing could be re-added: ' . implode('; ', $skipped) . '.';
  header('Location: user_order_tracking.php');
  exit();
}

$_SESSION['message'] = "$added item(s) added to your cart at current prices"
  . ($notes ? ' — ' . implode('; ', $notes) : '') . '.';
$_SESSION['success'] = true;
header('Location: cart.php');
exit();
