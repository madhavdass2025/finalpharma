<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    header('Location: manage_sales.php');
    exit();
}

// Fetch Invoice Header
$stmt = $mysqli->prepare("SELECT si.*, c.customer_name, c.customer_id as cid, u.name as user_name FROM sales_invoices si LEFT JOIN customers c ON si.customer_id = c.id JOIN users u ON si.user_id = u.id WHERE si.id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    die("Invoice not found.");
}

// Fetch Invoice Items
$stmt = $mysqli->prepare("SELECT ii.*, p.product_name, sb.batch_number FROM invoice_items ii JOIN products p ON ii.product_id = p.id JOIN stock_batches sb ON ii.batch_id = sb.id WHERE ii.invoice_id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch Payments
$stmt = $mysqli->prepare("SELECT * FROM payments WHERE invoice_id = ?");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Invoice</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, .15); font-size: 16px; line-height: 24px; font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; color: #555; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr.top table td { padding-bottom: 20px; }
        .invoice-box table tr.information table td { padding-bottom: 40px; }
        .invoice-box table tr.heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .invoice-box table tr.item td{ border-bottom: 1px solid #eee; }
        .invoice-box table tr.total td:nth-child(2) { border-top: 2px solid #eee; font-weight: bold; }
        @media print { body, .invoice-box { border: 0; margin: 0; padding: 0; box-shadow: none;} .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="no-print mb-3">
             <a href="manage_sales.php" class="btn btn-secondary">Back to Sales</a>
             <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
        </div>

        <div class="invoice-box">
            <table>
                <tr class="top">
                    <td colspan="4">
                        <table>
                            <tr>
                                <td class="title"><h2>Clinic Pharmacy</h2></td>
                                <td>
                                    Invoice #: <?php echo htmlspecialchars($invoice['invoice_number']); ?><br>
                                    Date: <?php echo $invoice['invoice_date']; ?><br>
                                    Billed by: <?php echo htmlspecialchars($invoice['user_name']); ?>
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
                                    <strong>Customer:</strong><br>
                                    <?php echo htmlspecialchars($invoice['customer_name'] ?? 'Walk-in Customer'); ?><br>
                                    <?php if(!empty($invoice['cid'])) echo "ID: " . htmlspecialchars($invoice['cid']); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr class="heading">
                    <td>Product</td>
                    <td style="text-align:center;">Qty</td>
                    <td style="text-align:center;">Price</td>
                    <td style="text-align:right;">Total</td>
                </tr>
                <?php foreach ($items as $item): ?>
                <tr class="item">
                    <td><?php echo htmlspecialchars($item['product_name']); ?> (Batch: <?php echo htmlspecialchars($item['batch_number']); ?>)</td>
                    <td style="text-align:center;"><?php echo $item['quantity']; ?></td>
                    <td style="text-align:center;"><?php echo number_format($item['price'], 2); ?></td>
                    <td style="text-align:right;"><?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                 <tr class="total">
                    <td colspan="3" style="text-align:right;"><strong>Net Amount:</strong></td>
                    <td style="text-align:right;"><?php echo number_format($invoice['net_amount'], 2); ?></td>
                </tr>
                 <tr class="heading">
                    <td colspan="2">Payment Method</td>
                    <td colspan="2" style="text-align:right;">Amount Paid</td>
                </tr>
                 <?php foreach ($payments as $payment): ?>
                <tr class="item">
                    <td colspan="2"><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                    <td colspan="2" style="text-align:right;"><?php echo number_format($payment['amount_paid'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
