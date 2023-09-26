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
        add_action( 'admin_post_ldnft_submit_action',           [ $this, 'ldnft_submit_action' ] );
        add_action( 'admin_notices',                            [ $this, 'ldnft_admin_notice' ] );
        add_action( 'admin_menu',                               [ $this, 'add_main_menu_page' ] );
        add_filter( 'plugin_action_links_'. LDNFT_BASE_DIR,     [ $this, 'plugin_setting_links' ] ); 
        add_action( 'in_admin_header',                          [ $this, 'remove_admin_notices' ], 100 );
        add_action( 'wp_ajax_ldnft_mailpoet_submit_action',     [ $this, 'mailpoet_submit_action' ], 100 );
        add_action( 'wp_ajax_ldnft_subscriber_check_next',      [ $this, 'subscriber_check_next' ], 100 );
        add_action( 'wp_ajax_ldnft_reviews_check_next',         [ $this, 'reviews_check_next' ], 100 );
        add_action( 'wp_ajax_ldnft_subscribers_view_detail',    [ $this, 'subscribers_view_detail' ], 100 );
        add_action( 'wp_ajax_ldnft_sales_view_detail',          [ $this, 'sales_view_detail' ], 100 );
        add_action( 'wp_ajax_ldnft_customers_check_next',       [ $this, 'customers_check_next' ], 100 );
        add_action( 'wp_ajax_ldnft_sales_check_next',       [ $this, 'sales_check_next' ], 100 );
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

        $interval_str = '';
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
            echo __('Transaction id and plugin id are required fields.', LDNFT_TEXT_DOMAIN);    
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
     * Returns the subscription data.
     */
    public function subscribers_view_detail() {
        
        $user_id        = isset( $_REQUEST['user_id'] ) ?intval( $_REQUEST['user_id'] ):0;
        $plugin_id      = isset( $_REQUEST['plugin_id'] ) ?intval( $_REQUEST['plugin_id'] ):0;
        $id             = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ):0;
        if( $id == 0 || $plugin_id == 0 )  {
            echo '<div class="ldnft-error-message">';
            echo __('Transaction id and plugin id are required fields.', LDNFT_TEXT_DOMAIN);    
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
        $result = $api->Api('plugins/'.$plugin_id.'/reviews.json?count='.$per_page.'&offset='.$offset_rec, 'GET', []);
        if( ! is_array( $result->reviews ) || count( $result->reviews ) == 0) {
            echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
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
     * display admin notice
     */
    public function mailpoet_submit_action() {
       

        $ldnft_mailpeot_plugin  = sanitize_text_field($_POST['ldnft_mailpeot_plugin']);
        $ldnft_mailpeot_list    = sanitize_text_field($_POST['ldnft_mailpeot_list']);

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $results = $api->Api('plugins/'.$ldnft_mailpeot_plugin.'/users.json', 'GET', []);
        $response = [];
        $count = 0;
        $exists = 0;
        $errors = [];
        foreach( $results->users as $user ) {
            $subscriber_data = array(
                'email' => $user->email,
                'first_name' => $user->first,
                'last_name' => $user->last,
            );
              
            $options = array(
                'send_confirmation_email' => false // default: true
                //'schedule_welcome_email' => false
            );
            try {
                $subscriber = \MailPoet\API\API::MP('v1')->getSubscriber($subscriber_data['email']);
                $exists++;
            } 
            catch(\MailPoet\API\MP\v1\APIException $exception) {
                if( $exception->getMessage() == 'This subscriber does not exist.' ) {
                    try {
                        $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, [$ldnft_mailpeot_list]); // Add to default mailing list
                        $count++;
                    } 
                    catch(\MailPoet\API\MP\v1\APIException $exception) {
                        $errors[] = $exception->getMessage();
                        
                    }
                    catch(Exception $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            }
            catch(Exception $exception) {
                $errors[] = $exception->getMessage();
            }
        }
        
        $message = '';
        if( $count > 0 ) {
            $message = sprintf( __('%d subscriber(s) imported.', LDNFT_TEXT_DOMAIN),$count );
        } 
        if( $exists > 0 ) {
            $message .= sprintf( __('%d subscriber(s) already exists.', LDNFT_TEXT_DOMAIN),$exists );
        } 
        
        $errormsg = '';
        if( count( $errors ) > 0 ) {
            $errormsg = __('Errors:', LDNFT_TEXT_DOMAIN).implode(' ', $errors );
        }
        if(empty($message) && empty($errormsg)) {
            $message = __('No available subscriber(s) to import.', LDNFT_TEXT_DOMAIN);
        }
        $response = ['added'=>$count, 'exists'=>$exists, 'message'=>$message, 'errors'=> $errors, 'errormsg'=> $errormsg ];
        echo json_encode($response);
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
    public function ldnft_submit_action() {
        
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
     * Create activities meta table on plugin updation.
     * 
     * @param $upgrader
     * @param $hook_extra
     */
    public function ldnft_create_table_when_plugin_update( $upgrader, $hook_extra ) {

        global $wpdb;
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
            [$this,'ldninjas_main'],
            LDNFT_ASSETS_URL.'images/freemius-icon-light-small.png',
            6 
        ); 
        
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
                    [ $this,'subscribers_page']
                );
                
                $default_hidden_columns = [ 
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
                if( get_user_option( 'subscription_hidden_columns_set', $user_id) != 'Yes' ) {
                    update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-subscriptionscolumnshidden', $default_hidden_columns );
                    update_user_option( $user_id, 'subscription_hidden_columns_set', 'Yes' );
                }

                add_action( "load-$hook", function () {
                    
                    global $ldnftSubscriptionsListTable;
                    
                    $option = 'per_page';
                    $args = array(
                            'label' => 'Subsriptions Per Page',
                            'default' => 10,
                            'option' => 'subscriptions_per_page'
                        );
                    add_screen_option( $option, $args );
                    $ldnftSubscriptionsListTable = new LDNFT_Subscriptions();
                } );

                $review_hook = add_submenu_page( 
                    'ldnft-freemius',
                    __( 'Reviews', LDNFT_TEXT_DOMAIN ),
                    __( 'Reviews', LDNFT_TEXT_DOMAIN ),
                    'manage_options',
                    'freemius-reviews',
                    [ $this,'reviews_page']
                );

                $reviews_hidden_columns = [ 
                    'user_id' ,
                    'username',
                    'useremail',
                    'job_title',
                    'company_url',
                    'picture',
                    'profile_url',
                    'is_verified',
                    'is_featured',
                    'sharable_img',
                ];
                if( get_user_option( 'reviews_hidden_columns_set', $user_id) != 'Yes' ) {
                    update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-reviewscolumnshidden', $reviews_hidden_columns );
                    update_user_option( $user_id, 'reviews_hidden_columns_set', 'Yes' );
                }

                add_action( "load-$review_hook", function () {
                    
                    global $ldnftReviewsListTable;
                    
                    $option = 'per_page';
                    $args = array(
                            'label' => 'Subsriptions Per Page',
                            'default' => 10,
                            'option' => 'reviews_per_page'
                        );
                    add_screen_option( $option, $args );
                    $ldnftReviewsListTable = new LDNFT_Reviews();
                } );


                $sales_hook = add_submenu_page( 
                    'ldnft-freemius',
                    __( 'Sales', LDNFT_TEXT_DOMAIN ),
                    __( 'Sales', LDNFT_TEXT_DOMAIN ),
                    'manage_options',
                    'freemius-sales',
                    [ $this,'sales_page']
                );
                
                $sales_hidden_columns = [ 
                    'created', 
                    'currency',
                    'country_code', 
                    'id', 
                    'user_id',  
                    'bound_payment_id', 
                    'vat', 
                    'install_id', 
                    'plan_id', 
                    'pricing_id', 
                    'ip', 
                    'zip_postal_code', 
                    'vat_id', 
                    'coupon_id', 
                    'user_card_id', 
                    'plugin_id', 
                    'external_id'
                ];
                if( get_user_option( 'sales_hidden_columns_set', $user_id) != 'Yes' ) {
                    update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-salescolumnshidden', $sales_hidden_columns );
                    update_user_option( $user_id, 'sales_hidden_columns_set', 'Yes' );
                }

                add_action( "load-$sales_hook", function () {
                    
                    global $ldnftSalesListTable;
                    
                    $option = 'per_page';
                    $args = array(
                            'label' => 'Sales Per Page',
                            'default' => 10,
                            'option' => 'sales_per_page'
                        );
                    add_screen_option( $option, $args );
                    $ldnftSalesListTable = new LDNFT_Sales();
                } );
                

                $customers_hook = add_submenu_page( 
                    'ldnft-freemius',
                    __( 'Customers', LDNFT_TEXT_DOMAIN ),
                    __( 'Customers', LDNFT_TEXT_DOMAIN ),
                    'manage_options',
                    'freemius-customers',
                    [ $this,'customers_page']
                );

                $customers_hidden_columns = [ 
                    'is_marketing_allowed', 
                    'is_verified'
                ];
                if( get_user_option( 'customers_hidden_columns_set', $user_id) != 'Yes' ) {
                    update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-customerscolumnshidden', $customers_hidden_columns );
                    update_user_option( $user_id, 'customers_hidden_columns_set', 'Yes' );
                }

                add_action( "load-$customers_hook", function () {
                    
                    global $ldnftCustomersListTable;
                    
                    $option = 'per_page';
                    $args = array(
                            'label' => 'Customers Per Page',
                            'default' => 10,
                            'option' => 'customers_per_page'
                        );
                    add_screen_option( $option, $args );
                    $ldnftCustomersListTable = new LDNFT_Customers();
                } );

            }
        } catch(Exception $e) {
            
        }

        add_submenu_page( 
            'ldnft-freemius',
            __( 'Settings', LDNFT_TEXT_DOMAIN ),
            __( 'Settings', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'freemius-settings',
            [ $this,'settings_page']
        );

        remove_submenu_page('ldnft-freemius','ldnft-freemius');
    }

   /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function sales_page( ) {
        
        /**
         * Create an instance of our package class... 
         */
        $testListTable = new LDNFT_Sales();
        
        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $testListTable->prepare_items();
        ?>
            <div class="wrap">
                
                <div id="icon-users" class="icon32"><br/></div>
                <h2><?php _e( 'Sales', LDNFT_TEXT_DOMAIN ); ?></h2>
                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                <form id="sales-filter" method="get">
                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <!-- Now we can render the completed list table -->
                    <?php $testListTable->display() ?>
                </form>
                
            </div>
        <?php
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function customers_page( ) {

        /**
         * Create an instance of our package class... 
         */
        $testListTable = new LDNFT_Customers();

        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $testListTable->prepare_items();
        ?>
            <div class="wrap">
                
                <div id="icon-users" class="icon32"><br/></div>
                <h2><?php _e( 'Customers', LDNFT_TEXT_DOMAIN ); ?></h2>

                
                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                <form id="movies-filter" method="get">
                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <!-- Now we can render the completed list table -->
                    <?php $testListTable->display() ?>
                </form>
                
            </div>
        <?php
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function reviews_page( ) {
        
        /**
         * Create an instance of our package class... 
         */
        $testListTable = new LDNFT_Reviews();
        
        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $testListTable->prepare_items();
        
        ?>
        <div class="wrap">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php _e( 'Reviews', LDNFT_TEXT_DOMAIN ); ?></h2>

            
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="movies-filter" method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <!-- Now we can render the completed list table -->
                <?php $testListTable->display() ?>
            </form>
            
        </div>
        <?php
    }
    

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function subscribers_page( ) {
        
        /**
         * Create an instance of our package class... 
         */
        $testListTable = new LDNFT_Subscriptions(); 
        
        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $testListTable->prepare_items();
        ?>
        <div class="wrap">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php _e( 'Subscriptions', LDNFT_TEXT_DOMAIN ); ?></h2>

            
            <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
            <form id="movies-filter" method="get">
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                <!-- Now we can render the completed list table -->
                <?php $testListTable->display() ?>
            </form>
            
        </div>
        <?php
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function settings_page( $current ) {
        
        global $wpdb;
        
        $ldnft_settings = get_option( 'ldnft_settings' );
        $api_scope      = isset( $ldnft_settings['api_scope'] ) ? sanitize_text_field( $ldnft_settings['api_scope'] ) : 'developer';
        $dev_id         = isset( $ldnft_settings['dev_id'] ) ? sanitize_text_field( $ldnft_settings['dev_id'] ) : '';
        $public_key     = isset( $ldnft_settings['public_key'] ) ? sanitize_text_field( $ldnft_settings['public_key'] ): '';
        $secret_key     = isset( $ldnft_settings['secret_key'] ) ? sanitize_text_field( $ldnft_settings['secret_key'] ): '';

        $api = new Freemius_Api_WordPress( $api_scope, $dev_id, $public_key, $secret_key);
        $is_connected = false;
        try {
            $plugins = $api->Api('plugins.json?fields=id,title', 'GET', ['fields'=>'id,title']);
            
            if( isset( $plugins->error ) && isset( $plugins->error->message ) ) {
                $_message = $plugins->error->message;
                $class = 'notice notice-success is-dismissible';
                $message = sanitize_text_field( $_message );
                printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
            } else{
                $is_connected = true;
            }
        } catch(Exception $e) {
            $class = 'notice notice-success is-dismissible';
            printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $e->getMessage() );
        }
        ?>
            <form class="ldnft-general-settings" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <div class="ldnft-wrap">

                    <h3 class="ldnft-settings-heading"><?php _e( 'Freemius Settings', LDNFT_TEXT_DOMAIN ); ?></h3>
                    <div class="ldnft-box">
                        <!-- <h3><?php _e( 'Drip-feed Lessons', LDNFT_TEXT_DOMAIN ); ?></h3> -->
                        <!-- <div class="ldnft-post-content">
                            <span class="ldnft-desc"><?php _e( 'API Scope', LDNFT_TEXT_DOMAIN ); ?></span>
                            <label>
                                <input type="text" id="ldnft_api_scope" name="ldnft_settings[api_scope]" value="<?php echo $api_scope;?>">
                            </label>
                            <p>
                                <?php _e( 'API Scope of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                            </p>
                        </div> -->
                        <div class="ldnft-post-content">
                            <span class="ldnft-desc"><?php _e( 'Developer ID', LDNFT_TEXT_DOMAIN ); ?></span>
                            <label>
                                <input type="text" id="ldnft_dev_id" name="ldnft_settings[dev_id]" value="<?php echo $dev_id;?>">
                            </label>
                            <p>
                                <?php _e( 'Developer ID of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                            </p>
                        </div>
                        <div class="ldnft-post-content">
                            <span class="ldnft-desc"><?php _e( 'Public Key', LDNFT_TEXT_DOMAIN ); ?></span>
                            <label>
                                <input type="text" id="ldnft_public_key" name="ldnft_settings[public_key]" value="<?php echo $public_key;?>">
                            </label>
                            <p>
                                <?php _e( 'Public Key of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                            </p>
                        </div>
                        <div class="ldnft-post-content">
                            <span class="ldnft-desc"><?php _e( 'Secret Key', LDNFT_TEXT_DOMAIN ); ?></span>
                            <label>
                                <input type="text" id="ldnft_secret_key" name="ldnft_settings[secret_key]" value="<?php echo $secret_key;?>">
                            </label>
                            <p>
                                <?php  _e('Scret Key of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                            </p>
                        </div>
                        <div class="ldfmt-button-wrapper">
                            <?php wp_nonce_field( 'ldnft_nounce', 'ldnft_nounce_field' ); ?>
                            <input type="hidden" name="action" value="ldnft_submit_action" />
                            <input type="submit" class="button button-primary ldnft-save-setting" name="ldnft_submit_form" value="<?php _e( 'Test & Save', LDNFT_TEXT_DOMAIN ); ?>">
                        </div>

                    </div>
                    
                </div>
            </form>
            <?php if( $is_connected ) { ?>
                <form class="ldnft-settings-mailpoet" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                    <div class="ldnft-wrap">
                        <h3 class="ldnft-settings-heading"><?php _e( 'Import Subscribers from Freemius to Mailpoet', LDNFT_TEXT_DOMAIN ); ?></h3>
                        <div class="ldnft-box">
                            <div id="ldnft-settings-import-mailpoet-message" style="display:none"></div>
                            <div id="ldnft-settings-import-mailpoet-errmessage" style="display:none"></div>
                            <div class="ldnft-post-content">
                                <span class="ldnft-desc"><?php _e( 'Mailpoet List', LDNFT_TEXT_DOMAIN ); ?></span>
                                <label>
                                    <select id="ldnft_mailpeot_list" name="ldnft_mailpeot_list">
                                        <?php
                                            $table_name = $wpdb->prefix.'mailpoet_segments';
                                            $list = $wpdb->get_results('select id, name from '.$table_name.'');
                                            foreach( $list as $item ) {
                                                echo '<option value="'.$item->id.'">'.$item->name.'</option>';
                                            }
                                        ?>
                                        
                                    </select>
                                </label>
                                <p>
                                    <?php _e( 'Select a list before import the actual subscribers.', LDNFT_TEXT_DOMAIN ); ?>
                                </p>
                            </div>
                            <div class="ldnft-post-content">
                                <span class="ldnft-desc"><?php _e( 'Plugin', LDNFT_TEXT_DOMAIN ); ?></span>
                                <label>
                                    
                                    <select id="ldnft_mailpeot_plugin" name="ldnft_mailpeot_plugin">
                                        <?php
                                            foreach( $plugins->plugins as $plugin ) {
                                                ?>
                                                    <option value="<?php echo $plugin->id; ?>"><?php echo $plugin->title; ?></option>
                                                <?php   
                                            }
                                        ?>
                                    </select>
                                </label>
                                <p>
                                    <?php _e( 'Select a plugin whose subscribers needs to be imported.', LDNFT_TEXT_DOMAIN ); ?>
                                </p>
                            </div>
                            <div class="ldfmt-button-wrapper">
                                <?php wp_nonce_field( 'ldnft_mailpoet_nounce', 'ldnft_mailpoet_nounce_field' ); ?>
                                <input type="hidden" name="action" value="ldnft_mailpoet_submit_action" />
                                <div class="ldnft-success-message">
                                    <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                                    <span class="ldnft-loading-wrap"><?php _e( 'Please wait! Import is being processed.', LDNFT_TEXT_DOMAIN ); ?></span>
                                </div>
                                <input type="submit" class="button button-primary ldnft-mailpoet-save-setting_import" name="ldnft_mailpoet_submit_form_import" value="<?php _e( 'Import Subscribers', LDNFT_TEXT_DOMAIN ); ?>">
                            </div>
                        </div>
                    </div>
                </form>

                <div class="ldnft-wrap">
                    <h3 class="ldnft-settings-heading"><?php _e( 'Shortcodes', LDNFT_TEXT_DOMAIN ); ?></h3>
                    <div class="ldnft-box">
                        <table>
                            <tr>
                                <td><h3><?php _e( 'Shortcode:', LDNFT_TEXT_DOMAIN ); ?> [LDNFT_Reviews]</h3></td>
                            </tr>
                            <tr>
                                <td clss="ldfmt-shortcode-desc"><?php _e( 'Displays plugin reviews on the frontend. User can filter the reviews based on the plugin.', LDNFT_TEXT_DOMAIN ); ?></td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td><h3><?php _e( 'Shortcode:', LDNFT_TEXT_DOMAIN ); ?> [LDNFT_Sales show="[ summary  |  listing  |  both ]"]</h3></td>
                            </tr>
                            <tr>
                                <td clss="ldfmt-shortcode-desc"><?php _e( 'This shortcode displays the plugin sales summary and listing on the frontend. Show parameter allows the user to control the display. Default value of the show parameter is both.', LDNFT_TEXT_DOMAIN ); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php } ?>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     *
     * @return bool
     */
    public function admin_enqueue_scripts_callback() {

        $screen = get_current_screen();
        if( $screen ) { 
            echo $screen->id;
            if( $screen->id == 'freemius-toolkit_page_freemius-settings' 
                || $screen->id == 'freemius-toolkit_page_freemius-subscriptions'
                || $screen->id == 'freemius-toolkit_page_freemius-sales' 
                || $screen->id == 'freemius-toolkit_page_freemius-customers' 
                || $screen->id == 'freemius-toolkit_page_freemius-reviews' ) {

                /**
                 * enqueue admin css
                 */
                wp_enqueue_style( 'fmt-backend-css', LDNFT_ASSETS_URL . 'css/backend.css', [], LDNFT_VERSION, null );
                
                /**
                 * add slect2 js
                 */
                
                wp_enqueue_script( 'fmt-backend-js', LDNFT_ASSETS_URL . 'js/backend.js', [ 'jquery' ], LDNFT_VERSION, true ); 
                    
                wp_localize_script( 'fmt-backend-js', 'LDNFT', array(  
                    'ajaxURL' => admin_url( 'admin-ajax.php' ),
                ) );
                
            }
        }  
    }
}

LDNFT_Admin::instance();