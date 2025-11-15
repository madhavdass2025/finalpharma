<?php
// ... (All backend PHP logic remains the same)
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../includes/csrf_helper.php';
// ...
?>

<h1 class="mt-4">Advanced Point of Sale</h1>

<form action="pos.php" method="post" id="pos-form">
    <?php csrf_input(); ?>
    <div class="row">
        <div class="col-lg-7">
            <!-- Fast Access Grid & Billing Items -->
            <div class="card">
                <div class="card-header">Billing Items</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Select Product</label>
                        <select id="product-select" class="form-control" style="width: 100%;">
                            <option value="">-- Search or Select a Product --</option>
                            <?php foreach($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['product_name'] . ' (Stock: ' . $product['total_stock'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <table class="table" id="billing-cart"><!-- ... --></table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">Customer & Payment</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-7">
                            <label>Find Customer by Reg. ID</label>
                            <input type="text" id="customer-reg-search" class="form-control" placeholder="Enter Reg. ID and press Enter">
                        </div>
                        <div class="col-5">
                            <label>Customer Name</label>
                            <input type="text" id="customer-display-name" class="form-control" disabled>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Or Select Customer</label>
                        <select name="customer_id" id="customer-select" class="form-control" style="width: 100%;">
                            <option value="">-- Walk-in Customer --</option>
                            <!-- ... customer options ... -->
                        </select>
                    </div>
                    <!-- ... payment section ... -->
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        // Initialize Select2 for both dropdowns
        $('#customer-select').select2({ placeholder: "Select or search customer", allowClear: true });
        $('#product-select').select2({ placeholder: "Search or Select a Product", allowClear: true });

        // Event handler for the new product dropdown
        $('#product-select').on('change', function() {
            const productId = this.value;
            if (productId) {
                const selectedProduct = products.find(p => p.id == productId);
                if (selectedProduct) {
                    fetchBatches(selectedProduct.id, selectedProduct.product_name);
                }
                $(this).val(null).trigger('change'); // Reset dropdown after adding
            }
        });

        // Event handler for the new customer registration ID search
        $('#customer-reg-search').on('keypress', async function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const regNo = this.value.trim();
                if (!regNo) return;

                try {
                    const response = await fetch(`api_get_customer.php?reg_no=${regNo}`);
                    const result = await response.json();

                    if (result.status === 'success') {
                        const customer = result.data;
                        $('#customer-display-name').val(customer.name);
                        // Set the value in the main customer dropdown and trigger change
                        $('#customer-select').val(customer.id).trigger('change');
                    } else {
                        alert(result.message || 'Customer not found.');
                        $('#customer-display-name').val('');
                        $('#customer-select').val(null).trigger('change');
                    }
                } catch (error) {
                    console.error('Failed to fetch customer:', error);
                    alert('An error occurred while searching for the customer.');
                }
            }
        });
    });
    // ... all other POS JavaScript functions (fetchBatches, addToCart, etc.)
</script>

<?php require_once '../includes/footer.php'; ?>