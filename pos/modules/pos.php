<?php
// ... (all backend PHP logic remains the same)
require_once '../includes/Auth.php';
Auth::check_access([1, 2]);

require_once '../includes/csrf_helper.php';
require_once '../config/database.php';

// Sale processing logic...
// ...

// Fetch customers, favorites, and all products...
$customers = []; // ...
$favorite_products = []; // ...
$products = []; // ...

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Advanced Point of Sale (POS) - CPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container .select2-selection--single { height: 38px; padding-top: 5px; }
        #product-search-results { position: absolute; background: white; border: 1px solid #ccc; z-index: 1000; width: 100%; }
        #product-search-results .list-group-item.active { background-color: #0d6efd; color: white; }
        .fav-product-btn { white-space: normal; height: 100%; }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <!-- ... (form and main layout is the same) ... -->
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    const products = <?php echo json_encode($products); ?>;
    let activeSuggestion = -1;

    // Customer Select2 initialization
    $(document).ready(function() {
        $('#customer-select').select2({ placeholder: "Select or search customer", allowClear: true });
        $('#customer-select').on('change', function() {
            $('#walkin-customer-div').toggle(!this.value);
        });
    });

    const searchInput = document.getElementById('product-search');
    const resultsContainer = document.getElementById('product-search-results');

    // Product search with keyboard navigation
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
        // ... (same as before)
    }

    function addToCart(productId, productName, batch) {
        // ... (same as before)
    }

    // ... (all payment and total calculation functions remain the same)

</script>
</body>
</html>