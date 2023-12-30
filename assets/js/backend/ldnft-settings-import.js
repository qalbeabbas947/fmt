(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let Settings_Import = {

            init: function() {

                Settings_Import.saveAPI();
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

        Settings_Import.init();
    });   
})( jQuery );