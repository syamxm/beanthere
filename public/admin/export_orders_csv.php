<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/report_range.php';

[$fromDate, $toDate] = report_range_from_get();
$excluded = REPORT_EXCLUDED_STATUSES;
$exPlaceholders = implode(',', array_fill(0, count($excluded), '?'));

// nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string -- $exPlaceholders is only "?" marks; values bound below
$sql = "SELECT o.orderTime, o.checkoutID, u.username, o.name, o.qty, o.drinkType, o.milkType, o.sugarLevel,
               o.syrups, o.toppings, o.delivery, o.orderStatus, o.total, o.delivery_fee, o.payment_method
        FROM orders o
        JOIN users u ON o.userID = u.userID
        WHERE o.orderTime BETWEEN ? AND ? AND o.orderStatus NOT IN ($exPlaceholders)
        ORDER BY o.orderTime, o.checkoutID";
$stmt = $conn->prepare($sql);
$params = array_merge([$fromDate, $toDate . ' 23:59:59'], $excluded);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"beanthere-orders-{$fromDate}-to-{$toDate}.csv\"");

$out = fopen('php://output', 'w');
fputcsv($out, [
  'Order time', 'Group ref', 'Customer', 'Item', 'Qty', 'Drink type', 'Milk', 'Sugar',
  'Syrups', 'Toppings', 'Fulfilment', 'Status', 'Line total (RM)', 'Delivery fee (RM)', 'Payment method',
]);
while ($row = $result->fetch_assoc()) {
  fputcsv($out, [
    $row['orderTime'],
    $row['checkoutID'] ?? 'legacy',
    $row['username'],
    $row['name'],
    $row['qty'],
    $row['drinkType'],
    $row['milkType'],
    $row['sugarLevel'],
    $row['syrups'],
    $row['toppings'],
    $row['delivery'],
    $row['orderStatus'],
    $row['total'],
    $row['delivery_fee'],
    $row['payment_method'],
  ]);
}
fclose($out);
$stmt->close();
