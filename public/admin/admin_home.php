<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <title>Admin Panel</title>
  <link rel="stylesheet" href="../assets/style_scrollbar.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      background-color: #1a1a1a;
      color: #f0f0f0;
      font-family: 'Poppins', sans-serif;
    }

    header {
      background-color: #202020;
      padding: 20px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.6);
    }

    header h1 {
      margin: 0;
      color: #ffe6a7;
      font-size: 26px;
      letter-spacing: 1px;
    }

    .icons {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .icons a {
      color: #ffffff;
      font-size: 22px;
      text-decoration: none;
      transition: 0.3s ease;
    }

    .icons a:hover {
      color: #ffe6a7;
    }

    .profile-dropdown {
      position: relative;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      background-color: #2c2c2c;
      min-width: 160px;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
    }

    .dropdown-content a,
    .dropdown-username {
      padding: 12px 16px;
      display: block;
      text-decoration: none;
      font-size: 14px;
      color: #eee;
    }

    .dropdown-content a:hover {
      background-color: #ffe6a7;
      color: #000;
    }

    .dropdown-username {
      font-weight: bold;
      color: #90ee90;
      background-color: #222;
      border-bottom: 1px solid #444;
    }

    .container {
      padding: 50px 20px;
      max-width: 1200px;
      margin: auto;
    }

    .category {
      margin-bottom: 50px;
    }

    .category h2 {
      font-size: 22px;
      color: #ffe6a7;
      margin-bottom: 20px;
      border-bottom: 2px solid #333;
      padding-bottom: 5px;
    }

    .grid-buttons {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 20px;
    }

    .admin-button {
      background: linear-gradient(145deg, #ffe6a7, #ffde95);
      color: #111;
      padding: 18px 20px;
      text-align: center;
      font-weight: 600;
      border-radius: 12px;
      text-decoration: none;
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease;
    }

    .admin-button:hover {
      background: #fff;
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(255, 230, 167, 0.3);
    }

    @media (max-width: 600px) {
      header h1 {
        font-size: 20px;
      }

      .admin-button {
        font-size: 14px;
        padding: 14px;
      }

      .grid-buttons {
        grid-template-columns: 1fr;
      }
    }

    .category-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 30px;
    }

    .category-card {
      background-color: #2a2a2a;
      border-radius: 12px;
      padding: 25px 20px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .category-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(255, 230, 167, 0.15);
    }

    .category-card h2 {
      font-size: 20px;
      color: #ffe6a7;
      margin-bottom: 20px;
      border-bottom: 1px solid #444;
      padding-bottom: 5px;
    }
  </style>
</head>

<body>

  <header>
    <h1>Admin Panel</h1>
    <div class="icons">
      <div class="profile-dropdown">
        <a href="#" id="profileToggle" title="Profile"><i class="fas fa-user"></i></a>
        <div class="dropdown-content" id="dropdownContent">
          <?php if (isset($_SESSION['current_admin'])): ?>
            <p class="dropdown-username"><?= htmlspecialchars($_SESSION['current_admin']) ?></p>
            <a href="admin_logout.php">Logout</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <div class="container">
    <div class="category-grid">

      <div class="category-card">
        <h2>Users</h2>
        <div class="grid-buttons">
          <a class="admin-button" href="view_users.php">View Users</a>
          <a class="admin-button" href="admin_order_tracking.php">User Order Status</a>
        </div>
      </div>

      <div class="category-card">
        <h2>Items</h2>
        <div class="grid-buttons">
          <a class="admin-button" href="add_item.php">Add New Item</a>
          <a class="admin-button" href="view_items.php">View Items</a>
        </div>
      </div>

      <div class="category-card">
        <h2>Orders</h2>
        <div class="grid-buttons">
          <a class="admin-button" href="adminOrderManagement.php">Manage Order Status</a>
          <a class="admin-button" href="admin_report.php">Order Report</a>
        </div>
      </div>

      <div class="category-card">
        <h2>Admins</h2>
        <div class="grid-buttons">
          <a class="admin-button" href="view_admins.php">View Admins</a>
          <a class="admin-button" href="admin_register.php">Register Admin</a>
        </div>
      </div>

      <div class="category-card">
        <h2>Vouchers</h2>
        <div class="grid-buttons">
          <a class="admin-button" href="add_voucher.php">Add Voucher</a>
          <a class="admin-button" href="manage_voucher.php">Manage Voucher</a>
        </div>
      </div>

      <div class="category-card">
        <h2>Membership</h2>
        <div class="grid-buttons">
          <a class="admin-button" href="admin_membership_manage.php">Manage Membership</a>
        </div>
      </div>

    </div>
  </div>


  <script>
    // Toggle dropdown
    document.getElementById("profileToggle").addEventListener("click", function(e) {
      e.preventDefault();
      const dropdown = document.getElementById("dropdownContent");
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function(e) {
      const toggle = document.getElementById("profileToggle");
      const dropdown = document.getElementById("dropdownContent");
      if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = "none";
      }
    });
  </script>

</body>

</html>