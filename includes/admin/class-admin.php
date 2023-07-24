<?php

/**
 * Admin template for rest course progress for leardash
 * 
 * Do not allow directly accessing this file.
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
 * FMT_Admin
 */
class FMT_Admin {

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {
        
        if ( is_null( self::$instance ) && ! ( self::$instance instanceof FMT_Admin ) ) {
            self::$instance = new self;

            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * Plugin hooks
    */
    private function hooks() {

        add_action( 'upgrader_process_complete', [ $this, 'ldfmt_create_table_when_plugin_update' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts_callback' ] );
        add_action( 'admin_post_ldnft_submit_action', [ $this, 'ldnft_submit_action' ] );
        add_action( 'admin_notices', [ $this, 'ldnft_admin_notice' ] );
        add_action( 'admin_menu', [ $this, 'add_main_menu_page' ] );
        add_filter( 'plugin_action_links_'. LDNFT_DIR, [ $this, 'plugin_setting_links' ] ); 
        add_action( 'in_admin_header', [ $this, 'remove_admin_notices' ], 100 );

        add_action( 'wp_ajax_ldnft_mailpoet_submit_action', [ $this, 'mailpoet_submit_action' ], 100 );
        add_action( 'wp_ajax_ldnft_update_subscritions', [ $this, 'ldnft_update_subscritions' ], 100 );
        add_action( 'wp_ajax_ldnft_update_sales', [ $this, 'ldnft_update_sales' ], 100 );
    }
    function ldnft_update_sales(){
        
        global $wpdb;
        $service = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        
        $plugins = $service->Api('plugins.json?fields=id,title', 'GET', []);
        $table_name = $wpdb->prefix.'ldnft_transactions';
        $inserted = 0;
        $updatednum = 0;
        if( isset( $plugins->plugins ) &&  count($plugins->plugins) > 0 ) {
            foreach( $plugins->plugins as $plugin ) {
                $pmtobj = $service->Api('plugins/'.$plugin->id.'/payments.json?count=50', 'GET', []);
                foreach( $pmtobj->payments as $payment ) {
                    
                    $country_code = $payment->country_code; 
                    $id = $payment->id; 
                    $user_id = $payment->user_id; 
                    $username = 'none';
                    $useremail = 'none';
                    
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
                    
                    $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $id ));
                    if( count( $res ) == 0 ) {
                        $wpdb->insert(
                            $table_name,
                            array(
                                'id'                        => $id,
                                'plugin_id'                 => $plugin_id,
                                'plugin_title'              => $plugin->title,
                                'user_id'                   => $user_id,
                                'username'                  => $username,
                                'useremail'                 => $useremail,
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
                                'plugin_id'                 => $plugin_id,
                                'plugin_title'              => $plugin->title,
                                'user_id'                   => $user_id,
                                'username'                  => $username,
                                'useremail'                 => $useremail,
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
            }
        }

        echo json_encode(['inserted' => $inserted, 'updated'=>$updatednum, 'message' => sprintf( __('Inserted: %d, Updated: %d', 'MWC'), $inserted, $updatednum)]);
        exit;
    }

    function ldnft_update_subscritions(){

        global $wpdb;
        $service = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        
        $plugins = $service->Api('plugins.json?fields=id,title', 'GET', ['fields'=>'id,title']);
        $table_name = $wpdb->prefix.'ldnft_subscription';
        $inserted = 0;
        $updatednum = 0;
        if( isset( $plugins->plugins ) &&  count($plugins->plugins) > 0 ) {
            foreach( $plugins->plugins as $plugin ) {
                $subobj = $service->Api('plugins/'.$plugin->id.'/subscriptions.json?count=50', 'GET', []);
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
                    $ip = $subscription->ip; 
                    $country_code = $subscription->country_code; 
                    $vat_id = $subscription->vat_id; 
                    $coupon_id = $subscription->coupon_id; 
                    $user_card_id = $subscription->user_card_id; 
                    $source = $subscription->source; 
                    $plugin_id = $subscription->plugin_id; 
                    $external_id = $subscription->external_id; 
                    $gateway = $subscription->gateway; 
                    $environment = $subscription->environment; 
                    $id = $subscription->id; 
                    $created = $subscription->created; 
                    $updated = $subscription->updated; 
                    $currency = $subscription->currency; 
                    $username = 'none';
                    $useremail = 'none';
                    $res = $wpdb->get_results($wpdb->prepare("select * from ".$table_name." where id=%d", $id ));
                    if( count( $res ) == 0 ) {
                        $wpdb->insert(
                            $table_name,
                            array(
                                'id'                        => $id,
                                'plugin_id'                 => $plugin_id,
                                'plugin_title'              => $plugin->title,
                                'user_id'                   => $user_id,
                                'username'                  => $username,
                                'useremail'                 => $useremail,
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
                                'plugin_title'              => $plugin->title,
                                'user_id'                   => $user_id,
                                'username'                  => $username,
                                'useremail'                 => $useremail,
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
            }
            
        }

        echo json_encode(['inserted' => $inserted, 'updated'=>$updatednum, 'message' => sprintf( __('Inserted: %d, Updated: %d', 'MWC'), $inserted, $updatednum)]);
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
            $message = sprintf( __('%d subscriber(s) imported.', 'mailpoet'),$count );
        }
        
        if( $exists > 0 ) {
            $message .= sprintf( __('%d subscriber(s) already exists.', 'mailpoet'),$exists );
        }
        
        $errormsg = '';
        if( count( $errors ) > 0 ) {
            $errormsg = __('Errors:', 'mailpoet').implode(' ', $errors );
        }

        $response = ['added'=>$count, 'exists'=>$exists, 'message'=>$message, 'errors'=> $errors, 'errormsg'=> $errormsg ];
        echo json_encode($response);
        die();
//         'email'         => 'coordinator947'.time().'@gmail.com',
        //         'first_name'    => 'coordinator947'.time(),
        //         'last_name'     => 'abbas'.time(),
        //         'status'        => 'subscribed',
        //         'segments[]'    => 4
        // $subscriber_data = array(
        //     'email' => 'coordinator947'.time().'@gmail.com',
        //     'first_name' => 'coordinator947'.time(),
        //     'last_name' => 'abbas'.time()
        //   );
          
        // $options = array(
        //     'send_confirmation_email' => false // default: true
        //     //'schedule_welcome_email' => false
        // );

        // $default_list_id = array(4);
        // try {
        //     $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, $default_list_id); // Add to default mailing list
        // } catch(Exception $exception) {
        //     echo json_encode($exception->getMessage());
        // }
        // Check if subscriber exists
        try {
            $subscriber = \MailPoet\API\API::MP('v1')->getSubscriber($subscriber_data['email']);
        } 
        catch(\MailPoet\API\MP\v1\APIException $exception) {
            echo json_encode($exception->getMessage());
            if( $exception->getMessage() == 'This subscriber does not exist.' ) {
                try {
                    $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, $default_list_id); // Add to default mailing list
                } 
                catch(\MailPoet\API\MP\v1\APIException $exception) {
                    echo json_encode($exception->getMessage());
                }
                catch(Exception $exception) {
                    echo json_encode($exception->getMessage());
                }
            }
        }
        catch(Exception $exception) {
            // Subscriber does not yet exist, try to add subscriber
            try {
                $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, $default_list_id); // Add to default mailing list
            } 
            catch(\MailPoet\API\MP\v1\APIException $exception) {
                echo json_encode($exception->getMessage());
            }
            catch(Exception $exception) {
                echo json_encode($exception->getMessage());
            }
        }
        // Subscriber exists and needs to be added to certain lists
        if (!empty($_POST["add_to_lists"])) {
            $add_to_lists = array_map('intval', $_POST['add_to_lists']);
            try {
                $subscriber = \MailPoet\API\API::MP('v1')->subscribeToLists($subscriber_data['email'], $add_to_lists, $options);
            } catch(\MailPoet\API\MP\v1\APIException $exception) {
                echo json_encode($exception->getMessage());
            }
            catch(Exception $exception) {
                echo json_encode($exception->getMessage());
            }
        }
        // Subscriber exists and needs to be removed from certain lists
        if (!empty($_POST["remove_from_lists"])) {
            $remove_from_lists = array_map('intval', $_POST['remove_from_lists']);
            try {
                $subscriber = \MailPoet\API\API::MP('v1')->unsubscribeFromLists($subscriber_data['email'], $remove_from_lists, $options);
            } 
            catch(\MailPoet\API\MP\v1\APIException $exception) {
                echo json_encode($exception->getMessage());
            }
            catch(Exception $exception) {
                echo json_encode($exception->getMessage());
            }
        }
        exit;
        // $request->set_query_params( [ 'per_page' => 12 ] );
        // $response = rest_do_request( $request );
        // $server = rest_get_server();
        // $data = $server->response_to_data( $response, false );
        // $json = wp_json_encode( $data );


        // $subs = new SubscriberSaveController();
        // $data = [
        //         'email'         => 'coordinator947'.time().'@gmail.com',
        //         'first_name'    => 'coordinator947'.time(),
        //         'last_name'     => 'abbas'.time(),
        //         'status'        => 'subscribed',
        //         'segments[]'    => 4
        //     ];
        // try {
        //     $subscriber = $this->saveController->save($data);
        // } catch (ValidationException $validationException) {
        //     print_r($validationException);
        // } catch (ConflictException $conflictException) {
        //     print_r($conflictException);
        // };    
        // $subscriber = $subs->save($data);
        // print_r($subscribe);
    }

    /**
     * display admin notice
     */
    public function ldnft_admin_notice() {

        if( isset( $_GET['message'] ) && sanitize_text_field( $_GET['message'] ) == 'ldnft_updated' ) {

            $class = 'notice notice-success is-dismissible';
            $message = __( 'Settings Updated', DFCE_TEXT_DOMAIN );
            printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
        }
    }

    /**
     * Save dripfeed settings data using ( Admin Post )
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
    public function ldfmt_create_table_when_plugin_update( $upgrader, $hook_extra ) {

        global $wpdb;

        // $meta_table = $wpdb->prefix.'ldmft_reset_course_activities_meta';
        // if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$meta_table'" ) ) ) {

        //     $wpdb->query( "CREATE TABLE $meta_table (
        //         ID INT PRIMARY KEY AUTO_INCREMENT,
        //         entry_id VARCHAR(65535),
        //         entry_time VARCHAR(65535),
        //         meta_key VARCHAR(65535),
        //         meta_value VARCHAR(65535)
        //     )" );     
        // }
    }

    /**
     * Remove Admin notices on reset course progress submenu
     */
    public function remove_admin_notices() {

        $screen = get_current_screen();
        if( $screen && $screen->id == 'freemius-toolkit_page_ldninjas-freemius-toolkit-settings' ) {

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

        $settings_link = '<a href="'. admin_url( 'admin.php?page=ldninjas-freemius-toolkit-settings' ) .'">'. __( 'Settings', LDNFT_TEXT_DOMAIN ) .'</a>';
        array_unshift( $links, $settings_link );

        return $links;
    }

    /**
     * Add Reset Course Progress submenu page under learndash menus
     */
    public function add_main_menu_page() {
        
        add_menu_page( 
            __( 'Freemius Toolkit', LDNFT_TEXT_DOMAIN ),
            __( 'Freemius Toolkit', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'ldninjas-freemius-toolkit',
            [$this,'ldninjas_main'],
            null,
            6
        ); 
        
        add_submenu_page( 
            'ldninjas-freemius-toolkit',
            __( 'Subscriptions', LDNFT_TEXT_DOMAIN ),
            __( 'Subscriptions', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'ldninjas-freemius-toolkit-subscriptions',
            [ $this,'subscribers_page']
        );
        add_submenu_page( 
            'ldninjas-freemius-toolkit',
            __( 'Reviews', LDNFT_TEXT_DOMAIN ),
            __( 'Reviews', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'ldninjas-freemius-toolkit-reviews',
            [ $this,'reviews_page']
        );
        add_submenu_page( 
            'ldninjas-freemius-toolkit',
            __( 'Sales', LDNFT_TEXT_DOMAIN ),
            __( 'Sales', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'ldninjas-freemius-toolkit-sales',
            [ $this,'sales_page']
        );
        add_submenu_page( 
            'ldninjas-freemius-toolkit',
            __( 'Customers', LDNFT_TEXT_DOMAIN ),
            __( 'Customers', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'ldninjas-freemius-toolkit-customers',
            [ $this,'customers_page']
        );
        add_submenu_page( 
            'ldninjas-freemius-toolkit',
            __( 'Settings', LDNFT_TEXT_DOMAIN ),
            __( 'Settings', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'ldninjas-freemius-toolkit-settings',
            [ $this,'settings_page']
        );

        remove_submenu_page('ldninjas-freemius-toolkit','ldninjas-freemius-toolkit'); // pay a attention
    }

   /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function sales_page( ) {
        //Create an instance of our package class...
        $testListTable = new LDFMT_Sales();
        //Fetch, prepare, sort, and filter our data...
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
        //Create an instance of our package class...
        $testListTable = new LDFMT_Customers();
        //Fetch, prepare, sort, and filter our data...
        $testListTable->prepare_items();
        ?>
            <div class="wrap">
                
                <div id="icon-users" class="icon32"><br/></div>
                <h2><?php _e( 'Subscribers', LDNFT_TEXT_DOMAIN ); ?></h2>

                
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
        //Create an instance of our package class...
        $testListTable = new LDFMT_Reviews();
        //Fetch, prepare, sort, and filter our data...
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
        //Create an instance of our package class...
        $testListTable = new LDFMT_Subscribers();
        //Fetch, prepare, sort, and filter our data...
        $testListTable->prepare_items();
        
        ?>
        <div class="wrap">
            
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php _e( 'Subscribers', LDNFT_TEXT_DOMAIN ); ?></h2>

            
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
        ?>
            <form class="ldnft-dripfeed-settings" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <div class="ldnft-wrap">

                    <h3 class="ldnft-dripfeed-heading"><?php _e( 'Freemius Settings', LDNFT_TEXT_DOMAIN ); ?></h3>
                    <div class="ldnft-box">
                        <!-- <h3><?php _e( 'Drip-feed Lessons', LDNFT_TEXT_DOMAIN ); ?></h3> -->
                        <div class="ldnft-post-content">
                            <span class="ldnft-desc"><?php _e( 'API Scope', LDNFT_TEXT_DOMAIN ); ?></span>
                            <label>
                                <input type="text" id="ldnft_api_scope" name="ldnft_settings[api_scope]" value="<?php echo $api_scope;?>">
                            </label>
                            <p>
                                <?php _e( 'API Scope of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                            </p>
                        </div>
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
                                <?php _e( 'Scret Key of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                            </p>
                        </div>
                    </div>
                    <div>
                        <?php wp_nonce_field( 'ldnft_nounce', 'ldnft_nounce_field' ); ?>
                        <input type="hidden" name="action" value="ldnft_submit_action" />
                        <input type="submit" class="button button-primary ldnft-save-setting" name="ldnft_submit_form" value="<?php _e( 'Save', LDNFT_TEXT_DOMAIN ); ?>">
                    </div>
                </div>
            </form>
            <form class="ldnft-dripfeed-settings-mailpoet" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <div class="ldnft-wrap">
                    <h3 class="ldnft-dripfeed-heading"><?php _e( 'Import Subscribers from Freemius', LDNFT_TEXT_DOMAIN ); ?></h3>
                    <div class="ldnft-box">
                        <div id="ldnft-settings-import-mailpoet-message"></div>
                        <div id="ldnft-settings-import-mailpoet-errmessage"></div>
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
                                <?php
                                    $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
                                    $plugins = $api->Api('plugins.json?fields=id,title', 'GET', ['fields'=>'id,title']);
                                ?>
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
                        <div>
                            <?php wp_nonce_field( 'ldnft_mailpoet_nounce', 'ldnft_mailpoet_nounce_field' ); ?>
                            <input type="hidden" name="action" value="ldnft_mailpoet_submit_action" />
                            <input type="submit" class="button button-primary ldnft-mailpoet-save-setting_import" name="ldnft_mailpoet_submit_form_import" value="<?php _e( 'Import', LDNFT_TEXT_DOMAIN ); ?>">
                            <div class="ldnft-success-message">
                                <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                                <span class="ldnft-loading-wrap"><?php _e( 'Please wait! Reset is being processed.' ); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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
            if( $screen->id == 'freemius-toolkit_page_ldninjas-freemius-toolkit-settings' 
                || $screen->id == 'freemius-toolkit_page_ldninjas-freemius-toolkit-subscriptions'
                || $screen->id == 'freemius-toolkit_page_ldninjas-freemius-toolkit-sales' ) {

                /**
                 * enqueue admin css
                 */
                wp_enqueue_style( 'fmt-backend-css', LDNFT_ASSETS_URL . 'css/backend.css', [], LDNFT_VERSION, null );
                
                /**
                 * add slect2 js
                 */
                
                wp_enqueue_script( 'fmt-backend-js', LDNFT_ASSETS_URL . 'js/backend.js', [ 'jquery' ], time(), true ); 
                    
                wp_localize_script( 'fmt-backend-js', 'LDNFT', array(  
                    'ajaxURL' => admin_url( 'admin-ajax.php' ),
                ) );
                
            }
        }  
    }
}

FMT_Admin::instance();