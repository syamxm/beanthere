<?php
if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit;
}
require_once __DIR__ . '/../src/dbconn.php';
require_once __DIR__ . '/../src/order_status.php';
require_once __DIR__ . '/../src/payment.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

// Release checkouts abandoned at the payment gateway (restock + free voucher).
payment_expire_stale($conn);

// Demo mode: the cron only nudges orders the barista has left alone
// (statusSource = 'auto'). Once an admin touches a group it becomes 'manual'
// and the cron stops fighting them.
$sql = "SELECT orderID, orderStatus, delivery, lastStatusUpdate, orderTime
        FROM orders
        WHERE statusSource = 'auto'
          AND orderStatus NOT IN ('Delivered', 'Done Pickup', 'Cancelled')";
$result = $conn->query($sql);

$minutesForStep = ['Order Received' => 1, 'Preparing' => 2, 'Ready for Pickup' => 2, 'Out for Delivery' => 2];

while ($row = $result->fetch_assoc()) {
  $orderID = $row['orderID'];
  $currentStatus = $row['orderStatus'];
  $delivery = $row['delivery'] ?? '';

  $lastUpdate = strtotime($row['lastStatusUpdate'] ?? '');
  if ($lastUpdate === false) {
    $lastUpdate = strtotime($row['orderTime'] ?? '');
  }
  if ($lastUpdate === false) {
    continue;
  }

  $threshold = $minutesForStep[$currentStatus] ?? null;
  if ($threshold === null || (time() - $lastUpdate) / 60 < $threshold) {
    continue;
  }

  $newStatus = order_next_status($delivery, $currentStatus);
  if ($newStatus === null) {
    continue;
  }

  $update = $conn->prepare("UPDATE orders SET orderStatus = ?, lastStatusUpdate = NOW() WHERE orderID = ?");
  $update->bind_param("si", $newStatus, $orderID);
  $update->execute();
  $update->close();

  file_put_contents(__DIR__ . "/../logs/status_log.txt", "[" . date("Y-m-d H:i:s") . "] Order #$orderID: $currentStatus ➜ $newStatus\n", FILE_APPEND);
}

$conn->close();
