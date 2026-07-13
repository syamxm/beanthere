<?php
session_start();

require_once __DIR__ . '/../src/dbconn.php';

// Message display
$message = $_SESSION['message'] ?? "";
$success = $_SESSION['success'] ?? false;

// Clear session message after displaying it once
unset($_SESSION['message'], $_SESSION['success']);

// Form values
$username = "";
$password = "";

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  // Validation
  if (empty($username) || empty($password)) {
    $_SESSION['message'] = "All fields are required.";
    $_SESSION['success'] = false;
  } else {
    // Check if credentials are correct
    $stmt = mysqli_prepare($conn, "SELECT username, password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $current_user, $storedPassword);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $valid = false;
    $needsRehash = false;
    if ($found) {
      if (password_verify($password, $storedPassword)) {
        $valid = true;
        $needsRehash = password_needs_rehash($storedPassword, PASSWORD_DEFAULT);
      } elseif (hash_equals($storedPassword, $password)) {
        $valid = true;
        $needsRehash = true;
      }
    }

    if ($valid) {
      if ($needsRehash) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE username = ?");
        mysqli_stmt_bind_param($update, "ss", $newHash, $current_user);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
      }

      session_regenerate_id(true);
      $_SESSION['current_user'] = $current_user;

      $_SESSION['message'] = "Login successful. Redirecting...";
      $_SESSION['success'] = true;

      // Don't redirect here — let the front-end JS handle it
    } else {
      $_SESSION['message'] = "Invalid Credentials, Please Try Again";
      $_SESSION['success'] = false;
    }
  }

  // Store entered values temporarily
  $_SESSION['old'] = [
    'username' => $username,
  ];

  header("Location: user_login.php");
  exit();
}

// Retrieve old values if available
if (isset($_SESSION['old'])) {
  $username = $_SESSION['old']['username'];
  unset($_SESSION['old']);
}
?>
<?php if ($success): ?>
  <script>
    // Wait for 1 second then redirect
    setTimeout(function() {
      window.location.href = "user_dashboard.php";
    }, 1000);
  </script>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Form</title>
  <link rel="stylesheet" href="assets/style.css" />

  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
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

    /* Password field wrapper to position toggle icon */
    .password-wrapper {
      position: relative;
    }

    .password-wrapper input {
      padding-right: 55px;
      /* Space for the eye icon */
    }

    #togglePassword {
      position: absolute;
      top: 50%;
      right: 12px;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 18px;
    }
  </style>

</head>

<body>
  <a href="user_dashboard.php" class="go-back-btn">← Go To Main Menu</a>
  <div class="form-container">
    <form action="" method="post">
      <h1>Login Form</h1><br>

      <div class="form-group">
        <label for="username">username</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>" />
      </div>

      <div class="form-group">
        <label for="password">password</label>
        <div class="password-wrapper">
          <input type="password" id="password" name="password" />
          <span id="togglePassword">👁️</span>
        </div>
      </div>



      <div class="form-group">
        <button type="submit" value="login" class="submit-button">Submit</button>
      </div>

    </form>
    <?php if (!empty($message)): ?>
      <div id="formOutput" class="output" style="color: <?= $success ? 'green' : 'red' ?>;">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

  </div>
</body>

<script>
  const toggle = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');

  toggle.addEventListener('click', function() {
    const type = passwordInput.type === 'password' ? 'text' : 'password';
    passwordInput.type = type;

    // Toggle icon
    toggle.textContent = type === 'password' ? '👁️' : '🔒';
  });
</script>


</html>