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

        $this->page_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'freemius-api';
        add_action( 'admin_menu',                               [ $this, 'setting_menu' ], 1001 );
        add_action( 'admin_post_ldnft_submit_action',           [ $this, 'save_settings' ] );
        add_action( 'admin_notices',                            [ $this, 'ldnft_admin_notice' ] );
        add_action( 'wp_ajax_ldnft_mailpoet_submit_action',     [ $this, 'mailpoet_submit_action' ], 100 );
        add_action( 'wp_ajax_ldnft_webhook_plugin_settings',    [ $this, 'webhook_plugin_settings' ], 100 );
        add_action( 'wp_ajax_ldnft_save_webhook_setting',       [ $this, 'save_webhook_setting' ], 100 );
        //add_action( 'admin_enqueue_scripts',                    [ $this, 'admin_enqueue_scripts_callback' ] );
	}
	
    /**
     * Action wp_ajax for fetching the first time table structure
     */
    public function admin_enqueue_scripts_callback() {
        $screen = get_current_screen();
        if( $screen->id == 'freemius-toolkit_page_freemius-settings' 
            || $screen->id == 'freemius-toolkit_page_freemius-settings-page'
            || $screen->id == 'freemius-toolkit_page_freemius-subscriptions'
            || $screen->id == 'freemius-toolkit_page_freemius-sales' 
            || $screen->id == 'freemius-toolkit_page_freemius-customers' 
            || $screen->id == 'freemius-toolkit_page_freemius-reviews' ) {

            wp_enqueue_style( 'dashicons' );
            wp_enqueue_style( 'ldnft-select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], LDNFT_VERSION, null );

            /**
             * enqueue admin css
             */
            wp_enqueue_style( 'fmt-backend-css', LDNFT_ASSETS_URL . 'css/backend/backend.css', [], LDNFT_VERSION, null );
            
            /**
             * enqueue admin js
             */
            wp_enqueue_script( 'fmt-select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ], LDNFT_VERSION, true ); 
            wp_enqueue_script( 'fmt-backendcookie-js', LDNFT_ASSETS_URL . 'js/backend/jquery.cookie.js', [ 'jquery' ], LDNFT_VERSION, true ); 

            wp_enqueue_script( 'fmt-backend-js', LDNFT_ASSETS_URL . 'js/backend/backend.js', [ 'jquery' ], LDNFT_VERSION, true ); 
            $cron_status    = get_option('ldnft_run_cron_based_on_plugins');

            $page = isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == 'freemius-settings' ? 'freemius' : '';
            $tab  = isset( $_REQUEST[ 'tab' ] ) && ! empty( $_REQUEST[ 'tab' ] )? sanitize_text_field( $_REQUEST[ 'tab' ] ) : 'freemius-api';
            $is_cron_page_check = 'no';
            if( $page == 'freemius' && $tab == 'freemius-api' ) {
                $is_cron_page_check = 'yes';
            }
            
            $page_id = '';
            if( isset($_REQUEST[ 'page' ]) ) {
                $page_id = sanitize_text_field( $_REQUEST[ 'page' ] );
            }
            
            $current_page = '';
            switch ( $page_id ) {
                case "freemius-customers":
                    $current_page = 'customers';
                    break;
                case "freemius-reviews":
                    $current_page = 'reviews';
                    break;
                case "freemius-sales":
                    $current_page = 'sales';
                    break;
                case "freemius-subscriptions":
                    $current_page = 'subscriptions';
                    break;
            } 

            wp_localize_script( 'fmt-backend-js', 'LDNFT', [  
                'ajaxURL'                       => admin_url( 'admin-ajax.php' ),
                'import_cron_status'            => $cron_status,
                'loader'                        => LDNFT_ASSETS_URL .'images/spinner-2x.gif',
                'is_cron_page_check'            => $is_cron_page_check,
                'preloader_gif_img'             => LDNFT_Admin::get_bar_preloader(),
                'current_page'                  => $current_page,
                'plugins_start_msg'             => __('plugins are updating', 'ldninjas-freemius-toolkit'),
                'plans_start_msg'               => __('Plans are updating', 'ldninjas-freemius-toolkit'),
                'customer_start_msg'            => __('Customers are updating', 'ldninjas-freemius-toolkit'),
                'sales_start_msg'               => __('Sales are updating', 'ldninjas-freemius-toolkit'),
                'subscription_start_msg'        => __('Subscriptions are updating', 'ldninjas-freemius-toolkit'),
                'reviews_start_msg'             => __('Reviews are updating', 'ldninjas-freemius-toolkit'),
                'complete_msg'                  => __('Import is complete', 'ldninjas-freemius-toolkit'),
                'test_n_save'                   => __('Test & Save', 'ldninjas-freemius-toolkit'),
                'sync_data'                     => __('Sync Data', 'ldninjas-freemius-toolkit'),
                'ldnft_error_reload_message'    => __('There seems to be an issue with API connectivity, please try again by <a href="admin.php?page=freemius-settings">reloading the page</a>.', 'ldninjas-freemius-toolkit'),
            ] );
        }
    }

    /**
     * load webhook settings
     */
    public function save_webhook_setting() {
        
        $ldnft_webhook_plugin_ddl  = sanitize_text_field( $_POST['ldnft_webhook_plugin_ddl'] );
        if( intval( $ldnft_webhook_plugin_ddl ) == 0 ) {
            $errormsg = __( 'Freemius plugin/product is required field.', 'ldninjas-freemius-toolkit' );
            echo $errormsg;exit;
        }

        $ldnft_disable_webhooks         = isset( $_POST['ldnft_disable_webhooks'] ) && $_POST['ldnft_disable_webhooks'] == 'yes' ? 'yes': 'no';
        $ldnft_mailpoet_subscription    = isset( $_POST['ldnft_mailpoet_subscription'] ) && $_POST['ldnft_mailpoet_subscription'] == 'yes' ? 'yes': 'no';
        $ldnft_mailpeot_list            = sanitize_text_field( $_POST['ldnft_mailpeot_list'] );
        update_option( 'ldnft_webhook_settings_'.$ldnft_webhook_plugin_ddl, [ 'mailpeot_list' => $ldnft_mailpeot_list, 'disable_webhooks' => $ldnft_disable_webhooks, 'mailpoet_subscription' => $ldnft_mailpoet_subscription ] );
        
        $msg = __( 'Freemius plugin/product webhook settings are updated.', 'ldninjas-freemius-toolkit' );
        echo $msg;
        exit;
    }

    /**
     * load webhook settings
     */
    public function webhook_plugin_settings() {

        global $wpdb;
        
        $plugin_id  = sanitize_text_field( $_POST['plugin_id'] );
        if( ! isset( $_POST['plugin_id'] ) || empty($plugin_id) ) {
            $errormsg = __('Freemius plugin/product is required field.', 'ldninjas-freemius-toolkit');
            echo $errormsg;exit;
        }

        $settings                       = get_option( 'ldnft_webhook_settings_'.$plugin_id );
        $ldnft_disable_webhooks         = isset( $settings['disable_webhooks'] ) && $settings['disable_webhooks']=='yes' ? 'yes': 'no';
        $ldnft_mailpoet_subscription    = isset( $settings['mailpoet_subscription'] ) && $settings['mailpoet_subscription']=='yes' ? 'yes': 'no';
        $ldnft_mailpeot_list            = intval( $settings['mailpeot_list'] );

        ob_start();
        ?>
            <table class="setting-table-wrapper">
                <tbody class="ldnft-table-content">
                    <tr> 
                        <td align="left" valign="top" width="42%">
						    <strong><label align="left" for="ldnft_disable_webhooks"><?php _e( 'Disable Webhooks:', 'ldninjas-freemius-toolkit' ); ?></label></strong>
					    </td width="58%">
                        <td>
                            <input type="checkbox" id="ldnft_disable_webhooks" <?php echo $ldnft_disable_webhooks=='yes'?'checked':''; ?> name="ldnft_disable_webhooks" value="yes"> <strong><label align="left" for="ldnft_disable_webhooks"><?php _e( 'Yes', 'ldninjas-freemius-toolkit' ); ?></label></strong>
                        </td>    
                    </tr> 
                    <?php if (defined('MAILPOET_VERSION')) { ?>  
                        <tr> 
                            <td align="left" valign="top">
                                <strong><label align = "left" for="ldnft_mailpoet_subscription"><?php _e( 'Mailpoet subscription for new customers:', 'ldninjas-freemius-toolkit' ); ?></label></strong>
                            </td>
                            <td>
                                <input type="checkbox" id="ldnft_mailpoet_subscription" <?php echo $ldnft_mailpoet_subscription=='yes'?'checked':''; ?> name="ldnft_mailpoet_subscription" value="yes"> <strong><label align="left" for="ldnft_mailpoet_subscription"><?php _e( 'Yes', 'ldninjas-freemius-toolkit' ); ?></label></strong>
                            </td>    
                        </tr> 
                        <tr> 
                            <td align="left" valign="top">
                                <strong><label align = "left" for="ldnft_mailpoet_subscription"><?php _e( 'Mailpoet List:', 'ldninjas-freemius-toolkit' ); ?></label></strong>
                            </td>
                            <td>
                                <?php
                                    
                                    $table_name = $wpdb->prefix.'mailpoet_segments';
                                    $list = $wpdb->get_results('select id, name from '.$table_name.'');
                                    $is_list_available = true;
                                    if( is_array($list) && count( $list ) > 0 ) {
                                        echo '<select id="ldnft_mailpeot_list" name="ldnft_mailpeot_list">';
                                        echo '<option value="">'.__( 'Select List', 'ldninjas-freemius-toolkit' ).'</option>';
                                        foreach( $list as $item ) {
                                            echo '<option value="'.$item->id.'" '.($ldnft_mailpeot_list == $item->id?'selected':"" ).'>'.$item->name.'</option>';
                                        }
                                        echo '</select>';
                                    }
                                    
                                ?>
                            </td>
                        </tr>
                    <?php } else {
                        echo '<tr><td></td><td><span class="mailpoet_unable_to_import">'.__( 'Activate the mailpoet plugin for customer subscriptions.', 'ldninjas-freemius-toolkit' ).'</span></td></tr>';
                        $allow_import = false;
                        $is_list_available = false;
                    } ?>
                </tbody>
            </table>
        <?php
        $content = ob_get_contents();

        exit;
    }

    /**
     * Process mailpoet
     */
    public function mailpoet_submit_action() {
       
        global $wpdb;

        $ldnft_mailpeot_plugin  = sanitize_text_field( $_POST['ldnft_mailpeot_plugin'] );
        if( ! isset( $_POST['ldnft_mailpeot_plugin'] ) || empty($ldnft_mailpeot_plugin) ) {
            $errormsg = __('Freemius product is required for import.', 'ldninjas-freemius-toolkit');
            $response = [ 'added' => 0, 'exists' => 0, 'message'=>'', 'errors' => [ $errormsg ], 'errormsg' => $errormsg ];
            echo json_encode( $response );exit;
        }

        $ldnft_mailpeot_list    = sanitize_text_field( $_POST['ldnft_mailpeot_list'] );
        if( ! isset( $_POST['ldnft_mailpeot_list'] ) || empty($ldnft_mailpeot_list) ) {
            $errormsg = __('Mailpoet list is required for import.', 'ldninjas-freemius-toolkit');
            $response = ['added' => 0, 'exists' => 0, 'message' => '', 'errors'=> [ $errormsg ], 'errormsg' => $errormsg ];
            echo json_encode( $response );exit;
        }

        $ldnft_mailpeot_ctype    = sanitize_text_field( $_POST['ldnft_mailpeot_ctype'] );
        if (!is_plugin_active('mailpoet/mailpoet.php')) {
            $errormsg = __('This section requires MailPoet to be installed and configured.', 'ldninjas-freemius-toolkit');
            $response = [ 'added' => 0, 'exists' => 0, 'message' => '', 'errors' => [ $errormsg ], 'errormsg' => $errormsg ];
            echo json_encode($response);exit;
        }

        $table_name = $wpdb->prefix.'ldnft_customers'; 
        $meta_table_name = $wpdb->prefix.'ldnft_customer_meta'; 
        
        $where_type = '';
        if( !empty( $ldnft_mailpeot_ctype ) ) {
            if( $ldnft_mailpeot_ctype == 'paid' ) {
                $where_type = " and ( m.status!='".$ldnft_mailpeot_plugin."' and m.status is not Null) ";
            } else {
                $where_type = " and (m.status='' or m.status is Null)";
            }
        }

        if( $ldnft_mailpeot_ctype != 'paid' ) {
            $res = $wpdb->get_results( "select id from ".$wpdb->prefix."mailpoet_tags where name='Free Subscriber'" );
            $tag_id = 0;
            if( count( $res ) == 0 ) {
                $wpdb->insert(
                    $wpdb->prefix.'mailpoet_tags',
                    array(
                        'name'                      => 'Free Subscriber',
                        'description'               => 'Freemius Free Subscriber',
                        'created_at'                 => date('Y-m-d H:i:s')
                    )
                );

                $tag_id = $wpdb->insert_id;

            } else {

                $tag_id = $res[0]->id;
            }
        }

        $result = $wpdb->get_results( "SELECT * FROM $table_name as c inner join $meta_table_name as m on(c.id=m.customer_id) where m.plugin_id='".$ldnft_mailpeot_plugin."'$where_type" );
        $response = [];
        $count = 0;
        $exists = 0;
        $total = 0;
        $errors = [];
        
        if( is_array( $result ) && count( $result ) > 0 ) {

            foreach( $result as $user ) {
                $total++;

                $status = $user->is_marketing_allowed == "1"? 'subscribed' : 'unconfirmed';

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
                    $subscriber = \MailPoet\API\API::MP('v1')->getSubscriber( $subscriber_data['email'] );
                    if( !empty( $subscriber['id'] ) ) {
                        $list_ids = $wpdb->get_results( $wpdb->prepare("select id from `".$wpdb->prefix."mailpoet_subscriber_segment` where subscriber_id=%d and segment_id=%d", $subscriber['id'], $ldnft_mailpeot_list) );
                        if( count($list_ids) == 0 ) {
                            $sql = "insert into `".$wpdb->prefix."mailpoet_subscriber_segment`(subscriber_id, segment_id, status, created_at, updated_at) values('".$subscriber['id']."', '".$ldnft_mailpeot_list."', '".$status."', now(), now() )";
                            $wpdb->query( $sql );
                        }
                    }
                    
                    if( $ldnft_mailpeot_ctype != 'paid' && empty( $user->status ) ) { 
                        $res = $wpdb->get_results( $wpdb->prepare("select * from ".$wpdb->prefix."mailpoet_subscriber_tag where subscriber_id=%d and tag_id=%d", $subscriber['id'], $tag_id ) );
                        if( count( $res ) == 0 ) {
                            $wpdb->insert(
                                $wpdb->prefix.'mailpoet_subscriber_tag',
                                array(
                                    'subscriber_id'        => $subscriber['id'],
                                    'tag_id'               => $tag_id,
                                    'created_at'           => date('Y-m-d H:i:s')
                                )
                            );
                        }
                    }
                    $exists++;
                } catch(\MailPoet\API\MP\v1\APIException $exception) {
                    if($exception->getCode() == 4 || $exception->getCode() == '4' ) {
                        try {
                            $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, [], $options); // Add to default mailing list
                            
                            if( ! empty( $subscriber['id'] ) ) {
                                $sql = "update `".$wpdb->prefix."mailpoet_subscribers` set status='".$status."' WHERE id='".$subscriber['id']."'";
                                $wpdb->query( $sql );
    
                                $sql = "insert into `".$wpdb->prefix."mailpoet_subscriber_segment`(subscriber_id, segment_id, status, created_at, updated_at) values('".$subscriber['id']."', '".$ldnft_mailpeot_list."', '".$status."', now(), now() )";
                                $wpdb->query( $sql );
                                $count++;
                            }
                            
                            if( $ldnft_mailpeot_ctype != 'paid' && empty( $user->status ) ) { 
                                $res = $wpdb->get_results( $wpdb->prepare("select * from ".$wpdb->prefix."mailpoet_subscriber_tag where subscriber_id=%d and tag_id=%d", $subscriber['id'], $tag_id ) );
                                if( count( $res ) == 0 ) {
                                    $wpdb->insert (
                                        $wpdb->prefix.'mailpoet_subscriber_tag',
                                        array (
                                            'subscriber_id'        => $subscriber['id'],
                                            'tag_id'               => $tag_id,
                                            'created_at'           => date('Y-m-d H:i:s')
                                        )
                                    );
                                }
                            }
                        } catch(\MailPoet\API\MP\v1\APIException $exception) {
                            if($exception->getCode() == 6 || $exception->getCode() == '6' ) {
                                $exists++;
                                
                                $errors[$exception->getMessage()] = $exception->getMessage();
                            }
                        } catch( Exception $exception ) {
                            $errors[$exception->getMessage()] = $exception->getMessage();
                        }
                    }
                } catch( Exception $exception ) {
                    $errors[$exception->getMessage()] = $exception->getMessage();
                }
            } 
        }
        
        $message = '';
        $errormsg = '';
        if( $count == $total ) {
            $message .= __('All subscribers are updated.', 'ldninjas-freemius-toolkit');
        } else if( $count > 0 ) {
            $message .= sprintf( __('%d subscriber(s) updated.', 'ldninjas-freemius-toolkit'),$count );
        } else if( $count == 0 && $exists > 0 ) {
            $message .= __('All subscribers are updated.', 'ldninjas-freemius-toolkit');
        } else{
            $errormsg .= __('Errors:', 'ldninjas-freemius-toolkit').'<br>'.implode('<br>', $errors );
        }
        
        if( empty( $message ) && empty( $errormsg ) ) {
            $message = __('No available subscriber(s) to import.', 'ldninjas-freemius-toolkit');
        }

        $response = [ 'tag_id' => $tag_id, 'added' => $count, 'exists' => $exists, 'message' => $message, 'errors' => $errors, 'errormsg' => $errormsg ];
        echo json_encode( $response );
        die();
    }
    
    /**
     * display admin notice
     */
    public function ldnft_admin_notice() {

        if( isset( $_GET['message'] ) ) {

            $class = 'notice notice-success is-dismissible';
            if( $_GET['message'] == 'ldnft_updated' ) {
                $message = __( 'Settings Updated', 'ldninjas-freemius-toolkit' );
            } else {
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

            $api = new Freemius_Api_WordPress( 'developer', $ldnft_settings_options['dev_id'], $ldnft_settings_options['public_key'], $ldnft_settings_options['secret_key'] );
            try {
                $products = $api->Api('plugins.json?fields=id,title', 'GET', []);
                update_option( 'ldnft__HAS_PLUGINS', 'no' );
                if( ! isset( $products->error )  ) {
        
                    update_option( 'ldnft__freemius_connected', 'yes' );
                    if( is_array( $products->plugins ) && count( $products->plugins ) > 0 ) {
                        update_option( 'ldnft__HAS_PLUGINS', 'yes' );
                    }
                } else {
                    update_option( 'ldnft__freemius_connected', 'no' );
                }
            } catch( Exception $e ) {
                update_option( 'ldnft__freemius_connected', 'no' );    
            } 
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
            __( 'Settings', 'ldninjas-freemius-toolkit' ),
            __( 'Settings', 'ldninjas-freemius-toolkit' ),
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
                'title' => __( 'Freemius API', 'ldninjas-freemius-toolkit' ),
                'icon' => 'rest-api',
            ),
        );

        if( FS__API_CONNECTION ) {
            
            $settings_sections['import'] =  array (
                'title' => __( 'Import', 'ldninjas-freemius-toolkit' ),
                'icon' => 'info',
            );

            $settings_sections['shortcodes'] =  array(
                'title' => __( 'Shortcodes', 'ldninjas-freemius-toolkit' ),
                'icon' => 'shortcode',
            );

            $settings_sections['webhook'] =  array(
                'title' => __( 'Webhooks', 'ldninjas-freemius-toolkit' ),
                'icon' => 'update-alt',
            );

            $settings_sections = apply_filters( 'ldnft_settings_sections', $settings_sections );
        }
        
        ?>
		<div class="wrap">
			<div id="icon-options-freemius-api" class="icon32"></div>
			<h2><?php _e( 'Freemius Settings', 'ldninjas-freemius-toolkit' ); ?></h2>
		
			<div class="nav-tab-wrapper">
				<?php foreach( $settings_sections as $key => $section ) { ?>
						<a href="?page=freemius-settings&tab=<?php echo $key; ?>"
							class="nav-tab <?php echo $this->page_tab == $key ? 'nav-tab-active' : ''; ?>">
							<span class="dashicons dashicons-<?php echo $section['icon']; ?>"></span>
                            <?php _e( $section['title'], 'ldninjas-freemius-toolkit' ); ?>
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