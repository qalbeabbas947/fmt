(function( $ ) { 'use strict';
    $( document ).ready( function() {
        
        var LDNFTbackEnd = {
            default_table_row: '',
            default_sales_table_row: '',
			default_customers_table_row: '',
			default_reviews_table_row: '',
            init: function() {
				
                $('.ldnft-success-message').hide();
                $('.ldnft-settings-mailpoet').on('submit', LDNFTbackEnd.post_mailpoet_form);
                $('.ldnft_check_load_next').on('click', LDNFTbackEnd.check_load_next);
                $('#ldnft_subscriptions_data').on('click', '.ldnft_subscribers_view_detail', LDNFTbackEnd.subscribers_view_detail);
                $('#ldnft_sales_data').on('click', '.ldnft_sales_view_detail', LDNFTbackEnd.sales_view_detail);
                $('.ldnft-admin-modal-close').on('click', LDNFTbackEnd.ldnft_subsciber_modal_close);
				$('#ldnft_reviews_data').on('click', '.ldnft_review_view_detail', LDNFTbackEnd.review_view_detail);

                /**
                 * Execute based on the conditions
                 */
				var script_type = $('.ldnft-script-freemius-type').val();
				if( script_type == 'subscribers' ) {
					$('.ldfmt-subscription-status-filter, .ldfmt-subscription-interval-filter, .ldfmt-subscription-plan_id-filter, .ldfmt-plugins-subscription-filter').on('change', LDNFTbackEnd.display_subscriptions_plus_summary);
					$('#ldnft_subscriptions_data').on('click', '.tablenav-pages a', LDNFTbackEnd.display_new_page_subscriptions);
					LDNFTbackEnd.display_subscriptions_plus_summary();					
				} else if( script_type == 'sales' ) { 
					$('#ldnft_sales_data').on('click', '.tablenav-pages a', LDNFTbackEnd.display_new_page_sales);
					$('.ldfmt-sales-interval-filter, .ldfmt-sales-filter, .ldfmt-plugins-sales-filter').on('change', LDNFTbackEnd.display_sales_plus_summary);
					LDNFTbackEnd.display_sales_plus_summary();
				} else if( script_type == 'customers' ) { 
					
					$('#ldnft_customers_data').on('click', '.tablenav-pages a', LDNFTbackEnd.display_new_page_customers);
					$('.ldfmt-plugins-customers-filter, .ldfmt-plugins-customers-status').on('change', LDNFTbackEnd.display_customers_onchange);
					LDNFTbackEnd.display_customers();
					
				} else if( script_type == 'reviews' ) { 
					$('#ldnft_reviews_data').on('click', '.tablenav-pages a', LDNFTbackEnd.display_new_page_reviews);
					$('.ldfmt-plugins-reviews-filter').on('change', LDNFTbackEnd.display_reviews_onchange);
					LDNFTbackEnd.display_reviews();
				}
                
            },
			/**
             * pagination click
			 */
			display_new_page_reviews: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_reviews();
            },
            /**
             * Show reviews based on filters
             */
			display_reviews_onchange: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_reviews();
            },
            /**
             * Display the reviews data based on ajax calls
             */
            display_reviews: function() {
				
                if( LDNFTbackEnd.default_reviews_table_row == '' ) {
                    LDNFTbackEnd.default_reviews_table_row = $('#ldnft_reviews_data table tbody').html();
                } else {
                    $('#ldnft_reviews_data table tbody').html( LDNFTbackEnd.default_reviews_table_row );
                }
               
                var ldnftpage       = $('.ldnft-freemius-page').val();
                var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                var ldnftstatus     = $('.ldfmt-plugins-reviews-status').val();
                
                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_reviews_display',
                        offset: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        status: ldnftstatus
                    },
                    success: function (response) {
                        $("#ldnft_reviews_data").html(response.display);

                        $("tbody").on("click", ".toggle-row", function(e) {
                            
                            $(this).closest("tr").toggleClass("is-expanded")
                        });
                    }
                });
            },
			/**
             * displays customers on pagination clicks
            */
			display_new_page_customers: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_customers();
            },
            /**
             * Show customers based on filters
             */
			display_customers_onchange: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_customers();
            },
            /**
             * Display the customers data based on ajax calls
             */
            display_customers: function() {
				
                if( LDNFTbackEnd.default_customers_table_row == '' ) {
                    LDNFTbackEnd.default_customers_table_row = $('#ldnft_customers_data table tbody').html();
                } else {
                    $('#ldnft_customers_data table tbody').html( LDNFTbackEnd.default_customers_table_row );
                }
               
                var ldnftpage       = $('.ldnft-freemius-page').val();
                var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                var ldnftstatus     = $('.ldfmt-plugins-customers-status').val();
                
                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_customers_display',
                        offset: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        status: ldnftstatus
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
            display_new_page_sales: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_sales();
            },
            /**
             * Show sales based on filters
             */
            display_sales_plus_summary: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_sales();
                LDNFTbackEnd.load_sales_summary();
            },
            /**
             * Display the sales data based on ajax calls
             */
            display_sales: function() {
                
                if( LDNFTbackEnd.default_sales_table_row == '' ) {
                    LDNFTbackEnd.default_sales_table_row = $('#ldnft_sales_data table tbody').html();
                } else {
                    $('#ldnft_sales_data table tbody').html( LDNFTbackEnd.default_sales_table_row );
                }
               
                var ldnftpage       = $('.ldnft-freemius-page').val();
                var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                var ldnftinterval   = $('.ldfmt-sales-interval-filter').val();
                var ldnftstatus     = $('.ldfmt-sales-status-filter').val();
                
                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_sales_display',
                        offset: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        interval: ldnftinterval,
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
                $('.ldnft_sales_tax_fee').css('display', 'none');
                $('.ldnft_sales_new_sales_count').css('display', 'none');
                $('.ldnft_sales_new_sales_count').css('display', 'none');
                $('.ldnft_sales_renewals_count').css('display', 'none');

                var ldnftpage       = $('.ldnft-freemius-page').val();
                var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                var ldnftinterval   = $('.ldfmt-sales-interval-filter').val();
                var ldnftstatus     = $('.ldfmt-sales-status-filter').val();
                var ldnftplan_id    = $('.ldfmt-sales-plan_id-filter').val();

                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_sales_summary',
                        offset: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        interval: ldnftinterval,
                        status: ldnftstatus,
                        plan_id: ldnftplan_id,
                    },
                    success: function ( response ) {

                        $('.ldnft_sales_points').html(response.gross_total).css('display', 'block');
                        $('.ldnft_sales_tax_fee').html(response.tax_rate_total).css('display', 'block');
                        $('.ldnft_sales_new_sales_count').html(response.total_number_of_sales).css('display', 'block');
                        $('.ldnft_sales_new_sales_count').html(response.total_new_sales).css('display', 'block');
                        $('.ldnft_sales_renewals_count').html(response.total_new_renewals).css('display', 'block');
                        $('.ldnft-subssummary-loader').css('display', 'none');
                        
                    }
                });
            },
            /**
             * Show subscription based on pagination
             */
            display_new_page_subscriptions: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_subscriptions();
            },
            /**
             * Show subscription summary
             */
            display_subscriptions_plus_summary: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_subscriptions();
                LDNFTbackEnd.load_subscription_summary();
            },
            /**
             * Display the subscriptions data based on ajax calls
             */
            display_subscriptions: function() {
                
                if( LDNFTbackEnd.default_table_row == '' ) {
                    LDNFTbackEnd.default_table_row = $('#ldnft_subscriptions_data table tbody').html();
                } else {
                    $('#ldnft_subscriptions_data table tbody').html( LDNFTbackEnd.default_table_row );
                }
               
                var ldnftpage       = $('.ldnft-freemius-page').val();
                var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                var ldnftinterval   = $('.ldfmt-subscription-interval-filter').val();
                var ldnftstatus     = $('.ldfmt-subscription-status-filter').val();
                var ldnftplan_id    = $('.ldfmt-subscription-plan_id-filter').val();
                
                $.ajax({

                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_subscriptions_display',
                        offset: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        interval: ldnftinterval,
                        status: ldnftstatus,
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

                var ldnftpage       = $('.ldnft-freemius-page').val();
                var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                var ldnftinterval   = $('.ldfmt-subscription-interval-filter').val();
                var ldnftstatus     = $('.ldfmt-subscription-status-filter').val();
                var ldnftplan_id    = $('.ldfmt-subscription-plan_id-filter').val();

                $.ajax({
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: 'ldnft_subscriptions_summary',
                        offset: ldnftpage,
                        ldfmt_plugins_filter: ldnftplugin,
                        interval: ldnftinterval,
                        status: ldnftstatus,
                        plan_id: ldnftplan_id,
                    },
                    success: function ( response ) {

                        $('.ldnft_subscription_points').html(response.gross_total).css('display', 'block');
                        $('.ldnft_subscription_tax_fee').html(response.tax_rate_total).css('display', 'block');
                        $('.ldnft_subscription_new_sales_count').html(response.total_number_of_sales).css('display', 'block');
                        $('.ldnft_subscription_new_subscriptions_count').html(response.total_new_subscriptions).css('display', 'block');
                        $('.ldnft_subscription_renewals_count').html(response.total_new_renewals).css('display', 'block');
                        $('.ldnft-subssummary-loader').css('display', 'none');
                        
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
				$('#ldnft-review-coloumn-pricing_id').html( lnk.data('pricing_id') );
				$('#ldnft-review-coloumn-ip').html( lnk.data('ip') );
				$('#ldnft-review-coloumn-zip_postal_code').html( lnk.data('zip_postal_code') );
				$('#ldnft-review-coloumn-vat_id').html( lnk.data('vat_id') );
				$('#ldnft-review-coloumn-coupon_id').html( lnk.data('coupon_id') );
				$('#ldnft-review-coloumn-user_card_id').html( lnk.data('user_card_id') );
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
               $('.ldnft-admin-modal-body').html('');
               jQuery.post( LDNFT.ajaxURL, lnk.data(), function( response ) {
                    $('#ldnft-admin-modal').css('display', 'block');
                    $('.ldnft-admin-modal-body').html(response);
                    $('.ldnft-popup-loader').css('display', 'none');
               } );
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