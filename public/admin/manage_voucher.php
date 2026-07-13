<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

$result = $conn->query("SELECT * FROM vouchers ORDER BY valid_from DESC");
$vouchers = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

$flash = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$pageTitle = 'Vouchers - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold">Vouchers</h1>
      <a href="add_voucher.php" class="btn-caramel"><i class="fa-solid fa-plus mr-1"></i> Add voucher</a>
    </div>

    <?php if ($flash): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <?php if (count($vouchers) === 0): ?>
      <p class="text-foam">No vouchers found.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Code</th>
              <th>Discount (%)</th>
              <th>Created by</th>
              <th>Valid from</th>
              <th>Valid until</th>
              <th>Status</th>
              <th colspan="2">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($vouchers as $v): ?>
              <tr>
                <td><?= (int)$v['voucherID'] ?></td>
                <td><?= htmlspecialchars($v['code']) ?></td>
                <td><?= number_format($v['discount_value'], 2) ?></td>
                <td><?= htmlspecialchars($v['created_by']) ?></td>
                <td><?= htmlspecialchars($v['valid_from']) ?></td>
                <td><?= htmlspecialchars($v['valid_until']) ?></td>
                <td><?= htmlspecialchars(ucfirst($v['status'])) ?></td>
                <td><a class="btn-outline" href="edit_voucher.php?voucherID=<?= (int)$v['voucherID'] ?>">Edit</a></td>
                <td>
                  <form method="POST" action="delete_voucher.php" onsubmit="return confirm('Are you sure you want to delete this voucher?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="voucherID" value="<?= (int)$v['voucherID'] ?>">
                    <button type="submit" class="btn-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
