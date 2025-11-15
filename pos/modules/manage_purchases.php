<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

// Fetch purchases
$query = "SELECT pb.id, pb.invoice_number, pb.invoice_date, s.supplier_name, pb.net_amount, pb.status
          FROM purchase_bills pb
          JOIN suppliers s ON pb.supplier_id = s.id
          ORDER BY pb.invoice_date DESC";
$purchases = $mysqli->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Purchases</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="content">
        <div class="container">
            <h2>Manage Purchases</h2>
            <a href="add_purchase.php" class="btn btn-success mb-3">Add New Purchase</a>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($purchase['invoice_number']); ?></td>
                        <td><?php echo $purchase['invoice_date']; ?></td>
                        <td><?php echo htmlspecialchars($purchase['supplier_name']); ?></td>
                        <td><?php echo number_format($purchase['net_amount'], 2); ?></td>
                        <td><span class="badge badge-<?php echo $purchase['status'] == 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($purchase['status']); ?></span></td>
                        <td>
                            <a href="view_purchase.php?id=<?php echo $purchase['id']; ?>" class="btn btn-info btn-sm">View</a>
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
