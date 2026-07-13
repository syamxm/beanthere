<?php
session_start();

require_once "dbconn.php";
require_once "src/get_recommendation.php";

$recommendations = [];
$error = "";

// Step 1: Capture POST and redirect to clear form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $_SESSION['form_data'] = $_POST;
  header("Location: recommendation.php#recommended");
  exit;
}

// Step 2: Process session data if it exists
if (isset($_SESSION['form_data'])) {
  $answers = [
    'roast' => trim($_SESSION['form_data']['roast'] ?? ''),
    'caffeine' => trim($_SESSION['form_data']['caffeine'] ?? ''),
    'flavour' => trim($_SESSION['form_data']['flavour'] ?? ''),
    'currentMood' => trim($_SESSION['form_data']['currentMood'] ?? ''),
    'currentWeather' => trim($_SESSION['form_data']['currentWeather'] ?? ''),
  ];
  unset($_SESSION['form_data']); // Clear after use

  if (implode('', $answers) === '') {
    $error = "Please fill in at least one field to get recommendations.";
  } else {
    $recommendations = get_recommendation($answers);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Recommendation - Bean There</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="style_scrollbar.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #111;
      color: #fff;
      margin: 0;
      padding: 0;
    }

    header {
      display: flex;
      justify-content: center;
      align-items: center;
      padding-top: 20px;
    }

    .logo {
      font-size: 28px;
      font-weight: 700;
      color: #c49b63;
      text-transform: uppercase;
      letter-spacing: 2px;
      text-align: center;
    }

    .page-content {
      display: flex;
      justify-content: center;
      padding-top: 100px;
      margin-bottom: 40px;
    }

    form {
      background: #222;
      padding: 20px;
      border-radius: 8px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
    }

    select,
    input[type="text"] {
      width: 100%;
      padding: 10px;
      background-color: #333;
      color: #fff;
      border: 1px solid #444;
      border-radius: 4px;
    }

    .submit-button {
      width: 100%;
      padding: 10px;
      background-color: #c49b63;
      color: #000;
      border: none;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
    }

    .submit-button:hover {
      background-color: #fff;
    }

    .error {
      color: red;
      text-align: center;
      margin-top: 20px;
    }

    .go-back-btn {
      position: fixed;
      top: 20px;
      left: 20px;
      background-color: #c49b63;
      color: #000;
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      text-decoration: none;
      z-index: 50;
    }

    .go-back-btn:hover {
      background-color: #fff;
      color: #000;
    }

    section.menu {
      text-align: center;
      margin-top: 40px;
    }

    section.menu h2 {
      font-size: 32px;
      color: #c49b63;
      margin-bottom: 20px;
    }

    section.menu h2 span {
      color: #fff;
    }

    .box-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      padding: 20px;
    }

    .box {
      background-color: #1a1a1a;
      border: 1px solid #444;
      border-radius: 10px;
      padding: 20px;
      width: 250px;
      text-align: center;
      transition: transform 0.3s;
    }

    .box:hover {
      transform: translateY(-5px);
    }

    .box img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-radius: 10px;
      margin-bottom: 15px;
    }

    .box h3 {
      margin-bottom: 10px;
      font-size: 20px;
    }

    .price {
      font-size: 18px;
      color: #c49b63;
    }

    .price span {
      text-decoration: line-through;
      color: #888;
      margin-left: 8px;
    }

    .btn {
      display: inline-block;
      margin-top: 15px;
      background: #c49b63;
      color: #000;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      text-decoration: none;
    }

    .btn:hover {
      background: #fff;
    }
  </style>
</head>

<body>
  <a href="user_dashboard.php" class="go-back-btn">← Go To Main Menu</a>

  <header>
    <div class="logo">Get Your Coffee Recommendation</div>
  </header>

  <div class="page-content">
    <div class="form-container">
      <form action="recommendation.php" method="post">
        <div class="form-group">
          <label for="roast">Roast Level</label>
          <select name="roast" id="roast">
            <option value="">Select...</option>
            <option value="light">Light</option>
            <option value="medium">Medium</option>
            <option value="dark">Dark</option>
          </select>
        </div>

        <div class="form-group">
          <label for="caffeine">Caffeine Level</label>
          <select name="caffeine" id="caffeine">
            <option value="">Select...</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>

        <div class="form-group">
          <label for="flavour">Flavour Profile</label>
          <input type="text" name="flavour" id="flavour" placeholder="e.g. Nutty, Smooth" />
        </div>

        <div class="form-group">
          <label for="currentMood">Current Mood</label>
          <input type="text" name="currentMood" id="currentMood" placeholder="e.g. jolly, low, relaxed" />
        </div>

        <div class="form-group">
          <label for="currentWeather">Current Weather</label>
          <input type="text" name="currentWeather" id="currentWeather" placeholder="e.g. Sunny, Cold, Rainy" />
        </div>

        <div class="form-group">
          <button type="submit" class="submit-button">Get Recommendation</button>
        </div>
      </form>
    </div>
  </div>

  <?php if (!empty($error)): ?>
    <p class="error"><?= $error ?></p>
  <?php endif; ?>

  <?php if (count($recommendations) > 0): ?>
    <section class="menu" id="recommended">
      <h2>YOUR <span>RECOMMENDATIONS</span></h2>
      <div class="box-container">
        <?php foreach ($recommendations as $item): ?>
          <form action="customize.php" method="post">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <input type="hidden" name="from_section" value="<?= $item['category'] ?>">
            <div class="box">
              <img loading="lazy" src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <h3><?= htmlspecialchars($item['name']) ?></h3>
              <div class="price">
                RM<?= number_format($item['price'], 2) ?>
                <?php if ($item['category'] === 'menu' && isset($item['old_price']) && $item['old_price'] > 0): ?>
                  <span>RM<?= number_format($item['old_price'], 2) ?></span>
                <?php endif; ?>
              </div>
              <button type="submit" class="btn"><?= $item['category'] === 'menu' ? 'Buy' : 'Add to Cart' ?></button>
            </div>
          </form>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

</body>

</html>