<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php');
  exit;
}

require_once __DIR__ . '/../src/dbconn.php';

$username = $_SESSION['current_user'];
$phone_number = "";
$email = "";
$authentication_status = false;

// Retrieve and clear flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Safely fetch user data
$sql = "SELECT phone_number, email, authentication_status FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
  mysqli_stmt_bind_param($stmt, "s", $username);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_bind_result($stmt, $phone_number, $email, $authentication_status);
  if (!mysqli_stmt_fetch($stmt)) {
    $_SESSION['flash_error'] = "User record not found.";
  }
  mysqli_stmt_close($stmt);
} else {
  $_SESSION['flash_error'] = "Something went wrong. Please try again.";
}

// Handle POST for verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$authentication_status) {
  if (isset($_POST['verify_method']) && in_array($_POST['verify_method'], ['phone', 'email'])) {
    $update_sql = "UPDATE users SET authentication_status = TRUE WHERE username = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    if ($update_stmt) {
      mysqli_stmt_bind_param($update_stmt, "s", $username);
      if (mysqli_stmt_execute($update_stmt)) {
        $_SESSION['flash_success'] = "User verified successfully!";
        header("Location: user_verify.php");
        exit;
      } else {
        $_SESSION['flash_error'] = "Failed to update verification: " . mysqli_stmt_error($update_stmt);
      }
      mysqli_stmt_close($update_stmt);
    } else {
      $_SESSION['flash_error'] = "Something went wrong. Please try again.";
    }
    header("Location: user_verify.php");
    exit;
  } else {
    $_SESSION['flash_error'] = "Please select a valid verification method.";
    header("Location: user_verify.php");
    exit;
  }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>User Verification - Bean There</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;700&display=swap" rel="stylesheet" />
  <style>
    body {
      background-color: #000;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      padding: 50px;
      max-width: 500px;
      margin: auto;
    }

    .header-container {
      position: relative;
      display: flex;
      align-items: center;
      margin-bottom: 30px;
      height: 40px;
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
      transition: background-color 0.3s, color 0.3s;
      z-index: 50;
    }

    .go-back-btn:hover {
      background-color: #fff;
      color: #000;
    }

    h1 {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      margin: 0;
      font-weight: 700;
      font-size: 28px;
      color: #c49b63;
      white-space: nowrap;
      pointer-events: none;
    }

    form {
      background-color: #111;
      padding: 30px;
      border-radius: 10px;
    }

    label {
      display: block;
      margin-top: 20px;
      font-weight: 500;
      cursor: pointer;
    }

    input[type="radio"] {
      margin-right: 10px;
      cursor: pointer;
    }

    .btn {
      background-color: #c49b63;
      color: #000;
      padding: 12px 25px;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      font-size: 18px;
      margin-top: 30px;
      cursor: pointer;
      width: 100%;
      display: block;
      text-align: center;
      text-decoration: none;
    }

    .btn:hover:not(:disabled) {
      background-color: #fff;
      color: #000;
    }

    .btn:disabled {
      background-color: #444;
      color: #888;
      cursor: not-allowed;
    }

    .messages {
      margin-top: 20px;
      text-align: center;
    }

    .error {
      color: #ff5555;
      margin-bottom: 10px;
    }

    .success {
      color: #7fff7f;
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <a href="user_dashboard.php" class="go-back-btn">← Go To Main Menu</a>

  <div class="header-container">
    <h1>User Verification</h1>
  </div>

  <form method="POST" action="user_verify.php">
    <?php if ($authentication_status): ?>
      <p style="text-align:center; font-weight:600; margin-bottom: 25px; color: #7fff7f;">
        Your account is already verified.
      </p>
    <?php else: ?>
      <?php
      $hasPhone = !empty($phone_number);
      $hasEmail = !empty($email);
      ?>

      <?php if ($hasPhone): ?>
        <label>
          <input type="radio" name="verify_method" value="phone" />
          Verify via Phone Number (<?= htmlspecialchars($phone_number) ?>)
        </label>
      <?php endif; ?>

      <?php if ($hasEmail): ?>
        <label>
          <input type="radio" name="verify_method" value="email" />
          Verify via Email (<?= htmlspecialchars($email) ?>)
        </label>
      <?php endif; ?>

      <?php if (!$hasPhone && !$hasEmail): ?>
        <p style="color: #ff5555; font-weight: 500; margin-top: 20px; text-align: center;">
          No verification methods available. Please update your profile.
        </p>
      <?php endif; ?>
    <?php endif; ?>

    <button type="submit" class="btn"
      <?= ($authentication_status || (!$hasPhone && !$hasEmail)) ? 'disabled' : '' ?>>
      <?= $authentication_status ? 'Verified' : 'Verify' ?>
    </button>
  </form>

  <div class="messages">
    <?php if (!empty($flash_success)): ?>
      <p class="success"><?= htmlspecialchars($flash_success) ?></p>
    <?php elseif (!empty($flash_error)): ?>
      <p class="error"><?= htmlspecialchars($flash_error) ?></p>
    <?php endif; ?>
  </div>
</body>

</html>