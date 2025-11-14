<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1, 2]);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_sale'])) {
    check_csrf();
    $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $customer_name = ($customer_id === null) ? $_POST['customer_name_walkin'] : "Registered Customer";
    $user_id = $_SESSION['id'];
    $invoice_date = date('Y-m-d');
    $net_amount = 0;
    if (isset($_POST['quantity'])) {
        foreach($_POST['quantity'] as $key => $qty) {
            if ($qty > 0) {
                $net_amount += $qty * $_POST['unit_price'][$key];
            }
        }
    }
    $total_paid = 0;
    if (isset($_POST['payment_method'])) {
        foreach($_POST['amount_paid'] as $paid_amount) {
            if (!empty($paid_amount)) {
                $total_paid += (float)$paid_amount;
            }
        }
    }
    $balance_due = $net_amount - $total_paid;
    $status = ($balance_due <= 0) ? 'Completed' : 'Credit';

    $conn->begin_transaction();
    try {
        $sql_invoice = "INSERT INTO sales_invoices (invoice_number, invoice_date, net_amount, gross_amount, total_tax, total_paid, balance_due, user_id, customer_id, customer_name, status) VALUES (?, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, ?)";
        $invoice_number = "INV-" . time();
        $stmt_invoice = $conn->prepare($sql_invoice);
        $stmt_invoice->bind_param("ssdddiisss", $invoice_number, $invoice_date, $net_amount, $net_amount, $total_paid, $balance_due, $user_id, $customer_id, $customer_name, $status);
        $stmt_invoice->execute();
        $sales_invoice_id = $stmt_invoice->insert_id;

        if (isset($_POST['payment_method'])) {
            foreach ($_POST['payment_method'] as $key => $method) {
                $amount = (float)$_POST['amount_paid'][$key];
                if ($amount > 0) {
                    $sql_payment = "INSERT INTO payments (invoice_id, payment_method, amount_paid, payment_date) VALUES (?, ?, ?, ?)";
                    $stmt_payment = $conn->prepare($sql_payment);
                    $stmt_payment->bind_param("isds", $sales_invoice_id, $method, $amount, $invoice_date);
                    $stmt_payment->execute();
                }
            }
        }
        if (isset($_POST['product_id'])) {
            foreach ($_POST['product_id'] as $key => $product_id) {
                $quantity = $_POST['quantity'][$key];
                if ($quantity <= 0) continue;
                $batch_id = $_POST['batch_id'][$key];
                $unit_price = $_POST['unit_price'][$key];
                $sql_item = "INSERT INTO invoice_items (sales_invoice_id, product_id, batch_id, quantity, unit_price) VALUES (?, ?, ?, ?, ?)";
                $stmt_item = $conn->prepare($sql_item);
                $stmt_item->bind_param("iiiid", $sales_invoice_id, $product_id, $batch_id, $quantity, $unit_price);
                $stmt_item->execute();
                $sql_stock = "UPDATE stock_batches SET current_qty = current_qty - ? WHERE id = ? AND current_qty >= ?";
                $stmt_stock = $conn->prepare($sql_stock);
                $stmt_stock->bind_param("iii", $quantity, $batch_id, $quantity);
                $stmt_stock->execute();
                if ($stmt_stock->affected_rows == 0) throw new Exception("Insufficient stock.");
                $sql_ledger = "INSERT INTO stock_ledger (product_id, batch_id, transaction_type, reference_id, quantity) VALUES (?, ?, 'OUT - Sale', ?, ?)";
                $stmt_ledger = $conn->prepare($sql_ledger);
                $sale_qty_negative = -$quantity;
                $stmt_ledger->bind_param("iiii", $product_id, $batch_id, $sales_invoice_id, $sale_qty_negative);
                $stmt_ledger->execute();
            }
        }
        $conn->commit();
        $success_message = "Sale processed! Invoice: " . $invoice_number;
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to process sale: " . $e->getMessage();
    }
}

$customers = [];
$sql_customers = "SELECT id, name, reg_no FROM customers ORDER BY name";
if ($result = $conn->query($sql_customers)) {
    while ($row = $result->fetch_assoc()) $customers[] = $row;
}

$favorite_products = [];
$sql_favorites = "SELECT id, product_name FROM products WHERE is_active = TRUE AND is_favorite = TRUE ORDER BY product_name";
if ($result = $conn->query($sql_favorites)) {
    while ($row = $result->fetch_assoc()) $favorite_products[] = $row;
}

$products = [];
$sql_products = "SELECT p.id, p.product_name, p.mrp, SUM(sb.current_qty) as total_stock FROM products p JOIN stock_batches sb ON p.id = sb.product_id WHERE p.is_active = TRUE AND sb.current_qty > 0 AND sb.expiry_date > CURDATE() GROUP BY p.id ORDER BY p.product_name";
if ($result = $conn->query($sql_products)) {
    while ($row = $result->fetch_assoc()) $products[] = $row;
}
$conn->close();
?>

<h1 class="mt-4">Advanced Point of Sale</h1>

<?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
<?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

<form action="pos.php" method="post" id="pos-form">
    <?php csrf_input(); ?>
    <div class="row">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Fast Access Products</div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php foreach($favorite_products as $fav): ?>
                        <div class="col-3">
                            <button type="button" class="btn btn-outline-primary w-100 fav-product-btn" onclick="fetchBatches(<?php echo $fav['id']; ?>, '<?php echo htmlspecialchars($fav['product_name'], ENT_QUOTES); ?>')">
                                <?php echo htmlspecialchars($fav['product_name']); ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Billing Items</div>
                <div class="card-body">
                    <div class="mb-3 position-relative">
                        <input type="text" id="product-search" class="form-control" placeholder="Search for other products...">
                        <div id="product-search-results"></div>
                    </div>
                    <table class="table" id="billing-cart">
                        <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Qty</th><th>Price</th><th>Total</th><th>Action</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Customer & Payment</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Customer</label>
                        <select name="customer_id" id="customer-select" class="form-control" style="width: 100%;">
                            <option value="">-- Walk-in Customer --</option>
                            <?php foreach($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name'] . ' (' . $customer['reg_no'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="walkin-customer-div">
                        <label>Walk-in Customer Name</label>
                        <input type="text" name="customer_name_walkin" class="form-control" value="Walk-in">
                    </div>
                    <hr>
                    <h4 class="mb-3">Bill Total: <span id="cart-total">0.00</span></h4>
                    <div id="payment-methods">
                        <div class="row payment-row mb-2">
                            <div class="col-6"><select name="payment_method[]" class="form-control"><option value="Cash">Cash</option><option value="Card">Card</option><option value="UPI">UPI</option></select></div>
                            <div class="col-6"><input type="number" step="0.01" name="amount_paid[]" class="form-control amount-paid-input" placeholder="Amount"></div>
                        </div>
                    </div>
                    <button type="button" id="add-payment-btn" class="btn btn-sm btn-info mb-3">Add Payment Method</button>
                    <hr>
                    <h5 class="mb-3">Total Paid: <span id="total-paid">0.00</span></h5>
                    <h5 class="mb-3">Balance Due: <span id="balance-due">0.00</span></h5>
                    <button type="submit" name="process_sale" class="btn btn-success w-100">Process Sale</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    const products = <?php echo json_encode($products); ?>;
    let activeSuggestion = -1;

    $(document).ready(function() {
        $('#customer-select').select2({
            placeholder: "Select or search customer",
            allowClear: true
        });

        $('#customer-select').on('change', function() {
            $('#walkin-customer-div').toggle(!this.value);
        });
    });

    const searchInput = document.getElementById('product-search');
    const resultsContainer = document.getElementById('product-search-results');

    searchInput.addEventListener('keyup', function(e) {
        const query = this.value.toLowerCase();

        if (e.key === 'ArrowDown') {
            if (activeSuggestion < resultsContainer.children[0].children.length - 1) {
                activeSuggestion++;
                updateSuggestionHighlight();
            }
            return;
        }
        if (e.key === 'ArrowUp') {
            if (activeSuggestion > 0) {
                activeSuggestion--;
                updateSuggestionHighlight();
            }
            return;
        }
        if (e.key === 'Enter') {
            e.preventDefault();
            const suggestions = resultsContainer.querySelectorAll('.list-group-item');
            if (activeSuggestion > -1 && suggestions[activeSuggestion]) {
                suggestions[activeSuggestion].click();
            }
            return;
        }

        resultsContainer.innerHTML = '';
        if (query.length < 2) { activeSuggestion = -1; return; }

        const filteredProducts = products.filter(p => p.product_name.toLowerCase().includes(query));
        let resultsHtml = '<ul class="list-group">';
        filteredProducts.forEach(p => {
            resultsHtml += `<li class="list-group-item" onclick="fetchBatches(${p.id}, '${p.product_name.replace(/'/g, "\\'")}')">${p.product_name} (Stock: ${p.total_stock})</li>`;
        });
        resultsHtml += '</ul>';
        resultsContainer.innerHTML = resultsHtml;
        activeSuggestion = -1;
    });

    function updateSuggestionHighlight() {
        const suggestions = resultsContainer.querySelectorAll('.list-group-item');
        suggestions.forEach((item, index) => {
            item.classList.toggle('active', index === activeSuggestion);
        });
    }

    async function fetchBatches(productId, productName) {
        document.getElementById('product-search').value = '';
        document.getElementById('product-search-results').innerHTML = '';
        try {
            const response = await fetch(`api_get_batches.php?product_id=${productId}`);
            const result = await response.json();
            if (result.status === 'success' && result.data.length > 0) {
                const batch = result.data[0]; // FEFO
                addToCart(productId, productName, batch);
            } else {
                alert(result.message || 'No stock found.');
            }
        } catch (error) {
            console.error('Error fetching batches:', error);
            alert('Failed to fetch product details.');
        }
    }

    function addToCart(productId, productName, batch) {
        const cartBody = document.querySelector("#billing-cart tbody");
        if (document.querySelector(`input[name='batch_id[]'][value='${batch.id}']`)) {
            alert('This batch is already in the cart.');
            return;
        }
        const row = document.createElement('tr');
        row.className = 'cart-item-row';
        row.innerHTML = `
            <td>${productName}<input type="hidden" name="product_id[]" value="${productId}"><input type="hidden" name="batch_id[]" value="${batch.id}"></td>
            <td>${batch.batch_number}</td>
            <td>${batch.expiry_date}</td>
            <td><input type="number" name="quantity[]" class="form-control quantity-input" value="1" min="1" max="${batch.current_qty}" required></td>
            <td><input type="number" step="0.01" name="unit_price[]" class="form-control price-input" value="${batch.mrp}" required></td>
            <td class="total-price">${batch.mrp}</td>
            <td><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove(); updateTotal();">X</button></td>
        `;
        cartBody.appendChild(row);
        updateTotal();
        row.querySelector('.quantity-input').addEventListener('input', updateTotal);
        row.querySelector('.price-input').addEventListener('input', updateTotal);
    }

    document.getElementById('add-payment-btn').addEventListener('click', function() {
        const container = document.getElementById('payment-methods');
        const newRow = document.createElement('div');
        newRow.className = 'row payment-row mb-2';
        newRow.innerHTML = `
            <div class="col-6"><select name="payment_method[]" class="form-control"><option value="Cash">Cash</option><option value="Card">Card</option><option value="UPI">UPI</option></select></div>
            <div class="col-5"><input type="number" step="0.01" name="amount_paid[]" class="form-control amount-paid-input" placeholder="Amount"></div>
            <div class="col-1"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.payment-row').remove(); updatePaymentTotals();">X</button></div>
        `;
        container.appendChild(newRow);
    });

    document.getElementById('pos-form').addEventListener('input', function(e) {
        if (e.target.classList.contains('amount-paid-input')) {
            updatePaymentTotals();
        }
    });

    function updateTotal() {
        let total = 0;
        document.querySelectorAll('.cart-item-row').forEach(row => {
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const rowTotal = quantity * price;
            row.querySelector('.total-price').textContent = rowTotal.toFixed(2);
            total += rowTotal;
        });
        document.getElementById('cart-total').textContent = total.toFixed(2);
        updatePaymentTotals();
    }

    function updatePaymentTotals() {
        let totalPaid = 0;
        document.querySelectorAll('.amount-paid-input').forEach(input => {
            totalPaid += parseFloat(input.value) || 0;
        });
        document.getElementById('total-paid').textContent = totalPaid.toFixed(2);
        const billTotal = parseFloat(document.getElementById('cart-total').textContent) || 0;
        const balanceDue = billTotal - totalPaid;
        document.getElementById('balance-due').textContent = balanceDue.toFixed(2);
    }
</script>

<?php require_once '../includes/footer.php'; ?>