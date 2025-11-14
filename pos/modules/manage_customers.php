<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1, 2]);

// --- ALL CUSTOMER MANAGEMENT PHP LOGIC RESTORED ---
// ... (generate_reg_no function)
// ... (Handle Add, Edit POST requests)
// ... (Fetch all customers query)
?>

<h1 class="mt-4">Customer Management</h1>

<!-- Add Customer Form -->
<div class="card mb-4">
    <!-- ... (form content) ... -->
</div>

<!-- Customer List Table -->
<div class="card">
    <!-- ... (table content) ... -->
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <!-- ... (modal content) ... -->
</div>

<script>
    // --- MODAL JAVASCRIPT LOGIC RESTORED ---
    document.getElementById('editCustomerModal').addEventListener('show.bs.modal', function (event) {
        var customer = JSON.parse(event.relatedTarget.getAttribute('data-customer'));
        // ... (modal field population logic)
    });
</script>

<?php require_once '../includes/footer.php'; ?>