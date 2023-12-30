(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let LDNFT_Subscriptions = {

            init: function() {

                LDNFT_Subscriptions.saveAPI();
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

        LDNFT_Subscriptions.init();
    });   
})( jQuery );