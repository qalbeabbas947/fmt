(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let LDNFT_Reviews = {

            init: function() {

                LDNFT_Reviews.saveAPI();
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

        LDNFT_Reviews.init();
    });   
})( jQuery );