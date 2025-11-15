<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_name = $_POST['product_name'];
    $generic_name = $_POST['generic_name'];
    $hsn_code = $_POST['hsn_code'];
    $mrp = $_POST['mrp'];
    $reorder_level = $_POST['reorder_level'];

    $stmt = $mysqli->prepare("INSERT INTO products (product_name, generic_name, hsn_code, mrp, reorder_level) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssidi", $product_name, $generic_name, $hsn_code, $mrp, $reorder_level);

    if ($stmt->execute()) {
        $message = "Product added successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <div class="container">
            <h2>Add New Product</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="add_product.php" method="post">
                <div class="form-group">
                    <label for="product_name">Product Name</label>
                    <input type="text" class="form-control" id="product_name" name="product_name" required>
                </div>
                <div class="form-group">
                    <label for="generic_name">Generic Name</label>
                    <input type="text" class="form-control" id="generic_name" name="generic_name">
                </div>
                <div class="form-group">
                    <label for="hsn_code">HSN Code</label>
                    <input type="text" class="form-control" id="hsn_code" name="hsn_code">
                </div>
                <div class="form-group">
                    <label for="mrp">MRP</label>
                    <input type="number" step="0.01" class="form-control" id="mrp" name="mrp" required>
                </div>
                <div class="form-group">
                    <label for="reorder_level">Reorder Level</label>
                    <input type="number" class="form-control" id="reorder_level" name="reorder_level">
                </div>
                <button type="submit" class="btn btn-primary">Add Product</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
