<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
// ... (includes)

Auth::check_access([1]);

// --- PHP LOGIC RESTORED ---
?>

<h1 class="mt-4">Stock Ledger Report</h1>

<div class="card mb-4"><!-- Filter --></div>
<?php if (!empty($selected_product)): ?>
<div class="card"><!-- Table --></div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>