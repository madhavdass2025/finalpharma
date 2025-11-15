<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Common data
$low_stock_items = get_low_stock_items($conn);
$expiring_soon_items = get_expiring_soon_items($conn, 90); // 90 days threshold

// Role-specific data
if ($role == 'admin') {
    // KPIs
    $today_sales = get_total_sales_for_date($conn, date('Y-m-d'));
    $yesterday_sales = get_total_sales_for_date($conn, date('Y-m-d', strtotime('-1 day')));
    $today_collections = get_collections_by_payment_method($conn, date('Y-m-d'));

    // Chart Data
    $sales_trend_weekly = get_sales_trend($conn, 'weekly');
    $sales_trend_monthly = get_sales_trend($conn, 'monthly');

} elseif ($role == 'billing') {
    // KPIs for billing user
    $today_sales_list = get_today_sales_for_user($conn, $user_id);
    $today_collections = get_collections_by_payment_method($conn, date('Y-m-d'), $user_id);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <div class="container-fluid">
            <h2>Dashboard</h2>
            <hr>

            <!-- Admin View -->
            <?php if ($role == 'admin'): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Today's Sales</h5>
                            <p class="card-text">₹<?php echo number_format($today_sales, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Yesterday's Sales</h5>
                            <p class="card-text">₹<?php echo number_format($yesterday_sales, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Today's Collections</h5>
                            <p class="card-text">
                                Cash: ₹<?php echo number_format($today_collections['Cash'] ?? 0, 2); ?><br>
                                Card: ₹<?php echo number_format($today_collections['Card'] ?? 0, 2); ?><br>
                                UPI: ₹<?php echo number_format($today_collections['UPI'] ?? 0, 2); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-6">
                    <canvas id="weeklySalesChart"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Billing/Pharmacist View -->
            <?php if ($role == 'billing'): ?>
            <div class="row">
                 <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">My Collections Today</h5>
                             <p class="card-text">
                                Cash: ₹<?php echo number_format($today_collections['Cash'] ?? 0, 2); ?><br>
                                Card: ₹<?php echo number_format($today_collections['Card'] ?? 0, 2); ?><br>
                                UPI: ₹<?php echo number_format($today_collections['UPI'] ?? 0, 2); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                     <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">My Sales Today</h5>
                             <ul class="list-group">
                                <?php foreach ($today_sales_list as $sale): ?>
                                    <li class="list-group-item">
                                        <?php echo htmlspecialchars($sale['invoice_number']); ?> - ₹<?php echo number_format($sale['net_amount'], 2); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>


            <!-- Common Alerts -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <h4>Low Stock Items</h4>
                    <ul class="list-group">
                        <?php foreach ($low_stock_items as $item): ?>
                            <li class="list-group-item">
                                <?php echo htmlspecialchars($item['product_name']); ?> (Current Qty: <?php echo $item['current_qty']; ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4>Expiring Soon Items (90 days)</h4>
                    <ul class="list-group">
                         <?php foreach ($expiring_soon_items as $item): ?>
                            <li class="list-group-item">
                                <?php echo htmlspecialchars($item['product_name']); ?> (Batch: <?php echo htmlspecialchars($item['batch_number']); ?>) - Expires on <?php echo $item['expiry_date']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <?php if ($role == 'admin'): ?>
    <script>
        // Weekly Sales Chart
        var ctxWeekly = document.getElementById('weeklySalesChart').getContext('2d');
        var weeklySalesChart = new Chart(ctxWeekly, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($sales_trend_weekly)); ?>,
                datasets: [{
                    label: 'Weekly Sales',
                    data: <?php echo json_encode(array_values($sales_trend_weekly)); ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });

        // Monthly Sales Chart
        var ctxMonthly = document.getElementById('monthlySalesChart').getContext('2d');
        var monthlySalesChart = new Chart(ctxMonthly, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($sales_trend_monthly)); ?>,
                datasets: [{
                    label: 'Monthly Sales',
                    data: <?php echo json_encode(array_values($sales_trend_monthly)); ?>,
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true } } }
        });
    </script>
    <?php endif; ?>
</body>
</html>
