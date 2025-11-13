<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Admins only

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Handle POST requests for Add, Edit, Delete
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();

    // Add Supplier
    if (isset($_POST['add_supplier'])) {
        $name = trim($_POST['supplier_name']);
        $phone = trim($_POST['phone']);
        $gstin = trim($_POST['gstin']);
        $address = trim($_POST['address']);
        $sql = "INSERT INTO suppliers (supplier_name, phone, gstin, address) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $name, $phone, $gstin, $address);
            $stmt->execute();
        }
    }

    // Edit Supplier
    if (isset($_POST['edit_supplier'])) {
        $id = $_POST['supplier_id'];
        $name = trim($_POST['supplier_name']);
        $phone = trim($_POST['phone']);
        $gstin = trim($_POST['gstin']);
        $address = trim($_POST['address']);
        $sql = "UPDATE suppliers SET supplier_name = ?, phone = ?, gstin = ?, address = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssi", $name, $phone, $gstin, $address, $id);
            $stmt->execute();
        }
    }

    // Delete Supplier
    if (isset($_POST['delete_supplier'])) {
        $id = $_POST['supplier_id'];
        $sql = "DELETE FROM suppliers WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }
    header("location: manage_suppliers.php");
    exit;
}

// Fetch all suppliers
$suppliers = [];
$sql = "SELECT id, supplier_name, phone, gstin, address FROM suppliers ORDER BY id";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) $suppliers[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Suppliers - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Supplier Management</h2>
    <div class="card mb-4">
        <div class="card-header">Add New Supplier</div>
        <div class="card-body">
            <form action="manage_suppliers.php" method="post">
                <?php csrf_input(); ?>
                <div class="row">
                    <div class="col-md-3"><input type="text" name="supplier_name" class="form-control" placeholder="Supplier Name" required></div>
                    <div class="col-md-2"><input type="text" name="phone" class="form-control" placeholder="Phone"></div>
                    <div class="col-md-2"><input type="text" name="gstin" class="form-control" placeholder="GSTIN"></div>
                    <div class="col-md-3"><input type="text" name="address" class="form-control" placeholder="Address"></div>
                    <div class="col-md-2"><button type="submit" name="add_supplier" class="btn btn-primary w-100">Add Supplier</button></div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Existing Suppliers</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>GSTIN</th><th>Address</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?php echo $supplier['id']; ?></td>
                            <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['gstin']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editSupplierModal" data-supplier='<?php echo json_encode($supplier); ?>'>Edit</button>
                                <form action="manage_suppliers.php" method="post" class="d-inline">
                                    <?php csrf_input(); ?>
                                    <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                    <button type="submit" name="delete_supplier" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
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

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_suppliers.php" method="post">
                <div class="modal-body">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="supplier_id" id="edit-supplier-id">
                    <div class="mb-3"><label>Name</label><input type="text" name="supplier_name" id="edit-name" class="form-control" required></div>
                    <div class="mb-3"><label>Phone</label><input type="text" name="phone" id="edit-phone" class="form-control"></div>
                    <div class="mb-3"><label>GSTIN</label><input type="text" name="gstin" id="edit-gstin" class="form-control"></div>
                    <div class="mb-3"><label>Address</label><input type="text" name="address" id="edit-address" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_supplier" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('editSupplierModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var supplier = JSON.parse(button.getAttribute('data-supplier'));
    var modal = this;
    modal.querySelector('#edit-supplier-id').value = supplier.id;
    modal.querySelector('#edit-name').value = supplier.supplier_name;
    modal.querySelector('#edit-phone').value = supplier.phone;
    modal.querySelector('#edit-gstin').value = supplier.gstin;
    modal.querySelector('#edit-address').value = supplier.address;
});
</script>
</body>
</html>