<?php
if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit;
}
require_once __DIR__ . '/../src/dbconn.php';

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Kuala_Lumpur');

// Prepare log file
$logPath = __DIR__ . "/../logs/voucher_log.txt";
$timestamp = date("Y-m-d H:i:s");
file_put_contents($logPath, "[$timestamp] Starting voucher assignment...\n", FILE_APPEND);

// Step 1: Get all valid monthly vouchers (reward vouchers are earned with points only)
$today = date('Y-m-d');
$sql = "SELECT voucherID, code FROM vouchers
        WHERE status = 'active' AND type = 'monthly'
          AND valid_from <= ? AND valid_until >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $today, $today);
$stmt->execute();
$result = $stmt->get_result();

$validVouchers = [];
while ($row = $result->fetch_assoc()) {
  $validVouchers[] = $row;
}
$stmt->close();

// Step 2: Get all active members
$sql = "SELECT membershipID FROM membership WHERE status = 'active'";
$membersResult = $conn->query($sql);
$members = [];
while ($row = $membersResult->fetch_assoc()) {
  $members[] = $row['membershipID'];
}

// Step 3: Assign vouchers
$assignCount = 0;

foreach ($members as $membershipID) {
  foreach ($validVouchers as $voucher) {
    $voucherID = $voucher['voucherID'];
    $voucherCode = $voucher['code'];

    // Check if already assigned
    $check = $conn->prepare("SELECT membershipID, voucherID FROM member_vouchers WHERE membershipID = ? AND voucherID = ?");
    $check->bind_param("ii", $membershipID, $voucherID);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if (!$exists) {
      // Assign the voucher
      $assign = $conn->prepare("INSERT INTO member_vouchers (membershipID, voucherID, assigned_at) VALUES (?, ?, NOW())");
      $assign->bind_param("ii", $membershipID, $voucherID);
      if ($assign->execute()) {
        $logMsg = "Assigned voucher '$voucherCode' to userID $membershipID\n";
        file_put_contents($logPath, $logMsg, FILE_APPEND);
        $assignCount++;
      }
      $assign->close();
    }
  }
}

file_put_contents($logPath, "[$timestamp] Done. Total assigned: $assignCount\n\n", FILE_APPEND);
$conn->close();
