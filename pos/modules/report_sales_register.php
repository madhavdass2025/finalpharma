<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';

Auth::check_access([1]);

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$user_id = $_GET['user_id'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';

$users = [];
$sql_users = "SELECT id, name FROM users";
if($result = $conn->query($sql_users)) {
    while($row = $result->fetch_assoc()) $users[] = $row;
}

$sales_data = [];
$sql = "
    SELECT si.invoice_number, si.invoice_date, si.customer_name, u.name as user_name, si.net_amount, p.payment_method
    FROM sales_invoices si
    JOIN users u ON si.user_id = u.id
    LEFT JOIN payments p ON si.id = p.invoice_id
    WHERE si.invoice_date BETWEEN ? AND ?
";
$params = ['ss', $start_date, $end_date];
if (!empty($user_id)) {
    $sql .= " AND si.user_id = ?";
    $params[0] .= 'i';
    $params[] = $user_id;
}
if (!empty($payment_method)) {
    $sql .= " AND p.payment_method = ?";
    $params[0] .= 's';
    $params[] = $payment_method;
}
$sql .= " ORDER BY si.invoice_date DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param(...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sales_data[] = $row;
    }
}
$conn->close();

if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_register_'.date('Y-m-d').'.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Invoice Number', 'Date', 'Customer', 'Billed By', 'Amount', 'Payment Method']);
    foreach ($sales_data as $row) {
        fputcsv($output, $row);
    }
    exit;
}
?>

<h1 class="mt-4">Sales Register</h1>
<div class="card mb-4">
    <div class="card-body">
        <form action="report_sales_register.php" method="get">
            <div class="row">
                <div class="col-md-3"><input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>"></div>
                <div class="col-md-3"><input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>"></div>
                <div class="col-md-2">
                    <select name="user_id" class="form-control">
                        <option value="">All Users</option>
                        <?php foreach($users as $user) echo "<option value='{$user['id']}' ".($user_id==$user['id']?'selected':'').">".htmlspecialchars($user['name'])."</option>"; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="payment_method" class="form-control">
                        <option value="">All Methods</option>
                        <option value="Cash" <?php if($payment_method=='Cash') echo 'selected'; ?>>Cash</option>
                        <option value="Card" <?php if($payment_method=='Card') echo 'selected'; ?>>Card</option>
                        <option value="UPI" <?php if($payment_method=='UPI') echo 'selected'; ?>>UPI</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
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
            <thead><tr><th>Invoice #</th><th>Date</th><th>Customer</th><th>Billed By</th><th>Amount</th><th>Payment Method</th></tr></thead>
            <tbody>
                <?php foreach ($sales_data as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['invoice_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo number_format($row['net_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>