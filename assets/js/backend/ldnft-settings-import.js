(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let Settings_Import = {

            init: function() {
                Settings_Import.post_mailpoet_form();
            },
            /**
             * Comment here
             */
            post_mailpoet_form: function() {
                $( '.ldnft-success-message' ).hide();
                $( '.ldnft-settings-mailpoet' ).on( 'submit', function( e ) {

                    e.preventDefault();
                    $('#ldnft-settings-import-mailpoet-message').html('').css('display', 'none').change();
                    $('#ldnft-settings-import-mailpoet-errmessage').html('').css('display', 'none').change();
                    $('.ldnft-success-message').show();
                    var data = $( this ).serialize();
                    $('#ldnft_mailpeot_list, #ldnft_mailpeot_plugin, .ldnft-mailpoet-save-setting_import').attr('disabled', true);
                    jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                        
                        if( JSON.parse(response).message!='' ) {
                            $('#ldnft-settings-import-mailpoet-message').html(JSON.parse(response).message).css('display', 'block').change();
                        } else if( JSON.parse(response).errormsg!='' ) {
                            $('#ldnft-settings-import-mailpoet-errmessage').html(JSON.parse(response).errormsg).css('display', 'block').change();
                        }
                        
                        $('#ldnft_mailpeot_list, #ldnft_mailpeot_plugin, .ldnft-mailpoet-save-setting_import').attr('disabled', false);
                        $('.ldnft-success-message').hide();
                    } );
                });
            },
        };

        Settings_Import.init();
    });   
})( jQuery );