<?php
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!isset($_SESSION['current_admin'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Not authorised']);
  exit;
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/order_status.php';

// Only live groups belong on the board — terminal ones drop off.
$liveStatuses = array_values(array_unique(array_merge(
  array_slice(PICKUP_FLOW, 0, -1),
  array_slice(DELIVERY_FLOW, 0, -1)
)));

$placeholders = implode(',', array_fill(0, count($liveStatuses), '?'));
// nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string -- $placeholders is only "?" marks; values bound below
$sql = "SELECT o.checkoutID, o.name, o.qty, o.drinkType, o.milkType, o.syrups, o.toppings, o.sugarLevel,
               o.delivery, o.orderStatus, o.orderTime, u.username
        FROM orders o
        JOIN users u ON o.userID = u.userID
        WHERE o.checkoutID IS NOT NULL AND o.orderStatus IN ($placeholders)
        ORDER BY o.orderTime ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($liveStatuses)), ...$liveStatuses);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$groups = [];
foreach ($rows as $row) {
  if (order_is_terminal($row['delivery'], $row['orderStatus'])) {
    continue;
  }
  $id = $row['checkoutID'];
  if (!isset($groups[$id])) {
    $groups[$id] = [
      'checkoutID' => $id,
      'username' => $row['username'],
      'delivery' => $row['delivery'],
      'status' => $row['orderStatus'],
      'next' => order_next_status($row['delivery'], $row['orderStatus']),
      'orderTime' => $row['orderTime'],
      'items' => [],
    ];
  }
  $options = array_filter([
    $row['drinkType'],
    $row['milkType'],
    $row['sugarLevel'] ? "Sugar {$row['sugarLevel']}" : null,
    $row['syrups'],
    $row['toppings'],
  ], 'strlen');
  $groups[$id]['items'][] = [
    'name' => $row['name'],
    'qty' => (int)$row['qty'],
    'options' => implode(' · ', $options),
  ];
}

$byStatus = [];
foreach ($liveStatuses as $status) {
  $byStatus[$status] = [];
}
foreach ($groups as $group) {
  $byStatus[$group['status']][] = $group;
}

echo json_encode(['columns' => $liveStatuses, 'groups' => $byStatus]);
