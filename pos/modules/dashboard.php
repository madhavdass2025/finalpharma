<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';

Auth::check_access([1, 2]);

// --- FULL DASHBOARD PHP LOGIC ---
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
$sql_yesterday_sales = "SELECT SUM(net_amount) as total FROM sales_invoices WHERE DATE(invoice_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$sql_today_collections = "SELECT payment_method, SUM(amount_paid) as total FROM payments WHERE DATE(payment_date) = CURDATE() GROUP BY payment_method";

if ($result = $conn->query($sql_today_sales)) $kpis['today_sales'] = $result->fetch_assoc()['total'] ?? 0;
if ($result = $conn->query($sql_yesterday_sales)) $kpis['yesterday_sales'] = $result->fetch_assoc()['total'] ?? 0;
if ($result = $conn->query($sql_today_collections)) {
    while ($row = $result->fetch_assoc()) {
        if(isset($kpis['today_collections'][$row['payment_method']])) {
            $kpis['today_collections'][$row['payment_method']] = $row['total'];
        }
    }
}

// Alerts
$low_stock_count = 0;
$expiring_soon_count = 0;
$sql_low_stock = "SELECT COUNT(*) as count FROM (SELECT p.id FROM products p JOIN stock_batches sb ON p.id = sb.product_id GROUP BY p.id, p.reorder_level HAVING SUM(sb.current_qty) <= p.reorder_level) as low_stock";
$sql_expiring = "SELECT COUNT(DISTINCT product_id) as count FROM stock_batches WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
if ($result = $conn->query($sql_low_stock)) $low_stock_count = $result->fetch_assoc()['count'] ?? 0;
if ($result = $conn->query($sql_expiring)) $expiring_soon_count = $result->fetch_assoc()['count'] ?? 0;

$conn->close();
?>

<h1 class="mt-4">Dashboard</h1>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary"><div class="card-body"><h5 class="card-title">Today's Sales</h5><p class="card-text fs-4"><?php echo number_format($kpis['today_sales'], 2); ?></p></div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-secondary"><div class="card-body"><h5 class="card-title">Yesterday's Sales</h5><p class="card-text fs-4"><?php echo number_format($kpis['yesterday_sales'], 2); ?></p></div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-body"><h5 class="card-title">Today's Collections</h5><p class="card-text fs-5">Cash: <?php echo number_format($kpis['today_collections']['Cash'], 2); ?> | Card: <?php echo number_format($kpis['today_collections']['Card'], 2); ?> | UPI: <?php echo number_format($kpis['today_collections']['UPI'], 2); ?></p></div></div>
    </div>
</div>

<?php if ($_SESSION["role_id"] == 1): ?>
    <div class="card mb-4">
        <div class="card-header"><h4>Sales Trend (Last 30 Days)</h4></div>
        <div class="card-body"><canvas id="salesTrendChart"></canvas></div>
    </div>
<?php endif; ?>

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