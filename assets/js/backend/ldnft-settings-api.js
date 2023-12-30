(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let Settings_API = {

            init: function() {

                Settings_API.saveAPI();
                Settings_API.syncData();
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

            /**
             * Sync Data 
             * From Freemius server to local db
             */
            syncData: function() {
                
                $( '.ldnft-load-more-btn' ).on( 'click', function( e ) {

                    e.preventDefault();
                    let self = $( this );
                });
            }
        };

        Settings_API.init();
    });   
})( jQuery );