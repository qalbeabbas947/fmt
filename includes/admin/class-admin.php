<?php
/**
 * Manages the admin side functionalities of plugin
 */
if( ! defined( 'ABSPATH' ) ) exit;

use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

/**
 * LDNFT_Admin
 */
class LDNFT_Admin {  

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * Contructor Class
     * 
     * @since 1.0
     * @return $this
     */
    public static function instance() {
        
        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LDNFT_Admin ) ) {
            self::$instance = new self;

            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * Return loader image
     * 
     * @since 1.0
     * @return $this
     */
    public static function get_bar_preloader( $class = 'ldnft-subssummary-loader' ) {
        
        Ob_start();
        ?>
            <img width="30px" class="<?php echo $class; ?>" src="<?php echo LDNFT_ASSETS_URL  . 'images/bar-preloader.gif'; ?>" />
        <?php
        $return  = ob_get_contents();
        ob_end_clean();
        return $return;
    }

    /**
     * Plugin hooks
    */
    private function hooks() {

        add_filter( 'set-screen-option', function( $status, $option, $value ){
            return ( $option == 'subscriptions_per_page' ) ? (int) $value : $status;
        }, 10, 3 );

        add_filter( 'set-screen-option', function( $status, $option, $value ){
            return ( $option == 'reviews_per_page' ) ? (int) $value : $status;
        }, 10, 3 );

        add_filter( 'set-screen-option', function( $status, $option, $value ){
            return ( $option == 'customers_per_page' ) ? (int) $value : $status;
        }, 10, 3 );

        add_filter( 'set-screen-option', function( $status, $option, $value ){
            return ( $option == 'sales_per_page' ) ? (int) $value : $status;
        }, 10, 3 );

        add_action( 'upgrader_process_complete',                [ $this, 'ldnft_create_table_when_plugin_update' ], 10, 2 );
        add_action( 'admin_enqueue_scripts',                    [ $this, 'admin_enqueue_scripts_callback' ] );
        add_action( 'admin_menu',                               [ $this, 'add_main_menu_page' ] );
        add_filter( 'plugin_action_links_'. LDNFT_BASE_DIR,     [ $this, 'plugin_setting_links' ] ); 
        add_action( 'in_admin_header',                          [ $this, 'remove_admin_notices' ], 100 );
        add_action( 'wp_ajax_ldnft_reviews_check_next',         [ $this, 'reviews_check_next' ], 100 );
        add_action( 'wp_ajax_ldnft_sales_view_detail',          [ $this, 'sales_view_detail' ], 100 );
        add_action( 'wp_ajax_ldnft_customers_check_next',       [ $this, 'customers_check_next' ], 100 );
        add_action( 'wp_ajax_ldnft_sales_check_next',           [ $this, 'sales_check_next' ], 100 );
        
    }

    /**
     * checks if there are customers records
     */
    public function sales_check_next() {
        
        $per_page       = isset($_REQUEST['per_page']) && intval($_REQUEST['per_page'])>0?intval($_REQUEST['per_page']):10;
        $offset         = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
        $current_recs   = isset($_REQUEST['current_recs']) && intval($_REQUEST['current_recs'])>0?intval($_REQUEST['current_recs']):0;

        $plugin_id      = isset($_REQUEST['plugin_id']) && intval($_REQUEST['plugin_id'])>0?intval($_REQUEST['plugin_id']):0;
        $status         = isset($_REQUEST['status']) && intval($_REQUEST['status'])>0?intval($_REQUEST['status']):'';
        $offset_rec     = ($offset-1) * $per_page;

        $interval_str = '12';
        if( !empty($this->selected_interval) ) {
            $interval_str = '&billing_cycle='.$this->selected_interval;
        }

        $status_str = '';
        if( !empty($this->selected_status) ) {
            $status_str = '&filter='.$this->selected_status;
        }
        
        $plan_str = '';
        if( !empty($this->selected_plan_id) ) {
           $plan_str = '&plan_id='.$this->selected_plan_id;
        }
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$plugin_id.'/payments.json?count='.$per_page.'&offset='.$offset_rec.$interval_str.$status_str.$plan_str, 'GET', []);
        
        if( ! is_array( $result->payments ) || count( $result->payments ) == 0) {
            echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
        }
        exit;
    }

    /**
     * checks if there are customers records
     */
    public function customers_check_next() {
        
        $per_page       = isset($_REQUEST['per_page']) && intval($_REQUEST['per_page'])>0?intval($_REQUEST['per_page']):10;
        $offset         = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
        $current_recs   = isset($_REQUEST['current_recs']) && intval($_REQUEST['current_recs'])>0?intval($_REQUEST['current_recs']):0;

        $plugin_id      = isset($_REQUEST['plugin_id']) && intval($_REQUEST['plugin_id'])>0?intval($_REQUEST['plugin_id']):0;
        $status         = isset($_REQUEST['status']) && intval($_REQUEST['status'])>0?intval($_REQUEST['status']):'';
        $offset_rec     = ($offset-1) * $per_page;

        $status_str = "";
        if( !empty( $status ) ) {
            $status_str = "&filter=".$status;
        }

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$plugin_id.'/users.json?count='.$per_page.'&offset='.$offset_rec.$status_str, 'GET', []);
        
        if( ! is_array( $result->users ) || count( $result->users ) == 0) {
            echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
        }
        exit;
    }

    /**
     * Returns the subscription data.
     */
    public function sales_view_detail() {
        
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
     * checks if there are reviews records
     */
    public function reviews_check_next() {
        
        $per_page       = isset($_REQUEST['per_page']) && intval($_REQUEST['per_page'])>0?intval($_REQUEST['per_page']):10;
        $offset         = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
        $current_recs   = isset($_REQUEST['current_recs']) && intval($_REQUEST['current_recs'])>0?intval($_REQUEST['current_recs']):0;

        $plugin_id      = isset($_REQUEST['plugin_id']) && intval($_REQUEST['plugin_id'])>0?intval($_REQUEST['plugin_id']):0;
        $interval       = isset($_REQUEST['interval']) && intval($_REQUEST['interval'])>0?intval($_REQUEST['interval']):'';
        $offset_rec     = ($offset-1)  * $per_page;

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$plugin_id.'/reviews.json?is_featured=true&count='.$per_page.'&offset='.$offset_rec, 'GET', []);
        if( ! is_array( $result->reviews ) || count( $result->reviews ) == 0) {
            echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
        }
        exit;
    }

    
    
    /**
     * Create activities meta table on plugin updation.
     * 
     * @param $upgrader
     * @param $hook_extra
     */
    public function ldnft_create_table_when_plugin_update( $upgrader, $hook_extra ) {

        // updation code will be here.
    }

    /**
     * Remove Admin notices on reset course progress submenu
     */
    public function remove_admin_notices() {

        $screen = get_current_screen();
        if( $screen && $screen->id == 'freemius-toolkit_page_freemius-settings' ) {

            remove_all_actions( 'admin_notices' );
        }
    }

    /**
     * Add Settings option on plugin activation
     *
     * @param $links
     * @return href
     */
    public function plugin_setting_links( $links ) {

        $settings_link = '<a href="'. admin_url( 'admin.php?page=freemius-settings' ) .'">'. __( 'Settings', LDNFT_TEXT_DOMAIN ) .'</a>';
        array_unshift( $links, $settings_link );

        return $links;
    }

    /**
     * Add Reset Course Progress submenu page under learndash menus
     */
    public function add_main_menu_page() { 
        
        $user_id = get_current_user_id();
        
        add_menu_page(  
            __( 'Freemius Toolkit', LDNFT_TEXT_DOMAIN ),
            __( 'Freemius Toolkit', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'ldnft-freemius',
            [],
            LDNFT_ASSETS_URL.'images/freemius-icon-light-small.png',
            6 
        ); 
    }
    
    /**
     * Enqueue admin scripts
     *
     * @return bool
     */
    public function admin_enqueue_scripts_callback() {

        $screen = get_current_screen();
        if( $screen ) { 
            if( $screen->id == 'freemius-toolkit_page_freemius-settings' 
                || $screen->id == 'freemius-toolkit_page_freemius-settings-page'
                || $screen->id == 'freemius-toolkit_page_freemius-subscriptions'
                || $screen->id == 'freemius-toolkit_page_freemius-sales' 
                || $screen->id == 'freemius-toolkit_page_freemius-customers' 
                || $screen->id == 'freemius-toolkit_page_freemius-reviews' ) {

                /**
                 * enqueue admin css
                 */
                wp_enqueue_style( 'fmt-backend-css', LDNFT_ASSETS_URL . 'css/backend.css', [], LDNFT_VERSION, null );
                
                /**
                 * enqueue admin js
                 */
                wp_enqueue_script( 'fmt-backend-js', LDNFT_ASSETS_URL . 'js/backend.js', [ 'jquery' ], LDNFT_VERSION, true ); 
                    
                wp_localize_script( 'fmt-backend-js', 'LDNFT', [  
                    'ajaxURL' => admin_url( 'admin-ajax.php' ),
                ] );
            }
        }  
    }
}

LDNFT_Admin::instance();