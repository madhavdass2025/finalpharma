<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
// ... (includes)

Auth::check_access([1]);

// --- PHP LOGIC RESTORED ---
?>

<h1 class="mt-4">Sales Register</h1>

<div class="card mb-4"><!-- Filter --></div>
<div class="card"><!-- Table --></div>

<?php require_once '../includes/footer.php'; ?>