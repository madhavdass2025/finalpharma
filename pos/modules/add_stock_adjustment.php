<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1]);

$products = [];
$sql_products = "SELECT id, product_name FROM products WHERE is_active = TRUE ORDER BY product_name";
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_adjustment'])) {
    check_csrf();
    $product_id = $_POST['product_id'];
    $batch_id = $_POST['batch_id'];
    $adjustment_type = $_POST['adjustment_type'];
    $quantity = (int)$_POST['quantity'];
    $reason = $_POST['reason'];

    if ($quantity <= 0) {
        $error_message = "Quantity must be a positive number.";
    } else {
        $conn->begin_transaction();
        try {
            $current_qty = 0;
            $sql_check = "SELECT current_qty FROM stock_batches WHERE id = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("i", $batch_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if($row = $result_check->fetch_assoc()) $current_qty = $row['current_qty'];

            $ledger_quantity = 0;
            if ($adjustment_type == 'IN') {
                $sql_stock = "UPDATE stock_batches SET current_qty = current_qty + ? WHERE id = ?";
                $ledger_quantity = $quantity;
            } else {
                if ($quantity > $current_qty) throw new Exception("Cannot adjust quantity below zero.");
                $sql_stock = "UPDATE stock_batches SET current_qty = current_qty - ? WHERE id = ?";
                $ledger_quantity = -$quantity;
            }

            $stmt_stock = $conn->prepare($sql_stock);
            $stmt_stock->bind_param("ii", $quantity, $batch_id);
            $stmt_stock->execute();

            $transaction_type = "ADJ - " . $adjustment_type;
            $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, ?, NULL, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $stmt_ledger->bind_param("iisi", $product_id, $batch_id, $transaction_type, $ledger_quantity);
            $stmt_ledger->execute();

            $conn->commit();
            $success_message = "Stock adjusted successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to adjust stock: " . $e->getMessage();
        }
    }
}
$conn->close();
?>

<h1 class="mt-4">Stock Adjustment</h1>
<?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
<?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
<div class="card">
    <div class="card-header">Create New Stock Adjustment</div>
    <div class="card-body">
        <form action="add_stock_adjustment.php" method="post" id="adjustment-form">
            <?php csrf_input(); ?>
            <div class="row">
                <div class="col-md-4 mb-3"><label>Product</label><select name="product_id" id="product-select" class="form-control" required><option value="">-- Select Product --</option><?php foreach ($products as $product) echo "<option value='{$product['id']}'>".htmlspecialchars($product['product_name'])."</option>"; ?></select></div>
                <div class="col-md-4 mb-3"><label>Batch</label><select name="batch_id" id="batch-select" class="form-control" required></select></div>
                <div class="col-md-4 mb-3"><label>Adjustment Type</label><select name="adjustment_type" class="form-control" required><option value="IN">Add Stock (IN)</option><option value="OUT">Remove Stock (OUT)</option></select></div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3"><label>Quantity to Adjust</label><input type="number" name="quantity" class="form-control" required></div>
                <div class="col-md-8 mb-3"><label>Reason for Adjustment</label><input type="text" name="reason" class="form-control" placeholder="e.g., Physical count correction, Expired stock" required></div>
            </div>
            <button type="submit" name="save_adjustment" class="btn btn-primary">Apply Adjustment</button>
        </form>
    </div>
</div>

<script>
document.getElementById('product-select').addEventListener('change', async function() {
    const productId = this.value;
    const batchSelect = document.getElementById('batch-select');
    batchSelect.innerHTML = '<option value="">Loading...</option>';
    if (!productId) {
        batchSelect.innerHTML = '<option value="">-- Select Product First --</option>';
        return;
    }
    try {
        const response = await fetch(`api_get_product_batches.php?product_id=${productId}`);
        const result = await response.json();
        if (result.status === 'success') {
            let options = '<option value="">-- Select Batch --</option>';
            result.data.forEach(batch => {
                options += `<option value="${batch.id}">${batch.batch_number} (Current Qty: ${batch.current_qty})</option>`;
            });
            batchSelect.innerHTML = options;
        } else {
            batchSelect.innerHTML = '<option value="">No batches found</option>';
        }
    } catch (error) {
        console.error('Failed to fetch batches:', error);
        batchSelect.innerHTML = '<option value="">Error loading batches</option>';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>