$(document).ready(function() {
    // Initialize Select2
    $('#customer_id, #product_search').select2();

    // Fetch customers
    $.ajax({
        url: 'api/get_customers.php', // This API needs to be created
        method: 'GET',
        success: function(data) {
            data.forEach(function(customer) {
                $('#customer_id').append(new Option(customer.name, customer.id));
            });
        }
    });

    // Product Search
    $('#product_search').select2({
        ajax: {
            url: 'api/search_products.php', // This API needs to be created
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data };
            },
            cache: true
        },
        placeholder: 'Search for a product',
        minimumInputLength: 1
    });

    // Add item to invoice
    $('#product_search').on('select2:select', function(e) {
        var product = e.params.data;
        // AJAX call to get batches for the selected product
        $.ajax({
            url: 'api_get_product_batches.php',
            method: 'GET',
            data: { product_id: product.id },
            success: function(batches) {
                if (batches.length > 0) {
                    // For simplicity, let's use the first available batch
                    var batch = batches[0];
                    var row = `
                        <tr data-product-id="${product.id}" data-batch-id="${batch.id}">
                            <td>${product.text}</td>
                            <td>${batch.batch_number}</td>
                            <td>${batch.expiry_date}</td>
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
        // Reset search
        $('#product_search').val(null).trigger('change');
    });

    // Update item total and net amount when quantity or price changes
    $(document).on('change', '.quantity, .price', function() {
        var row = $(this).closest('tr');
        var qty = parseFloat(row.find('.quantity').val());
        var price = parseFloat(row.find('.price').val());
        row.find('.item-total').text((qty * price).toFixed(2));
        updateTotals();
    });

    // Remove item from invoice
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        updateTotals();
    });

    // Add new payment method row
    $('#add-payment').click(function() {
        var newPaymentRow = $('.payment-method-row').first().clone();
        newPaymentRow.find('input').val('');
        $('#payment-methods').append(newPaymentRow);
    });

    // Remove payment method row
    $(document).on('click', '.remove-payment', function() {
        if ($('.payment-method-row').length > 1) {
            $(this).closest('.payment-method-row').remove();
        }
    });

    // Update total paid and balance
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
            if (!isNaN(amount)) {
                totalPaid += amount;
            }
        });
        $('#total-paid').val(totalPaid.toFixed(2));

        var balance = netAmount - totalPaid;
        $('#balance').val(balance.toFixed(2));
    }
});
