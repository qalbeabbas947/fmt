(function( $ ) { 'use strict';
    $( document ).ready( function() {
        var LDFMTCheckout = {
            /**
             * Initial function to load things
             */
            init: function() {
                
                var plugin_id_value = $('#ldnft-checkout-plugin_id').val();
                var plan_id_value = $('#ldnft-checkout-plan_id').val();
                var public_key_value = $('#ldnft-checkout-public_key').val();
                var image_value = $('#ldnft-checkout-image').val();
                
                var handler = FS.Checkout.configure({
                    plugin_id: plugin_id_value,
                    plan_id: plan_id_value,
                    public_key: public_key_value,
                    image: image_value,
                });
                $('#ldnft-purchase').on('click', function(e) {
                    handler.open({
                        name: 'Custom Tabs for LearnDash',
                        licenses: $('input[name="ld_licenses_options"]:checked').val(),
                        // You can consume the response for after purchase logic.
                        purchaseCompleted: function(response) {
                            // The logic here will be executed immediately after the purchase confirmation.                                // alert(response.user.email);
                        },
                        success: function(response) {
                            // The logic here will be executed after the customer closes the checkout, after a successful purchase.                                // alert(response.user.email);
                        }
                    });
                    e.preventDefault();
                });
            },
        };

        LDFMTCheckout.init();
    });   
})( jQuery );

