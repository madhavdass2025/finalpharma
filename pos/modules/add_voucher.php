<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
// ... (includes)

Auth::check_access([1]);

// --- PHP LOGIC RESTORED ---
?>

<h1 class="mt-4">Voucher Entry</h1>

<div class="row">
    <div class="col-md-5"><!-- Add Voucher Card --></div>
    <div class="col-md-7"><!-- Recent Vouchers Card --></div>
</div>

<?php require_once '../includes/footer.php'; ?>