<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

include "dbconn.php";

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['membership_id'], $_POST['action'])) {
  $membership_id = intval($_POST['membership_id']);
  $action = $_POST['action'];

  if ($action === 'approve') {
    $status = 'active';
    $_SESSION['message'] = "Membership approved successfully.";
  } elseif ($action === 'reject') {
    $status = 'rejected';
    $_SESSION['message'] = "Membership rejected.";
  } elseif ($action === 'revoke') {
    // Revoke means deleting the record
    $status = "revoked";
    $stmt = $conn->prepare("UPDATE membership SET status = ? WHERE membershipID = ?");
    $stmt->bind_param("si", $status, $membership_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['message'] = "Membership revoked successfully.";
    header("Location: admin_membership_manage.php");
    exit();
  }

  if (isset($status)) {
    $stmt = $conn->prepare("UPDATE membership SET status = ? WHERE membershipID = ?");
    $stmt->bind_param("si", $status, $membership_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_membership_manage.php");
    exit();
  }
}

// Get pending memberships
$pending_query = "SELECT m.membershipID, m.status, u.username 
                  FROM membership m 
                  JOIN users u ON m.userID = u.userID 
                  WHERE m.status = 'pending'";
$pending_result = $conn->query($pending_query);
$pending_memberships = $pending_result->fetch_all(MYSQLI_ASSOC);

// Get active memberships
$active_query = "SELECT m.membershipID, m.status, u.username 
                 FROM membership m 
                 JOIN users u ON m.userID = u.userID 
                 WHERE m.status = 'active'";
$active_result = $conn->query($active_query);
$active_memberships = $active_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Memberships</title>
  <style>
    body {
      background-color: #121212;
      color: #ffffff;
      font-family: Arial, sans-serif;
      padding: 120px 20px 20px 20px;
      margin: 0;
    }

    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background-color: #1f1f1f;
      padding: 20px 20px 10px 20px;
      z-index: 999;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.6);
    }

    .back-link {
      position: absolute;
      top: 20px;
      left: 20px;
      color: #c49b63;
      text-decoration: none;
      font-weight: bold;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    h1 {
      color: #c49b63;
      margin: 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th,
    td {
      border: 1px solid #333;
      padding: 12px;
      text-align: center;
    }

    th {
      background-color: #1f1f1f;
      color: #c49b63;
    }

    tr:nth-child(even) {
      background-color: #1a1a1a;
    }

    tr:nth-child(odd) {
      background-color: #222;
    }

    form {
      display: flex;
      justify-content: center;
      gap: 10px;
    }

    .approve-btn,
    .reject-btn,
    .revoke-btn {
      padding: 6px 12px;
      font-weight: bold;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
    }

    .approve-btn {
      background-color: #4caf50;
      color: #000;
    }

    .reject-btn,
    .revoke-btn {
      background-color: #f44336;
      color: #fff;
    }

    .approve-btn:hover {
      background-color: #45a049;
    }

    .reject-btn:hover,
    .revoke-btn:hover {
      background-color: #da190b;
    }

    .no-data {
      text-align: center;
      color: #f8b400;
      margin-top: 20px;
    }

    .message {
      text-align: center;
      color: #f8b400;
      font-weight: bold;
      margin-top: 20px;
    }

    h2 {
      color: #f8b400;
      margin-top: 40px;
    }
  </style>
</head>

<body>

  <header>
    <a href="admin%20page.php" class="back-link">⬅ Back to Admin Page</a>
    <h1>Manage Memberships</h1>
  </header>

  <?php if (isset($_SESSION['message'])): ?>
    <p class="message"><?= htmlspecialchars($_SESSION['message']) ?></p>
    <?php unset($_SESSION['message']); ?>
  <?php endif; ?>

  <!-- Pending Applications -->
  <h2>Pending Applications</h2>
  <?php if (count($pending_memberships) === 0): ?>
    <p class="no-data">No pending membership applications at the moment.</p>
  <?php else: ?>
    <table>
      <tr>
        <th>Membership ID</th>
        <th>Username</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
      <?php foreach ($pending_memberships as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['membershipID']) ?></td>
          <td><?= htmlspecialchars($m['username']) ?></td>
          <td><?= htmlspecialchars($m['status']) ?></td>
          <td>
            <form method="POST">
              <input type="hidden" name="membership_id" value="<?= $m['membershipID'] ?>">
              <button name="action" value="approve" class="approve-btn" onclick="return confirm('Accept this user\'s membership application?');">Approve</button>
              <button name="action" value="reject" class="reject-btn" onclick="return confirm('Reject this user\'s membership application?');">Reject</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <!-- Active Members -->
  <h2>Active Members</h2>
  <?php if (count($active_memberships) === 0): ?>
    <p class="no-data">No active members at the moment.</p>
  <?php else: ?>
    <table>
      <tr>
        <th>Membership ID</th>
        <th>Username</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
      <?php foreach ($active_memberships as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['membershipID']) ?></td>
          <td><?= htmlspecialchars($m['username']) ?></td>
          <td><?= htmlspecialchars($m['status']) ?></td>
          <td>
            <form method="POST">
              <input type="hidden" name="membership_id" value="<?= $m['membershipID'] ?>">
              <button name="action" value="revoke" class="revoke-btn" onclick="return confirm('Revoke this user\'s membership?');">Revoke</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

</body>

</html>