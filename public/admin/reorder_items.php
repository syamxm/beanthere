<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  http_response_code(403);
  exit;
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

csrf_verify();

$order = $_POST['order'] ?? [];
if (!is_array($order) || empty($order)) {
  http_response_code(400);
  exit;
}

$conn->begin_transaction();
$stmt = $conn->prepare("UPDATE menu_items SET sort_order = ? WHERE id = ?");
foreach (array_values($order) as $position => $id) {
  $sortOrder = $position + 1;
  $itemID = (int)$id;
  $stmt->bind_param("ii", $sortOrder, $itemID);
  $stmt->execute();
}
$stmt->close();
$conn->commit();
$conn->close();

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
