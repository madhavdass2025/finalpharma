<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1]);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_purchase'])) {
    check_csrf();
    $purchase_id = $_POST['purchase_id'];
    $conn->begin_transaction();
    try {
        $sql_check = "SELECT status FROM purchase_bills WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $purchase_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $current_status = $result_check->fetch_assoc()['status'];
        if ($current_status !== 'Completed') {
            throw new Exception("This purchase bill cannot be cancelled.");
        }
        $items = [];
        $sql_items = "SELECT product_id, batch_id, quantity FROM purchase_items WHERE purchase_bill_id = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $purchase_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while($row = $result_items->fetch_assoc()) $items[] = $row;
        foreach ($items as $item) {
            $sql_stock = "UPDATE stock_batches SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?";
            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("ii", $item['quantity'], $item['batch_id'], $item['quantity']);
            $stmt_stock->execute();
            if ($stmt_stock->affected_rows == 0) throw new Exception("Insufficient stock to reverse for batch ID {$item['batch_id']}.");
            $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, 'IN - Cancelled', ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $reversal_qty = -$item['quantity'];
            $stmt_ledger->bind_param("iiii", $item['product_id'], $item['batch_id'], $purchase_id, $reversal_qty);
            $stmt_ledger->execute();
        }
        $sql_update = "UPDATE purchase_bills SET status = 'Cancelled' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $purchase_id);
        $stmt_update->execute();
        $conn->commit();
        $success_message = "Purchase bill #$purchase_id has been cancelled successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to cancel purchase bill: " . $e->getMessage();
    }
}

$purchases = [];
$sql = "SELECT pb.id, pb.bill_number, pb.bill_date, s.supplier_name, pb.total_amount, pb.status FROM purchase_bills pb JOIN suppliers s ON pb.supplier_id = s.id ORDER BY pb.bill_date DESC, pb.id DESC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) $purchases[] = $row;
}
$conn->close();
?>

<h1 class="mt-4">Manage Purchase Bills</h1>
<?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
<?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
<div class="card">
    <div class="card-header"><a href="add_purchase.php" class="btn btn-primary">Add New Purchase Bill</a></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Bill Number</th><th>Bill Date</th><th>Supplier</th><th class="text-end">Total Amount</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['bill_number']); ?></td>
                        <td><?php echo htmlspecialchars($p['bill_date']); ?></td>
                        <td><?php echo htmlspecialchars($p['supplier_name']); ?></td>
                        <td class="text-end"><?php echo number_format($p['total_amount'], 2); ?></td>
                        <td><span class="badge bg-<?php echo $p['status'] == 'Completed' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
                        <td>
                            <a href="view_purchase.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-info">View</a>
                            <?php if ($p['status'] == 'Completed'): ?>
                            <form action="manage_purchases.php" method="post" class="d-inline">
                                <?php csrf_input(); ?>
                                <input type="hidden" name="purchase_id" value="<?php echo $p['id']; ?>">
                                <button type="submit" name="cancel_purchase" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this purchase? This will reverse the stock changes.')">Cancel</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>