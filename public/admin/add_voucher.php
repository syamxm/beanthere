<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
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
      $_SESSION['flash_error'] = "Could not add voucher. Please try again.";
    }
  }

  header("Location: add_voucher.php");
  exit();
}

$conn->close();

$pageTitle = 'Add voucher - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6 text-center">Add voucher</h1>

    <?php if ($flash_success): ?>
      <p class="text-green-400 text-center mb-4"><?= htmlspecialchars($flash_success) ?></p>
    <?php elseif ($flash_error): ?>
      <p class="text-red-400 text-center mb-4"><?= htmlspecialchars($flash_error) ?></p>
    <?php endif; ?>

    <form method="POST" class="admin-form">
      <?= csrf_field() ?>
      <label for="code">Voucher code</label>
      <input type="text" name="code" id="code" required>

      <label for="discount_value">Discount value (%)</label>
      <input type="number" name="discount_value" id="discount_value" step="0.01" required>

      <label for="valid_from">Valid from</label>
      <input type="date" name="valid_from" id="valid_from" required>

      <label for="valid_until">Valid until</label>
      <input type="date" name="valid_until" id="valid_until" required>

      <label for="status">Status</label>
      <select name="status" id="status" required>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <button type="submit" class="btn-caramel w-full mt-5">Save voucher</button>
    </form>
  </main>
</body>

</html>
