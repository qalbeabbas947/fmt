(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let LDNFT_Sales = {

            init: function() {

                LDNFT_Sales.saveAPI();
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

        LDNFT_Sales.init();
    });   
})( jQuery );