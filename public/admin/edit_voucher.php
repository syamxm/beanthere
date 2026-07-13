<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';

// Fetch existing voucher
if (!isset($_GET['voucherID'])) {
  die("Voucher ID missing.");
}

$voucherID = intval($_GET['voucherID']);
$stmt = $conn->prepare("SELECT * FROM vouchers WHERE voucherID = ?");
$stmt->bind_param("i", $voucherID);
$stmt->execute();
$result = $stmt->get_result();
$voucher = $result->fetch_assoc();
$stmt->close();

if (!$voucher) {
  die("Voucher not found.");
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = $_POST['code'];
  $discount = floatval($_POST['discount_value']);
  $valid_from = $_POST['valid_from'];
  $valid_until = $_POST['valid_until'];
  $status = $_POST['status'];

  $stmt = $conn->prepare("UPDATE vouchers SET code = ?, discount_value = ?, valid_from = ?, valid_until = ?, status = ? WHERE voucherID = ?");
  $stmt->bind_param("sdsssi", $code, $discount, $valid_from, $valid_until, $status, $voucherID);

  if ($stmt->execute()) {
    header("Location: manage_voucher.php");
    exit();
  } else {
    echo "<p style='color:red; text-align:center;'>Update failed.</p>";
  }

  $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Voucher</title>
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
      margin: 0 auto;
      background: #1e1e1e;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
    }

    h2 {
      text-align: center;
      color: #c49b63;
      margin-bottom: 20px;
    }

    form {
      display: flex;
      flex-direction: column;
    }

    label {
      margin-bottom: 5px;
      font-weight: bold;
      color: #ddd;
    }

    input,
    select {
      padding: 10px;
      margin-bottom: 20px;
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
    <a href="manage_voucher.php" class="back-link">⬅ Back to Voucher Management</a>
    <h1>Edit Voucher</h1>
  </header>

  <div class="container">
    <h2>Update Voucher</h2>
    <form method="POST">
      <label>Voucher Code:</label>
      <input type="text" name="code" value="<?= htmlspecialchars($voucher['code']) ?>" required>

      <label>Discount Value (%):</label>
      <input type="number" step="0.01" name="discount_value" value="<?= htmlspecialchars($voucher['discount_value']) ?>" required>

      <label>Valid From:</label>
      <input type="date" name="valid_from" value="<?= htmlspecialchars($voucher['valid_from']) ?>" required>

      <label>Valid Until:</label>
      <input type="date" name="valid_until" value="<?= htmlspecialchars($voucher['valid_until']) ?>" required>

      <label>Status:</label>
      <select name="status" required>
        <option value="active" <?= $voucher['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $voucher['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>

      <button type="submit">Update Voucher</button>
    </form>
  </div>
</body>

</html>