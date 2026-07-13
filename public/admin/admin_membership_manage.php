<?php
session_start();

if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/csrf.php';

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['membership_id'], $_POST['action'])) {
  csrf_verify();
  $membership_id = intval($_POST['membership_id']);
  $action = $_POST['action'];

  if ($action === 'approve') {
    $status = 'active';
    $_SESSION['message'] = "Membership approved successfully.";
  } elseif ($action === 'reject') {
    $status = 'rejected';
    $_SESSION['message'] = "Membership rejected.";
  } elseif ($action === 'revoke') {
    $status = "revoked";
    $_SESSION['message'] = "Membership revoked successfully.";
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

$flash = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$pageTitle = 'Memberships - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6">Memberships</h1>

    <?php if ($flash): ?>
      <p class="flash-message"><?= htmlspecialchars($flash) ?></p>
    <?php endif; ?>

    <h2 class="text-caramel font-semibold mt-6 mb-3">Pending applications</h2>
    <?php if (count($pending_memberships) === 0): ?>
      <p class="text-foam text-sm">No pending applications.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="admin-table">
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
          <?php foreach ($pending_memberships as $m): ?>
            <tr>
              <td><?= (int)$m['membershipID'] ?></td>
              <td><?= htmlspecialchars($m['username']) ?></td>
              <td><?= htmlspecialchars($m['status']) ?></td>
              <td>
                <form method="POST" class="flex gap-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="membership_id" value="<?= (int)$m['membershipID'] ?>">
                  <button name="action" value="approve" class="btn-caramel" onclick="return confirm('Accept this user\'s membership application?');">Approve</button>
                  <button name="action" value="reject" class="btn-danger" onclick="return confirm('Reject this user\'s membership application?');">Reject</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>

    <h2 class="text-caramel font-semibold mt-10 mb-3">Active members</h2>
    <?php if (count($active_memberships) === 0): ?>
      <p class="text-foam text-sm">No active members at the moment.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="admin-table">
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
          <?php foreach ($active_memberships as $m): ?>
            <tr>
              <td><?= (int)$m['membershipID'] ?></td>
              <td><?= htmlspecialchars($m['username']) ?></td>
              <td><?= htmlspecialchars($m['status']) ?></td>
              <td>
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="membership_id" value="<?= (int)$m['membershipID'] ?>">
                  <button name="action" value="revoke" class="btn-danger" onclick="return confirm('Revoke this user\'s membership?');">Revoke</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
