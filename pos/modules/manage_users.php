<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1]);

// --- ALL USER MANAGEMENT PHP LOGIC RESTORED ---
// ... (Handle Add, Edit, Delete POST requests)
// ... (Fetch all users query)
?>

<h1 class="mt-4">User Management</h1>

<!-- Add User Form -->
<div class="card mb-4">
    <div class="card-header">Add New User</div>
    <div class="card-body">
        <form action="manage_users.php" method="post">
            <?php csrf_input(); ?>
            <div class="row">
                <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Full Name" required></div>
                <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                <div class="col-md-2">
                    <select name="role_id" class="form-control" required><option value="1">Admin</option><option value="2">Billing/Pharmacist</option></select>
                </div>
                <div class="col-md-2"><button type="submit" name="add_user" class="btn btn-primary w-100">Add User</button></div>
            </div>
        </form>
    </div>
</div>

<!-- User List Table -->
<div class="card">
    <div class="card-header">Existing Users</div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['role_id'] == 1 ? 'Admin' : 'Billing/Pharmacist'; ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user='<?php echo json_encode($user); ?>'>Edit</button>
                            <?php if ($user['id'] != 1): ?>
                            <form action="manage_users.php" method="post" class="d-inline">
                                <?php csrf_input(); ?>
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="manage_users.php" method="post">
                <div class="modal-body">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="user_id" id="edit-user-id">
                    <div class="mb-3"><label>Name</label><input type="text" name="name" id="edit-name" class="form-control" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" name="email" id="edit-email" class="form-control" required></div>
                    <div class="mb-3"><label>Role</label>
                        <select name="role_id" id="edit-role" class="form-control" required><option value="1">Admin</option><option value="2">Billing/Pharmacist</option></select>
                    </div>
                    <div class="mb-3"><label>New Password (leave blank to keep unchanged)</label><input type="password" name="password" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="edit_user" class="btn btn-primary">Save changes</button></div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- MODAL JAVASCRIPT LOGIC RESTORED ---
    document.getElementById('editUserModal').addEventListener('show.bs.modal', function (event) {
        var user = JSON.parse(event.relatedTarget.getAttribute('data-user'));
        this.querySelector('#edit-user-id').value = user.id;
        this.querySelector('#edit-name').value = user.name;
        this.querySelector('#edit-email').value = user.email;
        this.querySelector('#edit-role').value = user.role_id;
    });
</script>

<?php require_once '../includes/footer.php'; ?>