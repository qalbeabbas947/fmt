(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let LDNFT_Subscriptions = {
            display_subscriptions_type: 'filter',
            subscription_page_loaded:   'no',
            init: function() {
                
                LDNFT_Subscriptions.display_subscriptions_plus_summary_submit();
                LDNFT_Subscriptions.display_new_page_subscriptions();
                LDNFT_Subscriptions.display_subscriptions_plus_summary_callback();
                LDNFT_Subscriptions.display_subscriptions_plus_summary();		
                LDNFT_Subscriptions.ldnft_subsciber_modal_close();
                LDNFT_Subscriptions.subscription_plans_dropdown();
                LDNFT_Subscriptions.attach_subscription_plans_dropdown();
                LDNFT_Subscriptions.subscribers_view_detail();
                LDNFT_Subscriptions.load_data_from_cookie();
            },
            /**
             * Show subscription based on pagination
             */
            display_new_page_subscriptions: function(e) {

                $('#ldnft_subscriptions_data').on('click', '.tablenav-pages a, th a', function(){
                    e.preventDefault();
                    $('.ldnft-freemius-order').val(LDNFT_Subscriptions.getParameterByName('order', $(this).attr('href')));
                    $('.ldnft-freemius-orderby').val(LDNFT_Subscriptions.getParameterByName('orderby', $(this).attr('href')));
    
                    var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                    LDNFT_Subscriptions.display_subscriptions();
                });
                
            },
            /**
             * Show subscription summary
             */
            display_subscriptions_plus_summary_callback: function() {
                
                $('.ldnft-subscription-search-button').on('click', function(){
                    LDNFT_Subscriptions.display_subscriptions_plus_summary();
                });
                
            },
            /**
             * Show subscription summary
             */
            display_subscriptions_plus_summary: function() {
                
                LDNFT_Subscriptions.display_subscriptions_type = 'filter';
                var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                LDNFT_Subscriptions.display_subscriptions();
                LDNFT_Subscriptions.load_subscription_summary();
            },

            /**
             * Show subscription summary
             */
            display_subscriptions_plus_summary_submit: function( e ) {

                $('#ldnft-subscription-filter-form-text').on('submit', function(){
                    e.preventDefault();
                    LDNFT_Subscriptions.display_subscriptions_type = 'text';
                    var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                    LDNFT_Subscriptions.display_subscriptions();
                    LDNFT_Subscriptions.load_subscription_summary();
                });
            },
            
            /**
             * Display the subscriptions data based on ajax calls
             */
            display_subscriptions: function() {
                
                var columns_count = $('#ldnft_subscriptions_data table thead tr:eq(0)').find('th:not(.hidden)').length; 
                var placeholder = '<tr>';
                for( var i = 0; i < columns_count; i++ ) {
                    placeholder += '<td align="center">' + LDNFT.preloader_gif_img + '</td>';
                }
                placeholder += '</tr>';
                $('#ldnft_subscriptions_data table tbody').html( placeholder );
               
                var ldnftpage       = $('.ldnft-freemius-page').val();
                if( LDNFT_Subscriptions.display_subscriptions_type == 'filter' ) {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftinterval   = $('.ldfmt-subscription-interval-filter').val();
                    var ldnftcountry     = $('.ldfmt-subscription-country-filter').val();
                    var ldnftplan_id    = $('.ldfmt-subscription-plan_id-filter').val();
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    var gateway_str     = $('.ldfmt-subscription-gateway-filter').val();
                    var status_str      = $('.ldfmt-subscription-status-filter').val();
                    var search_str      = '';

                    jQuery.cookie( LDNFT.current_page + '_ldfmt-plugins-filter', ldnftplugin, { expires: 30, path: '/' } );
                    jQuery.cookie( LDNFT.current_page + '_ldfmt-sales-interval-filter', ldnftinterval, { expires: 30, path: '/' } );
                    jQuery.cookie( LDNFT.current_page + '_ldfmt-subscription-country-filter', ldnftcountry, { expires: 30, path: '/' } );
                    if( LDNFT_Subscriptions.subscription_page_loaded == 'yes' ) {
                        jQuery.cookie( LDNFT.current_page + '_ldfmt-sales-plan_id-filter', ldnftplan_id, { expires: 30, path: '/' } );
                    }
                    
                    jQuery.cookie( LDNFT.current_page + '_ldfmt-subscription-gateway-filter', gateway_str, { expires: 30, path: '/' } );
                } else {
                    var ldnftplugin     = '';
                    var ldnftinterval   = '';
                    var ldnftcountry     = '';
                    var ldnftplan_id    = '';
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    var gateway_str     = '';
                    var status_str      = '';
                    var search_str      = $('.ldnft-subscription-general-search').val();
                    jQuery.cookie( LDNFT.current_page + '_ldnft-subscription-general-search', search_str, { expires: 30, path: '/' } );
                }
                
                $.ajax({

                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_subscriptions_display',
                        paged: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        interval: ldnftinterval,
                        country: ldnftcountry,
                        gateway: gateway_str,
                        search: search_str,
                        status: status_str,
                        order: order_str,
                        orderby: orderby_str,
                        plan_id: ldnftplan_id,
                    },
                    success: function (response) {
                        $("#ldnft_subscriptions_data").html(response.display);

                        $("tbody").on("click", ".toggle-row", function(e) {
                            
                            $(this).closest("tr").toggleClass("is-expanded")
                        });
                        LDNFT_Subscriptions.subscription_page_loaded = 'yes';
                    }
                });
            },
            /**
             * Show subscription summary based on filters
             */
            load_subscription_summary: function() {
                
                $('.ldnft-subssummary-loader').css('display', 'inline');
                $('.ldnft_subscription_points').css('display', 'none');
                //$('.ldnft_subscription_tax_fee').css('display', 'none');
                $('.ldnft_subscription_new_sales_count').css('display', 'none');
                $('.ldnft_subscription_new_subscriptions_count').css('display', 'none');
                $('.ldnft_subscription_renewals_count').css('display', 'none');
                $('.ldnft_subscription_new_attempts_count').css('display', 'none');
                var ldnftpage       = $('.ldnft-freemius-page').val();
                if( LDNFT_Subscriptions.display_subscriptions_type == 'filter' ) {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftinterval   = $('.ldfmt-subscription-interval-filter').val();
                    var ldnftcountry     = $('.ldfmt-subscription-country-filter').val();
                    var ldnftplan_id    = $('.ldfmt-subscription-plan_id-filter').val();
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    var gateway_str     = $('.ldfmt-subscription-gateway-filter').val();
                    var status_str      = $('.ldfmt-subscription-status-filter').val();
                    
                    var search_str      = '';
                } else {
                    var ldnftplugin     = '';
                    var ldnftinterval   = '';
                    var ldnftcountry     = '';
                    var ldnftplan_id    = '';
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    var gateway_str     = '';
                    var status_str     = '';
                    var search_str      = $('.ldnft-subscription-general-search').val();
                }

                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_subscriptions_summary',
                        paged: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        interval: ldnftinterval,
                        country: ldnftcountry,
                        plan_id: ldnftplan_id,
                        gateway: gateway_str,
                        search: search_str,
                        status: status_str,
                    },
                    success: function ( response ) {
                        var list_items = '<ul>';
                        if( parseInt( response.gross_total_count ) > 0 ) {
                            
                            for (const key in response.gross_total ) {
                                if (Object.hasOwnProperty.call(response.gross_total, key)) {
                                
                                    list_items += '<li>'+key+': '+response.gross_total[key]+'</li>';
                                    
                                }
                            }
                            
                        } else {
                            list_items += '<li>0</li>';
                        }
                        list_items += '</ul>';
                        $('.ldnft_subscription_points').html(list_items).css('display', 'block');
                        
                        var tax_rate_total = '<ul>';
                        if( parseInt( response.gross_total_count ) > 0 ) {
                            for (const key in response.tax_rate_total ) {
                                if (Object.hasOwnProperty.call(response.tax_rate_total, key)) {
                                
                                    tax_rate_total += '<li>'+key+': '+response.tax_rate_total[key]+'</li>';
                                    
                                }
                            }
                        } else {
                            tax_rate_total += '<li>0</li>';
                        }
                        tax_rate_total += '</ul>';
                        //$('.ldnft_subscription_tax_fee').html(tax_rate_total).css('display', 'block');

                        $('.ldnft_subscription_gross_message').html(response.gross_message);
                        //$('.ldnft_subscription_new_tax_message').html(response.tax_message);
                        $('.ldnft_subscription_new_subscriptions_message').html(response.new_subscriptions_message);
                        //$('.ldnft_subscription_new_renewals_message').html(response.new_renewals_message); 
                        $('.ldnft_subscription_failed_payments_message').html(response.failed_payments_message);
                        $('.ldnft_subscription_countries_message').html(response.countries_message);

                        //$('.ldnft_subscription_points').html(response.gross_total).css('display', 'block');
                        //$('.ldnft_subscription_tax_fee').html(response.tax_rate_total).css('display', 'block');
                        $('.ldnft_subscription_new_sales_count').html(response.total_number_of_sales).css('display', 'block');
                       // $('.ldnft_subscription_new_subscriptions_count').html(response.total_new_subscriptions).css('display', 'block');
                        //$('.ldnft_subscription_renewals_count').html(response.total_new_renewals).css('display', 'block');
                        $('.ldnft_subscription_new_attempts_count').html(response.failed_payments).css('display', 'block');
                        $('.ldnft-subssummary-loader').css('display', 'none');
                        
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
                        
                        $('.ldnft_subscription_top3_countries').html(list_items).css('display', 'block');
                    }
                });
            },
            /**
             * closes the popup
             *
             * @param e
             */
            ldnft_subsciber_modal_close: function() { 
                $( '.ldnft-admin-modal-close' ).on( 'click', function(e) {
                    $('#ldnft-admin-modal').css('display', 'none');
                });
            },
            /**
             * attach country ddl with select2
             */
            load_data_from_cookie: function() {
                $('.ldfmt-subscription-country-filter').select2( { minimumInputLength: 3, allowClear: true, width: '150px', placeholder: "Select a Country" } );
            },
            /**
             * data from cookie
             */
            load_data_from_cookie: function() {
                $('.ldfmt-plugins-subscription-filter').val(jQuery.cookie( 'subscriptions_ldfmt-plugins-filter' ) );
                $('.ldfmt-subscription-plan_id-filter').val(jQuery.cookie( 'subscriptions_ldfmt-sales-plan_id-filter' ) );
                $('.ldfmt-subscription-interval-filter').val(jQuery.cookie( 'subscriptions_ldfmt-sales-interval-filter' ) );
                $('.ldfmt-subscription-country-filter').val(jQuery.cookie( 'subscriptions_ldfmt-subscription-country-filter' ) );
                $('.ldfmt-subscription-gateway-filter').val(jQuery.cookie( 'subscriptions_ldfmt-subscription-gateway-filter' ) );
                $('.ldnft-subscription-general-search').val(jQuery.cookie( 'subscriptions_ldnft-subscription-general-search') );
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
             * plans data changed
             */
            attach_subscription_plans_dropdown: function() {
                $( '.ldfmt-plugins-subscription-filter' ).on( 'change', function( e ){
                    LDNFT_Subscriptions.subscription_plans_dropdown();
                } );
            },
            /**
             * plans data changed
             */
            subscription_plans_dropdown: function() {
                var sel_plugin_id = $(".ldfmt-plugins-subscription-filter").val();
        
                var data = {
                    action: 'ldnft_subscription_plans_dropdown', plugin_id: sel_plugin_id
                }

                $('.ldfmt-subscription-plan_id-filter').attr('disabled', true).html("");

                jQuery.post( LDNFT.ajaxURL, data, function( response ) {

                    $('.ldfmt-subscription-plan_id-filter').attr( 'disabled', false ).html( response );
                    $('.ldfmt-subscription-plan_id-filter').val(jQuery.cookie( LDNFT.current_page + '_ldfmt-sales-plan_id-filter' ) );
                    
                } ); 
            },
            /**
             * Comment here
             */
            subscribers_view_detail: function() {

                $( '#ldnft_subscriptions_data' ).on( 'click', '.ldnft_subscribers_view_detail', function( e ) {

                    e.preventDefault();
               
                    var lnk = $( this );
                    
                    $('#ldnft-admin-modal').css('display', 'block');
                    $('.ldnft-popup-loader').css('display', 'block');
                    $('#ldnft-review-coloumn-transaction-id').html( lnk.data('id') );
                    $('#ldnft-review-coloumn-user_id').html( lnk.data('user_id') );
                    $('#ldnft-review-coloumn-plugin_id').html( lnk.data('plugin_id') );
                    $('#ldnft-review-coloumn-username').html( lnk.data('username') );
                    $('#ldnft-review-coloumn-useremail').html( lnk.data('useremail') );
                    $('#ldnft-review-coloumn-amount_per_cycle').html( lnk.data('amount_per_cycle') );
                    $('#ldnft-review-coloumn-discount').html( lnk.data('discount') );
                    $('#ldnft-review-coloumn-billing_cycle').html( lnk.data('billing_cycle') );
                    $('#ldnft-review-coloumn-gross').html( lnk.data('gross') );
                    $('#ldnft-review-coloumn-gateway').html( lnk.data('gateway') );
                    $('#ldnft-review-coloumn-renewal_amount').html( lnk.data('renewal_amount') );
                    $('#ldnft-review-coloumn-outstanding_balance').html( lnk.data('outstanding_balance') );
                    $('#ldnft-review-coloumn-failed_payments').html( lnk.data('failed_payments') );
                    $('#ldnft-review-coloumn-trial_ends').html( lnk.data('trial_ends') );
                    $('#ldnft-review-coloumn-created').html( lnk.data('created') );
                    $('#ldnft-review-coloumn-initial_amount').html( lnk.data('initial_amount') );
                    $('#ldnft-review-coloumn-next_payment').html( lnk.data('next_payment') );
                    $('#ldnft-review-coloumn-currency').html( lnk.data('currency') );
                    $('#ldnft-review-coloumn-country_code').html( lnk.data('country_code') );
                    $('#ldnft-review-coloumn-install_id').html( lnk.data('install_id') );
                    $('#ldnft-review-coloumn-coupon_id').html( lnk.data('coupon_id') );
                    $('#ldnft-review-coloumn-updated_at').html( lnk.data('updated_at') );
                    $('#ldnft-review-coloumn-external_id').html( lnk.data('external_id') );
                    $('#ldnft-review-coloumn-plan_id').html( lnk.data('plan_id') );
                    $('#ldnft-review-coloumn-pricing_id').html( lnk.data('pricing_id') );
                    $('#ldnft-review-coloumn-renewals_discount').html( lnk.data('renewals_discount') );
                    $('#ldnft-review-coloumn-license_id').html( lnk.data('license_id') );
                    $('#ldnft-review-coloumn-status').html( lnk.data('status') );
                    $('#ldnft-review-coloumn-canceled_at').html( lnk.data('canceled_at') );
                    $('.ldnft-popup-loader').css('display', 'none');
                });
            },
        };

        LDNFT_Subscriptions.init();
    });   
})( jQuery );