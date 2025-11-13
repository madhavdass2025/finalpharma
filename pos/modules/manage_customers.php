<?php
require_once '../includes/Auth.php';
Auth::check_access([1, 2]); // Admins and Pharmacists can manage customers

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Function to generate the next registration number
function generate_reg_no($conn) {
    $year = date('Y');
    $sql = "SELECT reg_no FROM customers WHERE reg_no LIKE ? ORDER BY reg_no DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $like_pattern = $year . "-%";
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $last_reg_no = $result->fetch_assoc()['reg_no'];
        $last_num = (int)substr($last_reg_no, -4);
        $next_num = $last_num + 1;
    } else {
        $next_num = 1;
    }
    return $year . "-" . str_pad($next_num, 4, '0', STR_PAD_LEFT);
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();

    if (isset($_POST['add_customer'])) {
        $reg_no = generate_reg_no($conn);
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $sql = "INSERT INTO customers (reg_no, name, phone, address) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $reg_no, $name, $phone, $address);
            $stmt->execute();
        }
    }

    if (isset($_POST['edit_customer'])) {
        $id = $_POST['customer_id'];
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $sql = "UPDATE customers SET name = ?, phone = ?, address = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $name, $phone, $address, $id);
            $stmt->execute();
        }
    }

    header("location: manage_customers.php");
    exit;
}

// Fetch all customers
$customers = [];
$sql = "SELECT id, reg_no, name, phone, address FROM customers ORDER BY id DESC";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) $customers[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Customer Management</h2>
    <div class="card mb-4">
        <div class="card-header">Add New Customer</div>
        <div class="card-body">
            <form action="manage_customers.php" method="post">
                <?php csrf_input(); ?>
                <div class="row">
                    <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Full Name" required></div>
                    <div class="col-md-3"><input type="text" name="phone" class="form-control" placeholder="Phone"></div>
                    <div class="col-md-3"><input type="text" name="address" class="form-control" placeholder="Address"></div>
                    <div class="col-md-2"><button type="submit" name="add_customer" class="btn btn-primary w-100">Add Customer</button></div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Registered Customers</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead><tr><th>Reg. No</th><th>Name</th><th>Phone</th><th>Address</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['reg_no']); ?></td>
                            <td><?php echo htmlspecialchars($customer['name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo htmlspecialchars($customer['address']); ?></td>
                            <td><button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCustomerModal" data-customer='<?php echo json_encode($customer); ?>'>Edit</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="manage_customers.php" method="post">
                <div class="modal-body">
                    <?php csrf_input(); ?>
                    <input type="hidden" name="customer_id" id="edit-customer-id">
                    <div class="mb-3"><label>Name</label><input type="text" name="name" id="edit-name" class="form-control" required></div>
                    <div class="mb-3"><label>Phone</label><input type="text" name="phone" id="edit-phone" class="form-control"></div>
                    <div class="mb-3"><label>Address</label><input type="text" name="address" id="edit-address" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="edit_customer" class="btn btn-primary">Save changes</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('editCustomerModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    var customer = JSON.parse(button.getAttribute('data-customer'));
    var modal = this;
    modal.querySelector('#edit-customer-id').value = customer.id;
    modal.querySelector('#edit-name').value = customer.name;
    modal.querySelector('#edit-phone').value = customer.phone;
    modal.querySelector('#edit-address').value = customer.address;
});
</script>
</body>
</html>