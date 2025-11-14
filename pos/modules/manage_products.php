<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();
    if (isset($_POST['add_product'])) {
        $sql = "INSERT INTO products (product_name, generic_name, hsn_code, mrp, reorder_level, is_favorite) VALUES (?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
            $stmt->bind_param("sssdis", $_POST['product_name'], $_POST['generic_name'], $_POST['hsn_code'], $_POST['mrp'], $_POST['reorder_level'], $is_favorite);
            $stmt->execute();
        }
    }
    if (isset($_POST['edit_product'])) {
        $sql = "UPDATE products SET product_name = ?, generic_name = ?, hsn_code = ?, mrp = ?, reorder_level = ?, is_favorite = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $is_favorite = isset($_POST['is_favorite']) ? 1 : 0;
            $stmt->bind_param("sssdisi", $_POST['product_name'], $_POST['generic_name'], $_POST['hsn_code'], $_POST['mrp'], $_POST['reorder_level'], $is_favorite, $_POST['product_id']);
            $stmt->execute();
        }
    }
    if (isset($_POST['delete_product'])) {
        $sql = "UPDATE products SET is_active = FALSE WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $_POST['product_id']);
            $stmt->execute();
        }
    }
    header("location: manage_products.php");
    exit;
}

$products = [];
$sql = "SELECT id, product_name, generic_name, hsn_code, mrp, reorder_level, is_favorite FROM products WHERE is_active = TRUE ORDER BY id";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}
$conn->close();
?>

<h1 class="mt-4">Product Management</h1>
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
            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_favorite" value="1" id="add_is_favorite"><label class="form-check-label" for="add_is_favorite">Mark as Fast Access Favorite</label></div>
            <button type="submit" name="add_product" class="btn btn-primary mt-2">Add Product</button>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-header">Product List</div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Name</th><th>Favorite</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo $product['is_favorite'] ? '⭐' : ''; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editProductModal" data-product='<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8'); ?>'>Edit</button>
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
                    <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_favorite" value="1" id="edit_is_favorite"><label class="form-check-label" for="edit_is_favorite">Mark as Fast Access Favorite</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="edit_product" class="btn btn-primary">Save changes</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editProductModal').addEventListener('show.bs.modal', function (event) {
    var product = JSON.parse(event.relatedTarget.getAttribute('data-product'));
    this.querySelector('#edit-product-id').value = product.id;
    this.querySelector('#edit-product-name').value = product.product_name;
    this.querySelector('#edit-generic-name').value = product.generic_name;
    this.querySelector('#edit-hsn-code').value = product.hsn_code;
    this.querySelector('#edit-mrp').value = product.mrp;
    this.querySelector('#edit-reorder-level').value = product.reorder_level;
    this.querySelector('#edit_is_favorite').checked = product.is_favorite == 1;
});
</script>

<?php require_once '../includes/footer.php'; ?>