<?php

session_start();
if (!isset($_SESSION['current_admin'])) {
  header('Location: admin_login.php');
  exit();
}
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="ISO-8859-1">
  <title>Registered Admins</title>
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
      margin-bottom: 30px;
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

    a.button {
      display: inline-block;
      padding: 6px 12px;
      margin: 2px;
      background-color: #c49b63;
      color: #000;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
    }

    a.button:hover {
      background-color: #fff;
    }
  </style>
</head>

<body>

  <header>
    <a href="admin_home.php" class="back-link">⬅ Back to Admin Page</a>
    <h1>Registered Admins</h1>
  </header>

  <table border="1" cellspacing="4" cellpadding="4">
    <tr>
      <th>ID</th>
      <th>Username</th>
      <th>Password</th>
      <th>Date Registered</th>
    </tr>
    <?php
    // Connect to DB
    require_once __DIR__ . '/../../src/dbconn.php';
    $sqlUserInfo = "SELECT * FROM admins";
    $result = mysqli_query($conn, $sqlUserInfo);
    if (mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id'];
        $username = $row['username'];
        $password = $row['password'];
        $reg_date = $row['reg_date'];
    ?>
        <tr>
          <td><?php echo $id ?></td>
          <td><?php echo htmlspecialchars($username) ?></td>
          <td><?php echo substr($password, 0, 2) . str_repeat('*', 6); ?></td>
          <td><?php echo htmlspecialchars($reg_date) ?></td>
        </tr>
      <?php
      }
    } else {
      ?>
      <tr>
        <td colspan="4">
          <h2>No registered admins found.</h2>
        </td>
      </tr>
    <?php
    }
    mysqli_close($conn);
    ?>
  </table>

</body>

</html>