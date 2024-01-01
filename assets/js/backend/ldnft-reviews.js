(function( $ ) { 'use strict';
    
    $( document ).ready( function() {
        let LDNFT_Reviews = {
            init: function() {
                
                LDNFT_Reviews.display_reviews();
                LDNFT_Reviews.review_view_detail();
                LDNFT_Reviews.ldnft_is_featured_enabled();
                LDNFT_Reviews.display_new_page_reviews();
                LDNFT_Reviews.display_reviews_onchange();
                LDNFT_Reviews.display_reviews_text();
                LDNFT_Reviews.load_review_cookies();

            },
            load_review_cookies : function(e){  
                $('.ldfmt-plugins-filter').val( jQuery.cookie( 'reviews_ldfmt-plugins-filter') );
                $('.ldfmt-plugins-reviews-verified').val( jQuery.cookie( 'reviews_ldfmt-plugins-status')  );
                $('.ldfmt-plugins-reviews-featured').val( jQuery.cookie( 'reviews_ldfmt-plugins-featured')  );
                $('#ldnft-reviews-general-search').val( jQuery.cookie( 'reviews_reviews_search' )  );
            },
            ldnft_is_featured_enabled: function(e){
                $( '#ldnft_reviews_data' ).on( 'click', '.ldnft_is_featured_enabled_click', function() {
                    $( '#ldnft-reviews-filter' ).find( '.ldnft_is_featured_enabled_click' ).attr( 'disabled', true );
                    var cid         = $(this).data( 'id' );
                    var pid         = $(this).data( 'plugin_id' );
                    var status_str  = $(this).prop( 'checked' );
                    var selected    = $(this);
                    $( '#ldnft_reviews_data' ).find( '.ldnft-checkmark' ).remove();
                    $.ajax( {
                        url: ajaxurl,
                        dataType: 'json',
                        data: {
                            action: 'ldnft_reviews_enable_disable',
                            id: cid,
                            plugin_id: pid,
                            status: status_str
                        },
                        success: function ( response ) {
                            selected.parent().find( '.ldnft-checkmark' ).remove();
    
                            selected.after('<div class="ldnft-checkmark">&#10003;</div>');
                            $( '#ldnft-reviews-filter' ).find( '.ldnft_is_featured_enabled_click' ).attr( 'disabled', false );
                        }
                    } );
                } );
            },
			/**
             * pagination click
			 */
			display_new_page_reviews: function() {
                $('#ldnft_reviews_data').on('click', '.tablenav-pages a, th a', function( e ){
                    e.preventDefault();

                    $( '.ldnft-freemius-order' ).val( LDNFT_Reviews.getParameterByName( 'order', $( this ).attr( 'href' ) ) );
                    $( '.ldnft-freemius-orderby' ).val( LDNFT_Reviews.getParameterByName( 'orderby', $( this ).attr( 'href' ) ) );
    
                    var page = $( '.ldnft-freemius-page' ).val( $( this ).data( 'paged' ) );
    
                    LDNFT_Reviews.display_reviews( );
                });
            },
            /**
             * closes the popup
             *
             * @param e
             */
            ldnft_subsciber_modal_close: function(e) { 
                $('#ldnft-admin-modal').css('display', 'none');
            },
            getParameterByName: function( name, url) {
                
                name = name.replace(/[\[\]]/g, '\\$&');
                
                var regex = new RegExp( '[?&]' + name + '(=([^&#]*)|&|#|$)' ),
                    results = regex.exec( url );
                if ( ! results ) return null;
                if ( ! results[2] ) return '';
                return decodeURIComponent( results[2].replace(/\+/g, ' ') );
            },
            /**
             * Show reviews based on filters
             */
			display_reviews_onchange: function() {
                $('.ldnft-reviews-search-button').on('click', function(){
                    $('.ldnft-display-review-type').val( 'filter' );
                    var page = $('.ldnft-freemius-page').val( 1 );
                    LDNFT_Reviews.display_reviews();
                });
            },
            /**
             * Display the reviews data based on ajax calls
             */
            display_reviews_text: function() {

                $('#ldnft-reviews-filter-text').on('submit', function( e ) {
                    e.preventDefault();
                
                    $('.ldnft-display-review-type').val( 'text' );
                    var page = $('.ldnft-freemius-page').val(1);
                    LDNFT_Reviews.display_reviews();
                });
                
                
            },
            /**
             * Display the reviews data based on ajax calls
             */
            display_reviews: function() {
				
                var columns_count = $('#ldnft_reviews_data table thead tr:eq(0)').find('th:not(.hidden)').length; 
                var placeholder = '<tr>';
                for( var i = 0; i < columns_count; i++ ) {

                    placeholder += '<td align="center">' + LDNFT.preloader_gif_img + '</td>';
                }
                
                placeholder += '</tr>';
                $( '#ldnft_reviews_data table tbody' ).html( placeholder );
               
                var ldnftpage       = $( '.ldnft-freemius-page' ).val();
                var order_str       = $( '.ldnft-freemius-order' ).val();
                var orderby_str     = $( '.ldnft-freemius-orderby' ).val();

                var display_type = $('.ldnft-display-review-type').val();
                if( display_type == 'filter' ) {

                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftverified   = $('.ldfmt-plugins-reviews-verified').val();
                    var featured_str    = $('.ldfmt-plugins-reviews-featured').val();
                    var search_str      = '';

                    jQuery.cookie( 'reviews_ldfmt-plugins-filter', ldnftplugin, { expires: 30, path: '/' } );
                    jQuery.cookie( 'reviews_ldfmt-plugins-status', ldnftverified, { expires: 30, path: '/' } );
                    jQuery.cookie( 'reviews_ldfmt-plugins-featured', featured_str, { expires: 30, path: '/' } );

                    
                } else {

                    var ldnftplugin     = '';
                    var ldnftverified   = '';
                    var featured_str    = '';
                    var search_str      = $('#ldnft-reviews-general-search').val();
                    jQuery.cookie( 'reviews_reviews_search', search_str, { expires: 30, path: '/' } );
                }

                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action:                 'ldnft_reviews_display',
                        paged:                  ldnftpage,
                        ldfmt_plugins_filter:   ldnftplugin,
                        order:                  order_str,
                        orderby:                orderby_str,
                        verified:               ldnftverified,
                        featured:               featured_str,
                        search:                 search_str
                    },
                    success: function (response) {

                        $("#ldnft_reviews_data").html(response.display);

                        $("tbody").on("click", ".toggle-row", function(e) {
                            
                            $(this).closest("tr").toggleClass("is-expanded");
                        });
                    }
                });
            },
            /**
             * Show review popup on get more click
             */
			review_view_detail: function() { 

                $( '#ldnft_reviews_data' ).on( 'click', '.ldnft_review_view_detail',  function( e ) {
                    e.preventDefault();
                
                    var lnk = $( this );
                    
                    $('#ldnft-review-coloumn-transaction-id').html( lnk.data('id') );
                    $('#ldnft-review-coloumn-user_id').html( lnk.data('user_id') );
                    $('#ldnft-review-coloumn-useremail').html( lnk.data('useremail') );
                    $('#ldnft-review-coloumn-name').html( lnk.data('name') );
                    $('#ldnft-review-coloumn-company').html( lnk.data('company') );
                    $('#ldnft-review-coloumn-job_title').html( lnk.data('job_title') );
                    $('#ldnft-review-coloumn-company_url').html( lnk.data('company_url') );
                    $('#ldnft-review-coloumn-picture').html( lnk.data('picture') );
                    
                    if( lnk.data('profile_url') != '' && lnk.data('profile_url')!='-' ) {
                        $('#ldnft-review-coloumn-profile_url').html( '<a href="'+lnk.data('profile_url')+'" target="_blank">'+lnk.data('profile_url')+'</a>' );
                    } else {
                        $('#ldnft-review-coloumn-profile_url').html( lnk.data('profile_url') );
                    }
                    
                    $('#ldnft-review-coloumn-is_verified').html( lnk.data('is_verified') );
                    $('#ldnft-review-coloumn-is_featured').html( lnk.data('is_featured') );
                    
                    if( lnk.data('sharable_img') != '' && lnk.data('sharable_img')!='-' ) {
                        $('#ldnft-review-coloumn-sharable_img').html( '<a href="'+lnk.data('sharable_img')+'" target="_blank">'+lnk.data('sharable_img')+'</a>' );
                    } else {
                        $('#ldnft-review-coloumn-sharable_img').html( lnk.data('sharable_img') );
                    }
                    
                    $('#ldnft-review-coloumn-title').html( lnk.data('title') );
                    
                    $('#ldnft-review-coloumn-rate').html( lnk.parent().parent().find('.column-rate').html() );
                    $('#ldnft-review-coloumn-text').html( lnk.data('text') );
                    $('#ldnft-review-coloumn-created').html( lnk.data('created') );
                    
                    $('#ldnft-admin-modal').css('display', 'block');
                } );
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