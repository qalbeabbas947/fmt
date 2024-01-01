(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let LDNFT_Customers = {
            init: function() {

                LDNFT_Customers.load_data_from_cookies();
                LDNFT_Customers.display_customers();
                LDNFT_Customers.display_customers_search_submit();
                LDNFT_Customers.display_new_page_customers();
                LDNFT_Customers.display_customers_onchange();
            },
            /**
             * Load data from the cookies
             */
            load_data_from_cookies: function() { 
                $('.ldfmt-plugins-filter').val(jQuery.cookie( 'customers_ldfmt-plugins-filter' ) );
                $('.ldfmt-plugins-customers-status').val(jQuery.cookie( 'customers_ldfmt-plugins-status' ) );
                $('.ldfmt-plugins-customers-marketing').val(jQuery.cookie( 'customers_ldfmt-plugins-marketing' ) );
                $('.ldnft-customers-general-search').val(jQuery.cookie( 'customers_ldnft-customers-general-search') );
                $('.ldfmt-payment-status').val(jQuery.cookie( 'customers_ldfmt-plugins-pmtstatus') );
            },
            /**
             * displays customers on pagination clicks
            */
			display_new_page_customers: function() { 

                $('#ldnft_customers_data').on('click', '.tablenav-pages a, th a', function( e ) {
                    $( '.ldnft-freemius-order'   ).val(LDNFT_Customers.getParameterByName( 'order', $( this ).attr('href')));
                    $( '.ldnft-freemius-orderby' ).val(LDNFT_Customers.getParameterByName( 'orderby', $( this ).attr('href') ));
                    
                    e.preventDefault();
                    var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                    LDNFT_Customers.display_customers();
                });
            },
            /**
             * Show customers based on filters
             */
			display_customers_onchange: function() {
                $('.ldnft-customer-search-button').on('click', function() {
                    $('.ldnft-display-customers-type').val('filter');
                    var page = $('.ldnft-freemius-page').val(1);
                    LDNFT_Customers.display_customers();
                });
            },
            /**
             * Show customers based on filters
             */
            display_customers_search_submit: function() {

                $('#ldnft-reviews-filter-text').on('submit', function(e) {
                    e.preventDefault();

                    $('.ldnft-display-customers-type').val('text');
                    var page = $('.ldnft-freemius-page').val(1);
                    LDNFT_Customers.display_customers();
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
            /**
             * Display the customers data based on ajax calls
             */
            display_customers: function() {
				
                var columns_count = $('#ldnft_customers_data table thead tr:eq(0)').find('th:not(.hidden)').length; 
                var placeholder = '<tr>';
                for( var i = 0; i < columns_count; i++ ) {
                    placeholder += '<td align="center">' + LDNFT_Customers.preloader_gif_img + '</td>';
                }
                placeholder += '</tr>';
                $('#ldnft_customers_data table tbody').html( placeholder );
               
                var ldnftpage       = $('.ldnft-freemius-page').val();
                var order_str       = $('.ldnft-freemius-order').val();
                var orderby_str     = $('.ldnft-freemius-orderby').val();

                let display_type = $('.ldnft-display-customers-type').val();
                if( display_type == 'text' ) {
                    var ldnftplugin     = '';
                    var ldnftstatus     = '';
                    var ldnftsearch     = $('.ldnft-customers-general-search').val();
                    var pmtstatus_str   = '';
                    var marketing_str   = '';
                    
                    jQuery.cookie( 'customers_ldnft-customers-general-search', ldnftsearch, { expires: 30, path: '/' } );

                } else {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftstatus     = $('.ldfmt-plugins-customers-status').val();
                    var ldnftsearch     = '';
                    var pmtstatus_str   = $('.ldfmt-payment-status').val();
                    var marketing_str   = $('.ldfmt-plugins-customers-marketing').val();

                    jQuery.cookie( 'customers__ldfmt-plugins-filter', ldnftplugin, { expires: 30, path: '/' } );
                    jQuery.cookie( 'customers__ldfmt-plugins-status', ldnftstatus, { expires: 30, path: '/' } );
                    jQuery.cookie( 'customers__ldfmt-plugins-pmtstatus', pmtstatus_str, { expires: 30, path: '/' } );
                    jQuery.cookie( 'customers__ldfmt-plugins-marketing', marketing_str, { expires: 30, path: '/' } );
                }

                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_customers_display', 
                        paged: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        status: ldnftstatus,
                        order: order_str,
                        search: ldnftsearch,
                        pmtstatus: pmtstatus_str,
                        marketing: marketing_str,
                        orderby: orderby_str,
                    },
                    success: function (response) {
                        $("#ldnft_customers_data").html(response.display);

                        $("tbody").on("click", ".toggle-row", function(e) {
                            
                            $(this).closest("tr").toggleClass("is-expanded")
                        });
                    }
                });
            },
        };

        LDNFT_Customers.init();
    });   
})( jQuery );