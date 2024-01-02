(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let LDNFT_Sales = {
            init: function() {
                
                LDNFT_Sales.initialize_country_ddl();
                LDNFT_Sales.display_sales_plus_summary();
                LDNFT_Sales.display_new_page_sales_text();
                LDNFT_Sales.ldnft_subsciber_modal_close();
                LDNFT_Sales.display_new_page_sales();
                LDNFT_Sales.sales_view_detail();
                LDNFT_Sales.display_sales_plus_summary_on_search();
                LDNFT_Sales.load_data_from_cookies();
            },
            /**
             * Load data from the cookies
             */
            initialize_country_ddl: function() { 
                $('.ldfmt-sales-country-filter').select2({ minimumInputLength: 3, allowClear: true, width: '150px', placeholder: "Select a Country"});
            },
            /**
             * Load data from the cookies
             */
            load_data_from_cookies: function() { 
                
                $('.ldfmt-plugins-filter').val(jQuery.cookie( 'sales_ldfmt-plugins-filter' ) );
                $('.ldfmt-sales-interval-filter').val(jQuery.cookie( 'sales_ldfmt-sales-interval-filter' ) );
                $('.ldfmt-sales-filter').val(jQuery.cookie( 'sales_ldfmt-sales-filter' ) );
                $('.ldnft-sales-payment-types').val(jQuery.cookie( 'sales_ldnft-sales-payment-types' ) );
                $('.ldfmt-sales-country-filter').val(jQuery.cookie( 'sales_ldfmt-sales-country-filter' ) );
                $('.ldfmt-sales-gateway-filter').val(jQuery.cookie( 'sales_ldfmt-sales-gateway-filter' ) );
                $('.ldnft-sales-general-search').val(jQuery.cookie( 'sales_ldnft-sales-general-search') );
            },
            /**
             * closes the popup
             *
             * @param e
             */
            ldnft_subsciber_modal_close: function(e) { 
                $( '.ldnft-admin-modal-close' ).on( 'click', function(e){
                    $('#ldnft-admin-modal').css('display', 'none');
                } );
            },
            /**
             * displays sals on pagination
             */
            display_new_page_sales_text: function() { 

                $('#ldnft-sales-filter-text' ).on( 'submit', function( e ) {
                    e.preventDefault();

                    $('.ldnft-display-sales-type').val('text');
                    $('.ldnft-freemius-order').val(LDNFT_Sales.getParameterByName('order', $(this).attr('href')));
                    $('.ldnft-freemius-orderby').val(LDNFT_Sales.getParameterByName('orderby', $(this).attr('href')));
                    var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                    LDNFT_Sales.display_sales();
                } );
            },
            /**
             * displays sals on pagination
             */
            display_new_page_sales: function() {
                $('#ldnft_sales_data').on( 'click', '.tablenav-pages a, th a', function(e) {
                    e.preventDefault();
                    $('.ldnft-freemius-order').val(LDNFT_Sales.getParameterByName('order', $(this).attr('href')));
                    $('.ldnft-freemius-orderby').val(LDNFT_Sales.getParameterByName('orderby', $(this).attr('href')));
                    var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                    LDNFT_Sales.display_sales();
                } );
            },
            /**
             * Show sales based on filters
             */
            display_sales_plus_summary: function() {
                $('.ldnft-display-sales-type').val('filter');
                    var page = $('.ldnft-freemius-page').val(1);
                    LDNFT_Sales.display_sales();
                    LDNFT_Sales.load_sales_summary();
            },/**
            * Show sales based on filters
            */
           display_sales_plus_summary_on_search: function() {
                $('.ldnft-sales-search-button').on('click', function(){
                   LDNFT_Sales.display_sales_plus_summary();
                });
           },
            /**
             * Display the sales data based on ajax calls
             */
            display_sales: function() {
                
                var columns_count = $('#ldnft_sales_data table thead tr:eq(0)').find('th:not(.hidden)').length; 
                var placeholder = '<tr>';
                for( var i = 0; i < columns_count; i++ ) {
                    placeholder += '<td align="center">' + LDNFT.preloader_gif_img + '</td>';
                }
                placeholder += '</tr>';
                $('#ldnft_sales_data table tbody').html( placeholder );
                
                var ldnftpage       = $('.ldnft-freemius-page').val();
                let display_type = $('.ldnft-display-sales-type').val();
                if( display_type == 'text' ) {
                    var ldnftplugin     = '';
                    var ldnftinterval   = '';
                    var ldnftstatus     = '';
                    var ldnftsearch     = $('.ldnft-sales-general-search').val();
                    var country_str     = '';
                    var ldnfttypes      = '';
                    var gateway_str     = '';
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    jQuery.cookie( LDNFT.current_page + '_ldnft-sales-general-search', ldnftsearch, { expires: 30, path: '/' } );
                    
                } else {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftinterval   = $('.ldfmt-sales-interval-filter').val();
                    var ldnftstatus     = $('.ldfmt-sales-filter').val();
                    var ldnftsearch     = '';
                    var gateway_str     = $('.ldfmt-sales-gateway-filter').val();
                    var country_str     = $('.ldfmt-sales-country-filter').val();
                    var ldnfttypes      = $('.ldnft-sales-payment-types').val();
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();

                    jQuery.cookie( LDNFT.current_page + '_ldfmt-plugins-filter', ldnftplugin, { expires: 30, path: '/' } );
                    jQuery.cookie( LDNFT.current_page + '_ldfmt-sales-interval-filter', ldnftinterval, { expires: 30, path: '/' } );
                    jQuery.cookie( LDNFT.current_page + '_ldnft-sales-payment-types', ldnfttypes, { expires: 30, path: '/' } );
                    jQuery.cookie( LDNFT.current_page + '_ldfmt-sales-country-filter', country_str, { expires: 30, path: '/' } );
                    jQuery.cookie( LDNFT.current_page + '_ldfmt-sales-gateway-filter', gateway_str, { expires: 30, path: '/' } );
                    jQuery.cookie( LDNFT.current_page + '_ldfmt-sales-filter', ldnftstatus, { expires: 30, path: '/' } );
                }
                
                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_sales_display',
                        paged: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        interval: ldnftinterval,
                        search: ldnftsearch,
                        country: country_str,
                        type: ldnfttypes,
                        gateway: gateway_str,
                        order: order_str,
                        orderby: orderby_str,
                        status: ldnftstatus
                    },
                    success: function (response) {
                        
                        $("#ldnft_sales_data").html(response.display);

                        $("tbody").on("click", ".toggle-row", function(e) {
                            
                            $(this).closest("tr").toggleClass("is-expanded")
                        });
                    }
                });
            },
            /**
             * Show sales summary based on filters
             */ 
            load_sales_summary: function() {
                
                $('.ldnft-subssummary-loader').css('display', 'inline');

                $('.ldnft_sales_points').css('display', 'none');
                $('.ldnft_sales_points_count').html('');
                $('.ldnft_sales_top3_countries').css('display', 'none');;
                //$('.ldnft_sales_tax_fee').css('display', 'none');
                $('.ldnft_sales_new_subscriptions_count').css('display', 'none');
                $('.ldnft_sales_renewals_count').css('display', 'none');
                
                var ldnftpage       = $('.ldnft-freemius-page').val();
                let display_type = $('.ldnft-display-sales-type').val();
                if( display_type == 'text' ) {
                    var ldnftplugin     = '';
                    var ldnftinterval   = '';
                    var ldnftstatus     = '';
                    var ldnftsearch     = $('.ldnft-sales-general-search').val();
                    var country_str     = '';
                    var ldnfttypes      = '';
                    var gateway_str     = '';
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                } else {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftinterval   = $('.ldfmt-sales-interval-filter').val();
                    var ldnftstatus     = $('.ldfmt-sales-filter').val();
                    var ldnftsearch     = '';
                    var gateway_str     = $('.ldfmt-sales-gateway-filter').val();
                    var country_str     = $('.ldfmt-sales-country-filter').val();
                    var ldnfttypes      = $('.ldnft-sales-payment-types').val();
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                }

                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_sales_summary',
                        paged: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        interval: ldnftinterval,
                        search: ldnftsearch,
                        gateway: gateway_str,
                        type: ldnfttypes,
                        country: country_str,
                        order: order_str,
                        orderby: orderby_str,
                        status: ldnftstatus
                    },
                    success: function ( response ) {

                        if( parseInt( response.gross_total_count ) > 0 ) {
                            var list_items = '<ul>';
                            for (const key in response.gross_total ) {
                                if (Object.hasOwnProperty.call(response.gross_total, key)) {
                                
                                    list_items += '<li>'+key+': '+response.gross_total[key]+'</li>';
                                    
                                }
                            }
                            list_items += '</ul>';
                        } else {
                            var list_items = '0';
                        }
                        
                        $('.ldnft_sales_points').html(list_items).css('display', 'block');
                        $('.ldnft_sales_points_tooltip').html( response.gross_message );
                        $('.ldnft_sales_points_count').html('(' + response.gross_total_count+')');
                        
                        if( parseInt( response.gross_total_count ) > 0 ) {
                            var tax_rate_total = '<ul>';
                            
                            for (const key in response.tax_rate_total ) {
                                if (Object.hasOwnProperty.call(response.tax_rate_total, key)) {
                                    tax_rate_total += '<li>'+key+': '+response.tax_rate_total[key]+'</li>';
                                }
                            }

                            tax_rate_total += '</ul>';
                        } else {
                            var tax_rate_total = '0';
                        }
                    // $('.ldnft_sales_tax_fee').html(tax_rate_total).css('display', 'block');
                        //$('.ldnft_sales_tax_fee_tooltip').html( response.tax_message );

                        $('.ldnft_sales_renewals_amount').html(response.total_new_renewals_amount).css('display', 'block');
                        $('.ldnft_new_renewals_count').html('(' + response.total_new_renewals + ')');
                        $('.ldnft_sales_renewals_tooltip').html( response.new_renewals_message );
                        $('.ldnft-subssummary-loader').css('display', 'none');
                        $('.ldnft_sales_new_subscriptions').html(response.total_new_subscriptions_amount).css('display', 'block');
                        $('.ldnft_new_subscriptions_count').html('(' + response.total_new_subscriptions+')');
                        $('.ldnft_new_subscriptions_tooltip').html( response.new_subscriptions_message );
                        $('.ldnft_sales_top3_countries_tooltip').html( response.countries_message );
                        
                        var idx = 0;
                        if( parseInt( response.gross_total_count ) > 0 ) {
                            var list_items = '<table class="ldnft-course-currency-totals">';
                            for (const key in response.countries ) {
                                if( idx == 0 ) {
                                    list_items += '<tr>';
                                    list_items += '<th>Country</th>';
                                    for (const key in response.currency_keys ) {
                                        list_items += '<th>'+response.currency_keys[key]+'</th>';
                                    }
                                    list_items += '</tr>';
                                    
                                }
                                idx++;
                                if (Object.hasOwnProperty.call(response.countries, key)) {
                                    const element = response.countries[key];
                                    var gross_str = '';

                                    list_items += '<tr>';
                                    list_items += '<td>'+element.country_name+'</td>';
                                    for (const key in element.gross ) {
                                        list_items += '<td>'+element.gross[key]+'</td>';
                                    }
                                    list_items += '</tr>';
                                }
                            }
                            list_items += '</ul>';
                        } else {
                            var list_items = '<span class="ldnft-empty-countries-box">-</span>';
                        }
                        
                        $('.ldnft_sales_top3_countries').html(list_items).css('display', 'block');

                    }
                });
            },
            /**
             * get Parameters by name
             */
            
            getParameterByName: function( name, url) {
                
                name = name.replace(/[\[\]]/g, '\\$&');
                
                var regex = new RegExp( '[?&]' + name + '(=([^&#]*)|&|#|$)' ),
                    results = regex.exec( url );
                if ( ! results ) return null;
                if ( ! results[2] ) return '';
                return decodeURIComponent( results[2].replace(/\+/g, ' ') );
            },
            /**
            * Display the popup. 
            *
            * @param e
            */
            sales_view_detail: function( e ) { 
                
                $( '#ldnft_sales_data' ).on( 'click', '.ldnft_sales_view_detail',  function( e ) {
                    e.preventDefault();
                
                    var lnk = $( this );
                    
                    $('#ldnft-admin-modal').css('display', 'block');
                    
                    $('#ldnft-review-coloumn-transaction-id').html( lnk.data('id') );
                    $('#ldnft-review-coloumn-user_id').html( lnk.data('user_id') );
                    $('#ldnft-review-coloumn-username').html( lnk.data('username') );
                    $('#ldnft-review-coloumn-useremail').html( lnk.data('useremail') );
                    $('#ldnft-review-coloumn-subscription_id').html( lnk.data('subscription_id') );
                    $('#ldnft-review-coloumn-gateway_fee').html( lnk.data('gateway_fee') );
                    $('#ldnft-review-coloumn-gross').html( lnk.data('gross') );
                    $('#ldnft-review-coloumn-license_id').html( lnk.data('license_id') );
                    $('#ldnft-review-coloumn-gateway').html( lnk.data('gateway') );
                    $('#ldnft-review-coloumn-country_code').html( lnk.data('country_code') );
                    $('#ldnft-review-coloumn-is_renewal').html( lnk.data('is_renewal') );
                    $('#ldnft-review-coloumn-type').html( lnk.data('type') );
                    $('#ldnft-review-coloumn-bound_payment_id').html( lnk.data('bound_payment_id') );
                    $('#ldnft-review-coloumn-created').html( lnk.data('created') );
                    $('#ldnft-review-coloumn-vat').html( lnk.data('vat') );
                    $('#ldnft-review-coloumn-install_id').html( lnk.data('install_id') );
                    $('#ldnft-review-coloumn-plan_id').html( lnk.data('plan_id') );
                    
                    $('#ldnft-review-coloumn-zip_postal_code').html( lnk.data('zip_postal_code') );
                    
                    $('#ldnft-review-coloumn-coupon_id').html( lnk.data('coupon_id') );
                    $('#ldnft-review-coloumn-plugin_id').html( lnk.data('plugin_id') );
                    $('#ldnft-review-coloumn-external_id').html( lnk.data('external_id') );
                    $('#ldnft-review-coloumn-currency').html( lnk.data('currency') );
                    $('#ldnft-review-coloumn-username').html( lnk.data('username') );
                    $('#ldnft-review-coloumn-useremail').html( lnk.data('useremail') );
                } );
             },
        };

        LDNFT_Sales.init();
    });   
})( jQuery );