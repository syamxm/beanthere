<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: adminLogin.php');
  exit();
}

include "dbconn.php";

// Fetch all orders and usernames
$sql = "SELECT o.*, u.username FROM orders o JOIN users u ON o.userID = u.userID ORDER BY o.orderTime DESC";
$result = $conn->query($sql);

$ordersByUser = [];
while ($row = $result->fetch_assoc()) {
  $ordersByUser[$row['username']][] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin - All Orders | Bean There</title>
  <link rel="stylesheet" href="style_scrollbar.css" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fdfaf6;
    }

    .container {
      max-width: 1000px;
      margin: 2rem auto;
      background: white;
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .user-section {
      margin-bottom: 2.5rem;
    }

    .user-title {
      font-size: 1.5rem;
      font-weight: bold;
      margin-bottom: 1rem;
      border-bottom: 2px solid #ddd;
      padding-bottom: 0.5rem;
    }

    .order-card {
      background-color: #fff7f0;
      border: 1px solid #ddd;
      border-radius: 0.75rem;
      padding: 1rem;
      margin-bottom: 1rem;
    }

    .status {
      font-weight: bold;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      display: inline-block;
      font-size: 0.95rem;
    }

    .status.processing {
      background-color: #fff3cd;
      color: #856404;
    }

    .status.in-transit {
      background-color: #bee3f8;
      color: #1e40af;
    }

    .status.complete {
      background-color: #d1fae5;
      color: #065f46;
    }

    .empty-msg {
      color: #999;
      text-align: center;
      margin-top: 2rem;
    }
  </style>
</head>

<body>
  <div class="container">
    <a href="admin page.php" class="fixed top-5 left-5 z-50 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg shadow-md hover:bg-gray-100 transition duration-300">
      ← Go Back
    </a>
    <h2 class="text-3xl font-bold text-center mb-8">All Customer Orders</h2>

    <?php if (empty($ordersByUser)): ?>
      <p class="empty-msg">No orders from any users.</p>
    <?php else: ?>
      <?php foreach ($ordersByUser as $username => $orders): ?>
        <div class="user-section">
          <div class="user-title">User: <?= htmlspecialchars($username) ?></div>

          <?php foreach ($orders as $order):
            $statusRaw = strtolower($order['orderStatus']);
            $statusClass = 'processing';
            $statusText = ucfirst($order['orderStatus']);

            if (in_array($statusRaw, ['out for delivery', 'ready for pickup'])) {
              $statusClass = 'in-transit';
            } elseif (in_array($statusRaw, ['delivered', 'done pickup'])) {
              $statusClass = 'complete';
            } elseif (in_array($statusRaw, ['order received', 'preparing'])) {
              $statusClass = 'processing';
            }
          ?>
            <div class="order-card">
              <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($order['name']) ?></h3>
              <p>Quantity: <?= $order['qty'] ?></p>
              <p>Total: RM <?= number_format($order['total'], 2) ?></p>
              <p>Delivery Method: <?= htmlspecialchars($order['delivery']) ?></p>
              <p>Ordered At: <?= date("Y-m-d H:i:s", strtotime($order['orderTime'])) ?></p>
              <p class="mt-2">Status: <span class="status <?= $statusClass ?>"><?= $statusText ?></span></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>

</html>