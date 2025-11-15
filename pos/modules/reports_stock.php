<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$report_type = $_GET['report_type'] ?? 'current'; // current, low, expiry
$product_filter = $_GET['product_id'] ?? '';
$expiry_days = $_GET['expiry_days'] ?? 90;

$data = [];
$title = "Current Stock Report";

switch ($report_type) {
    case 'low':
        $title = "Low Stock Report";
        $data = get_low_stock_items($mysqli, $product_filter);
        break;
    case 'expiry':
        $title = "Expiring Soon Report (in $expiry_days days)";
        $data = get_expiring_soon_items($mysqli, $expiry_days, $product_filter);
        break;
    case 'current':
    default:
        $query = "SELECT p.product_name, sb.batch_number, sb.expiry_date, sb.current_qty, sb.purchase_price, p.mrp
                  FROM stock_batches sb
                  JOIN products p ON sb.product_id = p.id
                  WHERE sb.current_qty > 0";
        $params = [];
        $types = '';
        if ($product_filter) {
            $query .= " AND p.id = ?";
            $types .= 'i';
            $params[] = $product_filter;
        }
        $query .= " ORDER BY p.product_name, sb.expiry_date ASC";

        $stmt = $mysqli->prepare($query);
        if(!empty($params)){
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        break;
}

// Fetch products for filter
$products = $mysqli->query("SELECT id, product_name FROM products ORDER BY product_name")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Reports</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="content">
        <div class="container">
            <h2><?php echo $title; ?></h2>
            <form method="get" class="form-inline mb-3">
                <select name="report_type" class="form-control mr-2" onchange="this.form.submit()">
                    <option value="current" <?php if($report_type == 'current') echo 'selected'; ?>>Current Stock</option>
                    <option value="low" <?php if($report_type == 'low') echo 'selected'; ?>>Low Stock</option>
                    <option value="expiry" <?php if($report_type == 'expiry') echo 'selected'; ?>>Expiring Soon</option>
                </select>
                <select name="product_id" class="form-control mr-2">
                    <option value="">All Products</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" <?php if($product_filter == $product['id']) echo 'selected'; ?>><?php echo $product['product_name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($report_type == 'expiry'): ?>
                <input type="number" name="expiry_days" value="<?php echo $expiry_days; ?>" class="form-control mr-2" style="width: 100px;">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <?php if($report_type != 'low'): ?>
                        <th>Batch</th>
                        <th>Expiry Date</th>
                        <?php endif; ?>
                        <th>Current Qty</th>
                        <th>Purchase Price</th>
                        <th>MRP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $item): ?>
                    <tr>
                        <td><?php echo $item['product_name']; ?></td>
                        <?php if($report_type != 'low'): ?>
                        <td><?php echo $item['batch_number'] ?? 'N/A'; ?></td>
                        <td><?php echo $item['expiry_date'] ?? 'N/A'; ?></td>
                        <?php endif; ?>
                        <td><?php echo $item['current_qty']; ?></td>
                        <td><?php echo number_format($item['purchase_price'] ?? 0, 2); ?></td>
                        <td><?php echo number_format($item['mrp'] ?? 0, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
