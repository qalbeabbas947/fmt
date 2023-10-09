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
            'currency',
            'country_code', 
            'id', 
            'user_id',  
        ];

        add_action( 'admin_menu', [ $this, 'admin_menu_page' ] );
        add_action('wp_ajax_ldnft_subscriptions_display', [ $this, 'ldnft_subscriptions_display' ], 100 );
        add_action( 'wp_ajax_ldnft_subscriptions_summary', [ $this, 'ldnft_subscriptions_summary_callback' ], 100 );
        add_action( 'wp_ajax_ldnft_subscriber_check_next',      [ $this, 'subscriber_check_next' ], 100 );
        add_action( 'wp_ajax_ldnft_subscribers_view_detail',    [ $this, 'subscribers_view_detail' ], 100 );
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
            echo __('Transaction id and Product id are required fields.', LDNFT_TEXT_DOMAIN);    
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
                    $discount  = __( 'Fixed - ', LDNFT_TEXT_DOMAIN ).'('.$result->renewals_discount.$result->currency.')';
                }
            }

            ob_start();
                ?>

                    <table id="ldnft-subscriptions" width="100%" cellpadding="5" cellspacing="1">
                        <tbody>
                            <tr>
                                <th><?php _e('Transaction', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->id;?></td>
                                <th><?php _e('User ID', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->user_id;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Name', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $user->first.' '.$user->last;?></td>
                                <th><?php _e('Email', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $user->email;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Country', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo LDNFT_Freemius::get_country_name_by_code( strtoupper($result->country_code) );?></td>
                                <th><?php _e('Discount', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $discount;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Amount Per Cycle:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->amount_per_cycle;?></td>
                                <th><?php _e('First Payment:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->initial_amount;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Tax Rate:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->tax_rate;?></td>
                                <th><?php _e('Total Amount:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->total_gross;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Renewal Amount:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->renewal_amount;?></td>
                                <th><?php _e('Billing Cycle:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->billing_cycle;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Outstanding Balance:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->outstanding_balance;?></td>
                                <th><?php _e('Failed Payments:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->failed_payments;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Trial Ends:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->trial_ends;?></td>
                                <th><?php _e('Next Payments:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->next_payment;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Cancelled At:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->canceled_at;?></td>
                                <th><?php _e('Install ID:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->install_id;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Plan ID:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->plan_id;?></td>
                                <th><?php _e('Plan:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $plan->title;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('License ID:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->license_id;?></td>
                                <th><?php _e('IP:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->ip;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Zip/Postal Code:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->zip_postal_code;?></td>
                                <th><?php _e('VAT ID:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->vat_id;?></td>
                            </tr>
                            <?php if($result->coupon_id) { ?>
                                <tr>
                                    <th><?php _e('Coupon ID:', LDNFT_TEXT_DOMAIN)?></th>
                                    <td><?php echo $result->coupon_id;?></td>
                                    <th><?php _e('Code:', LDNFT_TEXT_DOMAIN)?></th>
                                    <td><?php echo $coupon->code;?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Coupon Discount Type:', LDNFT_TEXT_DOMAIN)?></th>
                                    <td><?php echo $coupon->discount_type;?></td>
                                    <th><?php _e('Coupon Discount:', LDNFT_TEXT_DOMAIN)?></th>
                                    <td><?php echo $coupon->discount_type=='percentage'?$coupon->discount.'%':$coupon->discount.$result->currency;?></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <th><?php _e('External ID:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->external_id;?></td>
                                <th><?php _e('Gateway', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->gateway;?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Payment Date:', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->created;?></td>
                                <th><?php _e('Gateway', LDNFT_TEXT_DOMAIN)?></th>
                                <td><?php echo $result->gateway;?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php
            $content = ob_get_contents();
            ob_get_clean();
            
            echo $content;
        } else {   
            echo '<div class="ldnft-error-message">';
            echo __('No record(s) found.', LDNFT_TEXT_DOMAIN) ;    
            echo '</div>';
        }
        exit;
    }

    /**
     * checks if there are subscribers records
     */
    public function subscriber_check_next() {
        
        $per_page       = isset($_REQUEST['per_page']) && intval($_REQUEST['per_page'])>0?intval($_REQUEST['per_page']):10;
        $offset         = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
        $current_recs   = isset($_REQUEST['current_recs']) && intval($_REQUEST['current_recs'])>0?intval($_REQUEST['current_recs']):0;

        $plugin_id      = isset($_REQUEST['plugin_id']) && intval($_REQUEST['plugin_id'])>0?intval($_REQUEST['plugin_id']):0;
        $interval       = isset($_REQUEST['interval']) && intval($_REQUEST['interval'])>0?intval($_REQUEST['interval']):'';
        $offset_rec     = ($offset-1) * $per_page;

        $interval_str = '';
        if( !empty($interval) ) {
           $interval_str = '&billing_cycle='.$interval;
        }

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$per_page.'&offset='.$offset_rec.$interval_str, 'GET', []);
        if( ! is_array( $result->subscriptions ) || count( $result->subscriptions ) == 0) {
            echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
        }
        exit;
    }

    /**
     * Action wp_ajax for fetching ajax_response
     */
     public function ldnft_subscriptions_summary_callback() {
        
        $selected_plugin_id = 0;
        if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
            $selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        }
    

        $interval_str = '';
        if( isset($_GET['interval']) && !empty( $_GET['interval'] ) ) {
            $interval_str = '&billing_cycle='.$_GET['interval'];
        }

        $status_str = '';
        if( isset( $_GET['status'] )  ) {
            $status_str = '&filter='.$_GET['status'];
        }
        
        $plan_str = '';
        if( isset( $_GET['plan_id'] ) && intval($_GET['plan_id']) > 0 ) {
            $plan_str = '&plan_id='.$_GET['plan_id']; 
        }

        $tem_per_page = 50;
        $tem_offset = 0;
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$selected_plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str.$status_str.$plan_str, 'GET', []);
        $gross_total = 0;
        $tax_rate_total = 0;
        $total_number_of_sales = 0;
        $total_new_subscriptions = 0;
        $total_new_renewals = 0;
        
        if( isset($result) && isset($result->subscriptions) ) {
            $has_more_records = true;
            while($has_more_records) {
                foreach( $result->subscriptions as $payment ) {
                    
                    $pmts = $api->Api('plugins/'.$selected_plugin_id.'/subscriptions/'.$payment->id.'/payments.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str, 'GET', []);
                    foreach($pmts->payments as $pmt) {
                        $gross_total += $pmt->gross;
                        $tax_rate_total += $pmt->vat;
                        $total_number_of_sales++;
                        if( $pmt->is_renewal == '1' || $pmt->is_renewal == 1 ) {
                            $total_new_renewals++;
                        } else {
                            $total_new_subscriptions++;
                        }
                    }
                } 

                $tem_offset += $tem_per_page;
                $result = $api->Api('plugins/'.$selected_plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str, 'GET', []);
                if( count( $result->subscriptions ) > 0 ) {
                    $has_more_records = true;
                } else {
                    $has_more_records = false;
                }
            }
        }

        $data = [
            'gross_total' => number_format($gross_total, 2),
            'tax_rate_total' => number_format($tax_rate_total, 2),
            'total_number_of_sales' => $total_number_of_sales,
            'total_new_subscriptions' => $total_new_subscriptions,
            'total_new_renewals' => $total_new_renewals
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
        if( FS__API_CONNECTION  ) {
                
            $hook = add_submenu_page( 
                'ldnft-freemius',
                __( 'Subscriptions', LDNFT_TEXT_DOMAIN ),
                __( 'Subscriptions', LDNFT_TEXT_DOMAIN ),
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
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public function subscribers_page() {
        
        if( !FS__HAS_PLUGINS ) {
            ?>
                <div class="wrap">
                    <h2><?php _e( 'Subscriptions', LDNFT_TEXT_DOMAIN ); ?></h2>
                    <p><?php _e( 'No product(s) exists in your freemius account. Please, add a product on freemius and reload the page.', LDNFT_TEXT_DOMAIN ); ?></p>
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
        
        $selected_plugin_id = 0;
        if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
            $selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        }

        $selected_interval = '12';
        if( isset($_GET['interval'])  ) {
            $selected_interval = $_GET['interval']; 
        }
        
        $selected_status = 'active';
        if( isset( $_GET['status'] )  ) {
            $selected_status = $_GET['status']; 
        }
        
        $selected_plan_id = '';
        if( isset( $_GET['plan_id'] ) ) {
            $selected_plan_id = $_GET['plan_id']; 
        }

        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $ListTable->prepare_items();
        ?>
        <div class="wrap">
            
            <h2><?php _e( 'Subscriptions', LDNFT_TEXT_DOMAIN ); ?></h2>

            <form id="ldnft-subscription-filter" method="get">
                
                <div class="ldnft_filters_top">
                    <div class="alignleft actions bulkactions">
                        <span class="ldnft_filter_labels"><?php _e( 'Filters:', LDNFT_TEXT_DOMAIN ); ?></span>
                        <select name="ldfmt-plugins-filter" class="ldfmt-plugins-filter ldfmt-plugins-subscription-filter">
                            <?php
                                foreach( $products->plugins as $plugin ) {
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
                        <?php $plans = $api->Api('plugins/'.$selected_plugin_id.'/plans.json?count=50', 'GET', []); ?>
                        <select name="ldfmt-sales-plan_id-filter" class="ldfmt-subscription-plan_id-filter">
                            <option value=""><?php _e( 'Filter by Plan', LDNFT_TEXT_DOMAIN ); ?></option>
                            <?php
                            if( isset( $plans->plans ) && is_array( $plans->plans ) ) {
                                foreach( $plans->plans as $plan ) {
                                    
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
                            <option value=""><?php echo __( 'All Time', LDNFT_TEXT_DOMAIN );?></option>
                            <option value="1" <?php echo $selected_interval=='1'?'selected':'';?>><?php echo __( 'Monthly', LDNFT_TEXT_DOMAIN );?></option>
                            <option value="12" <?php echo $selected_interval=='12'?'selected':'';?>><?php echo __( 'Annual', LDNFT_TEXT_DOMAIN );?></option>
                        </select>
                        <select name="ldfmt-sales-interval-filter" class="ldfmt-subscription-status-filter">
                            <option value="all"><?php echo __( 'All Status', LDNFT_TEXT_DOMAIN );?></option>
                            <option value="active" <?php echo $selected_status=='active'?'selected':'';?>><?php echo __( 'Active', LDNFT_TEXT_DOMAIN );?></option>
                            <option value="cancelled" <?php echo $selected_status=='cancelled'?'selected':'';?>><?php echo __( 'Cancelled', LDNFT_TEXT_DOMAIN );?></option>
                        </select>
                    </div>
                    <div style="clear:both">&nbsp;</div> 
                    <div class="ldfmt-sales-upper-info">
                        <div class="ldfmt-gross-sales-box ldfmt-sales-box">
                            <label><?php echo __( 'Gross Sales', LDNFT_TEXT_DOMAIN );?></label>
                            <div class="ldnft_points">
                                <span class="ldnft_subscription_points"></span>
                                <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                            </div>
                        </div>
                        <div class="ldfmt-gross-gateway-box ldfmt-sales-box">
                            <label><?php echo __('Total Tax Rate', LDNFT_TEXT_DOMAIN);?></label>
                            <div class="ldnft_tax_fee">
                                <span class="ldnft_subscription_tax_fee"></span>
                                <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                            </div>
                            
                        </div>
                        <div class="ldfmt-new-sales-box ldfmt-sales-box">
                            <label><?php echo __('Total Sales Count', LDNFT_TEXT_DOMAIN);?></label>
                            <div class="ldnft_new_sales_count">
                                <span class="ldnft_subscription_new_sales_count"></span>
                                <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                            </div>
                        </div>
                        <div class="ldfmt-new-subscriptions-box ldfmt-sales-box">
                            <label><?php echo __('New subscriptions', LDNFT_TEXT_DOMAIN);?></label>
                            <div class="ldnft_new_subscriptions_count">
                                <span class="ldnft_subscription_new_subscriptions_count"></span>
                                <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                            </div>
                        </div>
                        <div class="ldfmt-renewals-count-box ldfmt-sales-box">
                            <label><?php echo __('Total Renewals', LDNFT_TEXT_DOMAIN);?></label>
                            <div class="ldnft_renewals_count">
                                <span class="ldnft_subscription_renewals_count"></span>
                                <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                            </div>
                        </div>
                    </div>
                    <div id="ldnft-admin-modal" class="ldnft-admin-modal">
                        <!-- Modal content -->
                        <div class="ldnft-admin-modal-content">
                            <div class="ldnft-admin-modal-header">
                            <span class="ldnft-admin-modal-close">&times;</span>
                                <h2><?php echo __( 'Subscription Detail', LDNFT_TEXT_DOMAIN );?></h2>
                            </div>
                            <div class="ldnft-admin-modal-body"></div>
                            <div class="ldnft-popup-loader"><img class="" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" /></div>
                        </div>
                    </div>
                </div>
                <div id="ldnft_subscriptions_data">
                    <?php $ListTable->display(); ?>
                </div>
                <input type="hidden" class="ldnft-freemius-page" name="page" value="1" />
				<input type="hidden" class="ldnft-script-freemius-type" name="ldnft-script-freemius-type" value="subscribers" />
            </form>
            
        </div>
        <?php
    }
}

new LDNFT_Subscriptions_Menu();