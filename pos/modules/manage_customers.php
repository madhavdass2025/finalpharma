<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/Auth.php';
require_once '../config/database.php';
require_once '../includes/csrf_helper.php';

Auth::check_access([1, 2]);

// --- PHP ---
?>
<h1 class="mt-4">Customer Management</h1>
<!-- HTML -->
<script>
    // JS
</script>
<?php require_once '../includes/footer.php'; ?>