<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  die("Unauthorized. Please log in.");
}

require_once __DIR__ . '/../src/dbconn.php';

$username = $_SESSION['current_user'];
$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

$orders = [];
$sql = "SELECT * FROM orders WHERE userID = ? ORDER BY orderTime DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $orders[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Your Orders - Bean There</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #fdfaf6;
      font-family: 'Poppins', sans-serif;
    }

    .container {
      max-width: 800px;
      margin: 50px auto;
      background: white;
      padding: 2rem;
      border-radius: 1rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .order-card {
      border: 1px solid #ddd;
      border-radius: 0.75rem;
      padding: 1rem;
      margin-bottom: 1rem;
      background-color: #fff7f0;
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

    .empty {
      text-align: center;
      color: #999;
      margin-top: 2rem;
    }
  </style>
</head>

<body>
  <div class="container">
    <a href="user_dashboard.php" class="fixed top-5 left-5 z-50 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg shadow-md hover:bg-gray-100 transition duration-300">
      ← Go To Main Menu
    </a>
    <h2 class="text-2xl font-bold text-center mb-6">Order Tracking</h2>

    <?php if (empty($orders)): ?>
      <p class="empty">No current orders found.</p>
    <?php else: ?>
      <?php foreach ($orders as $order):
        $statusRaw = strtolower($order['orderStatus']);
        $statusClass = "processing";
        $statusText = "Processing";

        if (in_array($statusRaw, ['out for delivery', 'ready for pickup'])) {
          $statusClass = "in-transit";
          $statusText = ucfirst($order['orderStatus']);
        } elseif (in_array($statusRaw, ['delivered', 'done pickup'])) {
          $statusClass = "complete";
          $statusText = ucfirst($order['orderStatus']);
        } elseif (in_array($statusRaw, ['order received', 'preparing'])) {
          $statusClass = "processing";
          $statusText = ucfirst($order['orderStatus']);
        }
      ?>
        <div class="order-card">
          <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($order['name']) ?></h3>
          <p>Quantity: <?= $order['qty'] ?></p>
          <p>Total: RM <?= number_format($order['total'], 2) ?></p>
          <p>Delivery: <?= htmlspecialchars($order['delivery']) ?></p>
          <p>Ordered At: <?= date("Y-m-d H:i:s", strtotime($order['orderTime'])) ?></p>
          <p class="mt-2">Status: <span class="status <?= $statusClass ?>"><?= $statusText ?></span></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>

</html>