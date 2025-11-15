$(document).ready(function() {
    // Initialize Select2
    $('#customer_id, #product_search').select2();

    // Customer search by ID
    $('#find-customer-btn').click(function() {
        var customerId = $('#customer_search_id').val();
        if (customerId) {
            $.ajax({
                url: 'api_get_customer.php',
                method: 'GET',
                data: { id: customerId },
                success: function(customer) {
                    if (customer && !customer.error) {
                        // Add customer to dropdown and select them
                        var option = new Option(customer.customer_name + ' (' + customer.phone + ')', customer.id, true, true);
                        $('#customer_id').append(option).trigger('change');
                    } else {
                        alert('Customer not found.');
                    }
                }
            });
        }
    });

    // Product Search (same as pos.js)
    $('#product_search').select2({
        ajax: {
            url: 'api/search_products.php',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term }; },
            processResults: function(data) { return { results: data }; },
            cache: true
        },
        placeholder: 'Search for a product',
        minimumInputLength: 1
    });

    function addProductToInvoice(productId, productName) {
         $.ajax({
            url: 'api_get_product_batches.php',
            method: 'GET',
            data: { product_id: productId },
            success: function(batches) {
                if (batches.length > 0) {
                    var batch = batches[0];
                    var row = `
                        <tr data-product-id="${productId}" data-batch-id="${batch.id}">
                            <td>${productName}</td>
                            <td>${batch.batch_number}</td>
                            <td><input type="number" class="form-control quantity" value="1" min="1" max="${batch.current_qty}"></td>
                            <td><input type="number" class="form-control price" value="${batch.mrp}" step="0.01"></td>
                            <td class="item-total">${batch.mrp}</td>
                            <td><button class="btn btn-danger btn-sm remove-item">X</button></td>
                        </tr>`;
                    $('#invoice-items').append(row);
                    updateTotals();
                } else {
                    alert('No stock available for this product.');
                }
            }
        });
    }

    // Add item from search
    $('#product_search').on('select2:select', function(e) {
        var product = e.params.data;
        addProductToInvoice(product.id, product.text);
        $('#product_search').val(null).trigger('change');
    });

    // Add item from fast access grid
    $('.fast-access-item').click(function() {
        var productId = $(this).data('product-id');
        var productName = $(this).text().trim();
        addProductToInvoice(productId, productName);
    });

    // Common functions (delegated events and updates)
    $(document).on('change', '.quantity, .price', function() {
        var row = $(this).closest('tr');
        var qty = parseFloat(row.find('.quantity').val());
        var price = parseFloat(row.find('.price').val());
        row.find('.item-total').text((qty * price).toFixed(2));
        updateTotals();
    });

    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        updateTotals();
    });

    $('#add-payment').click(function() {
        var newPaymentRow = $('.payment-method-row').first().clone();
        newPaymentRow.find('input').val('');
        $('#payment-methods').append(newPaymentRow);
    });

    $(document).on('click', '.remove-payment', function() {
        if ($('.payment-method-row').length > 1) {
            $(this).closest('.payment-method-row').remove();
        }
    });

    $(document).on('input', '.amount-paid', function() {
        updateTotals();
    });

    function updateTotals() {
        var netAmount = 0;
        $('.item-total').each(function() {
            netAmount += parseFloat($(this).text());
        });
        $('#net-amount').text(netAmount.toFixed(2));

        var totalPaid = 0;
        $('.amount-paid').each(function() {
            var amount = parseFloat($(this).val());
            if (!isNaN(amount)) { totalPaid += amount; }
        });
        $('#total-paid').text(totalPaid.toFixed(2));

        var balance = netAmount - totalPaid;
        $('#balance').text(balance.toFixed(2));
    }
});
