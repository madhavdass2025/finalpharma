<?php
require_once '../includes/Auth.php';
Auth::check_access([1, 2]); // Admins and Billing/Pharmacists

require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: manage_sales.php");
    exit;
}

$invoice_id = $_GET['id'];
$invoice = null;
$invoice_items = [];

// Fetch invoice details
$sql_invoice = "
    SELECT si.id, si.invoice_number, si.invoice_date, si.customer_name, u.name as billed_by, si.net_amount, si.status, p.payment_method
    FROM sales_invoices si
    JOIN users u ON si.user_id = u.id
    LEFT JOIN payments p ON si.id = p.invoice_id
    WHERE si.id = ?
";
if ($stmt_invoice = $conn->prepare($sql_invoice)) {
    $stmt_invoice->bind_param("i", $invoice_id);
    $stmt_invoice->execute();
    $result_invoice = $stmt_invoice->get_result();
    if ($result_invoice->num_rows == 1) {
        $invoice = $result_invoice->fetch_assoc();
    } else {
        header("location: manage_sales.php"); // Not found
        exit;
    }
    $stmt_invoice->close();
}

// Fetch invoice items
$sql_items = "
    SELECT p.product_name, sb.batch_number, ii.quantity, ii.unit_price, (ii.quantity * ii.unit_price) as item_total
    FROM invoice_items ii
    JOIN products p ON ii.product_id = p.id
    JOIN stock_batches sb ON ii.batch_id = sb.id
    WHERE ii.sales_invoice_id = ?
";
if ($stmt_items = $conn->prepare($sql_items)) {
    $stmt_items->bind_param("i", $invoice_id);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    while ($row = $result_items->fetch_assoc()) {
        $invoice_items[] = $row;
    }
    $stmt_items->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Invoice - CPMS</title>
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
             <a href="manage_sales.php" class="btn btn-secondary">Back to List</a>
             <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
        </div>
        <div class="invoice-box">
            <table>
                <tr class="top">
                    <td colspan="4">
                        <table>
                            <tr>
                                <td class="title"><h2>Invoice</h2></td>
                                <td>
                                    Invoice #: <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
                                    Date: <?php echo htmlspecialchars($invoice['invoice_date']); ?>
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
                                    <strong>Billed To:</strong><br>
                                    <?php echo htmlspecialchars($invoice['customer_name']); ?>
                                </td>
                                <td class="text-end">
                                    <strong>Billed By:</strong><br>
                                    <?php echo htmlspecialchars($invoice['billed_by']); ?>
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
                <?php foreach ($invoice_items as $item): ?>
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
                    <td class="text-end"><strong><?php echo number_format($invoice['net_amount'], 2); ?></strong></td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end">Payment Method:</td>
                    <td class="text-end"><?php echo htmlspecialchars($invoice['payment_method']); ?></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>