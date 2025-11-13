<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Admins only

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Handle POST requests for Add, Edit, Delete
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();

    // Add Product
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['product_name']);
        $generic = trim($_POST['generic_name']);
        $hsn = trim($_POST['hsn_code']);
        $mrp = trim($_POST['mrp']);
        $reorder = trim($_POST['reorder_level']);
        $sql = "INSERT INTO products (product_name, generic_name, hsn_code, mrp, reorder_level) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssdi", $name, $generic, $hsn, $mrp, $reorder);
            $stmt->execute();
        }
    }

    // Edit Product
    if (isset($_POST['edit_product'])) {
        $id = $_POST['product_id'];
        $name = trim($_POST['product_name']);
        $generic = trim($_POST['generic_name']);
        $hsn = trim($_POST['hsn_code']);
        $mrp = trim($_POST['mrp']);
        $reorder = trim($_POST['reorder_level']);
        $sql = "UPDATE products SET product_name = ?, generic_name = ?, hsn_code = ?, mrp = ?, reorder_level = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssdis", $name, $generic, $hsn, $mrp, $reorder, $id);
            $stmt->execute();
        }
    }

    // Delete Product (Soft Delete by setting is_active = false)
    if (isset($_POST['delete_product'])) {
        $id = $_POST['product_id'];
        $sql = "UPDATE products SET is_active = FALSE WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }
    header("location: manage_products.php");
    exit;
}

// Fetch all active products
$products = [];
$sql = "SELECT id, product_name, generic_name, hsn_code, mrp, reorder_level FROM products WHERE is_active = TRUE ORDER BY id";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Product Management</h2>
    <div class="card mb-4">
        <div class="card-header">Add New Product</div>
        <div class="card-body">
            <form action="manage_products.php" method="post">
                <?php csrf_input(); ?>
                <div class="row">
                    <div class="col-md-3"><input type="text" name="product_name" class="form-control" placeholder="Product Name" required></div>
                    <div class="col-md-3"><input type="text" name="generic_name" class="form-control" placeholder="Generic Name"></div>
                    <div class="col-md-2"><input type="text" name="hsn_code" class="form-control" placeholder="HSN Code"></div>
                    <div class="col-md-2"><input type="number" step="0.01" name="mrp" class="form-control" placeholder="MRP" required></div>
                    <div class="col-md-2"><input type="number" name="reorder_level" class="form-control" placeholder="Reorder Level" required></div>
                </div>
                <button type="submit" name="add_product" class="btn btn-primary mt-2">Add Product</button>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Product List</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead><tr><th>ID</th><th>Name</th><th>Generic</th><th>HSN</th><th>MRP</th><th>Reorder</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['generic_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['hsn_code']); ?></td>
                            <td><?php echo htmlspecialchars($product['mrp']); ?></td>
                            <td><?php echo htmlspecialchars($product['reorder_level']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editProductModal" data-product='<?php echo json_encode($product); ?>'>Edit</button>
                                <form action="manage_products.php" method="post" class="d-inline">
                                    <?php csrf_input(); ?>
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="manage_products.php" method="post">
                <div class="modal-body">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="product_id" id="edit-product-id">
                    <div class="row">
                        <div class="col-md-6 mb-3"><label>Product Name</label><input type="text" name="product_name" id="edit-product-name" class="form-control" required></div>
                        <div class="col-md-6 mb-3"><label>Generic Name</label><input type="text" name="generic_name" id="edit-generic-name" class="form-control"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label>HSN Code</label><input type="text" name="hsn_code" id="edit-hsn-code" class="form-control"></div>
                        <div class="col-md-4 mb-3"><label>MRP</label><input type="number" step="0.01" name="mrp" id="edit-mrp" class="form-control" required></div>
                        <div class="col-md-4 mb-3"><label>Reorder Level</label><input type="number" name="reorder_level" id="edit-reorder-level" class="form-control" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_product" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('editProductModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var product = JSON.parse(button.getAttribute('data-product'));
    var modal = this;
    modal.querySelector('#edit-product-id').value = product.id;
    modal.querySelector('#edit-product-name').value = product.product_name;
    modal.querySelector('#edit-generic-name').value = product.generic_name;
    modal.querySelector('#edit-hsn-code').value = product.hsn_code;
    modal.querySelector('#edit-mrp').value = product.mrp;
    modal.querySelector('#edit-reorder-level').value = product.reorder_level;
});
</script>
</body>
</html>