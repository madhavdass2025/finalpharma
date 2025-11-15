$(document).ready(function() {
    // Initialize Select2 for supplier and product dropdowns
    $('#supplier_id, .product-select').select2();

    // Add new item row
    $('#add-item').click(function() {
        var template = $('#item-template').html();
        $('#purchase-items').append(template);
        // Initialize select2 on the new product dropdown
        $('#purchase-items .product-select').last().select2();
    });

    // Remove item row
    $(document).on('click', '.remove-item', function() {
        $(this).closest('.item-row').remove();
        updateTotals();
    });

    // Update totals when quantity, price, tax, or discount changes
    $(document).on('change', '.quantity, .purchase-price, .tax-rate, #discount', function() {
        updateTotals();
    });

    function updateTotals() {
        var totalAmount = 0;
        $('.item-row').each(function() {
            var row = $(this);
            var qty = parseFloat(row.find('.quantity').val()) || 0;
            var price = parseFloat(row.find('.purchase-price').val()) || 0;
            var taxRate = parseFloat(row.find('.tax-rate').val()) || 0;

            var itemTotal = qty * price * (1 + taxRate / 100);
            row.find('.item-total').val(itemTotal.toFixed(2));
            totalAmount += itemTotal;
        });

        $('#total-amount').val(totalAmount.toFixed(2));

        var discount = parseFloat($('#discount').val()) || 0;
        var netAmount = totalAmount - discount;
        $('#net-amount').val(netAmount.toFixed(2));
    }
});
