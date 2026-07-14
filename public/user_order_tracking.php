<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php?return=user_order_tracking.php');
  exit();
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

$pageTitle = 'Your orders - Bean There';
?>
<!DOCTYPE html>
<?php include __DIR__ . '/../src/partials/html_open.php'; ?>

<head>
  <?php include __DIR__ . '/../src/partials/head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen flex flex-col">
  <?php include __DIR__ . '/../src/partials/nav.php'; ?>

  <main class="grow max-w-3xl mx-auto w-full px-4 py-10">
    <h1 class="text-3xl font-bold mb-2">Your orders</h1>
    <p class="text-foam text-sm mb-8">Status updates automatically as we work on your order.</p>

    <?php if (empty($orders)): ?>
      <div class="bg-roast border border-bean rounded-2xl p-10 text-center">
        <i class="fa-solid fa-receipt text-caramel text-4xl mb-4"></i>
        <p class="text-lg font-semibold mb-2">No orders yet</p>
        <p class="text-foam text-sm mb-6">When you place an order, you can track it here.</p>
        <a href="user_dashboard.php" class="bg-caramel text-espresso font-semibold px-6 py-2.5 rounded-full hover:bg-crema transition">Browse the menu</a>
      </div>
    <?php else: ?>
      <div class="flex flex-col gap-4">
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
            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
              <h2 class="font-semibold text-lg"><?= htmlspecialchars($order['name']) ?></h2>
              <span class="text-sm font-semibold px-3 py-1 rounded-full <?= $badge ?>"><?= htmlspecialchars(ucwords($order['orderStatus'])) ?></span>
            </div>
            <p class="text-foam text-sm">
              Qty <?= (int)$order['qty'] ?> · RM<?= number_format($order['total'], 2) ?> ·
              <?= htmlspecialchars($order['delivery']) ?> ·
              ordered <?= htmlspecialchars(date("j M Y, g:ia", strtotime($order['orderTime']))) ?>
            </p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/../src/partials/footer.php'; ?>
</body>

</html>
