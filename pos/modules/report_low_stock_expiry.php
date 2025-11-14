<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';

Auth::check_access([1, 2]);

$low_stock_items = [];
$sql_low_stock = "
    SELECT p.product_name, p.reorder_level, SUM(sb.current_qty) as total_stock
    FROM products p
    JOIN stock_batches sb ON p.id = sb.product_id
    GROUP BY p.id, p.product_name, p.reorder_level
    HAVING total_stock <= p.reorder_level
";
if ($result = $conn->query($sql_low_stock)) {
    while ($row = $result->fetch_assoc()) $low_stock_items[] = $row;
}

$expiring_items = [];
$sql_expiring = "
    SELECT p.product_name, sb.batch_number, sb.expiry_date, sb.current_qty
    FROM stock_batches sb
    JOIN products p ON sb.product_id = p.id
    WHERE sb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
    ORDER BY sb.expiry_date ASC
";
if ($result = $conn->query($sql_expiring)) {
    while ($row = $result->fetch_assoc()) $expiring_items[] = $row;
}
$conn->close();
?>

<h1 class="mt-4">Low Stock & Expiry Report</h1>
<div class="card mb-4">
    <div class="card-header bg-warning"><h4>Low Stock Items</h4></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Product Name</th><th>Reorder Level</th><th>Total Stock Available</th></tr></thead>
            <tbody>
                <?php if (empty($low_stock_items)): ?>
                    <tr><td colspan="3" class="text-center">No items are currently low on stock.</td></tr>
                <?php else: ?>
                    <?php foreach ($low_stock_items as $item): ?>
                        <tr><td><?php echo htmlspecialchars($item['product_name']); ?></td><td><?php echo htmlspecialchars($item['reorder_level']); ?></td><td><?php echo htmlspecialchars($item['total_stock']); ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="card">
    <div class="card-header bg-danger text-white"><h4>Items Expiring Soon (Next 90 Days)</h4></div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Product Name</th><th>Batch Number</th><th>Expiry Date</th><th>Quantity in Batch</th></tr></thead>
            <tbody>
                 <?php if (empty($expiring_items)): ?>
                    <tr><td colspan="4" class="text-center">No items are expiring in the next 90 days.</td></tr>
                <?php else: ?>
                    <?php foreach ($expiring_items as $item): ?>
                        <tr><td><?php echo htmlspecialchars($item['product_name']); ?></td><td><?php echo htmlspecialchars($item['batch_number']); ?></td><td><?php echo htmlspecialchars($item['expiry_date']); ?></td><td><?php echo htmlspecialchars($item['current_qty']); ?></td></tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>