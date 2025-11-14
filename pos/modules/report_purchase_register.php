<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';

Auth::check_access([1]);

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$supplier_id = $_GET['supplier_id'] ?? '';

$suppliers = [];
$sql_suppliers = "SELECT id, supplier_name FROM suppliers";
if($result = $conn->query($sql_suppliers)) {
    while($row = $result->fetch_assoc()) $suppliers[] = $row;
}

$purchase_data = [];
$sql = "
    SELECT pb.bill_number, pb.bill_date, s.supplier_name, pb.total_amount, pb.status
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
}
$conn->close();

if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="purchase_register_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Bill Number', 'Date', 'Supplier', 'Amount', 'Payment Status']);
    foreach ($purchase_data as $row) {
        fputcsv($output, $row);
    }
    exit;
}
?>

<h1 class="mt-4">Purchase Register</h1>
<div class="card mb-4">
    <div class="card-body">
        <form action="report_purchase_register.php" method="get">
            <div class="row">
                <div class="col-md-4"><input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>"></div>
                <div class="col-md-4"><input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>"></div>
                <div class="col-md-3">
                    <select name="supplier_id" class="form-control">
                        <option value="">All Suppliers</option>
                        <?php foreach($suppliers as $supplier) echo "<option value='{$supplier['id']}' ".($supplier_id==$supplier['id']?'selected':'').">".htmlspecialchars($supplier['supplier_name'])."</option>"; ?>
                    </select>
                </div>
                <div class="col-md-1"><button type="submit" class="btn btn-primary">Filter</button></div>
            </div>
        </form>
    </div>
</div>
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
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>