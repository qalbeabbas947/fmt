(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let Settings_WebHook = {

            init: function() {

                Settings_WebHook.saveAPI();
            },

            /**
             * Comment here
             */
            saveAPI: function() {

                $( '.ldnft-load-more-btn' ).on( 'click', function( e ) {

                    e.preventDefault();
                    let self = $( this );
                });
            },
        };

        Settings_WebHook.init();
    });   
})( jQuery );