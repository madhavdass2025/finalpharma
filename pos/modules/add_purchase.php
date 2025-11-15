<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

// Fetch suppliers and products for dropdowns
$suppliers = $mysqli->query("SELECT id, supplier_name FROM suppliers ORDER BY supplier_name")->fetch_all(MYSQLI_ASSOC);
$products = $mysqli->query("SELECT id, product_name FROM products ORDER BY product_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Purchase</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="content">
        <div class="container">
            <h2>Add New Purchase</h2>
            <form id="purchase-form" action="process_purchase.php" method="post">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-control" required>
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['supplier_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="invoice_number">Invoice Number</label>
                        <input type="text" name="invoice_number" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="invoice_date">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <hr>
                <h4>Purchase Items</h4>
                <div id="purchase-items">
                    <!-- JS will add item rows here -->
                </div>
                <button type="button" id="add-item" class="btn btn-secondary">Add Item</button>
                <hr>

                <div class="row">
                    <div class="col-md-6 ml-auto">
                         <table class="table">
                            <tr>
                                <th>Total Amount</th>
                                <td><input type="text" id="total-amount" class="form-control" readonly></td>
                            </tr>
                             <tr>
                                <th>Discount</th>
                                <td><input type="number" step="0.01" name="discount" id="discount" class="form-control" value="0"></td>
                            </tr>
                            <tr>
                                <th>Net Amount</th>
                                <td><input type="text" name="net_amount" id="net-amount" class="form-control" readonly></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <button type="submit" class="btn btn-primary">Save Purchase</button>
            </form>
        </div>
    </div>

    <!-- Product Row Template -->
    <template id="item-template">
        <div class="form-row item-row mb-3">
            <div class="col-md-3"><select name="product_id[]" class="form-control product-select" required><option value="">Select Product</option><?php foreach ($products as $product) { echo "<option value='{$product['id']}'>".htmlspecialchars($product['product_name'])."</option>"; } ?></select></div>
            <div class="col-md-2"><input type="text" name="batch_number[]" class="form-control" placeholder="Batch Number" required></div>
            <div class="col-md-2"><input type="date" name="expiry_date[]" class="form-control" required></div>
            <div class="col-md-1"><input type="number" name="quantity[]" class="form-control quantity" placeholder="Qty" required></div>
            <div class="col-md-1"><input type="number" step="0.01" name="purchase_price[]" class="form-control purchase-price" placeholder="Price" required></div>
             <div class="col-md-1"><input type="number" step="0.01" name="tax_rate[]" class="form-control tax-rate" placeholder="Tax %" value="0" required></div>
            <div class="col-md-1"><input type="text" class="form-control item-total" readonly></div>
            <div class="col-md-1"><button type="button" class="btn btn-danger remove-item">X</button></div>
        </div>
    </template>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../assets/js/purchase.js"></script>
</body>
</html>
