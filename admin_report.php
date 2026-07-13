<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: adminLogin.php");
  exit();
}

include "dbconn.php";


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
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Report</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fffaf2;
      color: #4a4a4a;
      padding: 20px;
      margin: 0;
    }

    h2 {
      text-align: center;
      color: #5d4037;
      margin-bottom: 50px;
    }

    .chart-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 30px;
      max-width: 1000px;
      margin: auto;
    }

    .chart-card {
      background: #ffffff;
      padding: 2rem 1.5rem;
      border-radius: 1.5rem;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      min-height: 380px;
    }

    .chart-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
    }

    .chart-card h3 {
      color: #6d4c41;
      font-size: 1.2rem;
      margin-bottom: 20px;
    }

    canvas {
      width: 100% !important;
      max-height: 280px;
    }

    @media (max-width: 768px) {
      .chart-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>


<body>
  <a href="admin page.php" class="fixed top-5 left-5 z-50 bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg shadow-md hover:bg-gray-100 transition duration-300">
    ← Go Back
  </a>
  <h2>Admin Dashboard Reports</h2>
  <div class="chart-grid">
    <div class="chart-card">
      <h3>Most Bought Items</h3>
      <canvas id="mostBoughtChart"></canvas>
    </div>
    <div class="chart-card">
      <h3>Top Customers</h3>
      <canvas id="topCustomerChart"></canvas>
    </div>
    <div class="chart-card">
      <h3>Drink Type Distribution</h3>
      <canvas id="drinkTypeChart"></canvas>
    </div>
    <div class="chart-card">
      <h3>Orders by Month</h3>
      <canvas id="monthlyOrdersChart"></canvas>
    </div>
  </div>

  <script>
    // Most Bought Items
    new Chart(document.getElementById("mostBoughtChart"), {
      type: "bar",
      data: {
        labels: <?= json_encode($items) ?>,
        datasets: [{
          label: "Total Ordered",
          data: <?= json_encode($counts) ?>,
          backgroundColor: "#d8c4b0ff"
        }]
      },
      options: {
        plugins: {
          legend: {
            display: false
          }
        },
        responsive: true
      }
    });

    // Top Customers
    new Chart(document.getElementById("topCustomerChart"), {
      type: "bar",
      data: {
        labels: <?= json_encode($customers) ?>,
        datasets: [{
          label: "Total Items Ordered",
          data: <?= json_encode($totals) ?>,
          backgroundColor: "#764f10ff"
        }]
      },
      options: {
        plugins: {
          legend: {
            display: false
          }
        },
        responsive: true
      }
    });

    // Drink Type Distribution
    new Chart(document.getElementById("drinkTypeChart"), {
      type: "pie",
      data: {
        labels: <?= json_encode($types) ?>,
        datasets: [{
          data: <?= json_encode($typeCounts) ?>,
          backgroundColor: ["#d7ccc8", "#a1887f", "#6d4c41", "#ffe0b2", "#bcaaa4"]
        }]
      },
      options: {
        responsive: true
      }
    });

    // Orders by Month
    new Chart(document.getElementById("monthlyOrdersChart"), {
      type: "line",
      data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
          label: "Orders",
          data: <?= json_encode($monthlyCounts) ?>,
          borderColor: "#6d4c41",
          fill: false,
          tension: 0.3
        }]
      },
      options: {
        responsive: true
      }
    });
  </script>
</body>

</html>