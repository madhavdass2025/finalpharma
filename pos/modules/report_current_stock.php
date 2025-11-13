<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Admins only

require_once '../config/database.php';

$stock_data = [];
$sql = "
    SELECT
        p.product_name,
        p.generic_name,
        p.mrp,
        sb.batch_number,
        sb.expiry_date,
        sb.current_qty,
        sb.purchase_price
    FROM stock_batches sb
    JOIN products p ON sb.product_id = p.id
    WHERE sb.current_qty > 0
    ORDER BY p.product_name, sb.expiry_date
";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $stock_data[] = $row;
    }
    $result->free();
}
$conn->close();

// Basic CSV export logic
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="current_stock_report_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Product Name', 'Generic Name', 'MRP', 'Batch Number', 'Expiry Date', 'Current Quantity', 'Purchase Price']);
    foreach ($stock_data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Current Stock Report - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Current Stock Report</h2>
            <div>
                <a href="report_current_stock.php?export=csv" class="btn btn-success">Export to CSV</a>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Generic Name</th>
                            <th>MRP</th>
                            <th>Batch Number</th>
                            <th>Expiry Date</th>
                            <th>Current Quantity</th>
                            <th>Purchase Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stock_data)): ?>
                            <tr><td colspan="7" class="text-center">No stock available.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stock_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['generic_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['mrp']); ?></td>
                                    <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                    <td><?php echo htmlspecialchars($row['expiry_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['current_qty']); ?></td>
                                    <td><?php echo htmlspecialchars($row['purchase_price']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>