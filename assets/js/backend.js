(function( $ ) { 'use strict';
    $( document ).ready( function() {
        
        var LDNFTbackEnd = {
            init: function() {
                $('.ldnft-success-message').hide();
                $('.ldnft-settings-mailpoet').on('submit', LDNFTbackEnd.post_mailpoet_form);
                $('.ldnft_subscriber_load_next').on('click', LDNFTbackEnd.subscriber_load_next);
                $('.ldnft_subscribers_view_detail').on('click', LDNFTbackEnd.subscribers_view_detail);
                $('.ldnft-admin-modal-close').on('click', LDNFTbackEnd.ldnft_subsciber_modal_close);
             },
            /**
             * closes the popup
             *
             * @param e
             */
            ldnft_subsciber_modal_close: function(e) {
                $('#ldnft-admin-modal').css('display', 'none');
            },
            /**
            * Display the popup.
            *
            * @param e
            */
            subscribers_view_detail: function( e ) { 
               e.preventDefault();
               
               var lnk = $( this );
               
               $('#ldnft-admin-modal').css('display', 'block');
               $('.ldnft-popup-loader').css('display', 'block');
               $('.ldnft-admin-modal-body').html('');
               jQuery.post( LDNFT.ajaxURL, lnk.data(), function( response ) {
                    $('#ldnft-admin-modal').css('display', 'block');
                    $('.ldnft-admin-modal-body').html(response);
                    $('.ldnft-popup-loader').css('display', 'none');
               } );
            },
            /**
             * imports the data from mailpoet.
             *
             * @param e
             */
            subscriber_load_next: function( e ) { 
                e.preventDefault();
                var lnk = $( this );
                //lnk.action = '';
                jQuery.post( LDNFT.ajaxURL, lnk.data(), function( response ) {
                    if(response == '') {
                        document.location = lnk.attr('href');
                    } else {
                        alert(response);
                    }
                } );
            },
            /**
             * imports the data from mailpoet.
             *
             * @param e
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