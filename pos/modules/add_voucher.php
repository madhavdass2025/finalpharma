<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1]);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_voucher'])) {
    check_csrf();
    $sql = "INSERT INTO vouchers (voucher_type, amount, description, voucher_date, user_id) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sdssi", $_POST['voucher_type'], $_POST['amount'], $_POST['description'], $_POST['voucher_date'], $_SESSION['id']);
        if ($stmt->execute()) {
            $success_message = "Voucher added successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    } else {
        $error_message = "Error preparing statement: " . $conn->error;
    }
}

$vouchers = [];
$sql_fetch = "SELECT v.voucher_date, v.voucher_type, v.description, v.amount, u.name as user_name FROM vouchers v JOIN users u ON v.user_id = u.id ORDER BY v.voucher_date DESC LIMIT 10";
if($result = $conn->query($sql_fetch)) {
    while($row = $result->fetch_assoc()) $vouchers[] = $row;
}
$conn->close();
?>

<h1 class="mt-4">Voucher Entry</h1>
<?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
<?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">Add New Voucher</div>
            <div class="card-body">
                <form action="add_voucher.php" method="post">
                    <?php csrf_input(); ?>
                    <div class="mb-3"><label>Voucher Date</label><input type="date" name="voucher_date" class="form-control" required></div>
                    <div class="mb-3"><label>Voucher Type</label><select name="voucher_type" class="form-control" required><option value="Expense">Expense</option><option value="Income">Income</option></select></div>
                    <div class="mb-3"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                    <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" required></textarea></div>
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

<?php require_once '../includes/footer.php'; ?>