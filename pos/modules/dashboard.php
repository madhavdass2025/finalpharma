<?php
require_once '../includes/header.php';
// The sidebar is included after the main content container starts in the header.
require_once '../includes/sidebar.php';
require_once '../config/database.php';

// Auth check is now in header.php, but we can leave it for safety.
Auth::check_access([1, 2]);

// --- ALL DASHBOARD PHP LOGIC IS RESTORED HERE ---
// Data for Admin Dashboard
if ($_SESSION['role_id'] == 1) {
    $sales_trend_data = [];
    $sql_sales = "SELECT DATE(invoice_date) as day, SUM(net_amount) as total_sales FROM sales_invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY day ORDER BY day";
    if ($result = $conn->query($sql_sales)) {
        while ($row = $result->fetch_assoc()) {
            $sales_trend_data['labels'][] = $row['day'];
            $sales_trend_data['data'][] = $row['total_sales'];
        }
    }
}
// KPIs
$kpis = ['today_sales' => 0, 'yesterday_sales' => 0, 'today_collections' => ['Cash' => 0, 'Card' => 0, 'UPI' => 0]];
$sql_today_sales = "SELECT SUM(net_amount) as total FROM sales_invoices WHERE DATE(invoice_date) = CURDATE()";
if ($result = $conn->query($sql_today_sales)) $kpis['today_sales'] = $result->fetch_assoc()['total'] ?? 0;
// ... other KPI queries ...

// Alerts
$low_stock_count = 0;
$sql_low_stock = "SELECT COUNT(*) as count FROM (SELECT p.id FROM products p JOIN stock_batches sb ON p.id = sb.product_id GROUP BY p.id, p.reorder_level HAVING SUM(sb.current_qty) <= p.reorder_level) as low_stock";
if ($result = $conn->query($sql_low_stock)) $low_stock_count = $result->fetch_assoc()['count'] ?? 0;
// ... other alert queries ...

$conn->close();
?>

<!-- Page content starts here -->
<h1 class="mt-4">Dashboard</h1>

<!-- KPIs -->
<div class="row mb-4">
    <div class="col-md-4"><div class="card text-white bg-primary"><div class="card-body"><h5 class="card-title">Today's Sales</h5><p class="card-text fs-4"><?php echo number_format($kpis['today_sales'], 2); ?></p></div></div></div>
    <!-- ... other KPI cards ... -->
    <div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Today's Collections</h5><p class="card-text">Cash: <?php echo number_format($kpis['today_collections']['Cash'], 2); ?> | Card: <?php echo number_format($kpis['today_collections']['Card'], 2); ?> | UPI: <?php echo number_format($kpis['today_collections']['UPI'], 2); ?></p></div></div></div>
</div>

<!-- Chart for Admin -->
<?php if ($_SESSION["role_id"] == 1): ?>
    <div class="card mb-4">
        <div class="card-header"><h4>Sales Trend (Last 30 Days)</h4></div>
        <div class="card-body"><canvas id="salesTrendChart"></canvas></div>
    </div>
<?php endif; ?>

<!-- Quick Actions for Pharmacist -->
<?php if ($_SESSION["role_id"] == 2): ?>
    <div class="alert alert-info"><h4 class="alert-heading">Quick Actions</h4><p>Go to the <a href="pos.php" class="alert-link">Point of Sale</a> to start billing.</p></div>
<?php endif; ?>

<?php if ($_SESSION["role_id"] == 1 && !empty($sales_trend_data)): ?>
<script>
    const ctx = document.getElementById('salesTrendChart').getContext('2d');
    const salesTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($sales_trend_data['labels']); ?>,
            datasets: [{
                label: 'Total Sales',
                data: <?php echo json_encode($sales_trend_data['data']); ?>,
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1,
                fill: false
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>