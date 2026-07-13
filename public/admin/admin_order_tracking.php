<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';

$sql = "SELECT o.*, u.username FROM orders o JOIN users u ON o.userID = u.userID ORDER BY o.orderTime DESC";
$result = $conn->query($sql);

$ordersByUser = [];
while ($row = $result->fetch_assoc()) {
  $ordersByUser[$row['username']][] = $row;
}
$conn->close();

$pageTitle = 'All orders - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-8">All customer orders</h1>

    <?php if (empty($ordersByUser)): ?>
      <p class="text-foam">No orders from any users.</p>
    <?php else: ?>
      <?php foreach ($ordersByUser as $username => $orders): ?>
        <h2 class="text-caramel font-semibold mt-8 mb-3"><i class="fa-solid fa-user mr-2"></i><?= htmlspecialchars($username) ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <?php foreach ($orders as $order):
            $statusRaw = strtolower($order['orderStatus']);
            if (in_array($statusRaw, ['out for delivery', 'ready for pickup'])) {
              $badge = 'bg-blue-400/20 text-blue-300';
            } elseif (in_array($statusRaw, ['delivered', 'done pickup'])) {
              $badge = 'bg-green-400/20 text-green-300';
            } else {
              $badge = 'bg-caramel/20 text-caramel';
            }
          ?>
            <div class="bg-roast border border-bean rounded-2xl p-5">
              <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <h3 class="font-semibold"><?= htmlspecialchars($order['name']) ?></h3>
                <span class="text-xs font-semibold px-3 py-1 rounded-full <?= $badge ?>"><?= htmlspecialchars(ucwords($order['orderStatus'])) ?></span>
              </div>
              <p class="text-foam text-sm">
                Qty <?= (int)$order['qty'] ?> · RM<?= number_format($order['total'], 2) ?> ·
                <?= htmlspecialchars($order['delivery']) ?><br>
                Ordered <?= htmlspecialchars(date("j M Y, g:ia", strtotime($order['orderTime']))) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>
</body>

</html>
