(function( $ ) { 'use strict';
    $( document ).ready( function() {  
        var LDNFTbackEnd = { 
            ajax_url_new: ajaxurl,
            display_subscriptions_type: 'filter',
            display_sales_type:         'filter',
            display_review_type:        'filter',
            display_customer_type:      'filter',
            current_cron_step:          'plugins',
            init: function() {

                LDNFTbackEnd.hooks();
                LDNFTbackEnd.listing_pages();
                LDNFTbackEnd.import_cron_status();
            },
            hooks: function() {
                
                $( '.ldnft-success-message' ).hide();
                $( '.ldnft-settings-mailpoet' ).on( 'submit',                                      LDNFTbackEnd.post_mailpoet_form );
                $( '.ldnft_check_load_next' ).on( 'click',                                         LDNFTbackEnd.check_load_next );
                $( '.ldnft-sync-data-setting' ).on( 'click',                                       LDNFTbackEnd.sync_data_from_freemius );
                $( '.ldfmt-plugins-subscription-filter' ).on( 'change',                            LDNFTbackEnd.subscription_plans_dropdown ).trigger( 'change' );
                $( '#ldnft_subscriptions_data' ).on( 'click', '.ldnft_subscribers_view_detail',    LDNFTbackEnd.subscribers_view_detail );
                $( '#ldnft_sales_data' ).on( 'click', '.ldnft_sales_view_detail',                  LDNFTbackEnd.sales_view_detail );
                $( '.ldnft-admin-modal-close' ).on( 'click',                                       LDNFTbackEnd.ldnft_subsciber_modal_close );
				$( '#ldnft_reviews_data' ).on( 'click', '.ldnft_review_view_detail',               LDNFTbackEnd.review_view_detail );
                $( '#ldnft_reviews_data' ).on( 'click', '.ldnft_is_featured_enabled_click',        LDNFTbackEnd.ldnft_is_featured_enabled );
            },
            print_asterik_line: function( ) {

                let line = '';

                for( let i = 0; i < 50; i++ ) {
                    line += '*';
                }

                return line;
            },
            subscription_plans_dropdown: function(){

                var sel_plugin_id = $(this).val();

                var data = {
                    action: 'ldnft_subscription_plans_dropdown', plugin_id: sel_plugin_id
                }

                $('.ldfmt-subscription-plan_id-filter').attr('disabled', true).html("");

                jQuery.post( LDNFT.ajaxURL, data, function( response ) {

                    $('.ldfmt-subscription-plan_id-filter').attr( 'disabled', false ).html( response );
                } ); 
            },
            sync_data_from_freemius: function(){
                var data = {
                    action: 'ldnft_run_freemius_import',
                    type: $(this).data('type')
                }
               
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    
                    LDNFT.is_cron_page_check    = response.is_cron_page_check;
                    LDNFT.import_cron_status    = response.import_cron_status;
                    $('.ldnft-settings-sync-data-message').html(response.message).css( 'display', 'block' );
                    if( response.is_cron_page_check == 'Yes' ) {
                        document.location.reload();
                    }
                    
                } ); 
            },
            listing_pages: function(){
                /**
                 * Execute based on the conditions
                 */
                var script_type = $('.ldnft-script-freemius-type').val();
                if( script_type == 'subscribers' ) {
                    $('.ldnft-subscription-search-button').on('click', LDNFTbackEnd.display_subscriptions_plus_summary);
                    $('#ldnft-subscription-filter-form-text').on('submit', LDNFTbackEnd.display_subscriptions_plus_summary_submit);
                    $('#ldnft_subscriptions_data').on('click', '.tablenav-pages a, th a', LDNFTbackEnd.display_new_page_subscriptions);
                    LDNFTbackEnd.display_subscriptions_plus_summary();					
                } else if( script_type == 'sales' ) { 
                    $('#ldnft_sales_data').on( 'click', '.tablenav-pages a, th a', LDNFTbackEnd.display_new_page_sales );
                    $('#ldnft-sales-filter-text' ).on( 'submit', LDNFTbackEnd.display_new_page_sales_text );
                    $('.ldnft-sales-search-button').on('click', LDNFTbackEnd.display_sales_plus_summary);

                    LDNFTbackEnd.display_sales_plus_summary();
                } else if( script_type == 'customers' ) { 
                    $('#ldnft_customers_data').on('click', '.tablenav-pages a, th a', LDNFTbackEnd.display_new_page_customers);
                    $('.ldnft-customer-search-button').on('click', LDNFTbackEnd.display_customers_onchange);
                    $('#ldnft-reviews-filter-text').on('submit', LDNFTbackEnd.display_customers_search_submit);
                    LDNFTbackEnd.display_customers();
                    
                } else if( script_type == 'reviews' ) { 
                    $('#ldnft_reviews_data').on('click', '.tablenav-pages a, th a', LDNFTbackEnd.display_new_page_reviews);
                    $('.ldnft-reviews-search-button').on('click', LDNFTbackEnd.display_reviews_onchange);
                    $('#ldnft-reviews-filter-text').on('submit', LDNFTbackEnd.display_reviews_text);
                    LDNFTbackEnd.display_reviews();
                }

                $('.ldfmt-sales-country-filter').select2({ minimumInputLength: 3, allowClear: true, width: '150px', placeholder: "Select a Country"});
                $('.ldfmt-subscription-country-filter').select2( { minimumInputLength: 3, allowClear: true, width: '150px', placeholder: "Select a Country" } );
            },
            import_cron_status: function() {
                
                if( LDNFT.is_cron_page_check=='yes' ) {
                    if( LDNFT.import_cron_status != 'complete' ) {

                        $('.ldnft-process-freemius-data-log').html( LDNFT.plugins_start_msg );
                        LDNFTbackEnd.timeout_obj = setTimeout( LDNFTbackEnd.check_cron_status, 3000 );
                    } else {

                        LDNFT.import_cron_status = 'complete';
                        clearTimeout( LDNFTbackEnd.timeout_obj );
                    }
                }
            },
            check_cron_status: function() {
                
                var data = {
                    action: 'ldnft_check_cron_status',
                    state: LDNFTbackEnd.current_cron_step
                }

                $('#ldnft-settings-import-error-message').html('').css( 'display', 'none' );
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    
                    if( response.status == 'complete' ) {

                        $('.ldnft-process-freemius-data-log').append('<br>'+LDNFT.complete_msg);
                        LDNFT.import_cron_status = 'complete';
                        clearTimeout(LDNFTbackEnd.timeout_obj);
                    } else {

                        $('.ldnft-process-freemius-data-log').css( 'display', 'block' );
                        switch( response.status ) {
                            case 'plans':
                                
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-process-freemius-data-log').append('<br>'+response.Planmsg);
                                }
                                
                                if( parseInt( response.Plans ) == 1 ) {

                                    LDNFTbackEnd.current_cron_step = 'customers';
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + LDNFTbackEnd.print_asterik_line() );
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + LDNFT.customer_start_msg );
                                }
                                break;
                            case 'customers':
                                
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + response.Customermsg);
                                }

                                if( parseInt( response.Customers ) == 1 ) {

                                    LDNFTbackEnd.current_cron_step = 'sales';
                                    $('.ldnft-process-freemius-data-log').append('<br>'+LDNFTbackEnd.print_asterik_line() );
                                    $('.ldnft-process-freemius-data-log').append('<br>'+LDNFT.sales_start_msg );
                                }
                                break;
                            case 'sales':
                                
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-process-freemius-data-log').append('<br>'+response.Salesmsg);
                                }

                                if( parseInt( response.Sales ) == 1 ) {

                                    LDNFTbackEnd.current_cron_step = 'subscription';
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + LDNFTbackEnd.print_asterik_line() );
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + LDNFT.subscription_start_msg );
                                }
                                break;
                            case 'subscription':
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-process-freemius-data-log').append('<br>'+response.Subscriptionmsg);
                                }
                                
                                if( parseInt( response.Subscription ) == 1 ) {

                                    LDNFTbackEnd.current_cron_step = 'reviews';
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + LDNFTbackEnd.print_asterik_line() );
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + LDNFT.reviews_start_msg );
                                }
                                break;
                            case 'reviews':
                                
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + response.Reviewsmsg );
                                }

                                if( parseInt( response.Reviews ) == 1 ) {

                                    LDNFTbackEnd.current_cron_step = 'complete';
                                    $( '.ldnft-process-freemius-data-log' ).append( '<br>' + LDNFTbackEnd.print_asterik_line() );
                                }
                                break; 
                            default:
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + response.Pluginmsg );
                                }
                                if( parseInt( response.Plugins ) == 1 ) {

                                    LDNFTbackEnd.current_cron_step = 'plans';
                                    $( '.ldnft-process-freemius-data-log' ).append( '<br>' + LDNFTbackEnd.print_asterik_line() );
                                    $( '.ldnft-process-freemius-data-log' ).append( '<br>' + LDNFT.plans_start_msg );
                                }
                                break;
                        }

                        LDNFTbackEnd.timeout_obj = setTimeout( LDNFTbackEnd.check_cron_status, 3000 );
                    }

                    if( response.error == 1 ) {

                        clearTimeout( LDNFTbackEnd.timeout_obj );
                     } 

                } ).fail(function(response) {
                    $('#ldnft-settings-import-error-message').html( LDNFT.ldnft_error_reload_message ).css( 'display', 'block' );
                    clearTimeout( LDNFTbackEnd.timeout_obj );
                });
            },
            ldnft_is_featured_enabled: function(e){
                
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
            },
			/**
             * pagination click
			 */
			display_new_page_reviews: function( e ) {
                
                e.preventDefault();

                $( '.ldnft-freemius-order' ).val( LDNFTbackEnd.getParameterByName( 'order', $( this ).attr( 'href' ) ) );
                $( '.ldnft-freemius-orderby' ).val( LDNFTbackEnd.getParameterByName( 'orderby', $( this ).attr( 'href' ) ) );

                var page = $( '.ldnft-freemius-page' ).val( $( this ).data( 'paged' ) );

                LDNFTbackEnd.display_reviews( );
            },
            /**
             * Show reviews based on filters
             */
			display_reviews_onchange: function() {

                LDNFTbackEnd.display_review_type = 'filter';
                var page = $('.ldnft-freemius-page').val( 1 );
                LDNFTbackEnd.display_reviews();
                
            },
            /**
             * Display the reviews data based on ajax calls
             */
            display_reviews_text: function(e) {
                e.preventDefault();
                LDNFTbackEnd.display_review_type = 'text';
                var page = $('.ldnft-freemius-page').val(1);
                LDNFTbackEnd.display_reviews();
                
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

                if( LDNFTbackEnd.display_review_type == 'filter' ) {

                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftverified   = $('.ldfmt-plugins-reviews-verified').val();
                    var featured_str    = $('.ldfmt-plugins-reviews-featured').val();
                    var search_str      = '';

                } else {

                    var ldnftplugin     = '';
                    var ldnftverified   = '';
                    var featured_str    = '';
                    var search_str      = $('#ldnft-reviews-general-search').val();
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
            getParameterByName: function( name, url) {
                
                name = name.replace(/[\[\]]/g, '\\$&');
                
                var regex = new RegExp( '[?&]' + name + '(=([^&#]*)|&|#|$)' ),
                    results = regex.exec( url );
                if ( ! results ) return null;
                if ( ! results[2] ) return '';
                return decodeURIComponent( results[2].replace(/\+/g, ' ') );
            },
			/**
             * displays customers on pagination clicks
            */
			display_new_page_customers: function( e ) { 

                $(  '.ldnft-freemius-order'   ).val(LDNFTbackEnd.getParameterByName( 'order', $( this ).attr('href')));
                $(  '.ldnft-freemius-orderby' ).val(LDNFTbackEnd.getParameterByName( 'orderby', $( this ).attr('href') ));
                
                e.preventDefault();
                var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                LDNFTbackEnd.display_customers();
            },
            /**
             * Show customers based on filters
             */
			display_customers_onchange: function() {
                LDNFTbackEnd.display_customer_type = 'filter';
                var page = $('.ldnft-freemius-page').val(1);
                LDNFTbackEnd.display_customers();
            },
            /**
             * Show customers based on filters
             */
            display_customers_search_submit: function(e) {
                e.preventDefault();

                LDNFTbackEnd.display_customer_type = 'text';
                var page = $('.ldnft-freemius-page').val(1);
                LDNFTbackEnd.display_customers();
            },
            
            /**
             * Display the customers data based on ajax calls
             */
            display_customers: function() {
				
                var columns_count = $('#ldnft_customers_data table thead tr:eq(0)').find('th:not(.hidden)').length; 
                var placeholder = '<tr>';
                for( var i = 0; i < columns_count; i++ ) {
                    placeholder += '<td align="center">' + LDNFT.preloader_gif_img + '</td>';
                }
                placeholder += '</tr>';
                $('#ldnft_customers_data table tbody').html( placeholder );
               
                var ldnftpage       = $('.ldnft-freemius-page').val();
                var order_str       = $('.ldnft-freemius-order').val();
                var orderby_str     = $('.ldnft-freemius-orderby').val();

                if( LDNFTbackEnd.display_customer_type == 'text' ) {
                    var ldnftplugin     = '';
                    var ldnftstatus     = '';
                    var ldnftsearch     = $('.ldnft-customers-general-search').val();
                    var marketing_str   = '';
                } else {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftstatus     = $('.ldfmt-plugins-customers-status').val();
                    var ldnftsearch     = '';
                    var marketing_str   = $('.ldfmt-plugins-customers-marketing').val();
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
            /**
             * displays sals on pagination
             */
            display_new_page_sales_text: function( e ) { 
                e.preventDefault();
            
                LDNFTbackEnd.display_sales_type = 'text';
                $('.ldnft-freemius-order').val(LDNFTbackEnd.getParameterByName('order', $(this).attr('href')));
                $('.ldnft-freemius-orderby').val(LDNFTbackEnd.getParameterByName('orderby', $(this).attr('href')));
                var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                LDNFTbackEnd.display_sales();
                 
            },
			/**
             * displays sals on pagination
             */
            display_new_page_sales: function(e) {
                
                e.preventDefault();
                $('.ldnft-freemius-order').val(LDNFTbackEnd.getParameterByName('order', $(this).attr('href')));
                $('.ldnft-freemius-orderby').val(LDNFTbackEnd.getParameterByName('orderby', $(this).attr('href')));
                var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                LDNFTbackEnd.display_sales();
            },
            /**
             * Show sales based on filters
             */
            display_sales_plus_summary: function() {
                LDNFTbackEnd.display_sales_type = 'filter';
                var page = $('.ldnft-freemius-page').val(1);
                LDNFTbackEnd.display_sales();
                LDNFTbackEnd.load_sales_summary();
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
                if( LDNFTbackEnd.display_sales_type == 'text' ) {
                    var ldnftplugin     = '';
                    var ldnftinterval   = '';
                    var ldnftstatus     = '';
                    var ldnftsearch     = $('.ldnft-sales-general-search').val();
                    var country_str     = '';
                    var ldnfttypes      = '';
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                } else {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftinterval   = $('.ldfmt-sales-interval-filter').val();
                    var ldnftstatus     = $('.ldfmt-sales-filter').val();
                    var ldnftsearch     = '';
                    var country_str     = $('.ldfmt-sales-country-filter').val();
                    var ldnfttypes      = $('.ldnft-sales-payment-types').val();
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
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
                $('.ldnft_sales_tax_fee').css('display', 'none');
                $('.ldnft_sales_new_subscriptions_count').css('display', 'none');
                $('.ldnft_sales_renewals_count').css('display', 'none');
                
                var ldnftpage       = $('.ldnft-freemius-page').val();
                if( LDNFTbackEnd.display_sales_type == 'text' ) {
                    var ldnftplugin     = '';
                    var ldnftinterval   = '';
                    var ldnftstatus     = '';
                    var ldnftsearch     = $('.ldnft-sales-general-search').val();
                    var country_str     = '';
                    var ldnfttypes      = '';
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                } else {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftinterval   = $('.ldfmt-sales-interval-filter').val();
                    var ldnftstatus     = $('.ldfmt-sales-filter').val();
                    var ldnftsearch     = '';
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
                        $('.ldnft_sales_points_count').html('(' + response.gross_total_count+')');
                        
                        if( response.tax_rate_total.length > 0 && response.gross_total != undefined ) {
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
                        $('.ldnft_sales_tax_fee').html(tax_rate_total).css('display', 'block');
                        
                        $('.ldnft_sales_renewals_amount').html(response.total_new_renewals_amount).css('display', 'block');
                        $('.ldnft_new_renewals_count').html('(' + response.total_new_renewals + ')');
                        $('.ldnft-subssummary-loader').css('display', 'none');
                        $('.ldnft_sales_new_subscriptions').html(response.total_new_subscriptions_amount).css('display', 'block');
                        $('.ldnft_new_subscriptions_count').html('(' + response.total_new_subscriptions+')');
                        
                        
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
             * Show subscription based on pagination
             */
            display_new_page_subscriptions: function(e) {

                e.preventDefault();
                $('.ldnft-freemius-order').val(LDNFTbackEnd.getParameterByName('order', $(this).attr('href')));
                $('.ldnft-freemius-orderby').val(LDNFTbackEnd.getParameterByName('orderby', $(this).attr('href')));

                var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                LDNFTbackEnd.display_subscriptions();
            },
            /**
             * Show subscription summary
             */
            display_subscriptions_plus_summary: function() {
                LDNFTbackEnd.display_subscriptions_type = 'filter';
                var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                LDNFTbackEnd.display_subscriptions();
                LDNFTbackEnd.load_subscription_summary();
            },
            /**
             * Show subscription summary
             */
            display_subscriptions_plus_summary_submit: function( e ) {
                e.preventDefault();
                LDNFTbackEnd.display_subscriptions_type = 'text';
                var page = $('.ldnft-freemius-page').val($(this).data('paged'));
                LDNFTbackEnd.display_subscriptions();
                LDNFTbackEnd.load_subscription_summary();
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
                if( LDNFTbackEnd.display_subscriptions_type == 'filter' ) {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftinterval   = $('.ldfmt-subscription-interval-filter').val();
                    var ldnftcountry     = $('.ldfmt-subscription-country-filter').val();
                    var ldnftplan_id    = $('.ldfmt-subscription-plan_id-filter').val();
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    var gateway_str     = $('.ldfmt-subscription-gateway-filter').val();
                    var search_str      = '';
                } else {
                    var ldnftplugin     = '';
                    var ldnftinterval   = '';
                    var ldnftcountry     = '';
                    var ldnftplan_id    = '';
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    var gateway_str     = '';
                    var search_str      = $('.ldnft-subscription-general-search').val();
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
                        order: order_str,
                        orderby: orderby_str,
                        plan_id: ldnftplan_id,
                    },
                    success: function (response) {
                        $("#ldnft_subscriptions_data").html(response.display);

                        $("tbody").on("click", ".toggle-row", function(e) {
                            
                            $(this).closest("tr").toggleClass("is-expanded")
                        });
                    }
                });
            },
            /**
             * Show subscription summary based on filters
             */
            load_subscription_summary: function() {
                
                $('.ldnft-subssummary-loader').css('display', 'inline');
                $('.ldnft_subscription_points').css('display', 'none');
                $('.ldnft_subscription_tax_fee').css('display', 'none');
                $('.ldnft_subscription_new_sales_count').css('display', 'none');
                $('.ldnft_subscription_new_subscriptions_count').css('display', 'none');
                $('.ldnft_subscription_renewals_count').css('display', 'none');
                $('.ldnft_subscription_new_attempts_count').css('display', 'none');
                var ldnftpage       = $('.ldnft-freemius-page').val();
                if( LDNFTbackEnd.display_subscriptions_type == 'filter' ) {
                    var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                    var ldnftinterval   = $('.ldfmt-subscription-interval-filter').val();
                    var ldnftcountry     = $('.ldfmt-subscription-country-filter').val();
                    var ldnftplan_id    = $('.ldfmt-subscription-plan_id-filter').val();
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    var gateway_str     = $('.ldfmt-subscription-gateway-filter').val();
                    var search_str      = '';
                } else {
                    var ldnftplugin     = '';
                    var ldnftinterval   = '';
                    var ldnftcountry     = '';
                    var ldnftplan_id    = '';
                    var order_str       = $('.ldnft-freemius-order').val();
                    var orderby_str     = $('.ldnft-freemius-orderby').val();
                    var gateway_str     = '';
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
                        search: search_str
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
                        $('.ldnft_subscription_tax_fee').html(tax_rate_total).css('display', 'block');

                        //$('.ldnft_subscription_points').html(response.gross_total).css('display', 'block');
                        //$('.ldnft_subscription_tax_fee').html(response.tax_rate_total).css('display', 'block');
                        $('.ldnft_subscription_new_sales_count').html(response.total_number_of_sales).css('display', 'block');
                        $('.ldnft_subscription_new_subscriptions_count').html(response.total_new_subscriptions).css('display', 'block');
                        $('.ldnft_subscription_renewals_count').html(response.total_new_renewals).css('display', 'block');
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
                        

                        // var list_items = '<ul>';
                        // for (const key in response.countries ) {
                        //     if (Object.hasOwnProperty.call(response.countries, key)) {
                        //         const element = response.countries[key];
                        //         list_items += '<li>'+element.country_name+': '+element.gross+'</li>';
                               
                        //     }
                        // }
                        // list_items += '</ul>';
                        $('.ldnft_subscription_top3_countries').html(list_items).css('display', 'block');
                    }
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
             * Show review popup on get more click
             */
			review_view_detail: function( e ) { 
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
                
            },
            
            /**
            * Display the popup. 
            *
            * @param e
            */
            sales_view_detail: function( e ) { 
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
             },
            /**
            * Display the popup.
            *
            * @param e
            */
            subscribers_view_detail: function( e ) { 
               
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
                $('.ldnft-popup-loader').css('display', 'none');
            },
            /**
             * imports the data from mailpoet.
             *
             * @param e
             */
            check_load_next: function( e ) { 
                e.preventDefault();
                var lnk = $( this );
                //lnk.action = '';
                jQuery.post( LDNFT.ajaxURL, lnk.data(), function( response ) {
                    if(response == '') {
                        document.location = lnk.attr('href');
                    } else {
                        alert(response);
                    }
                } );
            },
            /**
             * imports the data from mailpoet.
             *
             * @param e
             */
            post_mailpoet_form: function( e ) {
                e.preventDefault();
                $('#ldnft-settings-import-mailpoet-message').html('').css('display', 'none');
                $('#ldnft-settings-import-mailpoet-errmessage').html('').css('display', 'none');
                $('.ldnft-success-message').show();
                var data = $( this ).serialize();
                $('#ldnft_mailpeot_list, #ldnft_mailpeot_plugin, .ldnft-mailpoet-save-setting_import').attr('disabled', true);
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    
                    if( JSON.parse(response).message!='' ) {
                        $('#ldnft-settings-import-mailpoet-message').html(JSON.parse(response).message).css('display', 'block');
                    } else if( JSON.parse(response).errormsg!='' ) {
                        $('#ldnft-settings-import-mailpoet-errmessage').html(JSON.parse(response).errormsg).css('display', 'block');
                    }
                    
                    $('#ldnft_mailpeot_list, #ldnft_mailpeot_plugin, .ldnft-mailpoet-save-setting_import').attr('disabled', false);
                    $('.ldnft-success-message').hide();
                } );
            },
        };

        LDNFTbackEnd.init();
    });
})( jQuery );