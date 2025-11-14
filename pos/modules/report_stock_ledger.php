<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';

Auth::check_access([1]);

$products = [];
$sql_products = "SELECT id, product_name FROM products WHERE is_active = TRUE ORDER BY product_name";
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}

$ledger_data = [];
$selected_product = '';
if (isset($_GET['product_id']) && !empty($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $selected_product = $product_id;
    $sql_ledger = "
        SELECT
            sl.transaction_date, sl.transaction_type, sb.batch_number, sl.quantity,
            CASE
                WHEN sl.transaction_type = 'IN' THEN CONCAT('Purchase Bill #', sl.reference_id)
                WHEN sl.transaction_type = 'OUT - Sale' THEN CONCAT('Sales Invoice #', sl.reference_id)
                WHEN sl.transaction_type = 'IN - Return' THEN CONCAT('Sales Return #', sl.reference_id)
                WHEN sl.transaction_type = 'OUT - Return' THEN CONCAT('Purchase Return #', sl.reference_id)
                WHEN sl.transaction_type LIKE 'ADJ%' THEN 'Stock Adjustment'
                ELSE sl.reference_id
            END as reference
        FROM stock_ledger sl
        JOIN stock_batches sb ON sl.batch_id = sb.id
        WHERE sl.product_id = ?
        ORDER BY sl.transaction_date DESC
    ";
    if ($stmt = $conn->prepare($sql_ledger)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ledger_data[] = $row;
        }
    }
}
$conn->close();
?>

<h1 class="mt-4">Stock Ledger Report</h1>
<div class="card mb-4">
    <div class="card-body">
        <form action="report_stock_ledger.php" method="get">
            <div class="row">
                <div class="col-md-4">
                    <label>Select Product</label>
                    <select name="product_id" class="form-control" required>
                        <option value="">-- Choose a Product --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" <?php echo ($selected_product == $product['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label>&nbsp;</label><button type="submit" class="btn btn-primary d-block">View Ledger</button></div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($selected_product)): ?>
<div class="card">
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Date & Time</th><th>Transaction Type</th><th>Batch Number</th><th>Quantity Change</th><th>Reference</th></tr></thead>
            <tbody>
                <?php if (empty($ledger_data)): ?>
                    <tr><td colspan="5" class="text-center">No transaction history found for this product.</td></tr>
                <?php else: ?>
                    <?php foreach ($ledger_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['transaction_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($row['reference']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>