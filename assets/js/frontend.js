(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        var LDNFT_Frontend = {

            init: function() {

                LDNFT_Frontend.paginatedReviews();
                LDNFT_Frontend.sliderReviews();
            },

            /**
             * 
             * To display the product reviews on frontend.
             * Options are pagination/onetime/slider
             */
            paginatedReviews: function() {

                $( '.ldnft-load-more-btn' ).on( 'click', function( e ) {

                    e.preventDefault();
                    let self = $( this );
                    let offSet = self.data( 'offset' ) + self.data( 'limit' );
                    let data = {
                        'action': 'ldnft_review_load_more',
                        'security': LDNFT.security,
                        'limit': self.data( 'limit' ),
                        'offset': self.data( 'offset' ),
                        'plugin_id': self.data( 'plugin_id' )
                    };

                    jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                        
                        response = JSON.parse( response );
                        if( response.status == true ) {
                            
                            let data = response.data;
                            self.parents( '.ldnft-reviews-load-more' ).siblings('.paginated-review-wrapper').append( data ).change();
                            self.parents( '.ldnft-reviews-load-more' ).find('.ldnft-load-more-btn').data( 'offset', offSet ).change();
                        }
                    });
                } );
            },

            /**
             * Add a slider review
             */
            sliderReviews: function() {
                if( $('.ldnft-slider-handler').length > 0 ) {
                    $( function(){
                        $('.ldnft-slider-handler').bxSlider({
                            mode: 'horizontal',
                            captions: true,
                            slideWidth: 600
                        } );
                    } );
                }   
            }
        };

        LDNFT_Frontend.init();
    });   
})( jQuery );