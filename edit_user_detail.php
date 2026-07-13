<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION["current_user"])) {
  header("Location: user_login.php");
  exit;
}

include "dbconn.php";

$current_username = $_SESSION["current_user"];

// Load flash messages
$flash_success = $_SESSION['flash_success'] ?? "";
$flash_errors = $_SESSION['flash_errors'] ?? [];
unset($_SESSION['flash_success'], $_SESSION['flash_errors']);

// Fetch current user info
$sql = "SELECT userID, username, phone_number, email FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
  die("User not found.");
}
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];

  // Sanitize input
  $username = trim($_POST['username']);
  $phone = trim($_POST['phone_number']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];

  // Validation
  if (empty($username)) {
    $errors[] = "Username cannot be empty.";
  }

  if (!empty($password)) {
    if ($password !== $confirm_password) {
      $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
      $errors[] = "Password must be at least 6 characters.";
    }
  }

  if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
  }

  // Check for unique username if changed
  if ($username !== $user['username']) {
    $stmt = $conn->prepare("SELECT userID FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
      $errors[] = "Username already taken.";
    }
    $stmt->close();
  }

  // Check unique phone number if changed
  if (!empty($phone) && $phone !== $user['phone_number']) {
    $stmt = $conn->prepare("SELECT userID FROM users WHERE phone_number = ? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
      $errors[] = "Phone number already in use.";
    }
    $stmt->close();
  }

  // Check unique email if changed
  if (!empty($email) && $email !== $user['email']) {
    $stmt = $conn->prepare("SELECT userID FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
      $errors[] = "Email already in use.";
    }
    $stmt->close();
  }

  // If no errors, proceed to update
  if (empty($errors)) {
    if (!empty($password)) {
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $update_sql = "UPDATE users SET username=?, password=?, phone_number=?, email=? WHERE userID=?";
      $stmt = $conn->prepare($update_sql);
      $stmt->bind_param("ssssi", $username, $hashed_password, $phone, $email, $user['userID']);
    } else {
      $update_sql = "UPDATE users SET username=?, phone_number=?, email=? WHERE userID=?";
      $stmt = $conn->prepare($update_sql);
      $stmt->bind_param("sssi", $username, $phone, $email, $user['userID']);
    }

    if ($stmt->execute()) {
      $_SESSION['flash_success'] = "Details updated successfully.";
      $_SESSION["current_user"] = $username;
    } else {
      $_SESSION['flash_errors'] = ["Failed to update details."];
    }
    $stmt->close();
    header("Location: edit_user_detail.php");
    exit;
  } else {
    $_SESSION['flash_errors'] = $errors;
    header("Location: edit_user_detail.php");
    exit;
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Edit User Details - Bean There</title>
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
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-top: 8px;
      background-color: #222;
      color: #fff;
      border: none;
      border-radius: 5px;
      font-size: 16px;
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

    .btn:hover {
      background-color: #fff;
      color: #000;
    }

    .messages {
      margin-top: 20px;
    }

    .error {
      color: #ff5555;
      margin-bottom: 10px;
    }

    .success {
      color: #7fff7f;
      margin-bottom: 10px;
      text-align: center;
    }
  </style>
</head>

<body>
  <a href="user_dashboard.php" class="go-back-btn">← Go To Main Menu</a>
  <div class="header-container">
    <h1>Edit Your Details</h1>
  </div>

  <div class="messages">
    <?php foreach ($flash_errors as $error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <?php if (!empty($flash_success)): ?>
      <div class="success"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>
  </div>

  <form method="POST" action="edit_user_detail.php">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($user['username']) ?>" />

    <label for="phone_number">Phone Number</label>
    <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" />

    <label for="password">New Password (leave blank to keep current)</label>
    <input type="password" id="password" name="password" autocomplete="new-password" />

    <label for="confirm_password">Confirm New Password</label>
    <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" />

    <button type="submit" class="btn">Update Details</button>
  </form>
</body>

</html>