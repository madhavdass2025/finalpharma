<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id']) || !check_user_role($mysqli, $_SESSION['user_id'], 'admin')) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$supplier_id = $_GET['id'] ?? null;
if (!$supplier_id) {
    header('Location: manage_suppliers.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplier_name = $_POST['supplier_name'];
    $phone = $_POST['phone'];
    $gstin = $_POST['gstin'];

    $stmt = $mysqli->prepare("UPDATE suppliers SET supplier_name = ?, phone = ?, gstin = ? WHERE id = ?");
    $stmt->bind_param("sssi", $supplier_name, $phone, $gstin, $supplier_id);

    if ($stmt->execute()) {
        $message = "Supplier updated successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch supplier data
$supplier = null;
$stmt = $mysqli->prepare("SELECT supplier_name, phone, gstin FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $supplier = $result->fetch_assoc();
}
$stmt->close();

if (!$supplier) {
    echo "Supplier not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <div class="container">
            <h2>Edit Supplier</h2>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo $message; ?></div>
            <?php endif; ?>
            <form action="edit_supplier.php?id=<?php echo $supplier_id; ?>" method="post">
                <div class="form-group">
                    <label for="supplier_name">Supplier Name</label>
                    <input type="text" class="form-control" id="supplier_name" name="supplier_name" value="<?php echo htmlspecialchars($supplier['supplier_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($supplier['phone']); ?>">
                </div>
                <div class="form-group">
                    <label for="gstin">GSTIN</label>
                    <input type="text" class="form-control" id="gstin" name="gstin" value="<?php echo htmlspecialchars($supplier['gstin']); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update Supplier</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
