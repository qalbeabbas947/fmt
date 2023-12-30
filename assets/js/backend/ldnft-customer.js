(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let LDNFT_Customers = {

            init: function() {

                LDNFT_Customers.saveAPI();
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

        LDNFT_Customers.init();
    });   
})( jQuery );