(function( $ ) { 'use strict';
    
    $( document ).ready( function() {

        let Settings_API = {
            current_cron_step: 'plugins',
            init: function() {
                
                Settings_API.import_cron_status();
                Settings_API.sync_data_from_freemius();
                Settings_API.ldnft_save_setting();
            },
            display_preloader: function(label, type) {
                
                var str = '<div class="ldnft-loading-wrapper">'+label;
                str += '<span class="ldnft-loading-dot ldnft-loading-dot-'+type+'">.</span>';
                str += '<span class="ldnft-loading-dot ldnft-loading-dot-'+type+'">.</span>';
                str += '<span class="ldnft-loading-dot ldnft-loading-dot-'+type+'">.</span>';
                str += '<span class="ldnft-loading-dot ldnft-loading-dot-'+type+'">.</span>';
                str += '<span class="ldnft-loading-dot ldnft-loading-dot-'+type+'">.</span>';
                str += '</div>';

                return str;
            },
            print_asterik_line: function( ) {

                let line = '';

                for( let i = 0; i < 50; i++ ) {
                    line += '*';
                }

                return line;
            },
            import_cron_status: function() {
                
                if( LDNFT.is_cron_page_check=='yes' ) {
                    if( LDNFT.import_cron_status != 'complete' ) {
                        $('.ldnft-process-freemius-data-log').append( '<br>' + Settings_API.print_asterik_line() );
                        $('.ldnft-process-freemius-data-log').append('<br>'+ Settings_API.display_preloader(LDNFT.plugins_start_msg, 'plugins') );
                        $('.ldnft-loading-dot-initial').css('display', 'none');
                        Settings_API.timeout_obj = setTimeout( Settings_API.check_cron_status, 6000 );
                    } else {
                        $('.ldnft-loading-dot').css('display', 'none');
                        LDNFT.import_cron_status = 'complete';
                        clearTimeout( Settings_API.timeout_obj );
                    }
                }
            },
            check_cron_status: function() {
                
                var data = {
                    action: 'ldnft_check_cron_status',
                    state: Settings_API.current_cron_step
                }

                $('#ldnft-settings-import-error-message').html('').css( 'display', 'none' );
                jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                    
                    if( response.status == 'complete' ) {
                        $('.ldnft-loading-dot').css('display', 'none');
                        $('.ldnft-process-freemius-data-log').append('<br>'+LDNFT.complete_msg);
                        LDNFT.import_cron_status = 'complete';
                        clearTimeout(Settings_API.timeout_obj);
                    } else {

                       // $('.ldnft-process-freemius-data-log').css( 'display', 'block' );
                        switch( response.status ) {
                            case 'plans':
                                
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-loading-dot').css('display', 'none');
                                    $('.ldnft-process-freemius-data-log').append('<br>'+response.Planmsg);
                                }
                                
                                if( parseInt( response.Plans ) == 1 ) {

                                    Settings_API.current_cron_step = 'customers';
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + Settings_API.print_asterik_line() );
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + Settings_API.display_preloader( LDNFT.customer_start_msg, response.status ) );
                                }

                                break;
                            case 'customers':
                                
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-loading-dot').css('display', 'none');
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + response.Customermsg);
                                }

                                if( parseInt( response.Customers ) == 1 ) {

                                    Settings_API.current_cron_step = 'sales';
                                    $('.ldnft-process-freemius-data-log').append('<br>'+Settings_API.print_asterik_line() );
                                    $('.ldnft-process-freemius-data-log').append('<br>'+Settings_API.display_preloader( LDNFT.sales_start_msg, response.status ) );
                                }

                                break;
                            case 'sales':
                                
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-loading-dot').css('display', 'none');
                                    $('.ldnft-process-freemius-data-log').append('<br>'+response.Salesmsg);
                                }

                                if( parseInt( response.Sales ) == 1 ) {

                                    Settings_API.current_cron_step = 'subscription';
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + Settings_API.print_asterik_line() );
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + Settings_API.display_preloader( LDNFT.subscription_start_msg, response.status ) );
                                }

                                break;
                            case 'subscription':

                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-loading-dot').css('display', 'none');
                                    $('.ldnft-process-freemius-data-log').append('<br>'+response.Subscriptionmsg);
                                }
                                
                                if( parseInt( response.Subscription ) == 1 ) {

                                    Settings_API.current_cron_step = 'reviews';
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + Settings_API.print_asterik_line() );
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + Settings_API.display_preloader( LDNFT.reviews_start_msg, response.status ) );
                                }

                                break;
                            case 'reviews':
                                
                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-loading-dot').css('display', 'none');
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + response.Reviewsmsg );
                                }

                                if( parseInt( response.Reviews ) == 1 ) {

                                    Settings_API.current_cron_step = 'complete';
                                    $( '.ldnft-process-freemius-data-log' ).append( '<br>' + Settings_API.print_asterik_line() );
                                }

                                break; 
                            default:

                                if( parseInt(response.no_messgae) != 1 ) {
                                    $('.ldnft-loading-dot').css('display', 'none');
                                    $('.ldnft-process-freemius-data-log').append( '<br>' + response.Pluginmsg );
                                }
 
                                if( parseInt( response.Plugins ) == 1 ) {

                                    Settings_API.current_cron_step = 'plans';
                                    $( '.ldnft-process-freemius-data-log' ).append( '<br>' + Settings_API.print_asterik_line() );
                                    $( '.ldnft-process-freemius-data-log' ).append( '<br>' + Settings_API.display_preloader( LDNFT.plans_start_msg, response.status ) );
                                }
                                
                                break;
                        }

                        Settings_API.timeout_obj = setTimeout( Settings_API.check_cron_status, 6000 );
                    }

                    if( response.error == 1 ) {

                        clearTimeout( Settings_API.timeout_obj );
                     } 

                } ).fail(function(response) {
                    $('#ldnft-settings-import-error-message').html( LDNFT.ldnft_error_reload_message ).css( 'display', 'block' );
                    clearTimeout( Settings_API.timeout_obj );
                });
            },
            /**
             * Save settings button is clicked
             */
            ldnft_save_setting: function() {
                $( '.ldnft-save-setting' ).on( 'click', function() {
                    $('.ldnft-submit-button a.ldnft-sync-data-setting').attr( 'disabled', true );
                    $('.ldnft-submit-button a.ldnft-save-setting').html(LDNFT.test_n_save + ' <img width="16px" src="'+LDNFT.loader+'">').attr( 'disabled', true );
                    $('#ldnft-save-setting-form').submit();
                } );
            },
            /**
             * Sync Data from Freemius website
             */
            sync_data_from_freemius: function(){
                $( '.ldnft-sync-data-setting' ).on( 'click', function(){
                    var data = {
                        action: 'ldnft_run_freemius_import',
                        type: $(this).data('type')
                    }
                    
                    $('.ldnft-submit-button a.ldnft-save-setting').attr( 'disabled', true );
                    $('.ldnft-submit-button a.ldnft-sync-data-setting').attr( 'disabled', true ).html(LDNFT.sync_data + ' <img width="16px" src="'+LDNFT.loader+'">');
                    
                    jQuery.post( LDNFT.ajaxURL, data, function( response ) {
                        
                        LDNFT.is_cron_page_check    = response.is_cron_page_check;
                        LDNFT.import_cron_status    = response.import_cron_status;
                        $('.ldnft-settings-sync-data-message').html(response.message).css( 'display', 'block' );
                        if( response.is_cron_page_check == 'Yes' ) {
                            document.location.reload();
                        } else {
                            $('.ldnft-submit-button a.ldnft-save-setting').attr( 'disabled', false );
                            $('.ldnft-submit-button a.ldnft-sync-data-setting').attr( 'disabled', false ).html(LDNFT.sync_data );
                        }
                        
                    } );
                });
            },
            
        };

        Settings_API.init();
    });   
})( jQuery );