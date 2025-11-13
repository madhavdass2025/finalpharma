<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Only Admins

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Handle POST requests for Add, Edit, Delete
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();

    // Add User
    if (isset($_POST['add_user'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role_id = trim($_POST['role_id']);
        $sql = "INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $name, $email, $password, $role_id);
            $stmt->execute();
        }
    }

    // Edit User
    if (isset($_POST['edit_user'])) {
        $id = $_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role_id = trim($_POST['role_id']);
        $password = trim($_POST['password']);

        if (!empty($password)) {
            $sql = "UPDATE users SET name = ?, email = ?, password = ?, role_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $name, $email, $password, $role_id, $id);
        } else {
            $sql = "UPDATE users SET name = ?, email = ?, role_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $name, $email, $role_id, $id);
        }
        $stmt->execute();
    }

    // Delete User
    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        // Avoid deleting the main admin user
        if ($id != 1) {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }
    header("location: manage_users.php");
    exit;
}

// Fetch all users
$users = [];
$sql = "SELECT id, name, email, role_id FROM users ORDER BY id";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) $users[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>User Management</h2>
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
                        <select name="role_id" class="form-control" required>
                            <option value="1">Admin</option>
                            <option value="2">Billing/Pharmacist</option>
                        </select>
                    </div>
                    <div class="col-md-2"><button type="submit" name="add_user" class="btn btn-primary w-100">Add User</button></div>
                </div>
            </form>
        </div>
    </div>
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
                                <?php if ($user['id'] != 1): // Prevent deleting admin user ?>
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
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="manage_users.php" method="post">
                <div class="modal-body">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="user_id" id="edit-user-id">
                    <div class="mb-3"><label>Name</label><input type="text" name="name" id="edit-name" class="form-control" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" name="email" id="edit-email" class="form-control" required></div>
                    <div class="mb-3"><label>Role</label>
                        <select name="role_id" id="edit-role" class="form-control" required>
                            <option value="1">Admin</option>
                            <option value="2">Billing/Pharmacist</option>
                        </select>
                    </div>
                    <div class="mb-3"><label>New Password (leave blank to keep unchanged)</label><input type="password" name="password" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('editUserModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var user = JSON.parse(button.getAttribute('data-user'));
    var modal = this;
    modal.querySelector('#edit-user-id').value = user.id;
    modal.querySelector('#edit-name').value = user.name;
    modal.querySelector('#edit-email').value = user.email;
    modal.querySelector('#edit-role').value = user.role_id;
});
</script>
</body>
</html>