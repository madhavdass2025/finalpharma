<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Fetch favorite/fast-moving products for the grid
$favorite_products = get_favorite_products($mysqli);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced POS</title>
    <link rel="stylesheet" href="httpss://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="httpss://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .fast-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .fast-access-item {
            cursor: pointer;
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
        }
        .fast-access-item:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <div class="container-fluid">
            <h2>Advanced POS</h2>
            <div class="row">
                <!-- Left side: Billing Form -->
                <div class="col-md-7">
                    <form id="pos-form" method="post" action="process_sale.php">
                        <!-- Customer Search by ID -->
                        <div class="input-group mb-3">
                           <input type="text" id="customer_search_id" class="form-control" placeholder="Search Customer by YYYY-XXXX ID">
                           <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="find-customer-btn">Find</button>
                           </div>
                        </div>
                        <div class="form-group">
                             <select id="customer_id" name="customer_id" class="form-control">
                                <option value="">Walk-in Customer</option>
                                <!-- JS will populate this -->
                            </select>
                        </div>

                        <!-- Product Search -->
                        <div class="form-group">
                            <label for="product_search">Add Product</label>
                            <select id="product_search" class="form-control">
                                <option value="">Search by name...</option>
                            </select>
                        </div>

                        <!-- Invoice Items -->
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items"></tbody>
                        </table>

                        <!-- Totals & Payment -->
                        <h4>Total: <span id="net-amount">0.00</span></h4>
                        <div id="payment-methods">
                             <div class="payment-method-row row mb-2">
                                <div class="col-md-5"><select name="payment_method[]" class="form-control"><option value="Cash">Cash</option><option value="Card">Card</option><option value="UPI">UPI</option><option value="Credit">Credit</option></select></div>
                                <div class="col-md-5"><input type="number" step="0.01" name="amount_paid[]" class="form-control amount-paid" placeholder="Amount"></div>
                                <div class="col-md-2"><button type="button" class="btn btn-danger remove-payment">-</button></div>
                            </div>
                        </div>
                        <button type="button" id="add-payment" class="btn btn-info btn-sm">+ Add Payment</button>

                        <div class="mt-3">
                            <p>Total Paid: <span id="total-paid">0.00</span></p>
                            <p>Balance: <span id="balance">0.00</span></p>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <button type="submit" class="btn btn-success btn-lg mt-2">Finalize Sale</button>
                    </form>
                </div>

                <!-- Right side: Fast Access Grid -->
                <div class="col-md-5">
                    <h4>Fast Access Products</h4>
                    <div class="fast-access-grid">
                        <?php foreach($favorite_products as $prod): ?>
                            <div class="fast-access-item" data-product-id="<?php echo $prod['id']; ?>">
                                <?php echo htmlspecialchars($prod['product_name']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="httpss://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="httpss://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="httpss://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../assets/js/advanced_pos.js"></script>
</body>
</html>
