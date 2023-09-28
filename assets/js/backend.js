(function( $ ) { 'use strict';
    $( document ).ready( function() {
        
        var LDNFTbackEnd = {
            default_table_row: '',
            init: function() {
                $('.ldnft-success-message').hide();
                $('.ldnft-settings-mailpoet').on('submit', LDNFTbackEnd.post_mailpoet_form);
                $('.ldnft_check_load_next').on('click', LDNFTbackEnd.check_load_next);
                $('#ldnft_subscriptions_data').on('click', '.ldnft_subscribers_view_detail', LDNFTbackEnd.subscribers_view_detail);
                $('.ldnft_sales_view_detail').on('click', LDNFTbackEnd.sales_view_detail);
                $('.ldnft-admin-modal-close').on('click', LDNFTbackEnd.ldnft_subsciber_modal_close);
                $('.ldfmt-sales-status-filter, .ldfmt-sales-interval-filter, .ldfmt-sales-plan_id-filter, .ldfmt-plugins-filter').on('change', LDNFTbackEnd.display_subscriptions_plus_summary);
                $('#ldnft_subscriptions_data').on('click', '.tablenav-pages a', LDNFTbackEnd.display_new_page_subscriptions);

                LDNFTbackEnd.display_subscriptions_plus_summary();
            },
            display_new_page_subscriptions: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_subscriptions();
            },
            display_subscriptions_plus_summary: function() {
                var page = $('.ldnft-freemius-page').val($(this).data('offset'));
                LDNFTbackEnd.display_subscriptions();
                LDNFTbackEnd.load_subscription_summary();
            },
            /** added method display
             * for getting first sets of data
             **/

            display_subscriptions: function() {
                
                if( LDNFTbackEnd.default_table_row == '' ) {
                    LDNFTbackEnd.default_table_row = $('#ldnft_subscriptions_data table tbody').html();
                } else {
                    $('#ldnft_subscriptions_data table tbody').html( LDNFTbackEnd.default_table_row );
                }
               
                $('.ldnft-subssummary-loader').css('display', 'inline');
                $('.ldnft_subscription_points').css('display', 'none');
                $('.ldnft_subscription_tax_fee').css('display', 'none');
                $('.ldnft_subscription_new_sales_count').css('display', 'none');
                $('.ldnft_subscription_new_subscriptions_count').css('display', 'none');
                $('.ldnft_subscription_renewals_count').css('display', 'none');

                var ldnftpage       = $('.ldnft-freemius-page').val();
                var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                var ldnftinterval   = $('.ldfmt-sales-interval-filter').val();
                var ldnftstatus     = $('.ldfmt-sales-status-filter').val();
                var ldnftplan_id    = $('.ldfmt-sales-plan_id-filter').val();
                
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
            load_subscription_summary: function() {

                var ldnftpage       = $('.ldnft-freemius-page').val();
                var ldnftplugin     = $('.ldfmt-plugins-filter').val();
                var ldnftinterval   = $('.ldfmt-sales-interval-filter').val();
                var ldnftstatus     = $('.ldfmt-sales-status-filter').val();
                var ldnftplan_id    = $('.ldfmt-sales-plan_id-filter').val();

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
            * Display the popup.
            *
            * @param e
            */
            sales_view_detail: function( e ) { 
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
                    }
                    
                    if( JSON.parse(response).errormsg!='' ) {
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