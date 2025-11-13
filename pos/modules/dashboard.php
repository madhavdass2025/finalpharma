<?php
require_once '../includes/Auth.php';
Auth::check_access([1, 2]); // 1 for Admin, 2 for Billing/Pharmacist

require_once '../config/database.php';

// Data for Admin Dashboard
if ($_SESSION['role_id'] == 1) {
    // Sales Trend (Last 30 days)
    $sales_trend_data = [];
    $sql_sales = "SELECT DATE(invoice_date) as day, SUM(net_amount) as total_sales FROM sales_invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY day ORDER BY day";
    if ($result = $conn->query($sql_sales)) {
        while ($row = $result->fetch_assoc()) {
            $sales_trend_data['labels'][] = $row['day'];
            $sales_trend_data['data'][] = $row['total_sales'];
        }
        $result->free();
    }
}

// Data for Both Roles
// KPIs: Today's Sales, Yesterday's Sales, Today's Collections by method
$kpis = [
    'today_sales' => 0,
    'yesterday_sales' => 0,
    'today_collections' => ['Cash' => 0, 'Card' => 0, 'UPI' => 0]
];
$sql_today_sales = "SELECT SUM(net_amount) as total FROM sales_invoices WHERE DATE(invoice_date) = CURDATE()";
$sql_yesterday_sales = "SELECT SUM(net_amount) as total FROM sales_invoices WHERE DATE(invoice_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
$sql_today_collections = "SELECT payment_method, SUM(amount_paid) as total FROM payments WHERE DATE(payment_date) = CURDATE() GROUP BY payment_method";

if ($result = $conn->query($sql_today_sales)) $kpis['today_sales'] = $result->fetch_assoc()['total'] ?? 0;
if ($result = $conn->query($sql_yesterday_sales)) $kpis['yesterday_sales'] = $result->fetch_assoc()['total'] ?? 0;
if ($result = $conn->query($sql_today_collections)) {
    while ($row = $result->fetch_assoc()) {
        $kpis['today_collections'][$row['payment_method']] = $row['total'];
    }
}

// Low Stock & Expiry Alerts
$low_stock_count = 0;
$expiring_soon_count = 0;
$sql_low_stock = "SELECT COUNT(*) as count FROM (SELECT p.id FROM products p JOIN stock_batches sb ON p.id = sb.product_id GROUP BY p.id HAVING SUM(sb.current_qty) <= p.reorder_level) as low_stock";
$sql_expiring = "SELECT COUNT(DISTINCT product_id) as count FROM stock_batches WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
if ($result = $conn->query($sql_low_stock)) $low_stock_count = $result->fetch_assoc()['count'] ?? 0;
if ($result = $conn->query($sql_expiring)) $expiring_soon_count = $result->fetch_assoc()['count'] ?? 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/chart.min.js"></script>
</head>
<body>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h1>
        <a href="logout.php" class="btn btn-danger">Sign Out</a>
    </div>

    <!-- Common KPIs for both roles -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Today's Sales</h5>
                    <p class="card-text fs-4"><?php echo number_format($kpis['today_sales'], 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-secondary">
                <div class="card-body">
                    <h5 class="card-title">Yesterday's Sales</h5>
                    <p class="card-text fs-4"><?php echo number_format($kpis['yesterday_sales'], 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Today's Collections</h5>
                    <p class="card-text fs-5">
                        Cash: <?php echo number_format($kpis['today_collections']['Cash'], 2); ?> |
                        Card: <?php echo number_format($kpis['today_collections']['Card'], 2); ?> |
                        UPI: <?php echo number_format($kpis['today_collections']['UPI'], 2); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content Area -->
        <div class="col-md-9">
            <?php if ($_SESSION["role_id"] == 1): // Admin View ?>
                <div class="card mb-4">
                    <div class="card-header"><h4>Sales Trend (Last 30 Days)</h4></div>
                    <div class="card-body">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>

             <!-- Placeholder for Billing/Pharmacist view specific components -->
             <?php if ($_SESSION["role_id"] == 2): ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">Quick Actions</h4>
                    <p>Go to the <a href="pos.php" class="alert-link">Point of Sale</a> to start billing.</p>
                </div>
             <?php endif; ?>
        </div>

        <!-- Right Sidebar with Alerts and Links -->
        <div class="col-md-3">
            <div class="card mb-3">
                <div class="card-header bg-warning"><h5>Alerts</h5></div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <a href="report_low_stock_expiry.php">Low Stock Items: <?php echo $low_stock_count; ?></a>
                    </li>
                    <li class="list-group-item">
                        <a href="report_low_stock_expiry.php">Expiring Soon (30 days): <?php echo $expiring_soon_count; ?></a>
                    </li>
                </ul>
            </div>

            <?php if ($_SESSION["role_id"] == 1): // Admin Links ?>
                <div class="card">
                    <div class="card-header bg-info"><h5>Management</h5></div>
                    <div class="list-group">
                        <a href="pos.php" class="list-group-item list-group-item-action">Point of Sale</a>
                        <a href="manage_users.php" class="list-group-item list-group-item-action">Manage Users</a>
                        <a href="manage_suppliers.php" class="list-group-item list-group-item-action">Manage Suppliers</a>
                        <a href="manage_products.php" class="list-group-item list-group-item-action">Manage Products</a>
                        <a href="add_purchase.php" class="list-group-item list-group-item-action">Add Purchase Bill</a>
                        <a href="add_purchase_return.php" class="list-group-item list-group-item-action">Add Purchase Return</a>
                        <a href="add_sales_return.php" class="list-group-item list-group-item-action">Add Sales Return</a>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-success text-white"><h5>Reports</h5></div>
                    <div class="list-group">
                        <a href="report_current_stock.php" class="list-group-item list-group-item-action">Current Stock</a>
                        <a href="report_stock_ledger.php" class="list-group-item list-group-item-action">Stock Ledger</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

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
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>
<?php endif; ?>

</body>
</html>