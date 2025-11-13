<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Admins only

require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: manage_purchases.php");
    exit;
}

$purchase_id = $_GET['id'];
$purchase_bill = null;
$purchase_items = [];

// Fetch purchase bill details
$sql_bill = "
    SELECT pb.id, pb.bill_number, pb.bill_date, s.supplier_name, s.address, s.gstin, pb.total_amount, pb.payment_status
    FROM purchase_bills pb
    JOIN suppliers s ON pb.supplier_id = s.id
    WHERE pb.id = ?
";
if ($stmt_bill = $conn->prepare($sql_bill)) {
    $stmt_bill->bind_param("i", $purchase_id);
    $stmt_bill->execute();
    $result_bill = $stmt_bill->get_result();
    if ($result_bill->num_rows == 1) {
        $purchase_bill = $result_bill->fetch_assoc();
    } else {
        header("location: manage_purchases.php"); // Not found
        exit;
    }
    $stmt_bill->close();
}

// Fetch purchase items
$sql_items = "
    SELECT p.product_name, sb.batch_number, pi.quantity, pi.unit_price, (pi.quantity * pi.unit_price) as item_total
    FROM purchase_items pi
    JOIN products p ON pi.product_id = p.id
    JOIN stock_batches sb ON pi.batch_id = sb.id
    WHERE pi.purchase_bill_id = ?
";
if ($stmt_items = $conn->prepare($sql_items)) {
    $stmt_items->bind_param("i", $purchase_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    while ($row = $result_items->fetch_assoc()) {
        $purchase_items[] = $row;
    }
    $stmt_items->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Purchase Bill - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 16px; line-height: 24px; color: #555; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.item td{ border-bottom: 1px solid #eee; }
        .invoice-box table tr.total td:last-child { border-top: 2px solid #eee; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-3">
             <a href="manage_purchases.php" class="btn btn-secondary">Back to List</a>
             <button onclick="window.print()" class="btn btn-primary">Print Bill</button>
        </div>
        <div class="invoice-box">
            <table>
                <tr class="top">
                    <td colspan="4">
                        <table>
                            <tr>
                                <td class="title"><h2>Purchase Bill</h2></td>
                                <td>
                                    Bill #: <?php echo htmlspecialchars($purchase_bill['bill_number']); ?><br>
                                    Date: <?php echo htmlspecialchars($purchase_bill['bill_date']); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="information">
                    <td colspan="4">
                        <table>
                            <tr>
                                <td>
                                    <strong>Supplier Details:</strong><br>
                                    <?php echo htmlspecialchars($purchase_bill['supplier_name']); ?><br>
                                    <?php echo nl2br(htmlspecialchars($purchase_bill['address'])); ?><br>
                                    GSTIN: <?php echo htmlspecialchars($purchase_bill['gstin']); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="heading">
                    <td>Product</td>
                    <td>Batch</td>
                    <td class="text-center">Quantity</td>
                    <td class="text-end">Unit Price</td>
                    <td class="text-end">Total</td>
                </tr>
                <?php foreach ($purchase_items as $item): ?>
                <tr class="item">
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($item['batch_number']); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-end"><?php echo number_format($item['item_total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="4" class="text-end"><strong>Grand Total</strong></td>
                    <td class="text-end"><strong><?php echo number_format($purchase_bill['total_amount'], 2); ?></strong></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>