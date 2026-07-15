<?php
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!isset($_SESSION['current_user'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Not authorised']);
  exit;
}

require_once __DIR__ . '/../src/dbconn.php';

$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['current_user']);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

if (!$userID) {
  http_response_code(403);
  echo json_encode(['error' => 'Not authorised']);
  exit;
}

$stmt = $conn->prepare("SELECT checkoutID, MIN(orderStatus) AS status
                        FROM orders
                        WHERE userID = ? AND checkoutID IS NOT NULL
                        GROUP BY checkoutID");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$statuses = [];
while ($row = $result->fetch_assoc()) {
  $statuses[$row['checkoutID']] = $row['status'];
}
$stmt->close();

echo json_encode(['statuses' => $statuses]);
