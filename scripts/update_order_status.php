<?php
if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit;
}
require_once __DIR__ . '/../src/dbconn.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

$sql = "SELECT orderID, orderStatus, delivery, lastStatusUpdate 
        FROM orders 
        WHERE orderStatus NOT IN ('Delivered', 'Done Pickup')";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
  $orderID = $row['orderID'];
  $currentStatus = $row['orderStatus'];
  $deliveryType = strtolower($row['delivery'] ?? '');
  $lastUpdate = strtotime($row['lastStatusUpdate'] ?? '');
  if ($lastUpdate === false) {
    continue;
  }
  $now = time();
  $minutesElapsed = ($now - $lastUpdate) / 60;

  $newStatus = $currentStatus;

  if ($currentStatus === 'Order Received' && $minutesElapsed >= 1) {
    $newStatus = 'Preparing';
  } elseif ($currentStatus === 'Preparing' && $minutesElapsed >= 2) {
    $newStatus = ($deliveryType === 'delivery') ? 'Out for Delivery' : 'Ready for Pickup';
  } elseif ($currentStatus === 'Out for Delivery' && $minutesElapsed >= 2) {
    $newStatus = 'Delivered';
  } elseif ($currentStatus === 'Ready for Pickup' && $minutesElapsed >= 2) {
    $newStatus = 'Done Pickup';
  }

  if ($newStatus !== $currentStatus) {
    $update = $conn->prepare("UPDATE orders SET orderStatus = ?, lastStatusUpdate = NOW() WHERE orderID = ?");
    $update->bind_param("si", $newStatus, $orderID);
    $update->execute();
    $update->close();

    file_put_contents(__DIR__ . "/../logs/status_log.txt", "[" . date("Y-m-d H:i:s") . "] Order #$orderID: $currentStatus ➜ $newStatus\n", FILE_APPEND);
  }
}

$conn->close();
