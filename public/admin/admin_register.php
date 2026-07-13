<?php

session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}
?>
<?php

// Connect to DB
require_once __DIR__ . '/../../src/dbconn.php';

// Message display
$message = $_SESSION['message'] ?? "";
$success = $_SESSION['success'] ?? false;

// Clear session message after displaying it once
unset($_SESSION['message'], $_SESSION['success']);

// Form values
$username = "";
$password = "";
$confirmPassword = "";

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $confirmPassword = trim($_POST['confirm-password']);

  // Validation
  if (empty($username) || empty($password) || empty($confirmPassword)) {
    $_SESSION['message'] = "All fields are required.";
    $_SESSION['success'] = false;
  } elseif ($password !== $confirmPassword) {
    $_SESSION['message'] = "Passwords do not match.";
    $_SESSION['success'] = false;
  } else {
    // Check if username is taken
    $stmt = mysqli_prepare($conn, "SELECT id FROM admins WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
      $_SESSION['message'] = "Username is already taken.";
      $_SESSION['success'] = false;
    } else {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $insert = mysqli_prepare($conn, "INSERT INTO admins (username, password) VALUES (?, ?)");
      mysqli_stmt_bind_param($insert, "ss", $username, $hashedPassword);

      if (mysqli_stmt_execute($insert)) {
        $_SESSION['message'] = "Registration successful!";
        $_SESSION['success'] = true;

        // Optionally clear inputs
        header("Location: admin_register.php");
        exit();
      } else {
        $_SESSION['message'] = "Error while registering.";
        $_SESSION['success'] = false;
      }
    }
  }

  // Store entered values temporarily
  $_SESSION['old'] = [
    'username' => $username,
  ];

  header("Location: admin_register.php");
  exit();
}

// Retrieve old values if available
if (isset($_SESSION['old'])) {
  $username = $_SESSION['old']['username'];
  unset($_SESSION['old']);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register Form</title>
  <link rel="stylesheet" href="../assets/style.css" />
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
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
  </style>
</head>

<body>
  <div class="form-container">
    <form action="" method="post">
      <h1>Admin Register Form</h1><br>

      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" />
      </div>

      <div class="form-group">
        <label for="confirm-password">Confirm Password</label>
        <input type="password" id="confirm-password" name="confirm-password" />
      </div>

      <div class="form-group">
        <button type="submit" class="submit-button">Submit</button>
      </div>
      <a href="admin_home.php" class="button-link">⬅ Back to Admin Page</a>
    </form>

    <?php if (!empty($message)): ?>
      <div id="formOutput" class="output" style="color: <?= $success ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
  </div>
</body>

</html>