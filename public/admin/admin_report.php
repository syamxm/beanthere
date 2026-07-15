<?php
session_start();
if (!isset($_SESSION['current_admin'])) {
  header("Location: admin_login.php");
  exit();
}

require_once __DIR__ . '/../../src/dbconn.php';
require_once __DIR__ . '/../../src/report_range.php';

[$fromDate, $toDate] = report_range_from_get();
$excluded = REPORT_EXCLUDED_STATUSES;
$exPlaceholders = implode(',', array_fill(0, count($excluded), '?'));
$rangeEnd = $toDate . ' 23:59:59';

function range_query(mysqli $conn, string $sql, string $fromDate, string $rangeEnd, array $excluded): array
{
  $stmt = $conn->prepare($sql);
  $params = array_merge([$fromDate, $rangeEnd], $excluded);
  $stmt->bind_param(str_repeat('s', count($params)), ...$params);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  return $rows;
}

// nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string -- $exPlaceholders is only "?" marks; values bound below
$revenueDaily = range_query($conn, "SELECT DATE(orderTime) AS day, SUM(total) + SUM(delivery_fee) AS revenue
    FROM orders
    WHERE orderTime BETWEEN ? AND ? AND orderStatus NOT IN ($exPlaceholders)
    GROUP BY day ORDER BY day", $fromDate, $rangeEnd, $excluded);

// nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string -- $exPlaceholders is only "?" marks; values bound below
$revenueWeekly = range_query($conn, "SELECT DATE_FORMAT(orderTime, '%x-W%v') AS week, SUM(total) + SUM(delivery_fee) AS revenue
    FROM orders
    WHERE orderTime BETWEEN ? AND ? AND orderStatus NOT IN ($exPlaceholders)
    GROUP BY week ORDER BY week", $fromDate, $rangeEnd, $excluded);

// nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string -- $exPlaceholders is only "?" marks; values bound below
$topItems = range_query($conn, "SELECT name, SUM(total) AS revenue, SUM(qty) AS quantity
    FROM orders
    WHERE orderTime BETWEEN ? AND ? AND orderStatus NOT IN ($exPlaceholders)
    GROUP BY name ORDER BY revenue DESC LIMIT 10", $fromDate, $rangeEnd, $excluded);

$topByQty = $topItems;
usort($topByQty, fn($a, $b) => (int)$b['quantity'] <=> (int)$a['quantity']);

// nosemgrep: php.lang.security.injection.tainted-sql-string.tainted-sql-string -- $exPlaceholders is only "?" marks; values bound below
$rangeTotals = range_query($conn, "SELECT COALESCE(SUM(total) + SUM(delivery_fee), 0) AS revenue, COUNT(DISTINCT COALESCE(checkoutID, orderID)) AS orders
    FROM orders
    WHERE orderTime BETWEEN ? AND ? AND orderStatus NOT IN ($exPlaceholders)", $fromDate, $rangeEnd, $excluded);
$rangeRevenue = $rangeTotals[0]['revenue'];
$rangeOrderCount = (int)$rangeTotals[0]['orders'];

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
  <script src="<?= htmlspecialchars($assetPrefix) ?>/vendor/chart.umd.min.js"></script>
</head>

<body class="bg-espresso text-crema font-sans min-h-screen">
  <?php include __DIR__ . '/../../src/partials/admin_nav.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold mb-8">Reports</h1>

    <div class="bg-roast border border-bean rounded-2xl p-6 mb-6">
      <form method="GET" class="flex flex-wrap items-end gap-4">
        <div>
          <label for="from" class="block text-sm text-foam mb-1.5">From</label>
          <input type="date" id="from" name="from" value="<?= htmlspecialchars($fromDate) ?>"
            class="bg-espresso border border-bean rounded-lg px-3 py-1.5 text-crema focus:outline-none focus:border-caramel">
        </div>
        <div>
          <label for="to" class="block text-sm text-foam mb-1.5">To</label>
          <input type="date" id="to" name="to" value="<?= htmlspecialchars($toDate) ?>"
            class="bg-espresso border border-bean rounded-lg px-3 py-1.5 text-crema focus:outline-none focus:border-caramel">
        </div>
        <button type="submit" class="btn-caramel">Apply</button>
        <a href="export_orders_csv.php?from=<?= urlencode($fromDate) ?>&amp;to=<?= urlencode($toDate) ?>"
          class="text-caramel hover:text-crema text-sm ml-auto">
          <i class="fa-solid fa-file-csv mr-1"></i>Export CSV
        </a>
      </form>
      <div class="grid grid-cols-2 gap-4 mt-6 text-center max-w-md">
        <div class="bg-espresso border border-bean rounded-xl px-4 py-3">
          <p class="text-2xl font-bold text-caramel">RM<?= htmlspecialchars(number_format((float)$rangeRevenue, 2)) ?></p>
          <p class="text-foam text-xs">revenue in range</p>
        </div>
        <div class="bg-espresso border border-bean rounded-xl px-4 py-3">
          <p class="text-2xl font-bold text-caramel"><?= htmlspecialchars(number_format($rangeOrderCount)) ?></p>
          <p class="text-foam text-xs">paid orders in range</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div class="bg-roast border border-bean rounded-2xl p-6">
        <h2 class="text-caramel font-semibold mb-4">Revenue by day</h2>
        <canvas id="revenueDailyChart"></canvas>
      </div>
      <div class="bg-roast border border-bean rounded-2xl p-6">
        <h2 class="text-caramel font-semibold mb-4">Revenue by week</h2>
        <canvas id="revenueWeeklyChart"></canvas>
      </div>
      <div class="bg-roast border border-bean rounded-2xl p-6">
        <h2 class="text-caramel font-semibold mb-4">Top items by revenue</h2>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-foam text-left border-b border-bean">
              <th class="py-2">Item</th>
              <th class="py-2 text-right">Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topItems as $row): ?>
              <tr class="border-b border-bean/50">
                <td class="py-2"><?= htmlspecialchars($row['name']) ?></td>
                <td class="py-2 text-right">RM<?= htmlspecialchars(number_format((float)$row['revenue'], 2)) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($topItems)): ?>
              <tr><td colspan="2" class="py-3 text-foam">No paid orders in this range.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="bg-roast border border-bean rounded-2xl p-6">
        <h2 class="text-caramel font-semibold mb-4">Top items by quantity</h2>
        <table class="w-full text-sm">
          <thead>
            <tr class="text-foam text-left border-b border-bean">
              <th class="py-2">Item</th>
              <th class="py-2 text-right">Quantity</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topByQty as $row): ?>
              <tr class="border-b border-bean/50">
                <td class="py-2"><?= htmlspecialchars($row['name']) ?></td>
                <td class="py-2 text-right"><?= htmlspecialchars(number_format((int)$row['quantity'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($topByQty)): ?>
              <tr><td colspan="2" class="py-3 text-foam">No paid orders in this range.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <h2 class="text-xl font-bold mb-4">All-time overview</h2>
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

    new Chart(document.getElementById("revenueDailyChart"), {
      type: "line",
      data: {
        labels: <?= json_encode(array_column($revenueDaily, 'day')) ?>,
        datasets: [{
          label: "Revenue (RM)",
          data: <?= json_encode(array_map('floatval', array_column($revenueDaily, 'revenue'))) ?>,
          borderColor: "#c49b63",
          fill: false,
          tension: 0.3
        }]
      },
      options: { responsive: true }
    });

    new Chart(document.getElementById("revenueWeeklyChart"), {
      type: "bar",
      data: {
        labels: <?= json_encode(array_column($revenueWeekly, 'week')) ?>,
        datasets: [{
          label: "Revenue (RM)",
          data: <?= json_encode(array_map('floatval', array_column($revenueWeekly, 'revenue'))) ?>,
          backgroundColor: "#c49b63"
        }]
      },
      options: { plugins: { legend: { display: false } }, responsive: true }
    });

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
