<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Admins only

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Fetch suppliers and products for dropdowns
$suppliers = $products = [];
$sql_suppliers = "SELECT id, supplier_name FROM suppliers ORDER BY supplier_name";
$sql_products = "SELECT id, product_name FROM products WHERE is_active = TRUE ORDER BY product_name";

if ($result = $conn->query($sql_suppliers)) {
    while ($row = $result->fetch_assoc()) $suppliers[] = $row;
    $result->free();
}
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
    $result->free();
}

// Form submission logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_purchase'])) {
    check_csrf();
    // Main bill details
    $supplier_id = $_POST['supplier_id'];
    $bill_number = $_POST['bill_number'];
    $bill_date = $_POST['bill_date'];
    $total_amount = 0; // Will be calculated

    // Start transaction
    $conn->begin_transaction();

    try {
        // Step 1: Insert into purchase_bills
        $sql_bill = "INSERT INTO purchase_bills (supplier_id, bill_number, bill_date, total_amount, payment_status) VALUES (?, ?, ?, ?, 'Pending')";
        $stmt_bill = $conn->prepare($sql_bill);
        $stmt_bill->bind_param("issd", $supplier_id, $bill_number, $bill_date, $total_amount);
        $stmt_bill->execute();
        $purchase_bill_id = $stmt_bill->insert_id;
        $stmt_bill->close();

        $bill_total = 0;

        // Step 2 & 3: Loop through items, update stock, and insert into purchase_items
        foreach ($_POST['product_id'] as $key => $product_id) {
            $batch_number = $_POST['batch_number'][$key];
            $mfg_date = $_POST['mfg_date'][$key];
            $expiry_date = $_POST['expiry_date'][$key];
            $quantity = $_POST['quantity'][$key];
            $purchase_price = $_POST['purchase_price'][$key];
            $tax_rate = $_POST['tax_rate'][$key];

            $item_total = $quantity * $purchase_price;
            $bill_total += $item_total;

            // Find or create stock_batch
            $batch_id = null;
            $sql_find_batch = "SELECT id FROM stock_batches WHERE product_id = ? AND batch_number = ?";
            $stmt_find = $conn->prepare($sql_find_batch);
            $stmt_find->bind_param("is", $product_id, $batch_number);
            $stmt_find->execute();
            $result_find = $stmt_find->get_result();
            if ($row = $result_find->fetch_assoc()) {
                // Batch exists, update quantity
                $batch_id = $row['id'];
                $sql_update_batch = "UPDATE stock_batches SET current_qty = current_qty + ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update_batch);
                $stmt_update->bind_param("ii", $quantity, $batch_id);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                // Batch does not exist, insert new
                $sql_insert_batch = "INSERT INTO stock_batches (product_id, batch_number, mfg_date, expiry_date, current_qty, purchase_price, tax_rate) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert_batch);
                $stmt_insert->bind_param("isssidd", $product_id, $batch_number, $mfg_date, $expiry_date, $quantity, $purchase_price, $tax_rate);
                $stmt_insert->execute();
                $batch_id = $stmt_insert->insert_id;
                $stmt_insert->close();
            }
            $stmt_find->close();

            // Insert into purchase_items
            $sql_item = "INSERT INTO purchase_items (purchase_bill_id, product_id, batch_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
            $stmt_item = $conn->prepare($sql_item);
            $stmt_item->bind_param("iiiid", $purchase_bill_id, $product_id, $batch_id, $quantity, $purchase_price);
            $stmt_item->execute();
            $stmt_item->close();

            // Insert into stock_ledger
            $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, 'IN', ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $stmt_ledger->bind_param("iiii", $product_id, $batch_id, $purchase_bill_id, $quantity);
            $stmt_ledger->execute();
            $stmt_ledger->close();
        }

        // Update total amount in purchase_bills
        $sql_update_total = "UPDATE purchase_bills SET total_amount = ? WHERE id = ?";
        $stmt_update_total = $conn->prepare($sql_update_total);
        $stmt_update_total->bind_param("di", $bill_total, $purchase_bill_id);
        $stmt_update_total->execute();
        $stmt_update_total->close();

        // If all good, commit
        $conn->commit();
        $success_message = "Purchase bill added successfully!";

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to add purchase bill: " . $e->getMessage();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Purchase Bill - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Add New Purchase Bill</h2>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form action="add_purchase.php" method="post">
        <?php csrf_input(); ?>
        <!-- Bill Details -->
        <div class="card mb-4">
            <div class="card-header">Bill Details</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control" required>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Supplier Bill Number</label>
                        <input type="text" name="bill_number" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Bill Date</label>
                        <input type="date" name="bill_date" class="form-control" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Items -->
        <div class="card">
            <div class="card-header">Products</div>
            <div class="card-body" id="items-container">
                <!-- Dynamic items will be added here -->
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-info" id="add-item-btn">Add Item</button>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" name="save_purchase" class="btn btn-primary">Save Purchase Bill</button>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </form>
</div>

<script>
document.getElementById('add-item-btn').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const itemIndex = container.children.length;
    const itemHtml = `
        <div class="row item-row mb-3">
            <div class="col-md-2">
                <select name="product_id[]" class="form-control" required>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['product_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="text" name="batch_number[]" class="form-control" placeholder="Batch No." required></div>
            <div class="col-md-2"><input type="date" name="mfg_date[]" class="form-control"></div>
            <div class="col-md-2"><input type="date" name="expiry_date[]" class="form-control"></div>
            <div class="col-md-1"><input type="number" name="quantity[]" class="form-control" placeholder="Qty" required></div>
            <div class="col-md-1"><input type="number" step="0.01" name="purchase_price[]" class="form-control" placeholder="Price" required></div>
            <div class="col-md-1"><input type="number" step="0.01" name="tax_rate[]" class="form-control" placeholder="Tax %"></div>
            <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.item-row').remove()">Remove</button></div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', itemHtml);
});
</script>

</body>
</html>