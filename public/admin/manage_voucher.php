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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Vouchers</title>
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
      max-width: 1000px;
      margin: 0 auto;
      padding: 20px;
    }

    h2 {
      text-align: center;
      color: #c49b63;
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th,
    td {
      border: 1px solid #333;
      padding: 12px;
      text-align: center;
    }

    th {
      background-color: #1f1f1f;
      color: #c49b63;
    }

    tr:nth-child(even) {
      background-color: #1a1a1a;
    }

    tr:nth-child(odd) {
      background-color: #222;
    }

    .no-data {
      text-align: center;
      color: #aaa;
      margin-top: 40px;
      font-size: 18px;
    }

    a.button {
      display: inline-block;
      padding: 6px 12px;
      margin: 2px;
      background-color: #c49b63;
      color: #000;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
    }

    a.button:hover {
      background-color: #fff;
    }
  </style>
</head>

<body>
  <header>
    <a href="admin_home.php" class="back-link">⬅ Back to Admin Page</a>
    <h1>Manage Vouchers</h1>
  </header>

  <div class="container">
    <h2>All Vouchers</h2>

    <?php if (count($vouchers) === 0): ?>
      <p class="no-data">No vouchers found.</p>
    <?php else: ?>
      <?php
      if (isset($_SESSION['message'])) {
        echo "<p style='text-align:center; font-weight:bold; color:#f8b400;'>" . $_SESSION['message'] . "</p>";
        unset($_SESSION['message']);
      }
      ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Code</th>
            <th>Discount (%)</th>
            <th>Created By</th>
            <th>Valid From</th>
            <th>Valid Until</th>
            <th>Status</th>
            <th>EDIT</th>
            <th>DELETE</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vouchers as $v): ?>
            <tr>
              <td><?= $v['voucherID'] ?></td>
              <td><?= htmlspecialchars($v['code']) ?></td>
              <td><?= number_format($v['discount_value'], 2) ?></td>
              <td><?= htmlspecialchars($v['created_by']) ?></td>
              <td><?= $v['valid_from'] ?></td>
              <td><?= $v['valid_until'] ?></td>
              <td><?= htmlspecialchars(ucfirst($v['status'])) ?></td>
              <td>
                <a class="button" href="edit_voucher.php?voucherID=<?= $v['voucherID'] ?>">Edit</a>

              </td>
              <td>
                <form method="POST" action="delete_voucher.php" onsubmit="return confirm('Are you sure you want to delete this voucher?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="voucherID" value="<?= $v['voucherID'] ?>">
                  <button type="submit" class="button">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</body>

</html>