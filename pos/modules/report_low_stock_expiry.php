<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
// ... (includes)

Auth::check_access([1, 2]);

// --- PHP LOGIC RESTORED ---
?>

<h1 class="mt-4">Low Stock & Expiry Report</h1>

<div class="card mb-4"><!-- Low Stock --></div>
<div class="card"><!-- Expiring --></div>

<?php require_once '../includes/footer.php'; ?>