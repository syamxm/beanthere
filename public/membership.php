<?php
session_start();

if (!isset($_SESSION['current_user'])) {
  header('Location: user_login.php');
  exit();
}

require_once __DIR__ . '/../src/dbconn.php';

$username = $_SESSION['current_user'];

// Get user info
$stmt = $conn->prepare("SELECT userID, authentication_status FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($userID, $verified);
$stmt->fetch();
$stmt->close();

$status = null;

// Check membership
$stmt = $conn->prepare("SELECT status FROM membership WHERE userID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($status);
$stmt->fetch();
$stmt->close();

// Handle application form
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $verified) {
  if ($status === null) {
    // Insert new membership request
    $stmt = $conn->prepare("INSERT INTO membership (userID) VALUES (?)");
    $stmt->bind_param("i", $userID);
    if ($stmt->execute()) {
      $success = "Membership application submitted! Please wait for admin approval.";
      $status = "pending";
    } else {
      $error = "Failed to apply for membership. Please try again.";
    }
    $stmt->close();
  } else if ($status === "rejected" || $status = "revoked") {
    // Reapply for membership
    $status = "pending";
    $update = $conn->prepare("UPDATE membership SET status = ? WHERE userID = ?");
    $update->bind_param("si", $status, $userID);
    if ($update->execute()) {
      $success = "Membership Reapplication submitted! Please wait for admin approval.";
    } else {
      $update = "Failed to apply for membership. Please try again.";
    }
    $update->close();
  } else {
    $error = "You have already applied or are a member.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Membership - Bean There</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-yellow-50 text-gray-800">
  <div class="max-w-xl mx-auto mt-16 bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Membership Status</h2>

    <?php if (!$verified): ?>
      <p class="text-red-600 font-medium">You must be a verified user to apply for membership.</p>

    <?php elseif ($status === 'active'): ?>
      <p class="text-green-600 font-semibold">✅ You are an active Bean There member!</p>

    <?php elseif ($status === 'pending'): ?>
      <p class="text-yellow-600 font-medium">Your membership request is pending approval.</p>

    <?php elseif ($status === 'revoked'): ?>
      <p class="text-red-600 font-medium mb-4">Your membership was revoked. You may reapply below.</p>
      <form method="post">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Reapply for Membership</button>
      </form>

    <?php elseif ($status === 'rejected'): ?>
      <p class="text-red-600 font-medium mb-4">Your membership application was rejected. You may reapply below.</p>
      <form method="post">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Reapply for Membership</button>
      </form>

    <?php else: ?>
      <?php if (!empty($success)): ?>
        <p class="text-green-600"><?= $success ?></p>
      <?php elseif (!empty($error)): ?>
        <p class="text-red-600"><?= $error ?></p>
      <?php else: ?>
        <p class="mb-4">You are not a member yet. Click below to apply for membership!</p>
        <form method="post">
          <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Apply for Membership</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>

    <a href="user_dashboard.php" class="mt-6 inline-block text-blue-600 hover:underline">← Back to Dashboard</a>
  </div>
</body>

</html>