<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

include "dbconn.php";

// Flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable exceptions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = trim($_POST['code']);
  $discount = floatval($_POST['discount_value']);
  $valid_from = $_POST['valid_from'];
  $valid_until = $_POST['valid_until'];
  $status = $_POST['status'];

  $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
  $stmt->bind_param("s", $_SESSION['current_admin']);
  $stmt->execute();
  $stmt->bind_result($adminID);
  $stmt->fetch();
  $stmt->close();

  try {
    $stmt = $conn->prepare("INSERT INTO vouchers (code, discount_value, created_by, valid_from, valid_until, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdssss", $code, $discount, $adminID, $valid_from, $valid_until, $status);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash_success'] = "Voucher '$code' added successfully.";
  } catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) {
      $_SESSION['flash_error'] = "Voucher code '$code' already exists.";
    } else {
      $_SESSION['flash_error'] = "Error occurred: " . $e->getMessage();
    }
  }

  header("Location: add_voucher.php");
  exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Add Voucher</title>
  <style>
    body {
      background-color: #121212;
      color: #ffffff;
      font-family: Arial, sans-serif;
      padding: 120px 20px 20px 20px;
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

    .container {
      max-width: 600px;
      margin: 30px auto;
      background: #1e1e1e;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
    }

    .container h2 {
      color: #c49b63;
      text-align: center;
      margin-bottom: 20px;
    }

    .messages {
      text-align: center;
      margin-bottom: 20px;
    }

    .messages .success {
      color: #7fff7f;
    }

    .messages .error {
      color: #ff5555;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      font-weight: bold;
      margin-bottom: 6px;
    }

    input,
    select {
      padding: 10px;
      margin-bottom: 18px;
      border-radius: 5px;
      border: none;
      background-color: #2a2a2a;
      color: white;
    }

    button {
      padding: 12px;
      background-color: #c49b63;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
      color: black;
      transition: 0.3s;
    }

    button:hover {
      background-color: #fff;
      color: #000;
    }
  </style>
</head>

<body>
  <header>
    <a href="admin%20page.php" class="back-link">⬅ Back to Admin Page</a>
    <h1>Add Voucher</h1>
  </header>

  <div class="container">
    <h2>Create New Voucher</h2>

    <div class="messages">
      <?php if (!empty($flash_success)): ?>
        <p class="success"><?= htmlspecialchars($flash_success) ?></p>
      <?php elseif (!empty($flash_error)): ?>
        <p class="error"><?= htmlspecialchars($flash_error) ?></p>
      <?php endif; ?>
    </div>

    <form method="POST">
      <label for="code">Voucher Code:</label>
      <input type="text" name="code" id="code" required>

      <label for="discount_value">Discount Value (%):</label>
      <input type="number" name="discount_value" id="discount_value" step="0.01" required>

      <label for="valid_from">Valid From:</label>
      <input type="date" name="valid_from" id="valid_from" required>

      <label for="valid_until">Valid Until:</label>
      <input type="date" name="valid_until" id="valid_until" required>

      <label for="status">Status:</label>
      <select name="status" id="status" required>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <button type="submit">Save Voucher</button>
    </form>
  </div>
</body>

</html>