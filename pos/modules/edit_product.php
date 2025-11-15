<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header('Location: manage_products.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $generic_name = $_POST['generic_name'];
    $hsn_code = $_POST['hsn_code'];
    $mrp = $_POST['mrp'];
    $reorder_level = $_POST['reorder_level'];

    $stmt = $mysqli->prepare("UPDATE products SET product_name = ?, generic_name = ?, hsn_code = ?, mrp = ?, reorder_level = ? WHERE id = ?");
    $stmt->bind_param("sssdii", $product_name, $generic_name, $hsn_code, $mrp, $reorder_level, $product_id);

    if ($stmt->execute()) {
        $message = "Product updated successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch product data
$product = null;
$stmt = $mysqli->prepare("SELECT product_name, generic_name, hsn_code, mrp, reorder_level FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
}
$stmt->close();

if (!$product) {
    echo "Product not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <div class="container">
            <h2>Edit Product</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="edit_product.php?id=<?php echo $product_id; ?>" method="post">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="generic_name">Generic Name</label>
                    <input type="text" class="form-control" id="generic_name" name="generic_name" value="<?php echo htmlspecialchars($product['generic_name']); ?>">
                </div>
                <div class="form-group">
                    <label for="hsn_code">HSN Code</label>
                    <input type="text" class="form-control" id="hsn_code" name="hsn_code" value="<?php echo htmlspecialchars($product['hsn_code']); ?>">
                </div>
                <div class="form-group">
                    <label for="mrp">MRP</label>
                    <input type="number" step="0.01" class="form-control" id="mrp" name="mrp" value="<?php echo htmlspecialchars($product['mrp']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="reorder_level">Reorder Level</label>
                    <input type="number" class="form-control" id="reorder_level" name="reorder_level" value="<?php echo htmlspecialchars($product['reorder_level']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update Product</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
