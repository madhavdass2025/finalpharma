<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$user_id_to_edit = $_GET['id'] ?? null;
if (!$user_id_to_edit) {
    header('Location: manage_users.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $password = $_POST['password']; // Optional: only update if provided

    if (!empty($password)) {
        $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, password = ?, role_id = ? WHERE id = ?");
        $stmt->bind_param("sssii", $name, $email, $password, $role_id, $user_id_to_edit);
    } else {
        $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, role_id = ? WHERE id = ?");
        $stmt->bind_param("ssii", $name, $email, $role_id, $user_id_to_edit);
    }

    if ($stmt->execute()) {
        $message = "User updated successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch user data
$user = null;
$stmt = $mysqli->prepare("SELECT name, email, role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id_to_edit);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

if (!$user) {
    echo "User not found.";
    exit();
}

// Fetch roles for the dropdown
$roles = [];
if ($result = $mysqli->query("SELECT id, role_name FROM roles")) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
    $result->free();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <div class="container">
            <h2>Edit User</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="edit_user.php?id=<?php echo $user_id_to_edit; ?>" method="post">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select class="form-control" id="role_id" name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" <?php echo ($user['role_id'] == $role['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update User</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
