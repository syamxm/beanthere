<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';

// Most Bought Items
$items = [];
$counts = [];
$sql = "SELECT name, SUM(qty) as total FROM orders WHERE drinkType IS NOT NULL AND drinkType != '' GROUP BY name ORDER BY total DESC LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
  $items[] = $row['name'];
  $counts[] = $row['total'];
}

// Best Customers
$customers = [];
$totals = [];
$sql2 = "SELECT u.username, SUM(o.qty) as total
         FROM orders o
         JOIN users u ON o.userID = u.userID
         GROUP BY u.username
         ORDER BY total DESC
         LIMIT 5";
$result2 = $conn->query($sql2);
while ($row = $result2->fetch_assoc()) {
  $customers[] = $row['username'];
  $totals[] = $row['total'];
}

// Drink Type Distribution
$types = [];
$typeCounts = [];
$sql3 = "SELECT drinkType, COUNT(*) as total FROM orders WHERE drinkType IS NOT NULL AND drinkType != '' GROUP BY drinkType";
$result3 = $conn->query($sql3);
while ($row = $result3->fetch_assoc()) {
  $types[] = $row['drinkType'];
  $typeCounts[] = $row['total'];
}

// Monthly Order Count
$months = [];
$monthlyCounts = [];
$sql4 = "SELECT DATE_FORMAT(orderTime, '%Y-%m') as month, COUNT(*) as total FROM orders WHERE drinkType IS NOT NULL AND drinkType != '' GROUP BY month ORDER BY month";
$result4 = $conn->query($sql4);
while ($row = $result4->fetch_assoc()) {
  $months[] = $row['month'];
  $monthlyCounts[] = $row['total'];
}

$pageTitle = 'Reports - Bean There Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include __DIR__ . '/../../src/partials/admin_head.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-8">Reports</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="bg-roast border border-bean rounded-2xl p-6">
        <h2 class="text-caramel font-semibold mb-4">Most bought items</h2>
        <canvas id="mostBoughtChart"></canvas>
      </div>
      <div class="bg-roast border border-bean rounded-2xl p-6">
        <h2 class="text-caramel font-semibold mb-4">Top customers</h2>
        <canvas id="topCustomerChart"></canvas>
      </div>
      <div class="bg-roast border border-bean rounded-2xl p-6">
        <h2 class="text-caramel font-semibold mb-4">Drink type distribution</h2>
        <canvas id="drinkTypeChart"></canvas>
      </div>
      <div class="bg-roast border border-bean rounded-2xl p-6">
        <h2 class="text-caramel font-semibold mb-4">Orders by month</h2>
        <canvas id="monthlyOrdersChart"></canvas>
      </div>
    </div>
  </main>

  <script>
    Chart.defaults.color = "#9c8b74";
    Chart.defaults.borderColor = "#3a2a1a";

    new Chart(document.getElementById("mostBoughtChart"), {
      type: "bar",
      data: {
        labels: <?= json_encode($items) ?>,
        datasets: [{
          label: "Total ordered",
          data: <?= json_encode($counts) ?>,
          backgroundColor: "#c49b63"
        }]
      },
      options: { plugins: { legend: { display: false } }, responsive: true }
    });

    new Chart(document.getElementById("topCustomerChart"), {
      type: "bar",
      data: {
        labels: <?= json_encode($customers) ?>,
        datasets: [{
          label: "Total items ordered",
          data: <?= json_encode($totals) ?>,
          backgroundColor: "#9c8b74"
        }]
      },
      options: { plugins: { legend: { display: false } }, responsive: true }
    });

    new Chart(document.getElementById("drinkTypeChart"), {
      type: "pie",
      data: {
        labels: <?= json_encode($types) ?>,
        datasets: [{
          data: <?= json_encode($typeCounts) ?>,
          backgroundColor: ["#c49b63", "#9c8b74", "#6d4c41", "#ede4d3", "#3a2a1a"]
        }]
      },
      options: { responsive: true }
    });

    new Chart(document.getElementById("monthlyOrdersChart"), {
      type: "line",
      data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
          label: "Orders",
          data: <?= json_encode($monthlyCounts) ?>,
          borderColor: "#c49b63",
          fill: false,
          tension: 0.3
        }]
      },
      options: { responsive: true }
    });
  </script>
</body>

</html>
