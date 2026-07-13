<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

include "dbconn.php";

// Validate and sanitize input
if (
  isset($_POST['id'], $_POST['name'], $_POST['image_path'], $_POST['price'], $_POST['category']) &&
  is_numeric($_POST['id']) &&
  is_numeric($_POST['price']) &&
  ($_POST['old_price'] === '' || is_numeric($_POST['old_price']))
) {
  $id = (int)$_POST['id'];
  $name = trim($_POST['name']);
  $image_path = trim($_POST['image_path']);
  $price = floatval($_POST['price']);
  $old_price = ($_POST['old_price'] === '') ? null : floatval($_POST['old_price']);
  $category = trim($_POST['category']);
  $stock = $_POST['stock'];

  if ($category === 'menu') {
    // Get menu-specific fields
    $roast_level = $_POST['roast_level'] ?? null;
    $caffeine_level = $_POST['caffeine_level'] ?? null;

    // Convert flavour profile string to JSON array
    $flavour_input = $_POST['flavour_profile'] ?? '';
    $flavour_array = array_map('trim', explode(',', $flavour_input));
    $flavour_profile = json_encode($flavour_array);

    $origin = $_POST['origin'] ?? null;
    $drink_type = $_POST['drinkType'] ?? null;
    $sugar_level = $_POST['sugar_level'] ?? null;

    //Convert Best Mood string to JSON array
    $bestMood_input = $_POST["bestMood"]  ?? '';
    $bestMood_array = array_map('trim', explode(',', $bestMood_input));
    $bestMood = json_encode($bestMood_array);

    //Convert Best Weather string to JSON array
    $bestWeather_input = $_POST["bestWeather"]  ?? '';
    $bestWeather_array = array_map('trim', explode(',', $bestWeather_input));
    $bestWeather = json_encode($bestWeather_array);
    //***** */

    // Update all fields for menu item
    $sql = "UPDATE menu_items 
            SET name = ?, image_path = ?, price = ?, old_price = ?, category = ?, 
                roast_level = ?, caffeine_level = ?, flavour_profile = ?, 
                origin = ?, drink_type = ?, sugar_level = ?, bestMood = ?, bestWeather = ?, stock = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
      mysqli_stmt_bind_param(
        $stmt,
        "ssddsssssssssii",
        $name,
        $image_path,
        $price,
        $old_price,
        $category,
        $roast_level,
        $caffeine_level,
        $flavour_profile,
        $origin,
        $drink_type,
        $sugar_level,
        $bestMood,
        $bestWeather,
        $stock,
        $id
      );
    }
  } elseif ($category === 'product') {
    // Set menu-related fields to NULL
    $sql = "UPDATE menu_items 
            SET name = ?, image_path = ?, price = ?, old_price = ?, category = ?,
                roast_level = NULL, caffeine_level = NULL, flavour_profile = NULL, 
                origin = NULL, drink_type = NULL, sugar_level = NULL, stock = ?
            WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "ssddsii", $name, $image_path, $price, $old_price, $category, $stock, $id);
    }
  }

  if ($stmt && mysqli_stmt_execute($stmt)) {
    $_SESSION['message'] = "✅ Item updated successfully.";
    mysqli_stmt_close($stmt);
  } else {
    $_SESSION['message'] = "❌ Failed to update item.";
  }
} else {
  $_SESSION['message'] = "⚠️ Invalid input. Please fill out all required fields correctly.";
}

mysqli_close($conn);
header("Location: view_items.php");
exit;
