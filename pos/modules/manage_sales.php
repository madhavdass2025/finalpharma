<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Fetch sales invoices
$query = "SELECT si.id, si.invoice_number, si.invoice_date, c.customer_name, si.net_amount, u.name as billed_by, si.status
          FROM sales_invoices si
          LEFT JOIN customers c ON si.customer_id = c.id
          JOIN users u ON si.user_id = u.id
          ORDER BY si.invoice_date DESC, si.id DESC";
$sales = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Sales</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="content">
        <div class="container">
            <h2>Manage Sales Invoices</h2>
            <a href="pos.php" class="btn btn-success mb-3">New Sale (POS)</a>
            <a href="advanced_pos.php" class="btn btn-info mb-3">New Sale (Advanced POS)</a>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Billed By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                        <td><?php echo $sale['invoice_date']; ?></td>
                        <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?></td>
                        <td><?php echo number_format($sale['net_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($sale['billed_by']); ?></td>
                        <td><span class="badge badge-<?php echo $sale['status'] == 'completed' ? 'success' : 'danger'; ?>"><?php echo ucfirst($sale['status']); ?></span></td>
                        <td>
                            <a href="view_invoice.php?id=<?php echo $sale['id']; ?>" class="btn btn-info btn-sm">View</a>
                            <!-- Add cancel/return logic if needed -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
