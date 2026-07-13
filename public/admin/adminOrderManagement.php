<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderID'], $_POST['newStatus'])) {
  $orderID = intval($_POST['orderID']);
  $newStatus = $_POST['newStatus'];

  $stmt = $conn->prepare("UPDATE orders SET orderStatus = ?, lastStatusUpdate = NOW() WHERE orderID = ?");
  $stmt->bind_param("si", $newStatus, $orderID);
  $stmt->execute();
  $stmt->close();

  $_SESSION['status_updated'] = "Order #$orderID status updated to '$newStatus'.";
  header("Location: adminOrderManagement.php");
  exit();
}

// Get orders grouped by user
$sql = "SELECT o.orderID, o.name, o.delivery, o.orderStatus, o.lastStatusUpdate, o.orderTime, u.username
        FROM orders o
        JOIN users u ON o.userID = u.userID
        ORDER BY u.username ASC, o.orderTime DESC";
$result = $conn->query($sql);
$orders = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

// Group by username
$groupedOrders = [];
foreach ($orders as $order) {
  $groupedOrders[$order['username']][] = $order;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Order Management</title>
  <link rel="stylesheet" href="../assets/style_scrollbar.css" />
  <style>
    body {
      margin: 0;
      background-color: #121212;
      color: #ffffff;
      font-family: Arial, sans-serif;
      padding: 120px 20px 20px 20px;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background-color: #1f1f1f;
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
      z-index: 999;
    }

    .back-link {
      position: absolute;
      top: 20px;
      left: 20px;
      color: #c49b63;
      font-weight: bold;
      text-decoration: none;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    h1 {
      margin: 0;
      color: #c49b63;
    }

    h2.customer-header {
      color: #c49b63;
      border-bottom: 2px solid #c49b63;
      margin-top: 40px;
    }

    .message {
      text-align: center;
      color: #7fff7f;
      margin-bottom: 15px;
      font-weight: bold;
    }

    .order-table {
      width: 100%;
      border-collapse: collapse;
      background-color: #1e1e1e;
      margin-top: 10px;
    }

    .order-table th,
    .order-table td {
      border: 1px solid #444;
      padding: 12px;
      text-align: center;
    }

    .order-table th {
      background-color: #333;
      color: #c49b63;
    }

    form {
      display: flex;
      justify-content: center;
      gap: 10px;
    }

    select,
    button {
      padding: 6px 12px;
      border: none;
      border-radius: 5px;
    }

    select {
      background-color: #2a2a2a;
      color: #fff;
    }

    button {
      background-color: #c49b63;
      font-weight: bold;
      color: black;
      cursor: pointer;
    }

    button:hover {
      background-color: #fff;
      color: #000;
    }

    .no-data {
      text-align: center;
      color: #aaa;
      margin-top: 40px;
    }
  </style>
</head>

<body>

  <header>
    <a href="admin_home.php" class="back-link">⬅ Back to Admin Panel</a>
    <h1>Order Management</h1>
  </header>

  <?php if (isset($_SESSION['status_updated'])): ?>
    <p class="message"><?= $_SESSION['status_updated'] ?></p>
    <?php unset($_SESSION['status_updated']); ?>
  <?php endif; ?>

  <?php if (empty($groupedOrders)): ?>
    <p class="no-data">No orders found.</p>
  <?php else: ?>
    <?php foreach ($groupedOrders as $username => $userOrders): ?>
      <h2 class="customer-header">Customer: <?= htmlspecialchars($username) ?></h2>
      <table class="order-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Item</th>
            <th>Delivery</th>
            <th>Status</th>
            <th>Last Update</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($userOrders as $order): ?>
            <tr>
              <td><?= $order['orderID'] ?></td>
              <td><?= htmlspecialchars($order['name']) ?></td>
              <td><?= htmlspecialchars($order['delivery']) ?></td>
              <td><?= htmlspecialchars($order['orderStatus']) ?></td>
              <td><?= $order['lastStatusUpdate'] ?></td>
              <td>
                <form method="POST">
                  <input type="hidden" name="orderID" value="<?= $order['orderID'] ?>">
                  <select name="newStatus">
                    <?php
                    $isPickup = strtolower($order['delivery']) === 'pickup';
                    $pickupStatuses = ['Order Received', 'Preparing', 'Ready for Pickup', 'Done Pickup'];
                    $deliveryStatuses = ['Order Received', 'Preparing', 'Out for Delivery', 'Delivered'];
                    $availableStatuses = $isPickup ? $pickupStatuses : $deliveryStatuses;

                    foreach ($availableStatuses as $status) {
                      $selected = ($order['orderStatus'] === $status) ? 'selected' : '';
                      echo "<option value=\"$status\" $selected>$status</option>";
                    }
                    ?>
                  </select>
                  <button type="submit">Update</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endforeach; ?>
  <?php endif; ?>

</body>

</html>