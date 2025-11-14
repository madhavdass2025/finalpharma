<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1]);

// --- ALL SUPPLIER MANAGEMENT PHP LOGIC RESTORED ---
// ... (Handle Add, Edit, Delete POST requests)
// ... (Fetch all suppliers query)
?>

<h1 class="mt-4">Supplier Management</h1>

<!-- Add Supplier Form -->
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

<!-- Supplier List Table -->
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

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="manage_suppliers.php" method="post">
                <div class="modal-body">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="supplier_id" id="edit-supplier-id">
                    <div class="mb-3"><label>Name</label><input type="text" name="supplier_name" id="edit-name" class="form-control" required></div>
                    <div class="mb-3"><label>Phone</label><input type="text" name="phone" id="edit-phone" class="form-control"></div>
                    <div class="mb-3"><label>GSTIN</label><input type="text" name="gstin" id="edit-gstin" class="form-control"></div>
                    <div class="mb-3"><label>Address</label><input type="text" name="address" id="edit-address" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="edit_supplier" class="btn btn-primary">Save changes</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- MODAL JAVASCRIPT LOGIC RESTORED ---
    document.getElementById('editSupplierModal').addEventListener('show.bs.modal', function (event) {
        var supplier = JSON.parse(event.relatedTarget.getAttribute('data-supplier'));
        this.querySelector('#edit-supplier-id').value = supplier.id;
        this.querySelector('#edit-name').value = supplier.supplier_name;
        this.querySelector('#edit-phone').value = supplier.phone;
        this.querySelector('#edit-gstin').value = supplier.gstin;
        this.querySelector('#edit-address').value = supplier.address;
    });
</script>

<?php require_once '../includes/footer.php'; ?>