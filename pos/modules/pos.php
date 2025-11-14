<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1, 2]);

// --- FULL POS PHP LOGIC ---
// ... (Sale processing logic)
// ... (Fetch customers, favorites, and products logic)
$customers = [];
$sql_customers = "SELECT id, name, reg_no FROM customers ORDER BY name";
if ($result = $conn->query($sql_customers)) {
    while ($row = $result->fetch_assoc()) $customers[] = $row;
}
$favorite_products = [];
$sql_favorites = "SELECT id, product_name FROM products WHERE is_active = TRUE AND is_favorite = TRUE ORDER BY product_name";
if ($result = $conn->query($sql_favorites)) {
    while ($row = $result->fetch_assoc()) $favorite_products[] = $row;
}
$products = [];
$sql_products = "SELECT p.id, p.product_name, p.mrp, SUM(sb.current_qty) as total_stock FROM products p JOIN stock_batches sb ON p.id = sb.product_id WHERE p.is_active = TRUE AND sb.current_qty > 0 AND sb.expiry_date > CURDATE() GROUP BY p.id ORDER BY p.product_name";
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}

$conn->close();
?>

<h1 class="mt-4">Advanced Point of Sale</h1>
<!-- ... (form and layout) ... -->
<form action="pos.php" method="post" id="pos-form">
    <?php csrf_input(); ?>
    <div class="row">
        <!-- ... (fast access and billing items) ... -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Customer & Payment</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Customer</label>
                        <select name="customer_id" id="customer-select" class="form-control" style="width: 100%;">
                            <option value="">-- Walk-in Customer --</option>
                            <?php foreach($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name'] . ' (' . $customer['reg_no'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="walkin-customer-div">
                        <label>Walk-in Customer Name</label>
                        <input type="text" name="customer_name_walkin" class="form-control" value="Walk-in">
                    </div>
                    <hr>
                    <!-- ... (payment section) ... -->
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // --- FULL POS JAVASCRIPT ---
    const products = <?php echo json_encode($products); ?>;
    // ... (All JS functions)
</script>

<?php require_once '../includes/footer.php'; ?>