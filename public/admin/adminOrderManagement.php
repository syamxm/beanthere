<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderID'], $_POST['newStatus'])) {
  csrf_verify();
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

$flash = $_SESSION['status_updated'] ?? '';
unset($_SESSION['status_updated']);

$pageTitle = 'Orders - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6">Order management</h1>

    <?php if ($flash): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <?php if (empty($groupedOrders)): ?>
      <p class="text-foam">No orders found.</p>
    <?php else: ?>
      <?php foreach ($groupedOrders as $username => $userOrders): ?>
        <h2 class="text-caramel font-semibold mt-8 mb-3"><i class="fa-solid fa-user mr-2"></i><?= htmlspecialchars($username) ?></h2>
        <div class="overflow-x-auto">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Item</th>
                <th>Delivery</th>
                <th>Status</th>
                <th>Last update</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($userOrders as $order): ?>
                <tr>
                  <td><?= (int)$order['orderID'] ?></td>
                  <td><?= htmlspecialchars($order['name']) ?></td>
                  <td><?= htmlspecialchars($order['delivery']) ?></td>
                  <td><?= htmlspecialchars($order['orderStatus']) ?></td>
                  <td class="whitespace-nowrap"><?= htmlspecialchars($order['lastStatusUpdate'] ?? '—') ?></td>
                  <td>
                    <form method="POST" class="flex items-center gap-2">
                      <?= csrf_field() ?>
                      <input type="hidden" name="orderID" value="<?= (int)$order['orderID'] ?>">
                      <select name="newStatus" class="bg-espresso border border-bean rounded-lg px-2 py-1.5 text-crema text-sm focus:outline-none focus:border-caramel">
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
                      <button type="submit" class="btn-caramel">Update</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>
</body>

</html>
