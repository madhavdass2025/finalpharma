<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$purchase_id = $_GET['id'] ?? null;
if (!$purchase_id) {
    header('Location: manage_purchases.php');
    exit();
}

// Fetch Purchase Header
$stmt = $mysqli->prepare("SELECT pb.*, s.supplier_name FROM purchase_bills pb JOIN suppliers s ON pb.supplier_id = s.id WHERE pb.id = ?");
$stmt->bind_param("i", $purchase_id);
$stmt->execute();
$purchase = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$purchase) {
    die("Purchase not found.");
}

// Fetch Purchase Items
$stmt = $mysqli->prepare("SELECT pi.*, p.product_name FROM purchase_items pi JOIN products p ON pi.product_id = p.id WHERE pi.purchase_id = ?");
$stmt->bind_param("i", $purchase_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Purchase</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .purchase-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print mb-3">
             <a href="manage_purchases.php" class="btn btn-secondary">Back to Purchases</a>
             <button onclick="window.print()" class="btn btn-primary">Print</button>
        </div>

        <div class="purchase-box">
            <h2>Purchase Details</h2>
            <hr>
            <p><strong>Supplier:</strong> <?php echo htmlspecialchars($purchase['supplier_name']); ?></p>
            <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($purchase['invoice_number']); ?></p>
            <p><strong>Date:</strong> <?php echo $purchase['invoice_date']; ?></p>
            <hr>
            <h4>Items</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Tax %</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                        <td><?php echo $item['expiry_date']; ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['purchase_price'], 2); ?></td>
                        <td><?php echo number_format($item['tax_rate'], 2); ?></td>
                        <td><?php echo number_format($item['quantity'] * $item['purchase_price'] * (1 + $item['tax_rate']/100), 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
             <div class="text-right">
                <p><strong>Total:</strong> <?php echo number_format($purchase['total_amount'], 2); ?></p>
                <p><strong>Discount:</strong> <?php echo number_format($purchase['discount'], 2); ?></p>
                <h3><strong>Net Amount:</strong> <?php echo number_format($purchase['net_amount'], 2); ?></h3>
            </div>
        </div>
    </div>
</body>
</html>
