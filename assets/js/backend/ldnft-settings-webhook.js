(function( $ ) { 'use strict';
    
    $( document ).ready( function() {
        
        let Settings_WebHook = {

            init: function() {
                Settings_WebHook.save_webhook_settings();
                Settings_WebHook.load_webhook_settings();
                Settings_WebHook.load_webhook_settings_callback();
            },
            /**
             * save webhook settings
             */
            save_webhook_settings: function() {

                $( '#ldnft-save-webhook-setting-form' ).on( 'submit', function( e ) {

                    e.preventDefault();
                    var form = $(this).serialize();
                    var calling_btn = $('.ldnft-save-webhook-setting');
                
                    calling_btn.attr( 'disabled', true );
                    $('.ldnft-webhook-message').html( '' ).css('display', 'none');
                    jQuery.post( LDNFT.ajaxURL, form, function( response ) {
                        $('.ldnft-webhook-message').html( response ).css('display', 'block');
                        calling_btn.attr('disabled', false);
                    } );
                } ); 
            },
            /**
             * load webhook settings
             */
            load_webhook_settings_callback: function() {
                
                $( '.ldnft-load-webhook-settings-button' ).on( 'click', function() {
                    Settings_WebHook.load_webhook_settings();
                });
            },

            /**
             * load webhook settings
             */
            load_webhook_settings: function() {
                
                var sel_plugin_id = $('#ldnft_webhook_plugin_ddl').val();

                var data = {
                    action: 'ldnft_webhook_plugin_settings', plugin_id: sel_plugin_id
                }
                $('.ldnft-save-webhook-setting').attr('disabled', true);
                $('.ldnft-plugin-ddl-loader').css('display', 'inline-block');
                
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    $('.ldnft-webhook-settings-fields').html( response );
                    $('.ldnft-webhook-message').html( '' ).css('display', 'none');
                    $('.ldnft-save-webhook-setting').attr( 'disabled', false );
                    $('.ldnft-plugin-ddl-loader').css('display', 'none');
                    
                } ); 
            },
        };

        Settings_WebHook.init();
    });   
})( jQuery );