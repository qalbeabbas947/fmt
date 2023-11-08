<?php

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class new LDNFT_Crons_Settings
 */
class LDNFT_Crons_Settings {

    /**
     * Constructor function
     */
    public function __construct() {
        
        global $wpdb;
        
        //import data via cron work
        add_action( 'wp_ajax_ldnft_check_cron_status',          [ $this, 'check_cron_status' ], 100 );
        add_action( 'ldnft_process_freemius_customers_data',    [ $this, 'ldnft_process_freemius_customers' ], 10, 3 );
        add_action( 'ldnft_process_freemius_plugins_data',      [ $this, 'ldnft_process_freemius_plugins' ], 10, 2 );
        add_action( 'ldnft_process_freemius_sales_data',        [ $this, 'process_freemius_sales' ], 10, 3 );
        add_action( 'ldnft_process_freemius_subscription_data', [ $this, 'process_freemius_subscription' ], 10, 3 );
        add_action( 'ldnft_process_freemius_reviews_data',      [ $this, 'process_freemius_reviews' ], 10, 3 );
        add_action( 'wp_ajax_ldnft_run_freemius_import',        [ $this, 'run_freemius_import' ], 10, 0 );
        
        if( FS__API_CONNECTION  ) {

            $cron_status    = get_option( 'ldnft_run_cron_based_on_plugins' );
            $cron_started   = get_option( 'ldnft_run_cron_based_on_plugins_started' );
            
            if( $cron_status != 'complete' ) { 
                switch( $cron_status ) {
                    case "customers":
                        if( $cron_started != 'yes' ) {
                            $plugins = $wpdb->get_results( 'select id from '.$wpdb->prefix.'ldnft_plugins' );
                            foreach( $plugins as $plugin ) {
                                if( intval( $plugin->id ) > 0 ) {
                                    $opt = get_option( 'ldnft_process_freemius_customers_stats' );
                                    if( empty( $opt ) ) {
                                        $active_crons[$plugin->id] = [0,0]; //first param is used for count and second to check if cron is complete.
                                        update_option('ldnft_process_freemius_customers_stats', $active_crons );
                                    }

                                    $start = get_option( 'ldnft_process_freemius_customers_index_'.$plugin->id );
                                    $this->ldnft_process_freemius_customers( $plugin->id, intval($start) );
                                }
                            }
                        }
                        break;
                    case "plans":
                        if( $cron_started != 'yes' ) {
                            $opt = get_option( 'ldnft_process_freemius_plans_stats' );
                            if( empty( $opt ) ) {
                                $active_crons = [ 0, 0 ]; //first param is used for count and second to check if cron is complete.
                                update_option( 'ldnft_process_freemius_plans_stats', $active_crons );
                            }

                            $this->ldnft_process_freemius_plans( );
                        }
                        break;
                    case "sales":
                        if( $cron_started != 'yes' ) {
                            $plugins = $wpdb->get_results( 'select id from '.$wpdb->prefix.'ldnft_plugins' );
                            foreach( $plugins as $plugin ) {
                                if( intval( $plugin->id ) > 0 ) {
                                    $opt = get_option( 'ldnft_process_freemius_sales_stats' );
                                    if( empty( $opt ) ) {
                                        $active_crons[$plugin->id] = [0,0]; //first param is used for count and second to check if cron is complete.
                                        update_option( 'ldnft_process_freemius_sales_stats', $active_crons );
                                    }

                                    $start = get_option( 'ldnft_process_freemius_sales_index_'.$plugin->id );
                                    $this->process_freemius_sales( $plugin->id, intval($start) );
                                }
                            }
                        }
                        
                        break;
                    case "subscription":
                        if( $cron_started != 'yes' ) {
                            $plugins = $wpdb->get_results( 'select id from '.$wpdb->prefix.'ldnft_plugins' );
                            
                            foreach( $plugins as $plugin ) {
                                if( intval( $plugin->id ) > 0 ) {
                                    $opt = get_option( 'ldnft_process_freemius_subscription_stats' );
                                    if( empty( $opt ) ) {
                                        $active_crons[$plugin->id] = [0,0]; //first param is used for count and second to check if cron is complete.
                                        update_option( 'ldnft_process_freemius_subscription_stats', $active_crons );
                                    }

                                    $start = get_option( 'ldnft_process_freemius_subscription_index_'.$plugin->id );
                                    $this->process_freemius_subscription( $plugin->id, intval($start) );
                                }
                            }
                        }
                        break;
                    case "reviews":
                        if( $cron_started != 'yes' ) {
                            $plugins = $wpdb->get_results( 'select id from '.$wpdb->prefix.'ldnft_plugins' );
                            
                            foreach( $plugins as $plugin ) {
                                if( intval( $plugin->id ) > 0 ) {
                                    $opt = get_option( 'ldnft_process_freemius_reviews_stats' );
                                    if( empty( $opt ) ) {
                                        $active_crons[$plugin->id] = [0,0]; //first param is used for count and second to check if cron is complete.
                                        update_option( 'ldnft_process_freemius_reviews_stats', $active_crons );
                                    }

                                    $start = get_option( 'ldnft_process_freemius_reviews_index_'.$plugin->id );
                                    $this->process_freemius_reviews( $plugin->id, intval($start) );
                                }
                            }
                        }
                        break;
                    default:
                        if( $cron_started != 'yes' ) {
                            $opt = get_option( 'ldnft_process_freemius_plugins_stats' );
                            if( empty( $opt ) ) {
                                $active_crons = [ 0, 0 ]; //first param is used for count and second to check if cron is complete.
                                update_option( 'ldnft_process_freemius_plugins_stats', $active_crons );
                            }
                            
                            $start = get_option( 'ldnft_process_freemius_plugins_index' );
                            $this->ldnft_process_freemius_plugins( intval($start) );
                        }
                        
                        break;
                }

                self::calculate_cron_process();
            }

            
        }
    }

    public function run_freemius_import(){
        
        global $wpdb;
        
        header('Content-Type: application/json; charset=utf-8');

        $cron_status    = get_option( 'ldnft_run_cron_based_on_plugins' );
        $cron_started   = get_option( 'ldnft_run_cron_based_on_plugins_started' );
        
        $response = [];
        if( $cron_status != 'complete' ) { 
            $table_name = $wpdb->prefix.'ldnft_plugins';
            if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
                update_option( 'ldnft_run_cron_based_on_plugins', 'plugins' );
                update_option( 'ldnft_run_cron_based_on_plugins_started', 'no' );
                update_option('ldnft_process_plg_updated', 'no' );
                update_option('ldnft_process_plan_updated', 'no' );
                update_option('ldnft_process_customers_updated', 'no' );
                update_option('ldnft_process_sale_updated', 'no' );
                update_option('ldnft_process_reviews_updated', 'no' );
                update_option('ldnft_process_subscription_updated', 'no' );

                $start = get_option( 'ldnft_process_freemius_plugins_index' );
                $this->ldnft_process_freemius_plugins( intval($start) );

                $response = [ 'is_cron_page_check' => 'Yes', 'import_cron_status' => $cron_status, 'message' => __('Sync process has been started.', LDNFT_TEXT_DOMAIN) ];
            } else {
                $response = [ 'is_cron_page_check' => 'No', 'import_cron_status' => $cron_status, 'message' => __('Sync process is already running.', LDNFT_TEXT_DOMAIN) ];
            }
        } else {
            if( $cron_started != 'yes' ) {

                update_option( 'ldnft_run_cron_based_on_plugins', 'plugins' );
                update_option( 'ldnft_run_cron_based_on_plugins_started', 'no' );
                update_option('ldnft_process_plg_updated', 'no' );
                update_option('ldnft_process_plan_updated', 'no' );
                update_option('ldnft_process_customers_updated', 'no' );
                update_option('ldnft_process_sale_updated', 'no' );
                update_option('ldnft_process_reviews_updated', 'no' );
                update_option('ldnft_process_subscription_updated', 'no' );

                $start = get_option( 'ldnft_process_freemius_plugins_index' );
                $this->ldnft_process_freemius_plugins( intval($start) );

                $response = [ 'is_cron_page_check' => 'Yes', 'import_cron_status' => $cron_status, 'message' => __('Sync process has been started.', LDNFT_TEXT_DOMAIN) ];
            } else{
                $response = [ 'is_cron_page_check' => 'No', 'import_cron_status' => $cron_status, 'message' => __('Sync process is already running.', LDNFT_TEXT_DOMAIN) ];
            }
        }
        
        wp_die( json_encode( $response ) );
    }

    /**
	 * checks if crons is running or complete.
	 */
    public function process_freemius_sales( $plugin_id,  $start = 0, $limit = 50 ) {
        
        global $wpdb;
        
        $table_name = $wpdb->prefix.'ldnft_transactions';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            $wpdb->query( "CREATE TABLE $table_name (
                `id` int(11) NOT NULL,
                `plugin_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `install_id` int(11) DEFAULT NULL,
                `subscription_id` int(11) DEFAULT NULL,
                `plan_id` int(11) DEFAULT NULL,
                `gross` float DEFAULT NULL,
                `gateway_fee` float DEFAULT NULL,
                `external_id` varchar(50) DEFAULT NULL,
                `gateway` varchar(50) DEFAULT NULL,
                `coupon_id` int(11) DEFAULT NULL,
                `pricing_id` int(11) DEFAULT NULL,
                `vat_id` int(11) DEFAULT NULL,
                `environment` int(11) DEFAULT NULL,
                `country_code` varchar(3) DEFAULT NULL,
                `bound_payment_id` int(11) DEFAULT NULL,
                `source` int(11) DEFAULT NULL,
                `created` datetime DEFAULT NULL,
                `updated` datetime DEFAULT NULL,
                `vat` float DEFAULT NULL,
                `is_renewal` tinyint(1) DEFAULT NULL,
                `type` varchar(15) DEFAULT NULL,
                `ip` varchar(15) DEFAULT NULL,
                `zip_postal_code` varchar(10) DEFAULT NULL,
                `currency` varchar(3) DEFAULT NULL,
                `license_id` int(11) DEFAULT NULL
            )" );     
        }
        
        
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $inserted = 0;
        $updatednum = 0;

        $pmtobj = $api->Api('plugins/'.$plugin_id.'/payments.json?count='.$limit.'&offset='.$start, 'GET', []);
        foreach( $pmtobj->payments as $payment ) {
            
            $country_code = $payment->country_code; 
            $id = $payment->id; 
            $user_id = $payment->user_id; 
            $plugin_id = $payment->plugin_id; 
            $install_id = $payment->install_id; 
            $subscription_id = $payment->subscription_id; 
            $plan_id = $payment->plan_id; 
            $gross = $payment->gross; 
            $license_id = $payment->license_id; 
            $bound_payment_id = $payment->bound_payment_id; 
            $external_id = $payment->external_id; 
            $gateway = $payment->gateway; 
            $gateway_fee = $payment->gateway_fee; 
            $vat = $payment->vat; 
            $vat_id = $payment->vat_id;
            $pricing_id = $payment->pricing_id;
            $type = $payment->type; 
            $is_renewal = $payment->is_renewal;
            $coupon_id = $payment->coupon_id; 
            $created = $payment->created; 
            $updated = $payment->updated; 
        
            $ip = $payment->ip; 
            $pricing_id = $payment->pricing_id; 
            $zip_postal_code = $payment->zip_postal_code; 
            $vat_id = $payment->vat_id; 
            $source = $payment->source; 
            $environment = $payment->environment; 
            $currency = $payment->currency; 
            
            $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $id ));
            if( count( $res ) == 0 ) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'id'                        => $id,
                        'ip'                        => $ip,
                        'pricing_id'                => $pricing_id,
                        'zip_postal_code'           => $zip_postal_code,
                        'source'                    => $source,
                        'environment'               => $environment,
                        'currency'                  => $currency,
                        'plugin_id'                 => $plugin_id,
                        'user_id'                   => $user_id,
                        'country_code'              => $country_code,
                        'subscription_id'           => $subscription_id,
                        'plan_id'                   => $plan_id,
                        'gross'                     => $gross,
                        'bound_payment_id'          => $bound_payment_id,
                        'external_id'               => $external_id,
                        'gateway'                   => $gateway,
                        'gateway_fee'               => $gateway_fee,
                        'vat'                       => $vat,
                        'type'                      => $type,
                        'is_renewal'                => $is_renewal,
                        'coupon_id'                 => $coupon_id,
                        'install_id'                => $install_id,
                        'license_id'                => $license_id,
                        'created'                   => $created,
                        'updated'                   => $updated,
                    )
                );
                $inserted++;
            } else {
                $wpdb->update($table_name, 
                    array(
                        'id'                        => $id,
                        'ip'                        => $ip,
                        'pricing_id'                => $pricing_id,
                        'zip_postal_code'           => $zip_postal_code,
                        'source'                    => $source,
                        'environment'               => $environment,
                        'currency'                  => $currency,
                        'plugin_id'                 => $plugin_id,
                        'user_id'                   => $user_id,
                        'country_code'              => $country_code,
                        'subscription_id'           => $subscription_id,
                        'plan_id'                   => $plan_id,
                        'gross'                     => $gross,
                        'bound_payment_id'          => $bound_payment_id,
                        'external_id'               => $external_id,
                        'gateway'                   => $gateway,
                        'gateway_fee'               => $gateway_fee,
                        'vat'                       => $vat,
                        'type'                      => $type,
                        'is_renewal'                => $is_renewal,
                        'coupon_id'                => $coupon_id,
                        'install_id'                => $install_id,
                        'license_id'                => $license_id,
                        'created'                   => $created,
                        'updated'                   => $updated,
                    ), array('id'=>$id));
                $updatednum++;
            }
        }

        if( intval( $plugin_id ) > 0 ) {
            $active_crons = get_option('ldnft_process_freemius_sales_stats' );
            $active_crons[$plugin_id][0] = intval( $active_crons[ $plugin_id ][0] ) + count( $pmtobj->payments );
            if( count( $pmtobj->payments ) < $limit) {
                $active_crons[$plugin_id][1] = 1;
                
                $meta_table_name = $wpdb->prefix.'ldnft_customer_meta';
                $records = $wpdb->get_results( $wpdb->prepare("select * from ".$meta_table_name." where plugin_id=%d", $plugin_id ) );
                foreach( $records as $record ) {
                    $status = $wpdb->get_var( $wpdb->prepare("select type from ".$table_name." where plugin_id=%d and user_id=%d order by id desc limit 1", $plugin_id, $record->customer_id ) );
                    
                    $wpdb->update( $meta_table_name, 
                        array(
                            'status'                => $status
                        ), array( 'plugin_id' => $plugin_id, 'customer_id' => $record->customer_id  ) );
                }
            }

            update_option('ldnft_process_freemius_sales_stats', $active_crons );
        }

        $new_start = $this->processed_records_update( 'ldnft_process_freemius_sales_index_'.$plugin_id, count( $pmtobj->payments ) );

        if( count( $pmtobj->payments ) == $limit) {
            
            if ( ! wp_next_scheduled( 'ldnft_process_freemius_sales_data' ) ) {
                $data = [
                        'plugin_id' => $plugin_id,
                        'start' => $new_start + $limit,
                        'limit' => $limit
                    ];
                
                wp_schedule_single_event( time() , 'ldnft_process_freemius_sales_data', $data );
            }
        }

        $this->check_and_complete( 'ldnft_process_freemius_sales_stats',  'subscription' );
    }

    /**
	 * checks if crons is running or complete.
	 */
    public function process_freemius_subscription( $plugin_id,  $start = 0, $limit = 50 ) {
        
        global $wpdb;

        $table_name = $wpdb->prefix.'ldnft_subscription';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            $wpdb->query( "CREATE TABLE $table_name (
                `id` int(11) NOT NULL,
                `plugin_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `user_card_id` int(11) DEFAULT NULL,
                `install_id` int(11) DEFAULT NULL,
                `amount_per_cycle` float DEFAULT NULL,
                `billing_cycle` int(11) DEFAULT NULL,
                `gross` float DEFAULT NULL,
                `tax_rate` float DEFAULT NULL,
                `outstanding_balance` float DEFAULT NULL,
                `failed_payments` int(11) DEFAULT NULL,
                `gateway` varchar(50) DEFAULT NULL,
                `coupon_id` int(11) DEFAULT NULL,
                `trial_ends` datetime DEFAULT NULL,
                `next_payment` datetime DEFAULT NULL,
                `created` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                `currency` varchar(3) DEFAULT NULL,
                `zip_postal_code` varchar(10) DEFAULT NULL,
                `external_id` varchar(35) DEFAULT NULL,
                `ip` varchar(15) DEFAULT NULL,
                `plan_id` int(11) DEFAULT NULL,
                `vat_id` int(11) DEFAULT NULL,
                `source` int(11) DEFAULT NULL,
                `environment` int(11) DEFAULT NULL,
                `country_code` varchar(2) DEFAULT NULL,
                `pricing_id` int(11) DEFAULT NULL,
                `initial_amount` float DEFAULT NULL,
                `renewal_amount` float DEFAULT NULL,
                `renewals_discount` float DEFAULT NULL,
                `renewals_discount_type` varchar(12) DEFAULT NULL,
                `license_id` int(11) DEFAULT NULL
            )" );     
        }

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $inserted = 0;
        $updatednum = 0;

        $subobj = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$limit.'&offset='.$start, 'GET', []);
        foreach( $subobj->subscriptions as $subscription ) {
            
            $total_gross = $subscription->total_gross; 
            $amount_per_cycle = $subscription->amount_per_cycle; 
            $initial_amount = $subscription->initial_amount; 
            $renewal_amount = $subscription->renewal_amount; 
            $renewals_discount = $subscription->renewals_discount; 
            $renewals_discount_type = $subscription->renewals_discount_type; 
            $billing_cycle = $subscription->billing_cycle; 
            $outstanding_balance = $subscription->outstanding_balance; 
            $failed_payments = $subscription->failed_payments; 
            $trial_ends = $subscription->trial_ends; 
            $next_payment = $subscription->next_payment; 
            $user_id = $subscription->user_id; 
            $install_id = $subscription->install_id; 
            $plan_id = $subscription->plan_id; 
            $pricing_id = $subscription->pricing_id; 
            $license_id = $subscription->license_id; 
            $country_code = $subscription->country_code; 
            $coupon_id = $subscription->coupon_id; 
            $user_card_id = $subscription->user_card_id; 
            $plugin_id = $subscription->plugin_id; 
            $external_id = $subscription->external_id; 
            $gateway = $subscription->gateway; 
            $id = $subscription->id; 
            $created = $subscription->created; 
            $updated = $subscription->updated; 
            $currency = $subscription->currency; 
            $tax_rate = $subscription->tax_rate;
            $ip = $subscription->ip;
            $zip_postal_code = $subscription->zip_postal_code; 
            $vat_id = $subscription->vat_id; 
            $source = $subscription->source; 
            $environment = $subscription->environment; 

            $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $id ));
            if( count( $res ) == 0 ) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'id'                        => $id,
                        'tax_rate'                  => $tax_rate,
                        'ip'                        => $ip,
                        'zip_postal_code'           => $zip_postal_code,
                        'vat_id'                    => $vat_id,
                        'source'                    => $source,
                        'user_card_id'              => $user_card_id,
                        'environment'               => $environment,
                        'plugin_id'                 => $plugin_id,
                        'user_id'                   => $user_id,
                        'install_id'                => $install_id,
                        'amount_per_cycle'          => $amount_per_cycle,
                        'billing_cycle'             => $billing_cycle,
                        'gross'                     => $total_gross,
                        'outstanding_balance'       => $outstanding_balance,
                        'failed_payments'           => $failed_payments,
                        'gateway'                   => $gateway,
                        'coupon_id'                 => $coupon_id,
                        'trial_ends'                => $trial_ends,
                        'next_payment'              => $next_payment,
                        'created'                   => $created,
                        'updated_at'                => $updated,
                        'currency'                  => $currency,
                        'pricing_id'                => $pricing_id,
                        'country_code'              => $country_code,
                        'plan_id'                   => $plan_id,
                        'external_id'               => $external_id,
                        'initial_amount'            => $initial_amount,
                        'renewal_amount'            => $renewal_amount,
                        'renewals_discount'         => $renewals_discount,
                        'renewals_discount_type'    => $renewals_discount_type,
                        'license_id'               => $license_id,
                    )
                );
                $inserted++;
            } else {
                $wpdb->update($table_name, 
                    array(
                        'plugin_id'                 => $plugin_id,
                        'user_id'                   => $user_id,
                        'tax_rate'                  => $tax_rate,
                        'ip'                        => $ip,
                        'zip_postal_code'           => $zip_postal_code,
                        'user_card_id'              => $user_card_id,
                        'vat_id'                    => $vat_id,
                        'source'                    => $source,
                        'environment'               => $environment,
                        'install_id'                => $install_id,
                        'amount_per_cycle'          => $amount_per_cycle,
                        'billing_cycle'             => $billing_cycle,
                        'gross'                     => $total_gross,
                        'outstanding_balance'       => $outstanding_balance,
                        'failed_payments'           => $failed_payments,
                        'gateway'                   => $gateway,
                        'coupon_id'                 => $coupon_id,
                        'trial_ends'                => $trial_ends,
                        'next_payment'              => $next_payment,
                        'created'                   => $created,
                        'updated_at'                => $updated,
                        'currency'                  => $currency,
                        'pricing_id'                => $pricing_id,
                        'country_code'              => $country_code,
                        'plan_id'                   => $plan_id,
                        'external_id'               => $external_id,
                        'initial_amount'            => $initial_amount,
                        'renewal_amount'            => $renewal_amount,
                        'renewals_discount'         => $renewals_discount,
                        'renewals_discount_type'    => $renewals_discount_type,
                        'license_id'               => $license_id
                    ), array('id'=>$id));
                    $updatednum++;
            }
            
        }

        if( intval( $plugin_id ) > 0 ) {
            $active_crons = get_option('ldnft_process_freemius_subscription_stats' );
            $active_crons[$plugin_id][0] = intval( $active_crons[$plugin_id][0] ) + count( $subobj->subscriptions );
            if( count( $subobj->subscriptions ) < $limit) {
                $active_crons[$plugin_id][1] = 1;
            }

            update_option('ldnft_process_freemius_subscription_stats', $active_crons );
        }
        
        $new_start = $this->processed_records_update( 'ldnft_process_freemius_subscription_index_'.$plugin_id, count( $subobj->subscriptions ) );

        if( count( $subobj->subscriptions ) == $limit) {
            
            if ( ! wp_next_scheduled( 'ldnft_process_freemius_subscription_data' ) ) {
                $data = [
                        'plugin_id' => $plugin_id,
                        'start' => $new_start + $limit,
                        'limit' => $limit
                    ];
                
                wp_schedule_single_event( time() , 'ldnft_process_freemius_subscription_data', $data );
            }
        }

        $this->check_and_complete( 'ldnft_process_freemius_subscription_stats',  'reviews' );

    }

    /**
	 * checks if crons is running or complete.
	 */
    public function process_freemius_reviews( $plugin_id,  $start = 0, $limit = 50 ) {
        
        global $wpdb;
        
        $table_name = $wpdb->prefix.'ldnft_reviews';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            $wpdb->query( "CREATE TABLE $table_name (
                `id` int(11) NOT NULL,
                `plugin_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL,
                `external_id` int(11) DEFAULT NULL,
                `rate` int(11) DEFAULT NULL,
                `title` varchar(255) DEFAULT NULL,
                `text` varchar(255) DEFAULT NULL,
                `name` varchar(255) DEFAULT NULL,
                `job_title` varchar(255) DEFAULT NULL,
                `company` varchar(255) DEFAULT NULL,
                `company_url` varchar(255) DEFAULT NULL,
                `picture` varchar(255) DEFAULT NULL,
                `profile_url` varchar(255) DEFAULT NULL,
                `license_id` int(11) DEFAULT NULL,
                `is_verified` tinyint(1) NOT NULL,
                `is_featured` tinyint(1) NOT NULL,
                `environment` int(11) DEFAULT NULL,
                `sharable_img` varchar(255) DEFAULT NULL,
                `created` datetime DEFAULT NULL,
                `updated` datetime DEFAULT NULL
            )" );     
        }
        
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $inserted = 0;
        $updatednum = 0;
        $reviewsobj = $api->Api('plugins/'.$plugin_id.'/reviews.json?count='.$limit.'&offset='.$start, 'GET', []);
        foreach( $reviewsobj->reviews as $review ) {
            
            $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $review->id ));
            if( count( $res ) == 0 ) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'id'                        => $review->id,
                        'plugin_id'                 => $review->plugin_id,
                        'user_id'                   => $review->user_id,
                        'external_id'               => $review->external_id,
                        'rate'                      => $review->rate,
                        'title'                     => $review->title,
                        'text'                      => $review->text,
                        'name'                      => $review->name,
                        'job_title'                 => $review->job_title,
                        'company'                   => $review->company,
                        'company_url'               => $review->company_url,
                        'picture'                   => $review->picture,
                        'profile_url'               => $review->profile_url,
                        'license_id'                => $review->license_id,
                        'is_verified'               => $review->is_verified,
                        'is_featured'               => $review->is_featured,
                        'environment'               => $review->environment,
                        'sharable_img'              => $review->sharable_img,
                        'created'                   => $review->created,
                        'updated'                   => $review->updated
                    )
                );
                $inserted++;
            } else {
                $wpdb->update($table_name, 
                    array(
                        'plugin_id'                 => $review->plugin_id,
                        'user_id'                   => $review->user_id,
                        'external_id'               => $review->external_id,
                        'rate'                      => $review->rate,
                        'title'                     => $review->title,
                        'text'                      => $review->text,
                        'name'                      => $review->name,
                        'job_title'                 => $review->job_title,
                        'company'                   => $review->company,
                        'company_url'               => $review->company_url,
                        'picture'                   => $review->picture,
                        'profile_url'               => $review->profile_url,
                        'license_id'                => $review->license_id,
                        'is_verified'               => $review->is_verified,
                        'is_featured'               => $review->is_featured,
                        'environment'               => $review->environment,
                        'sharable_img'              => $review->sharable_img,
                        'updated'                   => $review->updated
                    ), array('id'=>$review->id));
                $updatednum++;
            }
        }

        if( intval( $plugin_id ) > 0 ) {
            $active_crons = get_option('ldnft_process_freemius_reviews_stats' );
            $active_crons[$plugin_id][0] = intval( $active_crons[$plugin_id][0] ) + count( $reviewsobj->reviews );
            if( count( $reviewsobj->reviews ) < $limit) {
                $active_crons[$plugin_id][1] = 1;
            }

            update_option('ldnft_process_freemius_reviews_stats', $active_crons );
        }
        
        $new_start = $this->processed_records_update( 'ldnft_process_freemius_reviews_index_'.$plugin_id, count( $reviewsobj->reviews ) );

        if( count( $reviewsobj->reviews ) == $limit) {
            
            if ( ! wp_next_scheduled( 'ldnft_process_freemius_reviews_data' ) ) {
                $data = [
                        'plugin_id' => $plugin_id,
                        'start' => $new_start + $limit,
                        'limit' => $limit
                    ];
                
                wp_schedule_single_event( time() , 'ldnft_process_freemius_reviews_data', $data );
            }
        }

        $this->check_and_complete( 'ldnft_process_freemius_reviews_stats',  'complete' );

    }

    /**
	 * checks if crons is running or complete.
	 */
    public function check_cron_status() {

        header('Content-Type: application/json; charset=utf-8');
        
        echo self::calculate_cron_process();
        exit;
    }
    
    /**
	 * checks if crons is complete.
	 */
    public static function calculate_cron_process( ) {
        
        $active_crons = get_option('ldnft_process_freemius_plugins_stats' );
        
        $status = [ 'Plugins' => 0, 'Pluginrecs' => 0, 'Pluginmsg' => __('Plugins are synced with freemius.', LDNFT_TEXT_DOMAIN) ];
        $plg_updated = get_option('ldnft_process_plg_updated' );
        if( $plg_updated == 'yes' ) {
            $status[ 'Plugins' ] = 1;
            $status[ 'Pluginmsg' ] = __('Plugins are synced with freemius.', LDNFT_TEXT_DOMAIN);
        } else {
            if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
                if( array_key_exists( 1, $active_crons ) ) {
                    if( $active_crons[1] == 1 || $active_crons[1] == "1" ) {
                        $status[ 'Plugins' ] = 1;
                        update_option('ldnft_process_plg_updated', 'yes' );
                        $status[ 'Pluginrecs' ] = $active_crons[0];
                        $status[ 'Pluginmsg' ] = __('Plugins are synced with freemius.', LDNFT_TEXT_DOMAIN);
                    }
                }
            }
        }
        
        $status[ 'Plans' ] = 0;
        $status[ 'Planrecs' ] = 0;
        $status[ 'Planmsg' ] = __('Plans are synced with freemius.', LDNFT_TEXT_DOMAIN);
        $plan_updated = get_option('ldnft_process_plan_updated' );
        if( $plan_updated == 'yes' ) {
            $status[ 'Plans' ] = 1;
            $status[ 'Planmsg' ] = __( 'Plans are synced with freemius.', LDNFT_TEXT_DOMAIN );
        } else {
            $active_crons = get_option('ldnft_process_freemius_plans_stats' );
            if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
                if( array_key_exists( 1, $active_crons ) ) {
                    if( $active_crons[1] == 1 || $active_crons[1] == "1" ) {
                        $status[ 'Plans' ] = 1;
                        update_option('ldnft_process_plan_updated', 'yes' );
                        $status[ 'Planrecs' ] = $active_crons[0];
                        $status[ 'Planmsg' ] = __( 'Plans are synced with freemius.', LDNFT_TEXT_DOMAIN );
                    }
                }
            }
        }
        

        $status[ 'Customers' ] = 0;
        $status[ 'Customerrecs' ] = 0;
        $status[ 'Customermsg' ] = __( 'Customers are synced with freemius.', LDNFT_TEXT_DOMAIN );
        $active_crons = get_option( 'ldnft_process_freemius_customers_stats' );
        $done_customers = 0;
        $customer_updated = get_option('ldnft_process_customers_updated' );
        if( $customer_updated == 'yes' ) {
            $status[ 'Customers' ] = 1;
            $status[ 'Customermsg' ] = __( 'Customers are synced with freemius.', LDNFT_TEXT_DOMAIN );
        } else {
            if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
                foreach( $active_crons as $key => $value ) {
                    if( array_key_exists(1, $value) && intval( $value[1] ) == 1 ) {
                        $done_customers++;
                        $status[ 'Customerrecs' ] = $value[0];
                    }   
                }

                if( $done_customers == count( $active_crons ) ) {
                    $status[ 'Customers' ] = 1;
                    update_option('ldnft_process_customers_updated', 'yes' );
                    $status[ 'Customermsg' ] = __( 'Customers are synced with freemius.', LDNFT_TEXT_DOMAIN );
                }
            } 
        }

        $status[ 'Sales' ] = 0;
        $status[ 'Salesrecs' ] = 0;
        $status[ 'Salesmsg' ] = __( 'Customers are synced with freemius.', LDNFT_TEXT_DOMAIN );
        $active_crons = get_option( 'ldnft_process_freemius_sales_stats' );
        $done_sales = 0;
        $sale_updated = get_option('ldnft_process_sale_updated' );
        if( $sale_updated == 'yes' ) {
            $status[ 'Sales' ] = 1;
            $status[ 'Salesmsg' ] = __( 'Sales are synced with freemius.', LDNFT_TEXT_DOMAIN );
        } else {
            if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
                foreach( $active_crons as $key => $value ) {
                    if( array_key_exists(1, $value) && intval( $value[1] ) == 1 ) {
                        $done_sales++;
                        $status[ 'Salesrecs' ] = $value[0];
                    }   
                }

                if( $done_sales == count( $active_crons ) ) {
                    $status[ 'Sales' ] = 1;
                    update_option('ldnft_process_sale_updated', 'yes' );
                    $status[ 'Salesmsg' ] = __( 'Sales are synced with freemius.', LDNFT_TEXT_DOMAIN );
                }
            } 
        }

        $status[ 'Subscription' ]       = 0;
        $status[ 'Subscriptionrecs' ]   = 0;
        $status[ 'Subscriptionmsg' ]    = __( 'Subscription are synced with freemius.', LDNFT_TEXT_DOMAIN );
        $active_crons                   = get_option( 'ldnft_process_freemius_subscription_stats' );
        $done_subscription = 0;
        $reviews_updated = get_option('ldnft_process_subscription_updated' );
        if( $reviews_updated == 'yes' ) {
            $status[ 'Subscription' ] = 1;
            $status[ 'Subscriptionmsg' ] = __( 'Subscription are synced with freemius.', LDNFT_TEXT_DOMAIN );
        } else {
            if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
                foreach( $active_crons as $key => $value ) {
                    if( array_key_exists(1, $value) && intval( $value[1] ) == 1 ) {
                        $done_subscription++;
                        $status[ 'Subscriptionrecs' ] = $value[0];
                    }   
                }

                if( $done_subscription == count( $active_crons ) ) {
                    $status[ 'Subscription' ] = 1;
                    update_option('ldnft_process_subscription_updated', 'yes' );
                    $status[ 'Subscriptionmsg' ] = __( 'Subscription are synced with freemius.', LDNFT_TEXT_DOMAIN );
                }
            } 
        }

        $status[ 'Reviews' ]        = 0;
        $status[ 'Reviewsrecs' ]    = 0;
        $status[ 'Reviewsmsg' ]     = __( 'Reviews are synced with freemius.', LDNFT_TEXT_DOMAIN );
        $active_crons               = get_option( 'ldnft_process_freemius_reviews_stats' );
        $done_reviews               = 0;
        $reviews_updated = get_option('ldnft_process_reviews_updated' );
        if( $reviews_updated == 'yes' ) {
            $status[ 'Reviews' ] = 1;
            $status[ 'Reviewsmsg' ] = __( 'Reviews are synced with freemius.', LDNFT_TEXT_DOMAIN );
        } else {
            if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
                foreach( $active_crons as $key => $value ) {
                    if( array_key_exists(1, $value) && intval( $value[1] ) == 1 ) {
                        $done_reviews++;
                        $status[ 'Reviewsrecs' ] = $value[0];
                    }   
                }

                if( $done_reviews == count( $active_crons ) ) {
                    $status[ 'Reviews' ] = 1;
                    update_option('ldnft_process_reviews_updated', 'yes' );
                    $status[ 'Reviewsmsg' ] = __( 'Reviews are synced with freemius.', LDNFT_TEXT_DOMAIN );
                }
            } 
        }
        
        return json_encode( [ 'status' => get_option('ldnft_run_cron_based_on_plugins'), 'individual_status' => $status ]);
    }
    
    /**
	 * process plans data.
	 */
	public function ldnft_process_freemius_plans( $start = 0, $limit = 50 ) {
        
        global $wpdb;

        $table_name = $wpdb->prefix.'ldnft_plans';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            $wpdb->query( "CREATE TABLE $table_name (
                `id` int(11) NOT NULL,
                `title` varchar(255) DEFAULT NULL,
                `name` varchar(255) DEFAULT NULL,
                `description` varchar(255) DEFAULT NULL,
                `plugin_id` int(11) DEFAULT NULL,
                `is_free_localhost` tinyint(1) DEFAULT NULL,
                `is_block_features` tinyint(1) DEFAULT NULL,
                `is_block_features_monthly` tinyint(1) DEFAULT NULL,
                `license_type` tinyint(1) DEFAULT NULL,
                `is_https_support` varchar(255) DEFAULT NULL,
                `trial_period` int(11) DEFAULT NULL,
                `is_require_subscription` tinyint(1) DEFAULT NULL,
                `support_kb` varchar(255) DEFAULT NULL,
                `support_forum` varchar(255) DEFAULT NULL,
                `support_email` varchar(255) DEFAULT NULL,
                `support_phone` varchar(20) DEFAULT NULL,
                `support_skype` varchar(255) DEFAULT NULL,
                `is_success_manager` varchar(255) DEFAULT NULL,
                `is_featured` tinyint(1) DEFAULT NULL,
                `is_hidden` tinyint(1) DEFAULT NULL,
                `created` datetime DEFAULT NULL
             )" ); 
         }

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $inserted = 0;
        $updatednum = 0;
        $plugins = $wpdb->get_results( 'select id from '.$wpdb->prefix.'ldnft_plugins' );
        foreach( $plugins as $plugin ) {
            $plans_obj = $api->Api('plugins/'.$plugin->id.'/plans.json', 'GET', []);
            foreach( $plans_obj->plans as $plan ) {
                
                $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $plan->id ));
                if( count( $res ) == 0 ) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'id'                        => $plan->id,
                            'title'                     => $plan->title,
                            'name'                      => $plan->name,
                            'description'               => $plan->description,
                            'plugin_id'                 => $plan->plugin_id,
                            'is_free_localhost'         => $plan->is_free_localhost,
                            'is_block_features'         => $plan->is_block_features,
                            'is_block_features_monthly' => $plan->is_block_features_monthly,
                            'license_type'              => $plan->license_type,
                            'is_https_support'          => $plan->is_https_support,
                            'trial_period'              => $plan->trial_period,
                            'is_require_subscription'   => $plan->is_require_subscription,
                            'support_kb'                => $plan->support_kb,
                            'support_forum'             => $plan->support_forum,
                            'support_email'             => $plan->support_email,
                            'support_phone'             => $plan->support_phone,
                            'support_skype'             => $plan->support_skype,
                            'is_success_manager'        => $plan->is_success_manager,
                            'is_featured'               => $plan->is_featured,
                            'is_hidden'                 => $plan->is_hidden,
                            'created'                   => $plan->created
                        )
                    );
                    $inserted++;
                } else {
                    $wpdb->update($table_name, 
                        array(
                            'title'                     => $plan->title,
                            'name'                      => $plan->name,
                            'description'               => $plan->description,
                            'plugin_id'                 => $plan->plugin_id,
                            'is_free_localhost'         => $plan->is_free_localhost,
                            'is_block_features'         => $plan->is_block_features,
                            'is_block_features_monthly' => $plan->is_block_features_monthly,
                            'license_type'              => $plan->license_type,
                            'is_https_support'          => $plan->is_https_support,
                            'trial_period'              => $plan->trial_period,
                            'is_require_subscription'   => $plan->is_require_subscription,
                            'support_kb'                => $plan->support_kb,
                            'support_forum'             => $plan->support_forum,
                            'support_email'             => $plan->support_email,
                            'support_phone'             => $plan->support_phone,
                            'support_skype'             => $plan->support_skype,
                            'is_success_manager'        => $plan->is_success_manager,
                            'is_featured'               => $plan->is_featured,
                            'is_hidden'                 => $plan->is_hidden,
                        ), array('id'=>$plan->id));
                    $updatednum++;
                }

            }

            $active_crons = get_option('ldnft_process_freemius_plans_stats' );
            $active_crons = [ $updatednum + $inserted, 0 ]; //first param is used for count and second to check if cron is complete.
            update_option( 'ldnft_process_freemius_plans_stats', $active_crons );
        }

        $active_crons = get_option('ldnft_process_freemius_plans_stats' );
        $active_crons = [ $updatednum + $inserted, 1 ]; //first param is used for count and second to check if cron is complete.
        update_option( 'ldnft_process_freemius_plans_stats', $active_crons );

        $new_start = $this->processed_records_update( 'ldnft_process_freemius_plan_index', count( $plans_obj->plans ) );

        update_option( 'ldnft_run_cron_based_on_plugins_started', 'no' );
        update_option( 'ldnft_run_cron_based_on_plugins', 'customers' );
        
    }

    /**
	 * process customers data.
	 */
	public function ldnft_process_freemius_plugins( $start = 0, $limit = 50 ) {
		
        global $wpdb;

        $table_name = $wpdb->prefix.'ldnft_plugins';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            $wpdb->query( "CREATE TABLE $table_name (
                `id` int(11) NOT NULL,
                `title` varchar(255) DEFAULT NULL,
                `slug` varchar(255) DEFAULT NULL,
                `default_plan_id` int(11) DEFAULT NULL,
                `plans` varchar(255) DEFAULT NULL,
                `features` varchar(255) DEFAULT NULL,
                `money_back_period` int(11) Default NULL,
                `created` datetime DEFAULT NULL
            )" ); 
        }

        $service = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $plugins = $service->Api('plugins.json?count='.$limit.'&offset='.$start, 'GET', []);
        
        $inserted = 0;
        $updatednum = 0;
        $updated_index = 0;
        if( isset( $plugins->plugins ) &&  count($plugins->plugins) > 0 ) {
            foreach( $plugins->plugins as $plugin ) {
                $updated_index++;
                $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $plugin->id ));
                if( count( $res ) == 0 ) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'id'                    => $plugin->id,
                            'title'                 => $plugin->title,
                            'slug'                  => $plugin->slug,
                            'default_plan_id'       => $plugin->default_plan_id,
                            'plans'                 => $plugin->plans,
                            'features'              => $plugin->features,
                            'money_back_period'     => $plugin->money_back_period,
                            'created'               => $plugin->created
                        )
                    );
                    $inserted++;
                } else {
                    $wpdb->update($table_name, 
                        array(
                            'title'                 => $plugin->title,
                            'slug'                  => $plugin->slug,
                            'default_plan_id'       => $plugin->default_plan_id,
                            'plans'                 => $plugin->plans,
                            'features'              => $plugin->features,
                            'money_back_period'     => $plugin->money_back_period,
                            'created'               => $plugin->created
                        ), array('id'=>$plugin->id));
                    $updatednum++;
                }
            }
        }

        $active_crons = get_option('ldnft_process_freemius_plugins_stats' );
        $active_crons[0] = intval( $active_crons[0] ) + count( $plugins->plugins );
        if( count( $plugins->plugins ) < $limit) {
            $active_crons[1] = 1;
            update_option( 'ldnft_run_cron_based_on_plugins_started', 'no' );
            update_option('ldnft_run_cron_based_on_plugins', 'plans');
        } else {
            $active_crons[1] = 0;
        }

        update_option('ldnft_process_freemius_plugins_stats', $active_crons );
        
        $new_start = $this->processed_records_update( 'ldnft_process_freemius_plugins_index', count( $plugins->plugins ) );
        
        if( count( $plugins->plugins ) == $limit) {
            
            if ( ! wp_next_scheduled( 'ldnft_process_freemius_plugins_data' ) ) {
                $data = [
                        'start' => $new_start + $limit,
                        'limit' => $limit
                    ];
                
                wp_schedule_single_event( time() , 'ldnft_process_freemius_plugins_data', $data );
            }
        }
        
        //echo json_encode(['inserted' => $inserted, 'updated'=>$updatednum, 'message' => sprintf( __('Inserted: %d, Updated: %d', 'MWC'), $inserted, $updatednum)]);
        //exit;
	}

    /**
	 * Record the processed records.
	 */
    public function processed_records_update( $key, $current_records ) {

        $customers_count = get_option( $key );
        $new_start = $current_records + intval( $customers_count );
        update_option( $key,  $new_start );

        return $new_start;
    }

    /**
	 * process customers data.
	 */
	public function ldnft_process_freemius_customers( $plugin_id, $start = 0, $limit = 50){
		
        global $wpdb;

        $table_name = $wpdb->prefix.'ldnft_customers';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            $wpdb->query( "CREATE TABLE $table_name (
                `id` int(11) NOT NULL,
                `email` varchar(255) DEFAULT NULL,
                `first` varchar(255) DEFAULT NULL,
                `last` varchar(255) DEFAULT NULL,
                `is_verified` tinyint(1) DEFAULT NULL,
                `is_marketing_allowed` tinyint(1) DEFAULT NULL,
                `created` datetime DEFAULT NULL
            )" );     
        }
        
        $meta_table_name = $wpdb->prefix.'ldnft_customer_meta';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$meta_table_name'" ) ) ) {
            $wpdb->query( "CREATE TABLE $meta_table_name (
                `plugin_id` int(11) NOT NULL,
                `customer_id` int(11) NOT NULL,
                `status` varchar(20) DEFAULT NULL,
                `created` datetime DEFAULT NULL
            )" );     
        }
        
        
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $inserted = 0;
        $updatednum = 0;
        $usrobj = $api->Api('plugins/'.$plugin_id.'/users.json?count='.$limit.'&offset='.$start, 'GET', []);
        if( ! isset( $usrobj->error )  ) {
            foreach( $usrobj->users as $user ) {
                
                $res = $wpdb->get_results( $wpdb->prepare("select * from ".$table_name." where id=%d", $user->id ));
                if( count( $res ) == 0 ) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'id'                    => $user->id,
                            'email'                 => $user->email,
                            'first'                 => $user->first,
                            'last'                  => $user->last,
                            'is_verified'           => $user->is_verified,
                            'created'               => $user->created,
                            'is_marketing_allowed'  => $user->is_marketing_allowed
                        )
                    );
                    
                    $wpdb->insert(
                        $meta_table_name,
                        array(
                            'plugin_id'               => $plugin_id,
                            'customer_id'             => $user->id,
                            'created'                 => $user->created,
                            'status'                  => ''
                        )
                    );
                    
                    $inserted++;
                } else {
                    $wpdb->update( $table_name, 
                        array(
                            'email'                 => $user->email,
                            'first'                 => $user->first,
                            'last'                  => $user->last,
                            'is_verified'           => $user->is_verified,
                            'created'               => $user->created,
                            'is_marketing_allowed'  => $user->is_marketing_allowed
                        ), array( 'id' => $user->id ) );
                    
                    $res = $wpdb->get_results( $wpdb->prepare("select * from ".$meta_table_name." where customer_id=%d and plugin_id=%d", $user->id, $plugin_id ));
                    if( count( $res ) == 0 ) {
                        $wpdb->insert(
                            $meta_table_name,
                            array(
                                'plugin_id'               => $plugin_id,
                                'customer_id'             => $user->id,
                                'created'                 => $user->created,
                                'status'                  => ''
                            )
                        );
                    }    
                    $updatednum++;
                }
            }

            if( intval( $plugin_id ) > 0 ) {
                $active_crons = get_option('ldnft_process_freemius_customers_stats' );
                $active_crons[$plugin_id][0] = intval( $active_crons[$plugin_id][0] ) + count( $usrobj->users );
                if( count( $usrobj->users ) < $limit) {
                    $active_crons[$plugin_id][1] = 1;
                }
                
                update_option( 'ldnft_process_freemius_customers_stats', $active_crons );
            }

            $new_start = $this->processed_records_update( 'ldnft_process_freemius_customers_index_'.$plugin_id, count( $usrobj->users ) );

            if( count( $usrobj->users ) == $limit) {
                
                if ( ! wp_next_scheduled( 'ldnft_process_freemius_customers_data' ) ) {
                    $data = [
                            'plugin_id' => $plugin_id,
                            'start' => $new_start + $limit,
                            'limit' => $limit
                        ];
                    
                    wp_schedule_single_event( time() , 'ldnft_process_freemius_customers_data', $data );
                }
            }

            $this->check_and_complete( 'ldnft_process_freemius_customers_stats',  'sales' );
        } else{
            if ( ! wp_next_scheduled( 'ldnft_process_freemius_customers_data' ) ) {
                $data = [
                        'plugin_id' => $plugin_id,
                        'start' => $start,
                        'limit' => $limit
                    ];
                
                wp_schedule_single_event( time() , 'ldnft_process_freemius_customers_data', $data );
            }
        }
	}

    /**
	 * check and mark as complete if all plugins data is imported
	 */
    public function check_and_complete( $stats_key,  $next_step ) {
        
        $active_crons = get_option( $stats_key );
        $done_customers = 0;
        
        if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
            foreach( $active_crons as $key => $value ) {
                
                if( array_key_exists(1, $value) && intval( $value[1] ) > 0 ) {
                    $done_customers++;
                }
            }
            
            if( count( $active_crons ) == $done_customers ) {
                update_option( 'ldnft_run_cron_based_on_plugins', $next_step );
                update_option( 'ldnft_run_cron_based_on_plugins_started', 'no' );
            }
        }

    }
}

new LDNFT_Crons_Settings();