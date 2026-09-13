/**
 * Created by rbs on 5/1/17.
 */
$( ".date-picker" ).datepicker({
    dateFormat: "dd-mm-yy"
});

$( ".dateCalendar" ).datepicker({
    dateFormat: "dd-mm-yy",
    changeMonth: true,
    changeYear: true,
    yearRange: "-100:+0",
});


var form = $("#purchaseItem").validate({
    submitHandler: function(form) {
        $.ajax({
            url         : $('form#purchaseItem').attr( 'action' ),
            type        : $('form#purchaseItem').attr( 'method' ),
            data        : new FormData($('form#purchaseItem')[0]),
            processData : false,
            contentType : false,
            success: function(response){
                $('#invoiceParticulars').html(response);
                $('form#purchaseItem')[0].reset();
            }
        });
    }
});

$(document).on('change', '.itemUpdate', function() {

    var id = $(this).attr('data-id');
    var quantity = parseFloat($('#quantity-'+id).val());
    var price = parseFloat($('#price-'+id).val());
    var bonusQuantity = parseFloat($('#bonusQuantity-'+id).val());
    var totalQuantity  = (quantity + bonusQuantity);
    var subTotal  = (quantity * price);
    $("#subTotal-"+id).html(subTotal);
    $("#totalQuantity-"+id).html(totalQuantity);
    if(id > 0){
        $.ajax({
            url: Routing.generate('business_purchase_item_update'),
            type: 'POST',
            data:'purchaseItem='+ id +'&quantity='+ quantity +'&price='+ price+'&bonusQuantity='+ bonusQuantity,
            success: function(response) {
                $('#invoiceParticulars').html(response);
            },
        })
    }

});

$(document).on('change', '.batchItemUpdate', function() {
    var url = $(this).attr("data-url");
    var id = $(this).attr('data-id');
    var endReading = $(this).val();
    $.get(url,{'endReading':endReading}, function( response ) {
        if(response === 'success'){
            location.reload();
        }
    });
});


