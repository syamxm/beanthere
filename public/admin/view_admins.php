<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';

$admins = [];
$result = mysqli_query($conn, "SELECT id, username, reg_date FROM admins ORDER BY id");
while ($row = mysqli_fetch_assoc($result)) {
  $admins[] = $row;
}
mysqli_close($conn);

$pageTitle = 'Admins - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold">Registered admins</h1>
      <a href="admin_register.php" class="btn-caramel"><i class="fa-solid fa-user-plus mr-1"></i> Register admin</a>
    </div>

    <?php if (empty($admins)): ?>
      <p class="text-foam">No registered admins found.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="admin-table">
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Registered</th>
          </tr>
          <?php foreach ($admins as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= htmlspecialchars($row['username']) ?></td>
              <td class="whitespace-nowrap"><?= htmlspecialchars($row['reg_date'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
