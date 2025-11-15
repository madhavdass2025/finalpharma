<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // Generate Customer ID: YYYY-XXXX
    $year = date('Y');
    $query = "SELECT MAX(CAST(SUBSTRING(customer_id, 6) AS UNSIGNED)) as max_id FROM customers WHERE customer_id LIKE '$year-%'";
    $result = $mysqli->query($query);
    $row = $result->fetch_assoc();
    $next_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
    $customer_id = $year . '-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

    $stmt = $mysqli->prepare("INSERT INTO customers (customer_id, customer_name, phone, email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $customer_id, $customer_name, $phone, $email);

    if ($stmt->execute()) {
        $message = "Customer added successfully. New Customer ID: " . $customer_id;
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <div class="container">
            <h2>Add New Customer</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="add_customer.php" method="post">
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                <button type="submit" class="btn btn-primary">Add Customer</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
