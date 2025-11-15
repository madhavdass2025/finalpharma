<?php
include_once '../includes/db_connect.php';
include_once '../includes/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Billing</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .select2-container { width: 100% !important; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>

    <div class="content">
        <div class="container-fluid">
            <h2>Point of Sale</h2>
            <form id="pos-form" method="post" action="process_sale.php">
                <!-- Customer Selection -->
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <select id="customer_id" name="customer_id" class="form-control">
                        <option value="">Select Customer</option>
                        <!-- Options populated by JS -->
                    </select>
                </div>

                <!-- Product Search -->
                <div class="form-group">
                    <label for="product_search">Add Product</label>
                    <select id="product_search" class="form-control">
                        <option value="">Search by name...</option>
                        <!-- Options populated by JS -->
                    </select>
                </div>

                <!-- Invoice Items Table -->
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Batch</th>
                            <th>Expiry</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="invoice-items">
                        <!-- Items added here by JS -->
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="row">
                    <div class="col-md-6 ml-auto">
                        <table class="table">
                            <tr>
                                <th>Net Amount</th>
                                <td id="net-amount">0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Payment -->
                <h4>Payment</h4>
                <div id="payment-methods">
                    <div class="payment-method-row row">
                        <div class="col-md-4">
                            <select name="payment_method[]" class="form-control">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="UPI">UPI</option>
                                <option value="Credit">Credit</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="number" step="0.01" name="amount_paid[]" class="form-control amount-paid" placeholder="Amount">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-danger remove-payment">-</button>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-payment" class="btn btn-secondary mt-2">+ Add Payment Method</button>

                <div class="form-group mt-3">
                    <label for="total-paid">Total Paid</label>
                    <input type="text" id="total-paid" class="form-control" readonly>
                </div>
                 <div class="form-group">
                    <label for="balance">Balance</label>
                    <input type="text" id="balance" class="form-control" readonly>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <button type="submit" class="btn btn-primary btn-lg mt-3">Complete Sale</button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../assets/js/pos.js"></script>
</body>
</html>
