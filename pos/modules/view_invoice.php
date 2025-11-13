<?php
require_once '../includes/Auth.php';
Auth::check_access([1, 2]);

require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: manage_sales.php");
    exit;
}
$invoice_id = $_GET['id'];

// Fetch invoice and customer details
$invoice = null;
$sql_invoice = "
    SELECT si.*, u.name as billed_by, p.payment_method, c.name as reg_customer_name, c.reg_no, c.address as customer_address
    FROM sales_invoices si
    JOIN users u ON si.user_id = u.id
    LEFT JOIN payments p ON si.id = p.invoice_id
    LEFT JOIN customers c ON si.customer_id = c.id
    WHERE si.id = ?
";
if ($stmt_invoice = $conn->prepare($sql_invoice)) {
    $stmt_invoice->bind_param("i", $invoice_id);
    $stmt_invoice->execute();
    $result_invoice = $stmt_invoice->get_result();
    if ($result_invoice->num_rows == 1) {
        $invoice = $result_invoice->fetch_assoc();
    } else {
        exit("Invoice not found.");
    }
}

// Fetch invoice items
$invoice_items = [];
// ... (rest of the item fetching logic is the same)
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<!-- ... head section with styles ... -->
<body>
    <div class="container mt-4">
        <!-- ... header and print button ... -->
        <div class="invoice-box">
            <table>
                <!-- ... top section of invoice ... -->
                <tr class="information">
                    <td colspan="4">
                        <table>
                            <tr>
                                <td>
                                    <strong>Billed To:</strong><br>
                                    <?php if ($invoice['customer_id']): ?>
                                        <strong><?php echo htmlspecialchars($invoice['reg_customer_name']); ?></strong><br>
                                        Reg. No: <?php echo htmlspecialchars($invoice['reg_no']); ?><br>
                                        <?php echo nl2br(htmlspecialchars($invoice['customer_address'])); ?>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($invoice['customer_name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <strong>Billed By:</strong><br>
                                    <?php echo htmlspecialchars($invoice['billed_by']); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <!-- ... rest of the invoice table ... -->
            </table>
        </div>
    </div>
</body>
</html>