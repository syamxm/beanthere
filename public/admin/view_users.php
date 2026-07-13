<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';

$users = [];
$result = mysqli_query($conn, "SELECT userID, username, reg_date, phone_number, email, authentication_status FROM users ORDER BY userID");
while ($row = mysqli_fetch_assoc($result)) {
  $users[] = $row;
}
mysqli_close($conn);

$pageTitle = 'Users - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-6">Registered users</h1>

    <?php if (empty($users)): ?>
      <p class="text-foam">No registered users found.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="admin-table">
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Registered</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Verified</th>
          </tr>
          <?php foreach ($users as $row): ?>
            <tr>
              <td><?= (int)$row['userID'] ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td class="whitespace-nowrap"><?= htmlspecialchars($row['reg_date'] ?? '—') ?></td>
              <td><?= htmlspecialchars($row['phone_number'] ?? '—') ?></td>
              <td><?= htmlspecialchars($row['email'] ?? '—') ?></td>
              <td><?= $row['authentication_status'] ? 'Yes' : 'No' ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
