<?php
require_once '../includes/Auth.php';
Auth::check_access([1, 2]); // Admins and Billing/Pharmacists

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Handle Cancel Invoice request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_invoice'])) {
    check_csrf();
    $invoice_id = $_POST['invoice_id'];

    $conn->begin_transaction();
    try {
        // 1. Check if the invoice is already cancelled
        $sql_check = "SELECT status FROM sales_invoices WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $invoice_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $current_status = $result_check->fetch_assoc()['status'];

        if ($current_status !== 'Completed') {
            throw new Exception("This invoice cannot be cancelled.");
        }

        // 2. Get all items from the invoice
        $items = [];
        $sql_items = "SELECT product_id, batch_id, quantity FROM invoice_items WHERE sales_invoice_id = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $invoice_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while($row = $result_items->fetch_assoc()) $items[] = $row;

        // 3. Reverse stock for each item
        foreach ($items as $item) {
            // Increment stock in stock_batches
            $sql_stock = "UPDATE stock_batches SET current_qty = current_qty + ? WHERE id = ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("ii", $item['quantity'], $item['batch_id']);
            $stmt_stock->execute();

            // Add reversal to stock_ledger
            $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, 'OUT - Cancelled', ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $stmt_ledger->bind_param("iiii", $item['product_id'], $item['batch_id'], $invoice_id, $item['quantity']);
            $stmt_ledger->execute();
        }

        // 4. Update the invoice status to 'Cancelled'
        $sql_update = "UPDATE sales_invoices SET status = 'Cancelled' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $invoice_id);
        $stmt_update->execute();

        // Note: Reversing payment is a complex accounting process not handled here.
        // This implementation focuses on correcting the stock levels.

        $conn->commit();
        $success_message = "Invoice #$invoice_id has been cancelled successfully.";

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to cancel invoice: " . $e->getMessage();
    }
}

// Fetch all sales invoices
$sales = [];
$sql = "SELECT si.id, si.invoice_number, si.invoice_date, si.customer_name, u.name as billed_by, si.net_amount, si.status FROM sales_invoices si JOIN users u ON si.user_id = u.id ORDER BY si.invoice_date DESC, si.id DESC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) $sales[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Sales Invoices - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Manage Sales Invoices</h2>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
    <?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

    <div class="card">
        <div class="card-header"><a href="pos.php" class="btn btn-primary">Create New Invoice (POS)</a></div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead><tr><th>ID</th><th>Invoice #</th><th>Date</th><th>Customer</th><th>Billed By</th><th class="text-end">Amount</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?php echo $sale['id']; ?></td>
                            <td><?php echo htmlspecialchars($sale['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($sale['invoice_date']); ?></td>
                            <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($sale['billed_by']); ?></td>
                            <td class="text-end"><?php echo number_format($sale['net_amount'], 2); ?></td>
                            <td><span class="badge bg-<?php echo $sale['status'] == 'Completed' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($sale['status']); ?></span></td>
                            <td>
                                <a href="view_invoice.php?id=<?php echo $sale['id']; ?>" class="btn btn-sm btn-info">View</a>
                                <?php if ($sale['status'] == 'Completed'): ?>
                                <form action="manage_sales.php" method="post" class="d-inline">
                                    <?php csrf_input(); ?>
                                    <input type="hidden" name="invoice_id" value="<?php echo $sale['id']; ?>">
                                    <button type="submit" name="cancel_invoice" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this invoice? This will reverse stock changes.')">Cancel</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>