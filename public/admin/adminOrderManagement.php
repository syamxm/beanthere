<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';
require_once __DIR__ . '/../../src/order_actions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkoutID'], $_POST['newStatus'])) {
  csrf_verify();
  [$ok, $message] = apply_group_status($conn, (string)$_POST['checkoutID'], (string)$_POST['newStatus']);
  $_SESSION['status_updated'] = $message;
  header("Location: adminOrderManagement.php");
  exit();
}

$sql = "SELECT o.orderID, o.checkoutID, o.name, o.qty, o.total, o.delivery_fee, o.delivery,
               o.orderStatus, o.lastStatusUpdate, o.orderTime, u.username
        FROM orders o
        JOIN users u ON o.userID = u.userID
        ORDER BY o.orderTime DESC, o.checkoutID";
$result = $conn->query($sql);

$groups = [];
foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
  $key = $row['checkoutID'] ?? ('legacy-' . $row['orderID']);
  if (!isset($groups[$key])) {
    $groups[$key] = [
      'checkoutID' => $row['checkoutID'],
      'username' => $row['username'],
      'delivery' => $row['delivery'],
      'status' => $row['orderStatus'],
      'time' => $row['orderTime'],
      'lastUpdate' => $row['lastStatusUpdate'],
      'deliveryFee' => (float)$row['delivery_fee'],
      'items' => [],
      'itemsTotal' => 0.0,
    ];
  }
  $groups[$key]['items'][] = $row;
  $groups[$key]['itemsTotal'] += (float)$row['total'];
}
$conn->close();

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
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold">Order management</h1>
      <a href="order_board.php" class="btn-caramel"><i class="fa-solid fa-mug-hot mr-1"></i> Barista board</a>
    </div>

    <?php if ($flash): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
      <p class="text-foam">No orders found.</p>
    <?php else: ?>
      <div class="flex flex-col gap-4">
        <?php foreach ($groups as $key => $group):
          $next = order_next_status($group['delivery'], $group['status']);
          $terminal = order_is_terminal($group['delivery'], $group['status']);
          $orderTotal = $group['itemsTotal'] + $group['deliveryFee'];
        ?>
          <div class="bg-roast border border-bean rounded-2xl p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
              <div>
                <span class="font-semibold"><i class="fa-solid fa-user mr-2 text-caramel"></i><?= htmlspecialchars($group['username']) ?></span>
                <span class="text-foam text-xs ml-2">#<?= htmlspecialchars($group['checkoutID'] ?? '—') ?></span>
                <span class="text-foam text-xs ml-2"><?= htmlspecialchars(date('j M, g:ia', strtotime($group['time']))) ?></span>
              </div>
              <span class="text-sm font-semibold px-3 py-1 rounded-full bg-caramel/20 text-caramel"><?= htmlspecialchars($group['status']) ?></span>
            </div>

            <table class="w-full text-sm mb-3">
              <tbody>
                <?php foreach ($group['items'] as $item): ?>
                  <tr class="border-b border-bean/40">
                    <td class="py-1.5"><?= htmlspecialchars($item['name']) ?> <span class="text-foam">×<?= (int)$item['qty'] ?></span></td>
                    <td class="py-1.5 text-right text-foam">RM<?= number_format($item['total'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if ($group['deliveryFee'] > 0): ?>
                  <tr class="border-b border-bean/40">
                    <td class="py-1.5 text-foam">Delivery fee</td>
                    <td class="py-1.5 text-right text-foam">RM<?= number_format($group['deliveryFee'], 2) ?></td>
                  </tr>
                <?php endif; ?>
                <tr>
                  <td class="py-1.5 font-semibold"><?= htmlspecialchars($group['delivery']) ?> total</td>
                  <td class="py-1.5 text-right font-semibold text-caramel">RM<?= number_format($orderTotal, 2) ?></td>
                </tr>
              </tbody>
            </table>

            <?php if (!$terminal): ?>
              <div class="flex flex-wrap gap-2">
                <?php if ($next !== null): ?>
                  <form method="POST" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="checkoutID" value="<?= htmlspecialchars($group['checkoutID']) ?>">
                    <input type="hidden" name="newStatus" value="<?= htmlspecialchars($next) ?>">
                    <button type="submit" class="btn-caramel">Mark <?= htmlspecialchars($next) ?></button>
                  </form>
                <?php endif; ?>
                <form method="POST" class="inline" onsubmit="return confirm('Cancel this whole order? Stock is restored and points are reversed; the voucher is not refunded.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="checkoutID" value="<?= htmlspecialchars($group['checkoutID']) ?>">
                  <input type="hidden" name="newStatus" value="<?= htmlspecialchars(ORDER_CANCELLED) ?>">
                  <button type="submit" class="btn-danger">Cancel</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
