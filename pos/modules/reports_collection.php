<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();
if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$payment_method = $_GET['payment_method'] ?? '';
$user_id_filter = $_GET['user_id'] ?? '';

// Fetch data
$query = "SELECT p.amount_paid, p.payment_method, si.invoice_date, u.name as user_name
          FROM payments p
          JOIN sales_invoices si ON p.invoice_id = si.id
          JOIN users u ON si.user_id = u.id
          WHERE si.invoice_date BETWEEN ? AND ?";
$params = ['ss', $start_date, $end_date];

if ($payment_method) {
    $query .= " AND p.payment_method = ?";
    $params[0] .= 's';
    $params[] = $payment_method;
}
if ($user_id_filter) {
    $query .= " AND si.user_id = ?";
    $params[0] .= 'i';
    $params[] = $user_id_filter;
}
$query .= " ORDER BY si.invoice_date DESC";

$stmt = $mysqli->prepare($query);
if ($params > 2) {
   $stmt->bind_param(...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$collections = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch users for filter dropdown
$users = $mysqli->query("SELECT id, name FROM users")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Collection Report</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="content">
        <div class="container">
            <h2>Collection Report</h2>
            <form method="get" class="form-inline mb-3">
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control mr-2">
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control mr-2">
                <select name="payment_method" class="form-control mr-2">
                    <option value="">All Methods</option>
                    <option value="Cash" <?php if($payment_method == 'Cash') echo 'selected'; ?>>Cash</option>
                    <option value="Card" <?php if($payment_method == 'Card') echo 'selected'; ?>>Card</option>
                    <option value="UPI" <?php if($payment_method == 'UPI') echo 'selected'; ?>>UPI</option>
                </select>
                <select name="user_id" class="form-control mr-2">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php if($user_id_filter == $user['id']) echo 'selected'; ?>><?php echo $user['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Payment Method</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_collection = 0;
                    foreach ($collections as $item):
                        $total_collection += $item['amount_paid'];
                    ?>
                    <tr>
                        <td><?php echo $item['invoice_date']; ?></td>
                        <td><?php echo $item['user_name']; ?></td>
                        <td><?php echo $item['payment_method']; ?></td>
                        <td><?php echo number_format($item['amount_paid'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total Collection</th>
                        <th><?php echo number_format($total_collection, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
</body>
</html>
