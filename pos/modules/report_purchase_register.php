<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Admins only

require_once '../config/database.php';

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$supplier_id = $_GET['supplier_id'] ?? '';

// Fetch suppliers for filter
$suppliers = [];
$sql_suppliers = "SELECT id, supplier_name FROM suppliers";
if($result = $conn->query($sql_suppliers)) {
    while($row = $result->fetch_assoc()) $suppliers[] = $row;
}

// Main query
$purchase_data = [];
$sql = "
    SELECT pb.bill_number, pb.bill_date, s.supplier_name, pb.total_amount, pb.payment_status
    FROM purchase_bills pb
    JOIN suppliers s ON pb.supplier_id = s.id
    WHERE pb.bill_date BETWEEN ? AND ?
";
$params = ['ss', $start_date, $end_date];

if (!empty($supplier_id)) {
    $sql .= " AND pb.supplier_id = ?";
    $params[0] .= 'i';
    $params[] = $supplier_id;
}
$sql .= " ORDER BY pb.bill_date DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $purchase_data[] = $row;
    }
    $stmt->close();
}
$conn->close();

// CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="purchase_register_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Bill Number', 'Date', 'Supplier', 'Amount', 'Payment Status']);
    foreach ($purchase_data as $row) {
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
    <title>Purchase Register Report - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Purchase Register</h2>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="report_purchase_register.php" method="get">
                <div class="row">
                    <div class="col-md-4"><input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>"></div>
                    <div class="col-md-4"><input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>"></div>
                    <div class="col-md-3">
                        <select name="supplier_id" class="form-control">
                            <option value="">All Suppliers</option>
                            <?php foreach($suppliers as $supplier) echo "<option value='{$supplier['id']}' ".($supplier_id==$supplier['id']?'selected':'').">{$supplier['supplier_name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-1"><button type="submit" class="btn btn-primary">Filter</button></div>
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
                <thead><tr><th>Bill #</th><th>Date</th><th>Supplier</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($purchase_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['bill_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['bill_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                            <td><?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['payment_status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>