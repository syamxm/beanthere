<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  die("Unauthorized. Please log in.");
}

require_once __DIR__ . '/../src/dbconn.php';

$username = $_SESSION['current_user'];

// Get userID
$stmt = $conn->prepare("SELECT userID FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID);
$stmt->fetch();
$stmt->close();

// Get membershipID
$stmt = $conn->prepare("SELECT membershipID FROM membership WHERE userID = ? AND status = 'active'");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($membershipID);
$stmt->fetch();
$stmt->close();

$vouchers = [];
if ($membershipID) {
  $stmt = $conn->prepare("
    SELECT v.code, v.discount_value, v.valid_from, v.valid_until, v.status, mv.used 
    FROM member_vouchers mv
    JOIN vouchers v ON mv.voucherID = v.voucherID
    WHERE mv.membershipID = ? AND v.status = 'active'
    ORDER BY mv.assigned_at DESC
  ");
  $stmt->bind_param("i", $membershipID);
  $stmt->execute();
  $result = $stmt->get_result();
  $vouchers = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>My Vouchers - Bean There</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #fdfaf6;
      font-family: 'Poppins', sans-serif;
    }

    .voucher-card {
      border: 1px solid #ddd;
      border-radius: 0.75rem;
      padding: 1rem;
      margin-bottom: 1rem;
      background-color: #fff7f0;
    }

    .used {
      color: #ff5e5e;
      font-weight: bold;
    }

    .unused {
      color: #28a745;
      font-weight: bold;
    }

    .empty {
      text-align: center;
      color: #999;
      margin-top: 2rem;
    }
  </style>
</head>

<body>
  <div class="container max-w-3xl mx-auto mt-12 bg-white p-8 rounded-2xl shadow-lg">
    <a href="user_dashboard.php" class="fixed top-5 left-5 z-50 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg shadow-md hover:bg-gray-100 transition duration-300">
      ← Go To Main Menu
    </a>
    <h2 class="text-2xl font-bold text-center mb-6">My Vouchers</h2>

    <?php if (empty($vouchers)): ?>
      <p class="empty">You currently have no vouchers.</p>
    <?php else: ?>
      <?php foreach ($vouchers as $v): ?>
        <div class="voucher-card">
          <h3 class="text-xl font-semibold mb-2">Code: <?= htmlspecialchars($v['code']) ?></h3>
          <p>Discount: <?= number_format($v['discount_value'], 2) ?>%</p>
          <p>Valid From: <?= $v['valid_from'] ?></p>
          <p>Valid Until: <?= $v['valid_until'] ?></p>
          <p>Status: <span class="<?= $v['used'] ? 'used' : 'unused' ?>"><?= $v['used'] ? 'Used' : 'Unused' ?></span></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>

</html>