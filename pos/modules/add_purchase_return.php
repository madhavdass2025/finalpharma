<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1]);

$suppliers = $products = [];
$sql_suppliers = "SELECT id, supplier_name FROM suppliers ORDER BY supplier_name";
$sql_products = "SELECT id, product_name FROM products WHERE is_active = TRUE ORDER BY product_name";

if ($result = $conn->query($sql_suppliers)) {
    while ($row = $result->fetch_assoc()) $suppliers[] = $row;
}
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_return'])) {
    check_csrf();
    $supplier_id = $_POST['supplier_id'];
    $return_date = $_POST['return_date'];
    $notes = $_POST['notes'];
    $total_amount = 0;
    $conn->begin_transaction();
    try {
        $sql_return = "INSERT INTO purchase_returns (supplier_id, return_date, total_amount, notes) VALUES (?, ?, ?, ?)";
        $stmt_return = $conn->prepare($sql_return);
        $stmt_return->bind_param("isds", $supplier_id, $return_date, $total_amount, $notes);
        $stmt_return->execute();
        $purchase_return_id = $stmt_return->insert_id;
        $return_total = 0;
        foreach ($_POST['product_id'] as $key => $product_id) {
            $batch_id = $_POST['batch_id'][$key];
            $quantity = $_POST['quantity'][$key];
            $unit_price = $_POST['unit_price'][$key];
            $item_total = $quantity * $unit_price;
            $return_total += $item_total;
            $sql_update_batch = "UPDATE stock_batches SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?";
            $stmt_update = $conn->prepare($sql_update_batch);
            $stmt_update->bind_param("iii", $quantity, $batch_id, $quantity);
            $stmt_update->execute();
            if ($stmt_update->affected_rows == 0) throw new Exception("Insufficient stock for product ID $product_id, batch ID $batch_id.");
            $sql_item = "INSERT INTO purchase_return_items (purchase_return_id, product_id, batch_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            $stmt_item->bind_param("iiiid", $purchase_return_id, $product_id, $batch_id, $quantity, $unit_price);
            $stmt_item->execute();
            $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, 'OUT - Return', ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $return_qty_negative = -$quantity;
            $stmt_ledger->bind_param("iiii", $product_id, $batch_id, $purchase_return_id, $return_qty_negative);
            $stmt_ledger->execute();
        }
        $sql_update_total = "UPDATE purchase_returns SET total_amount = ? WHERE id = ?";
        $stmt_update_total = $conn->prepare($sql_update_total);
        $stmt_update_total->bind_param("di", $return_total, $purchase_return_id);
        $stmt_update_total->execute();
        $conn->commit();
        $success_message = "Purchase return processed successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to process return: " . $e->getMessage();
    }
}
$conn->close();
?>

<h1 class="mt-4">Add New Purchase Return</h1>
<?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
<?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
<form action="add_purchase_return.php" method="post">
    <?php csrf_input(); ?>
    <div class="card mb-4">
        <div class="card-header">Return Details</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label>Supplier</label><select name="supplier_id" class="form-control" required><?php foreach ($suppliers as $supplier) echo "<option value='{$supplier['id']}'>".htmlspecialchars($supplier['supplier_name'])."</option>"; ?></select></div>
                <div class="col-md-4 mb-3"><label>Return Date</label><input type="date" name="return_date" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Products to Return</div>
        <div class="card-body" id="items-container"></div>
        <div class="card-footer"><button type="button" class="btn btn-info" id="add-item-btn">Add Item</button></div>
    </div>
    <div class="mt-3"><button type="submit" name="save_return" class="btn btn-primary">Process Return</button></div>
</form>

<script>
document.getElementById('add-item-btn').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const itemHtml = `
        <div class="row item-row mb-3">
            <div class="col-md-3"><select name="product_id[]" class="form-control product-select" required><option value="">-- Select Product --</option><?php foreach ($products as $product) echo "<option value='{$product['id']}'>".htmlspecialchars($product['product_name'])."</option>"; ?></select></div>
            <div class="col-md-3"><select name="batch_id[]" class="form-control batch-select" required><option value="">-- Select Batch --</option></select></div>
            <div class="col-md-2"><input type="number" name="quantity[]" class="form-control" placeholder="Qty" required></div>
            <div class="col-md-2"><input type="number" step="0.01" name="unit_price[]" class="form-control" placeholder="Price" required></div>
            <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.item-row').remove()">Remove</button></div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', itemHtml);
});
document.getElementById('items-container').addEventListener('change', async function(e) {
    if (e.target && e.target.classList.contains('product-select')) {
        const productId = e.target.value;
        const batchSelect = e.target.closest('.item-row').querySelector('.batch-select');
        batchSelect.innerHTML = '<option value="">Loading...</option>';
        if (!productId) {
            batchSelect.innerHTML = '<option value="">-- Select Batch --</option>';
            return;
        }
        try {
            const response = await fetch(`api_get_product_batches.php?product_id=${productId}`);
            const result = await response.json();
            if (result.status === 'success') {
                let options = '<option value="">-- Select Batch --</option>';
                result.data.forEach(batch => {
                    options += `<option value="${batch.id}">${batch.batch_number} (Qty: ${batch.current_qty})</option>`;
                });
                batchSelect.innerHTML = options;
            } else {
                batchSelect.innerHTML = '<option value="">No batches found</option>';
            }
        } catch (error) {
            console.error('Failed to fetch batches:', error);
            batchSelect.innerHTML = '<option value="">Error loading</option>';
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>