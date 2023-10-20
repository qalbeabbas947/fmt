<?php
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class LDNFT_Settings
 */
class LDNFT_Settings {

	private $page_tab;
    
    /**
     * Constructor function
     */
    public function __construct() {
        
        global $wpdb;

        $this->page_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'freemius-api';
        add_action( 'admin_menu',                               [ $this, 'setting_menu' ], 1001 );
        add_action( 'admin_post_ldnft_submit_action',           [ $this, 'save_settings' ] );
        add_action( 'admin_notices',                            [ $this, 'ldnft_admin_notice' ] );
        add_action( 'wp_ajax_ldnft_mailpoet_submit_action',     [ $this, 'mailpoet_submit_action' ], 100 );
        

        // /**
        //  * import data via cron work 
        //  */
        // if( FS__API_CONNECTION && !wp_doing_ajax() ) {
        //     add_action( 'wp_ajax_ldnft_check_cron_status',          [ $this, 'check_cron_status' ], 100 );
        //     add_filter('cron_schedules', [ $this, 'ldnft_cron_schedules' ], 9999, 1);
            
        //     $cron_status    = get_option( 'ldnft_run_cron_based_on_plugins' );
        //     $cron_started   = get_option( 'ldnft_run_cron_based_on_plugins_started' );
            
        //     if( $cron_status != 'complete' ) { 
        //         switch( $cron_status ) {
        //             case "customers":
        //                 if( $cron_started != 'yes' ) {
        //                     $plugins = $wpdb->get_results( 'select id from '.$wpdb->prefix.'ldnft_plugins' );
        //                     foreach( $plugins as $plugin ) {
        //                         if( intval( $plugin->id ) > 0 ) {
        //                             $active_crons = get_option('ldnft_process_freemius_customers_stats' );
        //                             $active_crons[$plugin->id] = [ 0, 0 ]; //first param is used for count and second to check if cron is complete.
        //                             update_option('ldnft_process_freemius_customers_stats', $active_crons );
                    
        //                             $this->ldnft_process_freemius_customers( $plugin->id );
        //                         }
        //                     }
        //                 }
        //                 break;
        //             case "plans":
        //                 if( $cron_started != 'yes' ) {
        //                     $active_crons = get_option('ldnft_process_freemius_plans_stats' );
        //                     $active_crons = [ 0, 0 ]; //first param is used for count and second to check if cron is complete.
        //                     update_option( 'ldnft_process_freemius_plans_stats', $active_crons );
        //                     $this->ldnft_process_freemius_plans( );
        //                 }
        //                 break;
        //             case "sales":
        //                 if( $cron_started != 'yes' ) {
        //                     echo 'sales';
        //                 }
        //                 exit;
        //                 break;
        //             case "subscription":
        //                 if( $cron_started != 'yes' ) {
        //                     echo 'subscription';
        //                 }
        //                 break;
        //             default:
        //                 if( $cron_started != 'yes' ) {
        //                     $active_crons = get_option('ldnft_process_freemius_plugins_stats' );
        //                     $active_crons = [ 0, 0 ]; //first param is used for count and second to check if cron is complete.
        //                     update_option( 'ldnft_process_freemius_plugins_stats', $active_crons );
        //                     $this->ldnft_process_freemius_plugins( );
        //                 }
                        
        //                 break;
        //         }

        //         self::calculate_cron_process();
        //     }

        //     // if( $cron_status != 'complete' && $cron_status != 'running' ) {
        //     //     add_action( 'init', [ $this, 'ldnft_run_cron_based_on_plugins' ] );
        //     // }
            
        //     add_action( 'ldnft_process_freemius_customers_data', [ $this, 'ldnft_process_freemius_customers' ], 10, 3 );
        //     add_action( 'ldnft_process_freemius_plugins_data', [ $this, 'ldnft_process_freemius_plugins' ], 10, 2 );

        //     add_action( 'ldnft_process_freemius_sales_data', [ $this, 'process_freemius_sales' ], 10, 2 );
        //     add_action( 'ldnft_process_freemius_subscription_data', [ $this, 'process_freemius_subscription' ], 10, 2 );
        //     add_action( 'ldnft_process_freemius_reviews_data', [ $this, 'process_freemius_reviews' ], 10, 2 );
        // }
    }

    /**
	 * checks if crons is running or complete.
	 */
    public function process_freemius_sales() {
        
        global $wpdb;
        error_log( 'ldnft_process_freemius_sales:'.$plugin_id.',  '.$start.', '.$limit );
        $table_name = $wpdb->prefix.'ldnft_transactions';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            $wpdb->query( "CREATE TABLE $table_name (
                `id` int(11) NOT NULL,
                `plugin_id` int(11) NOT NULL,
                `plugin_title` varchar(255) NOT NULL,
                `user_id` int(11) NOT NULL,
                `username` varchar(255) DEFAULT NULL,
                `useremail` varchar(255) DEFAULT NULL,
                `install_id` int(11) DEFAULT NULL,
                `subscription_id` int(11) DEFAULT NULL,
                `plan_id` int(11) DEFAULT NULL,
                `gross` float DEFAULT NULL,
                `gateway_fee` float DEFAULT NULL,
                `external_id` varchar(50) DEFAULT NULL,
                `gateway` int(11) DEFAULT NULL,
                `coupon_id` int(11) DEFAULT NULL,
                `country_code` varchar(3) DEFAULT NULL,
                `bound_payment_id` int(11) DEFAULT NULL,
                `created` datetime DEFAULT NULL,
                `updated` datetime DEFAULT NULL,
                `vat` float DEFAULT NULL,
                `is_renewal` tinyint(1) NOT NULL,
                `type` varchar(15) NOT NULL,
                `license_id` int(11) DEFAULT NULL
            )" );     
        }
        
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $inserted = 0;
        $updatednum = 0;
        $usrobj = $api->Api('plugins/'.$plugin_id.'/users.json?count='.$limit.'&offset='.$start, 'GET', []);
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
                    ), array('id'=>$user->id));
                $updatednum++;
            }
        }

        if( intval( $plugin_id ) > 0 ) {
            $active_crons = get_option('ldnft_process_freemius_customers_stats' );
            $active_crons[$plugin_id][0] = intval( $active_crons[$plugin_id][0] ) + count( $usrobj->users );
            if( count( $usrobj->users ) < $limit) {
                $active_crons[$plugin_id][1] = 1;
            }
        }

        update_option('ldnft_process_freemius_customers_stats', $active_crons );

        if( count( $usrobj->users ) == $limit) {
            
            if ( ! wp_next_scheduled( 'ldnft_process_freemius_customers_data' ) ) {
                $data = [
                        'plugin_id' => $plugin_id,
                        'start' => $start + $limit,
                        'limit' => $limit
                    ];
                
                error_log( 'ldnft_process_freemius_customers passing:'.$plugin_id.',  '.$start + $limit.', '.$limit.'  ');
                
                wp_schedule_single_event( time() , 'ldnft_process_freemius_customers_data', $data );
            }
        }

        error_log( print_r(['inserted' => $inserted, 'updated'=>$updatednum, 'message' => sprintf( __('Inserted: %d, Updated: %d', 'MWC'), $inserted, $updatednum)], true) );
        //return ['inserted' => $inserted, 'updated'=>$updatednum, 'message' => sprintf( __('Inserted: %d, Updated: %d', 'MWC'), $inserted, $updatednum)]; 
    }

    /**
	 * checks if crons is running or complete.
	 */
    // public function process_freemius_subscription() {
        
    // }

    /**
	 * checks if crons is running or complete.
	 */
    // public function process_freemius_reviews() {
        
    // }

    /**
	 * checks if crons is running or complete.
	 */
    // public function check_cron_status() {

    //     header('Content-Type: application/json; charset=utf-8');
        
    //     echo self::calculate_cron_process();
    //     exit;
    // }
    
    /**
	 * checks if crons is complete.
	 */
    // public static function calculate_cron_process( ) {
        
    //     $active_crons = get_option('ldnft_process_freemius_plugins_stats' );
        
    //     $status = [ 'Plugins' => 0, 'Pluginrecs' => 0, 'Pluginmsg' => __('Please wait, while we are syncing the freemius plugins data.', LDNFT_TEXT_DOMAIN) ];
    //     if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
    //         if( array_key_exists( 1, $active_crons ) ) {
    //             if( $active_crons[1] == 1 || $active_crons[1] == "1" ) {
    //                 $status[ 'Plugins' ] = 1;
    //                 $status[ 'Pluginrecs' ] = $active_crons[0];
    //                 $status[ 'Pluginmsg' ] = __('Plugins are synced with freemius.', LDNFT_TEXT_DOMAIN);
    //             }
    //         }
    //     }

    //     $status[ 'Plans' ] = 0;
    //     $status[ 'Planrecs' ] = 0;
    //     $status[ 'Planmsg' ] = __('Please wait, while we are syncing the freemius plans data.', LDNFT_TEXT_DOMAIN);
    //     $active_crons = get_option('ldnft_process_freemius_plans_stats' );
    //     if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
    //         if( array_key_exists( 1, $active_crons ) ) {
    //             if( $active_crons[1] == 1 || $active_crons[1] == "1" ) {
    //                 $status[ 'Plans' ] = 1;
    //                 $status[ 'Planrecs' ] = $active_crons[0];
    //                 $status[ 'Planmsg' ] = __('Plans are synced with freemius.', LDNFT_TEXT_DOMAIN);
    //             }
    //         }
    //     }

    //     $status[ 'Customers' ] = 1;
    //     $status[ 'Customerrecs' ] = 0;
    //     $status[ 'Customermsg' ] = __('Please wait, while we are syncing the freemius customers data.', LDNFT_TEXT_DOMAIN);
    //     $active_crons = get_option('ldnft_process_freemius_customers_stats' );
    //     $done_customers = 0;
    //     if( is_array( $active_crons ) && count( $active_crons ) > 0 ) {
    //         foreach( $active_crons as $key => $value ) {
    //             if( array_key_exists(1, $value) && intval( $value[1] ) < 1 && $status[ 'Customers' ] == 1 ) {
    //                 $status[ 'Customers' ] = 0;
    //                 $status[ 'Customerrecs' ] = 0;
    //                 $status[ 'Customermsg' ] = __('Customers are synced with freemius.', LDNFT_TEXT_DOMAIN);
    //             }   

    //             if( intval( $value[1] ) > 0 ) {
    //                 $done_customers++;
    //             }
    //         }
    //         if( count( $active_crons ) == $done_customers ) {
    //             update_option( 'ldnft_run_cron_based_on_plugins', 'sales' );
    //             update_option( 'ldnft_run_cron_based_on_plugins_started', 'no' );
    //         }
    //     } 


        

    //     error_log( print_r([ 'status' => get_option('ldnft_run_cron_based_on_plugins'), 'individual_status' => $status ], true));
    //     return json_encode( [ 'status' => get_option('ldnft_run_cron_based_on_plugins'), 'individual_status' => $status ]);
    // }
    
    /**
	 * initialize the crons based on the plugins.
	 */
    // public function ldnft_run_cron_based_on_plugins() {
        
    //     $service = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
    //     $plugins = $service->Api('plugins.json?fields=id,title', 'GET', []);
        
    //     $inserted = 0;
    //     $updatednum = 0;
        
    //     if( isset( $plugins->plugins ) &&  count($plugins->plugins) > 0 ) {
    //         update_option('ldnft_run_cron_based_on_plugins', 'running' );

            

    //         foreach( $plugins->plugins as $plugin ) { 
    //             if( intval( $plugin->id ) > 0 ) {
    //                 $active_crons = get_option('ldnft_process_freemius_customers_stats' );
    //                 $active_crons[$plugin->id] = [ 0, 0 ]; //first param is used for count and second to check if cron is complete.
    //                 update_option('ldnft_process_freemius_customers_stats', $active_crons );
    
    //                 $this->ldnft_process_freemius_customers( $plugin->id );
    //             }
    //         }
    //     } else {
    //         update_option('ldnft_run_cron_based_on_plugins', 'complete' );
    //     }
    // }


    /**
	 * process plans data.
	 */
	// public function ldnft_process_freemius_plans( $start = 0, $limit = 10 ) {
    //     global $wpdb;
    //     error_log( 'ldnft_process_freemius_plans_data:'.$start.', '.$limit);
    //     $table_name = $wpdb->prefix.'ldnft_plans';
    //     if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
    //         $wpdb->query( "CREATE TABLE $table_name (
    //             `id` int(11) NOT NULL,
    //             `title` varchar(255) DEFAULT NULL,
    //             `name` varchar(255) DEFAULT NULL,
    //             `description` varchar(255) DEFAULT NULL,
    //             `plugin_id` int(11) DEFAULT NULL,
    //             `is_free_localhost` tinyint(1) DEFAULT NULL,
    //             `is_block_features` tinyint(1) DEFAULT NULL,
    //             `is_block_features_monthly` tinyint(1) DEFAULT NULL,
    //             `license_type` tinyint(1) DEFAULT NULL,
    //             `is_https_support` varchar(255) DEFAULT NULL,
    //             `trial_period` int(11) DEFAULT NULL,
    //             `is_require_subscription` tinyint(1) DEFAULT NULL,
    //             `support_kb` varchar(255) DEFAULT NULL,
    //             `support_forum` varchar(255) DEFAULT NULL,
    //             `support_email` varchar(255) DEFAULT NULL,
    //             `support_phone` varchar(20) DEFAULT NULL,
    //             `support_skype` varchar(255) DEFAULT NULL,
    //             `is_success_manager` varchar(255) DEFAULT NULL,
    //             `is_featured` tinyint(1) DEFAULT NULL,
    //             `is_hidden` tinyint(1) DEFAULT NULL,
    //             `created` datetime DEFAULT NULL
    //          )" ); 
    //      }

    //     $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
    //     $inserted = 0;
    //     $updatednum = 0;
    //     $plugins = $wpdb->get_results( 'select id from '.$wpdb->prefix.'ldnft_plugins' );
    //     foreach( $plugins as $plugin ) {
    //         $plans_obj = $api->Api('plugins/'.$plugin->id.'/plans.json', 'GET', []);
    //         foreach( $plans_obj->plans as $plan ) {
                
    //             $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $plugin->id ));
    //             if( count( $res ) == 0 ) {
    //                 $wpdb->insert(
    //                     $table_name,
    //                     array(
    //                         'id'                        => $plan->id,
    //                         'title'                     => $plan->title,
    //                         'name'                      => $plan->name,
    //                         'description'               => $plan->description,
    //                         'plugin_id'                 => $plan->plugin_id,
    //                         'is_free_localhost'         => $plan->is_free_localhost,
    //                         'is_block_features'         => $plan->is_block_features,
    //                         'is_block_features_monthly' => $plan->is_block_features_monthly,
    //                         'license_type'              => $plan->license_type,
    //                         'is_https_support'          => $plan->is_https_support,
    //                         'trial_period'              => $plan->trial_period,
    //                         'is_require_subscription'   => $plan->is_require_subscription,
    //                         'support_kb'                => $plan->support_kb,
    //                         'support_forum'             => $plan->support_forum,
    //                         'support_email'             => $plan->support_email,
    //                         'support_phone'             => $plan->support_phone,
    //                         'support_skype'             => $plan->support_skype,
    //                         'is_success_manager'        => $plan->is_success_manager,
    //                         'is_featured'               => $plan->is_featured,
    //                         'is_hidden'                 => $plan->is_hidden,
    //                         'created'                   => $plan->created
    //                     )
    //                 );
    //                 $inserted++;
    //             } else {
    //                 $wpdb->update($table_name, 
    //                     array(
    //                         'title'                     => $plan->title,
    //                         'name'                      => $plan->name,
    //                         'description'               => $plan->description,
    //                         'plugin_id'                 => $plan->plugin_id,
    //                         'is_free_localhost'         => $plan->is_free_localhost,
    //                         'is_block_features'         => $plan->is_block_features,
    //                         'is_block_features_monthly' => $plan->is_block_features_monthly,
    //                         'license_type'              => $plan->license_type,
    //                         'is_https_support'          => $plan->is_https_support,
    //                         'trial_period'              => $plan->trial_period,
    //                         'is_require_subscription'   => $plan->is_require_subscription,
    //                         'support_kb'                => $plan->support_kb,
    //                         'support_forum'             => $plan->support_forum,
    //                         'support_email'             => $plan->support_email,
    //                         'support_phone'             => $plan->support_phone,
    //                         'support_skype'             => $plan->support_skype,
    //                         'is_success_manager'        => $plan->is_success_manager,
    //                         'is_featured'               => $plan->is_featured,
    //                         'is_hidden'                 => $plan->is_hidden,
    //                     ), array('id'=>$plan->id));
    //                 $updatednum++;
    //             }

    //         }

    //         $active_crons = get_option('ldnft_process_freemius_plans_stats' );
    //         $active_crons = [ $updatednum + $inserted, 0 ]; //first param is used for count and second to check if cron is complete.
    //         update_option( 'ldnft_process_freemius_plans_stats', $active_crons );
    //     }

    //     $active_crons = get_option('ldnft_process_freemius_plans_stats' );
    //     $active_crons = [ $updatednum + $inserted, 1 ]; //first param is used for count and second to check if cron is complete.
    //     update_option( 'ldnft_process_freemius_plans_stats', $active_crons );

    //     update_option( 'ldnft_run_cron_based_on_plugins_started', 'no' );
    //     update_option( 'ldnft_run_cron_based_on_plugins', 'customers' );
        
    // }

    /**
	 * process customers data.
	 */
	// public function ldnft_process_freemius_plugins( $start = 0, $limit = 3 ) {
		
    //     global $wpdb;
    //     error_log( 'ldnft_process_freemius_plugins_data:'.$start.', '.$limit);
    //     $table_name = $wpdb->prefix.'ldnft_plugins';
    //     if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
    //         $wpdb->query( "CREATE TABLE $table_name (
    //             `id` int(11) NOT NULL,
    //             `title` varchar(255) DEFAULT NULL,
    //             `slug` varchar(255) DEFAULT NULL,
    //             `default_plan_id` int(11) DEFAULT NULL,
    //             `plans` varchar(255) DEFAULT NULL,
    //             `features` varchar(255) DEFAULT NULL,
    //             `money_back_period` int(11) Default NULL,
    //             `created` datetime DEFAULT NULL
    //         )" ); 
    //     }

    //     $service = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
    //     $plugins = $service->Api('plugins.json?count='.$limit.'&offset='.$start, 'GET', []);
        
    //     $inserted = 0;
    //     $updatednum = 0;
    //     if( isset( $plugins->plugins ) &&  count($plugins->plugins) > 0 ) {
    //         foreach( $plugins->plugins as $plugin ) {
               
    //             $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $plugin->id ));
    //             if( count( $res ) == 0 ) {
    //                 $wpdb->insert(
    //                     $table_name,
    //                     array(
    //                         'id'                    => $plugin->id,
    //                         'title'                 => $plugin->title,
    //                         'slug'                  => $plugin->slug,
    //                         'default_plan_id'       => $plugin->default_plan_id,
    //                         'plans'                 => $plugin->plans,
    //                         'features'              => $plugin->features,
    //                         'money_back_period'     => $plugin->money_back_period,
    //                         'created'               => $plugin->created
    //                     )
    //                 );
    //                 $inserted++;
    //             } else {
    //                 $wpdb->update($table_name, 
    //                     array(
    //                         'title'                 => $plugin->title,
    //                         'slug'                  => $plugin->slug,
    //                         'default_plan_id'       => $plugin->default_plan_id,
    //                         'plans'                 => $plugin->plans,
    //                         'features'              => $plugin->features,
    //                         'money_back_period'     => $plugin->money_back_period,
    //                         'created'               => $plugin->created
    //                     ), array('id'=>$plugin->id));
    //                 $updatednum++;
    //             }
    //         }
    //     }

    //     $active_crons = get_option('ldnft_process_freemius_plugins_stats' );
    //     $active_crons[0] = intval( $active_crons[0] ) + count( $plugins->plugins );
    //     if( count( $plugins->plugins ) < $limit) {
    //         $active_crons[1] = 1;
    //         update_option( 'ldnft_run_cron_based_on_plugins_started', 'no' );
    //         update_option('ldnft_run_cron_based_on_plugins', 'plans');
    //     }

    //     update_option('ldnft_process_freemius_plugins_stats', $active_crons );
    //     if( count( $plugins->plugins ) == $limit) {
            
    //         if ( ! wp_next_scheduled( 'ldnft_process_freemius_plugins_data' ) ) {
    //             $data = [
    //                     'start' => $start + $limit,
    //                     'limit' => $limit
    //                 ];
                
    //             wp_schedule_single_event( time() , 'ldnft_process_freemius_plugins_data', $data );
    //         }
    //     }
        
    //     //echo json_encode(['inserted' => $inserted, 'updated'=>$updatednum, 'message' => sprintf( __('Inserted: %d, Updated: %d', 'MWC'), $inserted, $updatednum)]);
    //     //exit;
	// }

    /**
	 * process customers data.
	 */
	// public function ldnft_process_freemius_customers( $plugin_id, $start = 0, $limit = 25){
		
    //     global $wpdb;

    //     error_log( 'ldnft_process_freemius_customers:'.$plugin_id.',  '.$start.', '.$limit );
    //     $table_name = $wpdb->prefix.'ldnft_customers';
    //     if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
    //         $wpdb->query( "CREATE TABLE $table_name (
    //             `id` int(11) NOT NULL,
    //             `email` varchar(255) DEFAULT NULL,
    //             `first` varchar(255) DEFAULT NULL,
    //             `last` varchar(255) DEFAULT NULL,
    //             `is_verified` tinyint(1) DEFAULT NULL,
    //             `is_marketing_allowed` tinyint(1) DEFAULT NULL,
    //             `created` datetime DEFAULT NULL,
    //             `status` varchar(20) DEFAULT NULL 
    //         )" );     
    //     }
        
    //     $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
    //     $inserted = 0;
    //     $updatednum = 0;
    //     $usrobj = $api->Api('plugins/'.$plugin_id.'/users.json?count='.$limit.'&offset='.$start, 'GET', []);
    //     foreach( $usrobj->users as $user ) {
            
    //         $res = $wpdb->get_results( $wpdb->prepare("select * from ".$table_name." where id=%d", $user->id ));
    //         if( count( $res ) == 0 ) {
    //             $wpdb->insert(
    //                 $table_name,
    //                 array(
    //                     'id'                    => $user->id,
    //                     'email'                 => $user->email,
    //                     'first'                 => $user->first,
    //                     'last'                  => $user->last,
    //                     'is_verified'           => $user->is_verified,
    //                     'created'               => $user->created,
    //                     'is_marketing_allowed'  => $user->is_marketing_allowed
    //                 )
    //             );
    //             $inserted++;
    //         } else {
    //             $wpdb->update( $table_name, 
    //                 array(
    //                     'email'                 => $user->email,
    //                     'first'                 => $user->first,
    //                     'last'                  => $user->last,
    //                     'is_verified'           => $user->is_verified,
    //                     'created'               => $user->created,
    //                     'is_marketing_allowed'  => $user->is_marketing_allowed
    //                 ), array('id'=>$user->id));
    //             $updatednum++;
    //         }
    //     }

    //     if( intval( $plugin_id ) > 0 ) {
    //         $active_crons = get_option('ldnft_process_freemius_customers_stats' );
    //         $active_crons[$plugin_id][0] = intval( $active_crons[$plugin_id][0] ) + count( $usrobj->users );
    //         if( count( $usrobj->users ) < $limit) {
    //             $active_crons[$plugin_id][1] = 1;
    //         }

    //         update_option( 'ldnft_process_freemius_customers_stats', $active_crons );
    //     }

        

    //     if( count( $usrobj->users ) == $limit) {
            
    //         if ( ! wp_next_scheduled( 'ldnft_process_freemius_customers_data' ) ) {
    //             $data = [
    //                     'plugin_id' => $plugin_id,
    //                     'start' => $start + $limit,
    //                     'limit' => $limit
    //                 ];
                
    //             error_log( 'ldnft_process_freemius_customers passing:'.$plugin_id.',  '.$start + $limit.', '.$limit.'  ' );
    //             wp_schedule_single_event( time() , 'ldnft_process_freemius_customers_data', $data );
    //         }
    //     }

    //     error_log( print_r(['inserted' => $inserted, 'updated'=>$updatednum, 'message' => sprintf( __('Inserted: %d, Updated: %d', 'MWC'), $inserted, $updatednum)], true) );
    //     //return ['inserted' => $inserted, 'updated'=>$updatednum, 'message' => sprintf( __('Inserted: %d, Updated: %d', 'MWC'), $inserted, $updatednum)];
	// }

    /**
	 * Cron test schedules of 5mins.
	 */
	// public function ldnft_cron_schedules($schedules){
		
	// 	if(!isset($schedules["1min"])) {
	// 		$schedules["1min"] = array(
	// 			'interval' => 1*60,
	// 			'display' => __('Once every 5 minutes'));
	// 	}
		
	// 	return $schedules;
	// }

    /**
     * Process mailpoet
     */
    public function mailpoet_submit_action() {
       
        global $wpdb;

        $ldnft_mailpeot_plugin  = sanitize_text_field($_POST['ldnft_mailpeot_plugin']);
        if( ! isset( $_POST['ldnft_mailpeot_plugin'] ) || empty($ldnft_mailpeot_plugin) ) {
            $errormsg = __('Freemius product is required for import.', LDNFT_TEXT_DOMAIN);
            $response = ['added'=>0, 'exists'=>0, 'message'=>'', 'errors'=> [$errormsg], 'errormsg'=> $errormsg ];
            echo json_encode($response);exit;
        }

        $ldnft_mailpeot_list    = sanitize_text_field($_POST['ldnft_mailpeot_list']);
        if( ! isset( $_POST['ldnft_mailpeot_list'] ) || empty($ldnft_mailpeot_list) ) {
            $errormsg = __('Mailpoet list is required for import.', LDNFT_TEXT_DOMAIN);
            $response = ['added'=>0, 'exists'=>0, 'message'=>'', 'errors'=> [$errormsg], 'errormsg'=> $errormsg ];
            echo json_encode($response);exit;
        }
        
        if (!is_plugin_active('mailpoet/mailpoet.php')) {
            $errormsg = __('This section requires MailPoet to be installed and configured.', LDNFT_TEXT_DOMAIN);
            $response = ['added'=>0, 'exists'=>0, 'message'=>'', 'errors'=> [$errormsg], 'errormsg'=> $errormsg ];
            echo json_encode($response);exit;
        }
        
        $tem_per_page = 25;
        $tem_offset = 0;
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result =  $api->Api('plugins/'.$ldnft_mailpeot_plugin.'/users.json?count='.$tem_per_page.'&offset='.$tem_offset.'&filter=paid', 'GET', []);
        $response = [];
        $count = 0;
        $exists = 0;
        $total = 0;
        $errors = [];
        
        if( isset($result) && isset($result->users) ) {
            $has_more_records = true;
            while($has_more_records) {
                foreach( $result->users as $user ) {
                    $total++;
                    $subscriber_data = [
                        'email' => $user->email,
                        'first_name' => $user->first,
                        'last_name' => $user->last,
                    ];
                      
                    $options = [
                        'send_confirmation_email' => false // default: true
                        //'schedule_welcome_email' => false
                    ];

                    $subscriber_id = 0;
                    try {
                        $subscriber = \MailPoet\API\API::MP('v1')->getSubscriber($subscriber_data['email']);
                        if( !empty( $subscriber['id'] ) ) {
                            $list_ids = $wpdb->get_results( $wpdb->prepare("select id from `".$wpdb->prefix."mailpoet_subscriber_segment` where subscriber_id=%d and segment_id=%d", $subscriber['id'], $ldnft_mailpeot_list) );
                            if( count($list_ids) == 0 ) {
                                $sql = "insert into `".$wpdb->prefix."mailpoet_subscriber_segment`(subscriber_id, segment_id, status, created_at, updated_at) values('".$subscriber['id']."', '".$ldnft_mailpeot_list."', 'subscribed', now(), now() )";
                                $wpdb->query( $sql );
                            }
                        }

                        $exists++;
                    } 
                    catch(\MailPoet\API\MP\v1\APIException $exception) {
                        if($exception->getCode() == 4 || $exception->getCode() == '4' ) {
                            try {
                                $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, [], $options); // Add to default mailing list
                                
                                if( !empty( $subscriber['id'] ) ) {
                                    $sql = "update `".$wpdb->prefix."mailpoet_subscribers` set status='subscribed' WHERE id='".$subscriber['id']."'";
                                    $wpdb->query( $sql );
        
                                    $sql = "insert into `".$wpdb->prefix."mailpoet_subscriber_segment`(subscriber_id, segment_id, status, created_at, updated_at) values('".$subscriber['id']."', '".$ldnft_mailpeot_list."', 'subscribed', now(), now() )";
                                    $wpdb->query( $sql );
                                    $count++;
                                }
                            } 
                            catch(\MailPoet\API\MP\v1\APIException $exception) {
                                if($exception->getCode() == 6 || $exception->getCode() == '6' ) {
                                    $exists++;
                                    
                                    $errors[$exception->getMessage()] = $exception->getMessage();
                                }
                            }
                            catch( Exception $exception ) {
                                $errors[$exception->getMessage()] = $exception->getMessage();
                            }
                        }
                    }
                    catch( Exception $exception ) {
                        $errors[$exception->getMessage()] = $exception->getMessage();
                    }
                } 

                $tem_offset += $tem_per_page;
                $result = $api->Api('plugins/'.$ldnft_mailpeot_plugin.'/users.json?count='.$tem_per_page.'&offset='.$tem_offset.'&filter=paid', 'GET', []);
                if( count( $result->users ) > 0 ) {
                    $has_more_records = true;
                } else {
                    $has_more_records = false;
                }
            }
        }
        
        
        $message = '';
        $errormsg = '';
        if( $count == $total ) {
            $message .= __('All subscribers are updated.', LDNFT_TEXT_DOMAIN);
        } else if( $count > 0 ) {
            $message .= sprintf( __('%d subscriber(s) updated.', LDNFT_TEXT_DOMAIN),$count );
        } else if( $count == 0 && $exists > 0 ) {
            $message .= __('All subscribers are updated.', LDNFT_TEXT_DOMAIN);
        } else{
            $errormsg .= __('Errors:', LDNFT_TEXT_DOMAIN).'<br>'.implode('<br>', $errors );
        }
        
        if( empty( $message ) && empty( $errormsg ) ) {
            $message = __('No available subscriber(s) to import.', LDNFT_TEXT_DOMAIN);
        }

        $response = [ 'added' => $count, 'exists' => $exists, 'message' => $message, 'errors' => $errors, 'errormsg' => $errormsg ];
        echo json_encode( $response );
        die();
    }
    
    /**
     * display admin notice
     */
    public function ldnft_admin_notice() {

        if( isset( $_GET['message'] ) ) {

            $class = 'notice notice-success is-dismissible';
            if( $_GET['message'] == 'ldnft_updated' )
                $message = __( 'Settings Updated', LDNFT_TEXT_DOMAIN );
            else {
                $message = sanitize_text_field( $_GET['message'] );
                
            }
            printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
        }
    }

    /**
     * Save settings data using ( Admin Post )
     */
    public function save_settings() {
        
        if( isset( $_POST['ldnft_settings'] ) 
            && check_admin_referer( 'ldnft_nounce', 'ldnft_nounce_field' ) 
            && current_user_can( 'manage_options' ) ) {
            
            $ldnft_settings_options = [];
            $ldnft_settings = $_POST['ldnft_settings'];
            if( isset( $ldnft_settings['api_scope'] ) && !empty( $ldnft_settings['api_scope'] ) ) {
                $ldnft_settings_options['api_scope'] = sanitize_text_field( $ldnft_settings['api_scope'] );
            }

            if( isset( $ldnft_settings['dev_id'] ) && !empty( $ldnft_settings['dev_id'] ) ) {
                $ldnft_settings_options['dev_id'] = sanitize_text_field( $ldnft_settings['dev_id'] );
            }

            if( isset( $ldnft_settings['public_key'] ) && !empty( $ldnft_settings['public_key'] ) ) {
                $ldnft_settings_options['public_key'] = sanitize_text_field( $ldnft_settings['public_key'] );
            }

            if( isset( $ldnft_settings['secret_key'] ) && !empty( $ldnft_settings['secret_key'] ) ) {
                $ldnft_settings_options['secret_key'] = sanitize_text_field( $ldnft_settings['secret_key'] );
            }

            update_option( 'ldnft_settings', $ldnft_settings_options );
            
        }

        wp_safe_redirect( esc_url_raw( add_query_arg( 'message', 'ldnft_updated', $_POST['_wp_http_referer'] ) ) );
        exit();
    }

    /**
     * Add new setting menu under WooCommerce menu
     */
    public function setting_menu() {
        
        /**
         * Add Setting Page
         */
        add_submenu_page(
            'ldnft-freemius',
            __( 'Settings', LDNFT_TEXT_DOMAIN ),
            __( 'Settings', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'freemius-settings',
            [ $this, 'load_setting_menu' ]
        );

        remove_submenu_page( 'ldnft-freemius','ldnft-freemius' );
    }
	
    /**
     * Load settings page content
     */
    public function load_setting_menu() {
        
		$settings_sections = array (
            'freemius-api' => array (
                'title' => __( 'Freemius API', LDNFT_TEXT_DOMAIN ),
                'icon' => 'fa-cog',
            ),
        );
        if( FS__API_CONNECTION ) {
            $settings_sections['import'] =  array (
                'title' => __( 'Import', LDNFT_TEXT_DOMAIN ),
                'icon' => 'fa-info',
            );

            $settings_sections['shortcodes'] =  array(
                'title' => __( 'Shortcodes', LDNFT_TEXT_DOMAIN ),
                'icon' => 'fa-info',
            );

            $settings_sections = apply_filters( 'ldnft_settings_sections', $settings_sections );
        }
        
        ?>
		<div class="wrap">
			<div id="icon-options-freemius-api" class="icon32"></div>
			<h2><?php _e( 'Freemius Settings', LDNFT_TEXT_DOMAIN ); ?></h2>
		
			<div class="nav-tab-wrapper">
				<?php foreach( $settings_sections as $key => $section ) { ?>
						<a href="?page=freemius-settings&tab=<?php echo $key; ?>"
							class="nav-tab <?php echo $this->page_tab == $key ? 'nav-tab-active' : ''; ?>">
							<i class="fa <?php echo $section['icon']; ?>" aria-hidden="true"></i>
							<?php _e( $section['title'], LDNFT_TEXT_DOMAIN ); ?>
						</a>
				<?php } ?>
			</div>
		
			<?php
                foreach( $settings_sections as $key => $section ) {
                    if( $this->page_tab == $key ) {
                        $key = str_replace( '_', '-', $key );
                        include( 'settings/' . $key . '.php' );
                    }
                }
			?>
		</div>
        <?php
    }
}

new LDNFT_Settings();