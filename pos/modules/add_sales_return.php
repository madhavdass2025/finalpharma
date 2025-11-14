<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1, 2]);

$products = [];
$sql_products = "SELECT id, product_name FROM products WHERE is_active = TRUE ORDER BY product_name";
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_return'])) {
    check_csrf();
    $return_date = $_POST['return_date'];
    $sales_invoice_id = !empty($_POST['sales_invoice_id']) ? $_POST['sales_invoice_id'] : null;
    $notes = $_POST['notes'];
    $user_id = $_SESSION['id'];
    $total_amount = 0;
    $conn->begin_transaction();
    try {
        $sql_return = "INSERT INTO sales_returns (return_date, sales_invoice_id, total_amount, notes, user_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_return = $conn->prepare($sql_return);
        $stmt_return->bind_param("sidss", $return_date, $sales_invoice_id, $total_amount, $notes, $user_id);
        $stmt_return->execute();
        $sales_return_id = $stmt_return->insert_id;
        $return_total = 0;
        foreach ($_POST['product_id'] as $key => $product_id) {
            $batch_id = $_POST['batch_id'][$key];
            $quantity = $_POST['quantity'][$key];
            $unit_price = $_POST['unit_price'][$key];
            $item_total = $quantity * $unit_price;
            $return_total += $item_total;
            $sql_update_batch = "UPDATE stock_batches SET current_qty = current_qty + ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update_batch);
            $stmt_update->bind_param("ii", $quantity, $batch_id);
            $stmt_update->execute();
            $sql_item = "INSERT INTO sales_return_items (sales_return_id, product_id, batch_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            $stmt_item->bind_param("iiiid", $sales_return_id, $product_id, $batch_id, $quantity, $unit_price);
            $stmt_item->execute();
            $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, 'IN - Return', ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $stmt_ledger->bind_param("iiii", $product_id, $batch_id, $sales_return_id, $quantity);
            $stmt_ledger->execute();
        }
        $sql_update_total = "UPDATE sales_returns SET total_amount = ? WHERE id = ?";
        $stmt_update_total = $conn->prepare($sql_update_total);
        $stmt_update_total->bind_param("di", $return_total, $sales_return_id);
        $stmt_update_total->execute();
        $conn->commit();
        $success_message = "Sales return processed successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to process return: " . $e->getMessage();
    }
}
$conn->close();
?>

<h1 class="mt-4">Add New Sales Return</h1>
<?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
<?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
<form action="add_sales_return.php" method="post">
    <?php csrf_input(); ?>
    <div class="card mb-4">
        <div class="card-header">Return Details</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3"><label>Return Date</label><input type="date" name="return_date" class="form-control" required></div>
                <div class="col-md-4 mb-3"><label>Original Invoice ID (Optional)</label><input type="number" name="sales_invoice_id" class="form-control"></div>
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