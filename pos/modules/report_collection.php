<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Admins only

require_once '../config/database.php';

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Main query
$collection_data = [];
$sql = "
    SELECT
        payment_date,
        SUM(CASE WHEN payment_method = 'Cash' THEN amount_paid ELSE 0 END) as cash_total,
        SUM(CASE WHEN payment_method = 'Card' THEN amount_paid ELSE 0 END) as card_total,
        SUM(CASE WHEN payment_method = 'UPI' THEN amount_paid ELSE 0 END) as upi_total,
        SUM(amount_paid) as daily_total
    FROM payments
    WHERE payment_date BETWEEN ? AND ?
    GROUP BY payment_date
    ORDER BY payment_date DESC
";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('ss', $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $collection_data[] = $row;
    }
    $stmt->close();
}
$conn->close();

// CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="collection_report_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Cash', 'Card', 'UPI', 'Total']);
    foreach ($collection_data as $row) {
        fputcsv($output, [
            $row['payment_date'],
            $row['cash_total'],
            $row['card_total'],
            $row['upi_total'],
            $row['daily_total']
        ]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Collection Report - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Daily Collection Report</h2>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="report_collection.php" method="get">
                <div class="row">
                    <div class="col-md-5"><input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>"></div>
                    <div class="col-md-5"><input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Table -->
    <div class="card">
         <div class="card-header d-flex justify-content-between">
            <span>Report Results</span>
            <a href="?<?php echo http_build_query($_GET + ['export' => 'csv']); ?>" class="btn btn-sm btn-success">Export to CSV</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead><tr><th>Date</th><th>Cash</th><th>Card</th><th>UPI</th><th>Total</th></tr></thead>
                <tbody>
                    <?php
                    $grand_total = 0;
                    foreach ($collection_data as $row):
                        $grand_total += $row['daily_total'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                            <td><?php echo number_format($row['cash_total'], 2); ?></td>
                            <td><?php echo number_format($row['card_total'], 2); ?></td>
                            <td><?php echo number_format($row['upi_total'], 2); ?></td>
                            <td><?php echo number_format($row['daily_total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">Grand Total:</td>
                        <td><?php echo number_format($grand_total, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</body>
</html>