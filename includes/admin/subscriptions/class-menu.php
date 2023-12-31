<?php
/**
 * LDNFT_Subscriptions_Menu class manages the admin side subscription menu of freemius subscriptions.
 */

/**
 * LDNFT_Subscriptions Menu class
 */
class LDNFT_Subscriptions_Menu {

    /**
     * Default hidden columns
     */
    private $default_hidden_columns;

    /**
     * Constructor class
     */
	function __construct() {

        $this->default_hidden_columns = [ 
            'outstanding_balance', 
            'failed_payments', 
            'trial_ends', 
            'created', 
            'initial_amount', 
            'next_payment', 
            'canceled_at',
            'currency',
            'country_code', 
            'id', 
            'user_id',  
            'status'
        ];

        add_action( 'admin_menu', [ $this, 'admin_menu_page' ] );
        add_action( 'wp_ajax_ldnft_subscriptions_display', [ $this, 'ldnft_subscriptions_display' ], 100 );
        add_action( 'wp_ajax_ldnft_subscriptions_summary', [ $this, 'ldnft_subscriptions_summary_callback' ], 100 );
        add_action( 'wp_ajax_ldnft_subscribers_view_detail',    [ $this, 'subscribers_view_detail' ], 100 );
        add_action( 'wp_ajax_ldnft_subscription_plans_dropdown',    [ $this, 'subscription_plans_dropdown' ], 100 );
        add_action( 'admin_enqueue_scripts',                    [ $this, 'admin_enqueue_scripts_callback' ] );
	}
	
    /**
     * Action wp_ajax for fetching the first time table structure
     */
    public function admin_enqueue_scripts_callback() { 
        $screen = get_current_screen();
        if( $screen->id == 'freemius-toolkit_page_freemius-subscriptions' ) {

            wp_enqueue_style( 'ldnft-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], LDNFT_VERSION, null );

            /**
             * enqueue admin css
             */
            wp_enqueue_style( 'ldnft-backend-css', LDNFT_ASSETS_URL . 'css/backend/backend.css', [], LDNFT_VERSION, null );
            
            /**
             * enqueue admin js
             */
            wp_enqueue_script( 'ldnft-select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], LDNFT_VERSION, true ); 
            wp_enqueue_script( 'ldnft-backendcookie-js', LDNFT_ASSETS_URL . 'js/backend/jquery.cookie.js', [ 'jquery' ], LDNFT_VERSION, true ); 

            wp_enqueue_script( 'ldnft-subscriptions-backend-js', LDNFT_ASSETS_URL . 'js/backend/ldnft-subscriptions.js', [ 'jquery' ], LDNFT_VERSION, true ); 
            wp_localize_script( 'ldnft-subscriptions-backend-js', 'LDNFT', [  
                'ajaxURL'                       => admin_url( 'admin-ajax.php' ),
                'loader'                        => LDNFT_ASSETS_URL .'images/spinner-2x.gif',
                'preloader_gif_img'             => LDNFT_Admin::get_bar_preloader()
            ] );
        }
    }

    /**
     * Returns the subscription data.
     */
    public function subscription_plans_dropdown() {
        
        global $wpdb;

        $_plugin_id = ( isset( $_REQUEST['plugin_id'] ) && intval( $_REQUEST['plugin_id'] ) > 0 ) ? intval( $_REQUEST['plugin_id'] ) : 0 ;
        
        $where = '';
        if( $_plugin_id > 0 ) {
            $where = " where plugin_id = '$_plugin_id'";
        }

        $table_name = $wpdb->prefix.'ldnft_plans'; 
        $plans = $wpdb->get_results("SELECT id, title FROM $table_name".$where );
        $options = '<option value="">'.__( 'All Plans', 'ldninjas-freemius-toolkit' ).'</option>';
        if( is_array( $plans ) && count( $plans ) > 0 ) {
            foreach( $plans as $plan ) {
                $options .= '<option value="'.$plan->id.'">'.$plan->title.'</option>';
            }
        }

        wp_die( $options );
    }

    /**
     * Returns the subscription data.
     */
    public function subscribers_view_detail() {
        
        $user_id        = isset( $_REQUEST['user_id'] ) ?intval( $_REQUEST['user_id'] ):0;
        $plugin_id      = isset( $_REQUEST['plugin_id'] ) ?intval( $_REQUEST['plugin_id'] ):0;
        $id             = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ):0;
        if( $id == 0 || $plugin_id == 0 )  {
            echo '<div class="ldnft-error-message">';
            echo __('Transaction id and Product id are required fields.', 'ldninjas-freemius-toolkit');    
            echo '</div>';
            exit;    
        }
        
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$plugin_id.'/subscriptions/'.$id.'.json', 'GET', []);
        if($result) {

            $user = $api->Api('plugins/'.$plugin_id.'/users/'.$result->user_id.'.json', 'GET', []);
            $plan = $api->Api('plugins/'.$plugin_id.'/plans/'.$result->plan_id.'.json', 'GET', []);
            $coupon = $api->Api('plugins/'.$plugin_id.'/coupons/'.$result->coupon_id.'.json', 'GET', []);
            
            $discount  = '';
            if(!empty($result->renewals_discount) && floatval($result->renewals_discount) > 0 ) {
                if(strtolower($result->renewals_discount_type) == 'percentage')
                    $discount  = $result->renewals_discount.'% - (' .number_format(($result->renewals_discount*$result->total_gross)/100, 2).$result->currency.')';
                else {
                    $discount  = __( 'Fixed - ', 'ldninjas-freemius-toolkit' ).'('.$result->renewals_discount.$result->currency.')';
                }
            }

            ob_start();
                ?>

                    <table id="ldnft-subscriptions" width="100%" cellpadding="5" cellspacing="1">
                        <tbody>
                            <tr>
                                <th><?php _e('Transaction', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->id;?></td>
                                <th><?php _e('User ID', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->user_id;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Name', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $user->first.' '.$user->last;?></td>
                                <th><?php _e('Email', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $user->email;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Country', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo LDNFT_Freemius::get_country_name_by_code( strtoupper($result->country_code) );?></td>
                                <th><?php _e('Discount', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $discount;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Amount Per Cycle:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->amount_per_cycle;?></td>
                                <th><?php _e('First Payment:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->initial_amount;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Tax Rate:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->tax_rate;?></td>
                                <th><?php _e('Total Amount:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->total_gross;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Renewal Amount:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->renewal_amount;?></td>
                                <th><?php _e('Billing Cycle (months):', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->billing_cycle;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Outstanding Balance:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->outstanding_balance;?></td>
                                <th><?php _e('Failed Payments:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->failed_payments;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Trial Ends:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->trial_ends;?></td>
                                <th><?php _e('Next Payments:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->next_payment;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Cancelled At:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->canceled_at;?></td>
                                <th><?php _e('Install ID:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->install_id;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Plan ID:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->plan_id;?></td>
                                <th><?php _e('Plan:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $plan->title;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('License ID:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->license_id;?></td>
                                <th><?php _e('IP:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->ip;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Zip/Postal Code:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->zip_postal_code;?></td>
                                <th><?php _e('VAT ID:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->vat_id;?></td>
                            </tr>
                            <?php if($result->coupon_id) { ?>
                                <tr>
                                    <th><?php _e('Coupon ID:', 'ldninjas-freemius-toolkit')?></th>
                                    <td><?php echo $result->coupon_id;?></td>
                                    <th><?php _e('Code:', 'ldninjas-freemius-toolkit')?></th>
                                    <td><?php echo $coupon->code;?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Coupon Discount Type:', 'ldninjas-freemius-toolkit')?></th>
                                    <td><?php echo $coupon->discount_type;?></td>
                                    <th><?php _e('Coupon Discount:', 'ldninjas-freemius-toolkit')?></th>
                                    <td><?php echo $coupon->discount_type=='percentage'?$coupon->discount.'%':$coupon->discount.$result->currency;?></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <th><?php _e('External ID:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->external_id;?></td>
                                <th><?php _e('Gateway', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->gateway;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Payment Date:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->created;?></td>
                                <th><?php _e('Gateway:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->gateway;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Status:', 'ldninjas-freemius-toolkit')?></th>
                                <td><?php echo $result->status;?></td>
                                <th></th>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                <?php
            $content = ob_get_contents();
            ob_get_clean();
            
            echo $content;
        } else {   
            echo '<div class="ldnft-error-message">';
            echo __('No record(s) found.', 'ldninjas-freemius-toolkit') ;    
            echo '</div>';
        }
        exit;
    }

    /**
     * Action wp_ajax for fetching ajax_response
     */
     public function ldnft_subscriptions_summary_callback() {
        
        global $wpdb;
        
        $selected_plugin_id = isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ? intval( $_GET['ldfmt_plugins_filter'] ) : 0;
        $table_name         = $wpdb->prefix.'ldnft_subscription t inner join '.$wpdb->prefix.'ldnft_customers c on (t.user_id=c.id)';  
        $where = " where 1 = 1";
        $where .= ! empty( $selected_plugin_id ) ? " and t.plugin_id='".$selected_plugin_id."'" : "";

        if( !empty( $_GET['interval'] )) {
            $interval = sanitize_text_field( $_GET['interval'] );
            switch( $interval ) {
                case "current_week":
                    $where .= " and YEARWEEK(t.created) = YEARWEEK(NOW())";
                    break;
                case "last_week":
                    $where .= ' and Date(t.created) between date_sub(now(),INTERVAL 1 WEEK) and now()';
                    break;
                case "current_month":
                    $where .= ' and MONTH(t.created) = MONTH(now()) and YEAR(t.created) = YEAR(now())';
                    break;
                case "last_month":
                    $where .= ' and Date(t.created) between Date((now() - interval 1 month)) and Date(now())';
                    break;
                default:
                    $where .= " and Date(t.created) = '".date('Y-m-d')."'";
                    break;
            }
        }

        $where .=  isset( $_GET['country'] ) && !empty( $_GET['country'] ) ? " and t.country_code='".sanitize_text_field( $_GET['country'] )."'" : "";
        $where .= isset( $_GET['plan_id'] ) && intval( $_GET['plan_id'] ) > 0 ? ' and t.plan_id='.sanitize_text_field( $_GET['plan_id'] ) : "";
        $where .= $_GET['status'] != ''? " and t.status='".sanitize_text_field( $_GET['status'] )."' " : '';
        $where .= $_GET['gateway'] != ''? " and t.gateway='".sanitize_text_field( $_GET['gateway'] )."' " : '';
        
        $search = sanitize_text_field( $_GET['search'] );
        if( ! empty( $search )) {
            $where   .= " and ( t.id like '%".$search."%' or t.user_id like '%".$search."%' or lower(c.email) like '%".strtolower($search)."%' or lower(c.first) like '%".strtolower($search)."%' or lower(c.last) like '%".strtolower($search)."%' )";
        }

        $result = $wpdb->get_results( "SELECT t.* FROM $table_name $where");
        $gross_total = []; 
        $gross_total_count = 0;
        $tax_rate_total = [];
        $total_number_of_sales = 0;
        $failed_payments = 0;

        if( isset($result) && isset($result) ) {
            foreach( $result as $obj ) {
                $gross_total_count++;
                if( ! array_key_exists( $obj->currency, $gross_total ) ) {
                    $gross_total[ $obj->currency ] = 0;    
                }

                $gross_total[ $obj->currency ] = floatval( $gross_total[ $obj->currency ] ) + floatval($obj->gross);

                if( ! array_key_exists( $obj->currency, $tax_rate_total ) ) {
                    $tax_rate_total[ $obj->currency ] = 0;    
                }
                
                $tax_rate_total[ $obj->currency ] = floatval( $tax_rate_total[ $obj->currency ] ) + floatval($obj->tax_rate);

                $total_number_of_sales++;
                $failed_payments += $obj->failed_payments;
            }
        }
        
        $countries = [];
        $countries_msg = '';
        $currency_keys = array_keys($gross_total);
        if( is_array( $currency_keys ) && count( $currency_keys ) > 0 ) {
            $records = $wpdb->get_results( "select country_code, sum(gross) as gross, currency from $table_name $where group by country_code order by gross desc limit 3" );
            foreach( $records as $rec ) {

                $country_gross = [];
                foreach( $currency_keys as $key ) {
                    $currency_where = " and t.currency='".$key."' and t.country_code='".$rec->country_code."'";
                    $country_gross[$key] = $wpdb->get_var( "select sum(gross) as gross from $table_name $where $currency_where limit 1" );
                    $country_gross[$key] = number_format( $country_gross[ $key ], 2 );
                }

                $countries[] = [
                    'country_code' => $rec->country_code,
                    'gross' => $country_gross,
                    'country_name' => LDNFT_Freemius::get_country_name_by_code( strtoupper( $rec->country_code ) )
                ];
            }
            
            $countries_msg = __( 'Countries with most subscription are from.', 'ldninjas-freemius-toolkit' );
        }
        $gross_str = '';
        foreach( $gross_total as $key => $value ) {
            $gross_total[$key] = number_format( $gross_total[$key] , 2 );
            $gross_str .= !empty($gross_str)? ", ":"";
            $gross_str .= $gross_total[$key].$key;
        }

        $tax_str = '';
        foreach( $tax_rate_total as $key => $value ) {
            $tax_rate_total[$key] = number_format( $tax_rate_total[$key] , 2 );
            $tax_str .= !empty($tax_str)? ", ":"";
            $tax_str .= $tax_rate_total[$key].$key;
        }

        $data = [
            'gross_total_count' => $gross_total_count,
            'gross_total' => $gross_total,
            'gross_message' => sprintf(__( 'Gross sale amount from total %d subscriptions.', 'ldninjas-freemius-toolkit' ), $gross_total_count),
            'tax_rate_total' => $tax_rate_total,
            'total_number_of_sales' => $total_number_of_sales,
            'tax_message' => sprintf(__( 'Total tax amount from %d subscription.', 'ldninjas-freemius-toolkit' ), $gross_total_count),
            'new_subscriptions_message' => __( 'Total new subscriptions from the selected filter.', 'ldninjas-freemius-toolkit' ),
            'failed_payments'       => $failed_payments,
            'failed_payments_message' => __( 'Number of failed attempts in auto renewal of subscription.', 'ldninjas-freemius-toolkit' ),
            'countries' => $countries,
            'currency_keys' => $currency_keys,
            'countries_message' => $countries_msg
        ];
        
        die(
            json_encode($data)
        );
    }

    /**
     * Action wp_ajax for fetching the first time table structure
     */
    public function ldnft_subscriptions_display() {
        
        $wp_list_table = new LDNFT_Subscriptions();
        $wp_list_table->prepare_items();

        ob_start();
        $wp_list_table->display();
        $display = ob_get_clean();

        die(
            json_encode([
                "display" => $display
            ])
        );
    }
    
    /**
     * Add Reset Course Progress submenu page under learndash menus
     */
    public function admin_menu_page() { 
        
        $user_id = get_current_user_id();
        $hook = add_submenu_page( 
            'ldnft-freemius',
            __( 'Subscriptions', 'ldninjas-freemius-toolkit' ),
            __( 'Subscriptions', 'ldninjas-freemius-toolkit' ),
            'manage_options',
            'freemius-subscriptions',
            [ $this, 'subscribers_page' ],
            0
        );
        
        if( get_user_option( 'subscription_hidden_columns_set', $user_id ) != 'Yes' ) {
            update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-subscriptionscolumnshidden', $this->default_hidden_columns );
            update_user_option( $user_id, 'subscription_hidden_columns_set', 'Yes' );
        }

        add_action( "load-$hook", function () {
            
            global $ldnftSubscriptionsListTable;
            
            $option = 'per_page';
            $args = [
                    'label' => 'Subsriptions Per Page',
                    'default' => 10,
                    'option' => 'subscriptions_per_page'
                ];
            add_screen_option( $option, $args );
            $ldnftSubscriptionsListTable = new LDNFT_Subscriptions();
        } );
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public function subscribers_page() {
        
        global $wpdb;

        $table_name = $wpdb->prefix.'ldnft_subscription';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            ?> 
                <div class="wrap">
                    <h2><?php _e( 'Subscriptions', 'ldninjas-freemius-toolkit' ); ?></h2>
                    <p id="ldnft-dat-not-imported-message"><?php _e( 'Subscriptions are not imported yet. Please, click <a href="admin.php?page=freemius-settings&tab=freemius-api">here</a> to open the setting page and start the import process automatically.', 'ldninjas-freemius-toolkit' ); ?></p>
                </div>
            <?php

            return;
        }

        if( !FS__HAS_PLUGINS ) {
            ?>
                <div class="wrap">
                    <h2><?php _e( 'Subscriptions', 'ldninjas-freemius-toolkit' ); ?></h2>
                    <p id="ldnft-dat-not-imported-message"><?php _e( 'No product(s) exists in your freemius account. Please, add a product on freemius and reload the page.', 'ldninjas-freemius-toolkit' ); ?></p>
                </div>
            <?php

            return;
        }

        /**
         * Create an instance of our package class... 
         */
        $ListTable = new LDNFT_Subscriptions(); 
        
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $products = LDNFT_Freemius::$products;
        
        $selected_plugin_id = isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ? intval( $_GET['ldfmt_plugins_filter'] ) : ""; 
        $selected_interval = isset($_GET['interval']) ? sanitize_text_field( $_GET['interval'] ) : 'current_month';
        $selected_country = isset( $_GET['country'] ) ? sanitize_text_field( $_GET['country'] ) : ""; 
        $selected_plan_id = isset( $_GET['plan_id'] ) ? sanitize_text_field( $_GET['plan_id'] ) : ''; 
        $selected_gateway = ( isset( $_GET['gateway'] )  ) ? sanitize_text_field( $_GET['gateway'] ) : ''; 
        $search = ( isset( $_GET['search'] )  ) ? sanitize_text_field( $_GET['search'] ) : ''; 

        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $ListTable->prepare_items();
        ?>
        <div class="wrap">
            
            <h2><?php _e( 'Subscriptions', 'ldninjas-freemius-toolkit' ); ?></h2>
            <div class="ldnft_filters_top">
                <form id="ldnft-subscription-filter-form" method="get">
                    <div class="alignleft actions bulkactions">
                        <span class="ldnft_filter_labels"><?php _e( 'Filters:', 'ldninjas-freemius-toolkit' ); ?></span>
                        <select name="ldfmt-plugins-filter" class="ldfmt-plugins-filter ldfmt-plugins-subscription-filter">
                            <option value=""><?php echo __( 'All Plugins/Products', 'ldninjas-freemius-toolkit' );?></option>
                            <?php
                                foreach( $products as $plugin ) {
                                    if( $selected_plugin_id == 0 ) {
                                        $selected_plugin_id = $plugin->id;
                                    }

                                    $selected = '';
                                    if( $selected_plugin_id == $plugin->id ) {
                                        $selected = ' selected = "selected"';   
                                    }
                                    ?>
                                        <option value="<?php echo $plugin->id; ?>" <?php echo $selected; ?>><?php echo $plugin->title; ?></option>
                                    <?php   
                                }
                            ?>
                        </select>
                        <?php 
                            $table_name = $wpdb->prefix.'ldnft_plans'; 
                            $plans = $wpdb->get_results($wpdb->prepare("SELECT id, title FROM $table_name where plugin_id = %d", $selected_plugin_id ) );
                        ?>
                        <select name="ldfmt-sales-plan_id-filter" class="ldfmt-subscription-plan_id-filter">
                            <option value=""><?php _e( 'All Plans', 'ldninjas-freemius-toolkit' ); ?></option>
                            <?php
                            if( isset( $plans ) && is_array( $plans ) ) {
                                foreach( $plans as $plan ) {
                                    
                                    $selected = '';
                                    if( $selected_plan_id == $plan->id ) {
                                        $selected = ' selected = "selected"';   
                                    }
                                    ?>
                                    <option value="<?php echo $plan->id; ?>" <?php echo $selected; ?>><?php echo $plan->title; ?></option>
                                    <?php   
                                }
                            }
                            ?>
                        </select>
                        
                        <select name="ldfmt-sales-interval-filter" class="ldfmt-subscription-interval-filter">
                            <option value=""><?php echo __( 'All Time', 'ldninjas-freemius-toolkit' );?></option>
                            <option value="today" <?php echo $selected_interval=='today'?'selected':'';?>><?php echo __( 'Today', 'ldninjas-freemius-toolkit' );?></option>
                            <option value="current_week" <?php echo $selected_interval=='current_week'?'selected':'';?>><?php echo __( 'Current Week', 'ldninjas-freemius-toolkit' );?></option>
                            <option value="last_week" <?php echo $selected_interval=='last_week'?'selected':'';?>><?php echo __( 'Last Week', 'ldninjas-freemius-toolkit' );?></option>
                            <option value="current_month" <?php echo $selected_interval=='current_month'?'selected':'';?>><?php echo __( 'Current Month', 'ldninjas-freemius-toolkit' );?></option>
                            <option value="last_month" <?php echo $selected_interval=='last_month'?'selected':'';?>><?php echo __( 'Last Month', 'ldninjas-freemius-toolkit' );?></option>
                        </select>
                        <select name="ldfmt-subscription-status-filter" class="ldfmt-subscription-status-filter">
                            <option value=""><?php echo __( 'All Statuses', 'ldninjas-freemius-toolkit' );?></option>
                            <option value="active" <?php echo $selected_interval=='active'?'selected':'';?>><?php echo __( 'Active', 'ldninjas-freemius-toolkit' );?></option>
                            <option value="expired" <?php echo $selected_interval=='expired'?'selected':'';?>><?php echo __( 'Expired', 'ldninjas-freemius-toolkit' );?></option>
                            <option value="cancelled" <?php echo $selected_interval=='cancelled'?'selected':'';?>><?php echo __( 'Cancelled', 'ldninjas-freemius-toolkit' );?></option>
                        </select>
                        <select name="ldfmt-subscription-country-filter" class="ldfmt-subscription-country-filter">
                            <option value=""><?php echo __( 'All Countries', 'ldninjas-freemius-toolkit' );?></option>
                            <?php $countries = LDNFT_Freemius::get_country_name_by_code( 'list' ); 
                                foreach( $countries as $key=>$value ) {
                            ?>
                                <option value="<?php echo $key;?>" <?php echo $selected_country==$key?'selected':'';?>><?php echo $value;?></option>
                            <?php } ?>
                        </select>
                        <?php 
                            $table_name = $wpdb->prefix.'ldnft_subscription'; 
                            $gateways      = $wpdb->get_results( "SELECT distinct( gateway ) as gateway FROM $table_name" );
                        ?>
                        <select name="ldfmt-subscription-gateway-filter" class="ldfmt-subscription-gateway-filter">
                            <option value=""><?php _e( 'All Gateways', 'ldninjas-freemius-toolkit' ); ?></option>
                            <?php
                            if( isset( $gateways ) && is_array( $gateways ) ) {
                                foreach( $gateways as $gateway ) {
                                    
                                    $selected = '';
                                    if( $selected_gateway == $gateway->gateway ) {
                                        $selected = ' selected = "selected"';   
                                    }
                                    ?>
                                        <option value="<?php echo $gateway->gateway; ?>" <?php echo $selected; ?>><?php echo $gateway->gateway; ?></option>
                                    <?php   
                                }
                            }
                            ?>
                        </select>
                        <!-- <input type="text" value="<?php echo $search;?>" name="ldnft-subscription-general-search" class="form-control ldnft-subscription-general-search" placeholder="<?php _e('Search', 'ldninjas-freemius-toolkit');?>"> -->
                        <input type="button" name="ldnft-subscription-search-button" value="<?php _e('Filter', 'ldninjas-freemius-toolkit');?>" class="btn button ldnft-subscription-search-button" />
                    </div>
                </form>
                    
                <div style="clear:both">&nbsp;</div> 
                <div class="ldfmt-sales-upper-info">
                    <div class="ldfmt-gross-sales-box ldfmt-sales-box ldnft-tooltip-container">
                        <label><?php echo __( 'Gross Sales', 'ldninjas-freemius-toolkit' );?>
                            <span class="lndft-tooltip ldnft_subscription_gross_message"><?php echo __( 'Loading details...', 'ldninjas-freemius-toolkit' ); ?></span>
                        </label>
                        <div class="ldnft_points">
                            <span class="ldnft_subscription_points"></span>
                            <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                        </div>
                    </div>
                    <div class="ldfmt-new-sales-box ldfmt-sales-box ldnft-tooltip-container">
                        <label><?php echo __('Total Subscriptions', 'ldninjas-freemius-toolkit');?>
                            <span class="lndft-tooltip ldnft_subscription_new_subscriptions_message"><?php echo __( 'Loading details...', 'ldninjas-freemius-toolkit' ); ?></span>
                        </label>
                        <div class="ldnft_new_sales_count">
                            <span class="ldnft_subscription_new_sales_count"></span>
                            <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                        </div>
                    </div>
                    <div class="ldfmt-new-subscriptions-box ldfmt-sales-box ldnft-tooltip-container">
                        <label><?php echo __('Total Failed Attempts', 'ldninjas-freemius-toolkit');?>
                            <span class="lndft-tooltip ldnft_subscription_failed_payments_message"><?php echo __( 'Loading details...', 'ldninjas-freemius-toolkit' ); ?></span>
                        </label>
                        <div class="ldnft_new_attempts_count">
                            <span class="ldnft_subscription_new_attempts_count"></span>
                            <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                        </div>
                    </div>
                    <div class="ldfmt-top3-countries-count-box ldfmt-sales-box ldnft-countries-tooltip-container">
                        <label><?php echo __('Top 3 Countries', 'ldninjas-freemius-toolkit');?>
                            <span class="lndft-countries-tooltip ldnft_subscription_countries_message"><?php echo __( 'Loading details...', 'ldninjas-freemius-toolkit' ); ?></span>
                        </label>
                        <div class="ldnft_subscription_top3_countries_main">
                            <div class="ldnft_subscription_top3_countries"></div>
                            <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                        </div>
                    </div>
                </div> 
                <div id="ldnft-admin-modal" class="ldnft-admin-modal">
                    <!-- Modal content -->
                    <div class="ldnft-admin-modal-content">
                        <div class="ldnft-admin-modal-header">
                        <span class="ldnft-admin-modal-close">&times;</span>
                            <h2><?php echo __( 'Subscription Detail', 'ldninjas-freemius-toolkit' );?></h2>
                        </div>
                        <div class="ldnft-admin-modal-body">
                            <table id="ldnft-subscriptions" width="100%" cellpadding="5" cellspacing="1">
                                <tbody>
                                    <tr>
                                        <th><?php _e('Transaction', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-transaction-id"></td>
                                        <th><?php _e('User ID', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-user_id"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Name', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-username"></td>
                                        <th><?php _e('Email', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-useremail"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Country', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-country_code"></td>
                                        <th><?php _e('Discount', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-discount"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Amount Per Cycle:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-amount_per_cycle"></td>
                                        <th><?php _e('First Payment:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-initial_amount"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Tax Rate:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-tax_rate"></td>
                                        <th><?php _e('Total Amount:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-gross"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Renewal Amount:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-renewal_amount"></td>
                                        <th><?php _e('Billing Cycle (months):', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-billing_cycle"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Outstanding Balance:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-outstanding_balance"></td>
                                        <th><?php _e('Failed Payments:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-failed_payments"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Trial Ends:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-trial_ends"></td>
                                        <th><?php _e('Next Payments:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-next_payment"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Cancelled At:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-canceled_at"></td>
                                        <th><?php _e('Install ID:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-install_id"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Plan ID:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-plan_id"></td>
                                        <th><?php _e('Plan:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-title"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('License ID:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-license_id"></td>
                                        <th><?php _e('Plugin ID:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-plugin_id"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Zip/Postal Code:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-zip_postal_code"></td>
                                        <th><?php _e('VAT ID:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-vat_id"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Coupon ID:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-coupon_id"></td>
                                        
                                        <th><?php _e('Currency:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-currency"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('External ID:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-external_id"></td>
                                        <th><?php _e('Gateway', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-gateway"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Pricing ID', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-pricing_id"></td>
                                        <th><?php _e('Renewal Discount:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-renewals_discount"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Payment Date:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-created"></td>
                                        <th><?php _e('Updated At:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-updated_at"></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e('Status:', 'ldninjas-freemius-toolkit')?></th>
                                        <td id = "ldnft-review-coloumn-status"></td>
                                        <th></th>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="ldnft-popup-loader"><img class="" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" /></div>
                    </div>
                </div>
                <form id="ldnft-subscription-filter-form-text" method="post">
                    <input type="text" value="<?php echo $search;?>" name="ldnft-subscription-general-search" class="form-control ldnft-subscription-general-search" placeholder="<?php _e('Search', 'ldninjas-freemius-toolkit');?>">
                    <input type="submit" name="ldnft-subscription-search" value="<?php _e('Search', 'ldninjas-freemius-toolkit');?>" class="btn button ldnft-subscription-search" />
                </form>
            </div>
            <div id="ldnft_subscriptions_data">
                <?php $ListTable->display(); ?>
            </div>
            <input type="hidden" class="ldnft-freemius-order" name="order" value="id" />
            <input type="hidden" class="ldnft-freemius-orderby" name="orderby" value="asc" />
            <input type="hidden" class="ldnft-freemius-page" name="page" value="1" />
            <input type="hidden" class="ldnft-script-freemius-type" name="ldnft-script-freemius-type" value="subscribers" />
            <input type="hidden" class="ldnft-display-subscribers-type" name="subscribers-type" value="filter" />
        </div>
        <?php
    }
}

new LDNFT_Subscriptions_Menu();