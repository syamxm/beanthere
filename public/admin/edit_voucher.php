<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

if (!isset($_GET['voucherID'])) {
  header("Location: manage_voucher.php");
  exit();
}

$voucherID = intval($_GET['voucherID']);
$stmt = $conn->prepare("SELECT * FROM vouchers WHERE voucherID = ?");
$stmt->bind_param("i", $voucherID);
$stmt->execute();
$result = $stmt->get_result();
$voucher = $result->fetch_assoc();
$stmt->close();

if (!$voucher) {
  $_SESSION['message'] = "Voucher not found.";
  header("Location: manage_voucher.php");
  exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $code = $_POST['code'];
  $discount = max(0, min(100, floatval($_POST['discount_value'])));
  $valid_from = $_POST['valid_from'];
  $valid_until = $_POST['valid_until'];
  $status = $_POST['status'];

  $stmt = $conn->prepare("UPDATE vouchers SET code = ?, discount_value = ?, valid_from = ?, valid_until = ?, status = ? WHERE voucherID = ?");
  $stmt->bind_param("sdsssi", $code, $discount, $valid_from, $valid_until, $status, $voucherID);

  if ($stmt->execute()) {
    header("Location: manage_voucher.php");
    exit();
  }
  $error = "Update failed. Please try again.";
  $stmt->close();
}

$conn->close();

$pageTitle = 'Edit voucher - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6 text-center">Edit voucher</h1>

    <?php if ($error): ?>
      <p class="text-red-400 text-center mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" class="admin-form">
      <?= csrf_field() ?>
      <label for="code">Voucher code</label>
      <input type="text" name="code" id="code" value="<?= htmlspecialchars($voucher['code']) ?>" required>

      <label for="discount_value">Discount value (%)</label>
      <input type="number" step="0.01" name="discount_value" id="discount_value" value="<?= htmlspecialchars($voucher['discount_value']) ?>" required>

      <label for="valid_from">Valid from</label>
      <input type="date" name="valid_from" id="valid_from" value="<?= htmlspecialchars($voucher['valid_from']) ?>" required>

      <label for="valid_until">Valid until</label>
      <input type="date" name="valid_until" id="valid_until" value="<?= htmlspecialchars($voucher['valid_until']) ?>" required>

      <label for="status">Status</label>
      <select name="status" id="status" required>
        <option value="active" <?= $voucher['status'] === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $voucher['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>

      <button type="submit" class="btn-caramel w-full mt-5">Update voucher</button>
      <a href="manage_voucher.php" class="block text-center text-foam hover:text-caramel text-sm mt-4">Back to vouchers</a>
    </form>
  </main>
</body>

</html>
