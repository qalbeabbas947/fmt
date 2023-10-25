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
        // add_action( 'wp_ajax_ldnft_reviews_check_next',         [ $this, 'reviews_check_next' ], 100 );
        // add_action( 'wp_ajax_ldnft_customers_check_next',       [ $this, 'customers_check_next' ], 100 );
        // add_action( 'wp_ajax_ldnft_sales_check_next',           [ $this, 'sales_check_next' ], 100 );
    }

    /**
     * checks if there are customers records
     */
    // public function sales_check_next() {
        
    //     $per_page       = isset($_REQUEST['per_page']) && intval($_REQUEST['per_page'])>0?intval($_REQUEST['per_page']):10;
    //     $offset         = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
    //     $current_recs   = isset($_REQUEST['current_recs']) && intval($_REQUEST['current_recs'])>0?intval($_REQUEST['current_recs']):0;

    //     $plugin_id      = isset($_REQUEST['plugin_id']) && intval($_REQUEST['plugin_id'])>0?intval($_REQUEST['plugin_id']):0;
    //     $status         = isset($_REQUEST['status']) && intval($_REQUEST['status'])>0?intval($_REQUEST['status']):'';
    //     $offset_rec     = ($offset-1) * $per_page;

    //     $interval_str = '12';
    //     if( !empty($this->selected_interval) ) {
    //         $interval_str = '&billing_cycle='.$this->selected_interval;
    //     }

    //     $status_str = '';
    //     if( !empty($this->selected_status) ) {
    //         $status_str = '&filter='.$this->selected_status;
    //     }
        
    //     $plan_str = '';
    //     if( !empty($this->selected_plan_id) ) {
    //        $plan_str = '&plan_id='.$this->selected_plan_id;
    //     }
    //     $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
    //     $result = $api->Api('plugins/'.$plugin_id.'/payments.json?count='.$per_page.'&offset='.$offset_rec.$interval_str.$status_str.$plan_str, 'GET', []);
        
    //     if( ! is_array( $result->payments ) || count( $result->payments ) == 0) {
    //         echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
    //     }
    //     exit;
    // }

    /**
     * checks if there are customers records
     */
    // public function customers_check_next() {
        
    //     $per_page       = isset($_REQUEST['per_page']) && intval($_REQUEST['per_page'])>0?intval($_REQUEST['per_page']):10;
    //     $offset         = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
    //     $current_recs   = isset($_REQUEST['current_recs']) && intval($_REQUEST['current_recs'])>0?intval($_REQUEST['current_recs']):0;

    //     $plugin_id      = isset($_REQUEST['plugin_id']) && intval($_REQUEST['plugin_id'])>0?intval($_REQUEST['plugin_id']):0;
    //     $status         = isset($_REQUEST['status']) && intval($_REQUEST['status'])>0?intval($_REQUEST['status']):'';
    //     $offset_rec     = ($offset-1) * $per_page;

    //     $status_str = "";
    //     if( !empty( $status ) ) {
    //         $status_str = "&filter=".$status;
    //     }

    //     $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
    //     $result = $api->Api('plugins/'.$plugin_id.'/users.json?count='.$per_page.'&offset='.$offset_rec.$status_str, 'GET', []);
        
    //     if( ! is_array( $result->users ) || count( $result->users ) == 0) {
    //         echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
    //     }
    //     exit;
    // }
    
    /**
     * checks if there are reviews records
     */
    // public function reviews_check_next() {
        
    //     $per_page       = isset($_REQUEST['per_page']) && intval($_REQUEST['per_page'])>0?intval($_REQUEST['per_page']):10;
    //     $offset         = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
    //     $current_recs   = isset($_REQUEST['current_recs']) && intval($_REQUEST['current_recs'])>0?intval($_REQUEST['current_recs']):0;

    //     $plugin_id      = isset($_REQUEST['plugin_id']) && intval($_REQUEST['plugin_id'])>0?intval($_REQUEST['plugin_id']):0;
    //     $interval       = isset($_REQUEST['interval']) && intval($_REQUEST['interval'])>0?intval($_REQUEST['interval']):'';
    //     $offset_rec     = ($offset-1)  * $per_page;

    //     $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
    //     $result = $api->Api('plugins/'.$plugin_id.'/reviews.json?is_featured=true&count='.$per_page.'&offset='.$offset_rec, 'GET', []);
    //     if( ! is_array( $result->reviews ) || count( $result->reviews ) == 0) {
    //         echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
    //     }
    //     exit;
    // }
    
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

				wp_enqueue_style( 'dashicons' );
        		wp_enqueue_style( 'ldnft-font-awesome-css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [], LDNFT_VERSION, null );
        
                /**
                 * enqueue admin css
                 */
                wp_enqueue_style( 'fmt-backend-css', LDNFT_ASSETS_URL . 'css/backend.css', [], LDNFT_VERSION, null );
                
                /**
                 * enqueue admin js
                 */
                wp_enqueue_script( 'fmt-backend-js', LDNFT_ASSETS_URL . 'js/backend.js?'.time(), [ 'jquery' ], LDNFT_VERSION, true ); 
                $cron_status    = get_option('ldnft_run_cron_based_on_plugins');

                $page = isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'freemius-settings' ? 'freemius' : '';
                $tab  = isset( $_REQUEST[ 'tab' ] ) && ! empty( $_REQUEST[ 'tab' ] )? $_REQUEST[ 'tab' ]: 'freemius-api';
                $is_cron_page_check = 'no';
                if( $page == 'freemius' && $tab == 'freemius-api' ) {
                    $is_cron_page_check = 'yes';
                }

                wp_localize_script( 'fmt-backend-js', 'LDNFT', [  
                    'ajaxURL' => admin_url( 'admin-ajax.php' ),
                    'import_cron_status' => $cron_status,
                    'is_cron_page_check' => $is_cron_page_check,
                ] );
            }
        }  
    }
}

LDNFT_Admin::instance();