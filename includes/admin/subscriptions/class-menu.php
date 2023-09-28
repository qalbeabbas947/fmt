<?php
/**
 * LDNFT_Subscriptions_Menu class manages the admin side subscription menu of freemius subscriptions.
 */

 if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * LDNFT_Subscriptions Menu class
 */
class LDNFT_Subscriptions_Menu {

    private $default_hidden_columns;

    /** ************************************************************************
     * REQUIRED. Set up a constructor.
     ***************************************************************************/

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
	}
    
    /**
     * Add Reset Course Progress submenu page under learndash menus
     */
    public function admin_menu_page() { 
        
        $user_id = get_current_user_id();
        
        $api = new Freemius_Api_WordPress( FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        try {
            
            $plugins = $api->Api('plugins.json?fields=id,title', 'GET', []);
            
            if( ! isset( $plugins->error )  ) {
                
                $hook = add_submenu_page( 
                    'ldnft-freemius',
                    __( 'Subscriptions', LDNFT_TEXT_DOMAIN ),
                    __( 'Subscriptions', LDNFT_TEXT_DOMAIN ),
                    'manage_options',
                    'freemius-subscriptions',
                    [ $this,'subscribers_page'],
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
        } catch(Exception $e) {
            
        }
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public function subscribers_page() {
        
        /**
         * Create an instance of our package class... 
         */
        $ListTable = new LDNFT_Subscriptions(); 
        
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $plugins = $api->Api('plugins.json?fields=id,title', 'GET', ['fields'=>'id,title']);
        $plugins->plugins = [];
        if( is_array( $plugins->plugins ) && count($plugins->plugins) == 0 ) {
            ?>
                <div class="wrap">
                    <h2><?php _e( 'Subscriptions', LDNFT_TEXT_DOMAIN ); ?></h2>
                    <p><?php _e( 'No product(s) exists in your freemius account. Please, add a product on freemius and the reload the page.', LDNFT_TEXT_DOMAIN ); ?></p>
                </div>
            <?php

            return;
        }
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
                        <select name="ldfmt-plugins-filter" class="ldfmt-plugins-filter">
                            <?php
                                foreach( $plugins->plugins as $plugin ) {
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
                            $plans = $api->Api('plugins/'.$selected_plugin_id.'/plans.json?count=50', 'GET', []);
                        ?>
                        <select name="ldfmt-sales-plan_id-filter" class="ldfmt-sales-plan_id-filter">
                            <option value=""><?php _e( 'Filter by Plan', LDNFT_TEXT_DOMAIN ); ?></option>
                            <?php
                                foreach( $plans->plans as $plan ) {
                                    
                                    $selected = '';
                                    if( $selected_plan_id == $plan->id ) {
                                        $selected = ' selected = "selected"';   
                                    }
                                    ?>
                                    <option value="<?php echo $plan->id; ?>" <?php echo $selected; ?>><?php echo $plan->title; ?></option>
                                    <?php   
                                }
                            ?>
                        </select>
                        
                        <select name="ldfmt-sales-interval-filter" class="ldfmt-sales-interval-filter">
                            <option value=""><?php echo __( 'All Time', LDNFT_TEXT_DOMAIN );?></option>
                            <option value="1" <?php echo $selected_interval=='1'?'selected':'';?>><?php echo __( 'Monthly', LDNFT_TEXT_DOMAIN );?></option>
                            <option value="12" <?php echo $selected_interval=='12'?'selected':'';?>><?php echo __( 'Annual', LDNFT_TEXT_DOMAIN );?></option>
                        </select>
                        <select name="ldfmt-sales-interval-filter" class="ldfmt-sales-status-filter">
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
            </form>
            
        </div>
        <?php
    }
}

new LDNFT_Subscriptions_Menu();