(function( $ ) { 'use strict';
    $( document ).ready( function() {
        var LDNFTbackEnd = {
            init: function() {
                $('.ldnft-success-message').hide();
                $('.ldnft-settings-mailpoet').on('submit', LDNFTbackEnd.post_mailpoet_form);
                $('.ldnft-update-subscriptions').on('click', LDNFTbackEnd.update_subscritions);
                $('.ldnft-update-sales').on('click', LDNFTbackEnd.update_sales);
                $('#ldnft-update-customers').on('click', LDNFTbackEnd.update_customers);
            },
            update_customers: function(e) {
                e.preventDefault();
                $('#ldnft-customers-import-message').html('').css('display', 'none');
                $('.ldfmt-data-loader').css('display', 'inline-block');
                var btn = $(this);
                btn.attr('disabled', true);
                var data = {
                    action: 'ldnft_update_customers'
                }
                
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    $('#ldnft-customers-import-message').html(response.message).css('display', 'block');
                    $('.ldfmt-data-loader').css('display', 'none');
                    btn.attr('disabled', false);
                   // document.location.reload();
                } );
            },
            update_sales: function(e) {
                e.preventDefault();
                $('#ldnft-sales-import-message').html('').css('display', 'none');
                $('.ldfmt-data-loader').css('display', 'inline-block');
                var btn = $(this);
                btn.attr('disabled', true);
                var data = {
                    action: 'ldnft_update_sales'
                }
                
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    $('#ldnft-sales-import-message').html(response.message).css('display', 'block');
                    $('.ldfmt-data-loader').css('display', 'none');
                    btn.attr('disabled', false);
                   // document.location.reload();
                } );
            },
            update_subscritions: function(e) {
                e.preventDefault();
                $('#ldnft-subscription-import-message').html('').css('display', 'none');
                $('.ldfmt-data-loader').css('display', 'inline-block');
                var btn = $(this);
                btn.attr('disabled', true);
                var data = {
                    action: 'ldnft_update_subscritions'
                    
                }
                
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    $('#ldnft-subscription-import-message').html(response.message).css('display', 'block');
                    btn.attr('disabled', false);
                    $('.ldfmt-data-loader').css('display', 'none');
                    document.location.reload();
                } );
            },
            /**
             * Exicute Ajax functionality after error message.
             *
             * @param action
             * @param formData
             */
            post_mailpoet_form: function( e ) {
                e.preventDefault();
                $('#ldnft-settings-import-mailpoet-message').html('').css('display', 'none');
                $('#ldnft-settings-import-mailpoet-errmessage').html('').css('display', 'none');
                $('.ldnft-success-message').show();
                var data = $( this ).serialize();
                $('#ldnft_mailpeot_list, #ldnft_mailpeot_plugin, .ldnft-mailpoet-save-setting_import').attr('disabled', true);
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    
                    if( JSON.parse(response).message!='' ) {
                        $('#ldnft-settings-import-mailpoet-message').html(JSON.parse(response).message).css('display', 'block');
                    }
                    
                    if( JSON.parse(response).errormsg!='' ) {
                        $('#ldnft-settings-import-mailpoet-errmessage').html(JSON.parse(response).errormsg).css('display', 'block');
                    }
                    
                    $('#ldnft_mailpeot_list, #ldnft_mailpeot_plugin, .ldnft-mailpoet-save-setting_import').attr('disabled', false);
                    $('.ldnft-success-message').hide();
                } );
            },
        };

        LDNFTbackEnd.init();
    });
})( jQuery );