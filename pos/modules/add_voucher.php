<?php
require_once '../includes/Auth.php';
Auth::check_access([1]); // Admins only

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_voucher'])) {
    check_csrf();
    $voucher_type = $_POST['voucher_type'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $voucher_date = $_POST['voucher_date'];
    $user_id = $_SESSION['id'];

    $sql = "INSERT INTO vouchers (voucher_type, amount, description, voucher_date, user_id) VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sdssi", $voucher_type, $amount, $description, $voucher_date, $user_id);
        if ($stmt->execute()) {
            $success_message = "Voucher added successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Error preparing statement: " . $conn->error;
    }
}

// Fetch recent vouchers
$vouchers = [];
$sql_fetch = "SELECT v.voucher_date, v.voucher_type, v.description, v.amount, u.name as user_name FROM vouchers v JOIN users u ON v.user_id = u.id ORDER BY v.voucher_date DESC LIMIT 10";
if($result = $conn->query($sql_fetch)) {
    while($row = $result->fetch_assoc()) $vouchers[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voucher Entry - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Voucher Entry</h2>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Add New Voucher</div>
                <div class="card-body">
                    <form action="add_voucher.php" method="post">
                        <?php csrf_input(); ?>
                        <div class="mb-3">
                            <label>Voucher Date</label>
                            <input type="date" name="voucher_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Voucher Type</label>
                            <select name="voucher_type" class="form-control" required>
                                <option value="Expense">Expense</option>
                                <option value="Income">Income</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" required></textarea>
                        </div>
                        <button type="submit" name="save_voucher" class="btn btn-primary">Save Voucher</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Recent Vouchers</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Amount</th><th>User</th></tr></thead>
                        <tbody>
                            <?php foreach($vouchers as $voucher): ?>
                            <tr>
                                <td><?php echo $voucher['voucher_date']; ?></td>
                                <td><?php echo $voucher['voucher_type']; ?></td>
                                <td><?php echo htmlspecialchars($voucher['description']); ?></td>
                                <td><?php echo number_format($voucher['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($voucher['user_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>