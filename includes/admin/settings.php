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
    }

    /**
     * load webhook settings
     */
    public function save_webhook_setting() {
        
        $ldnft_webhook_plugin_ddl  = sanitize_text_field( $_POST['ldnft_webhook_plugin_ddl'] );
        if( intval( $ldnft_webhook_plugin_ddl ) == 0 ) {
            $errormsg = __('Freemius plugin/product is required field.', LDNFT_TEXT_DOMAIN);
            echo $errormsg;exit;
        }

        $ldnft_disable_webhooks          = isset( $_POST['ldnft_disable_webhooks'] ) && $_POST['ldnft_disable_webhooks'] == 'yes' ? 'yes': 'no';
        $ldnft_mailpoet_subscription    = isset( $_POST['ldnft_mailpoet_subscription'] ) && $_POST['ldnft_mailpoet_subscription'] == 'yes' ? 'yes': 'no';
        $ldnft_mailpeot_list            = sanitize_text_field( $_POST['ldnft_mailpeot_list'] );
        update_option( 'ldnft_webhook_settings_'.$ldnft_webhook_plugin_ddl, [ 'mailpeot_list' => $ldnft_mailpeot_list, 'disable_webhooks' => $ldnft_disable_webhooks, 'mailpoet_subscription' => $ldnft_mailpoet_subscription ] );
        
        $msg = __('Freemius plugin/product webhook settings are updated.', LDNFT_TEXT_DOMAIN);
        echo $msg;exit;
    }

    /**
     * load webhook settings
     */
    public function webhook_plugin_settings() {

        global $wpdb;
        
        $plugin_id  = sanitize_text_field( $_POST['plugin_id'] );
        if( ! isset( $_POST['plugin_id'] ) || empty($plugin_id) ) {
            $errormsg = __('Freemius plugin/product is required field.', LDNFT_TEXT_DOMAIN);
            echo $errormsg;exit;
        }

        $settings = get_option( 'ldnft_webhook_settings_'.$plugin_id );
        
        $ldnft_disable_webhooks          = isset( $settings['disable_webhooks'] ) && $settings['disable_webhooks']=='yes' ? 'yes': 'no';
        $ldnft_mailpoet_subscription    = isset( $settings['mailpoet_subscription'] ) && $settings['mailpoet_subscription']=='yes' ? 'yes': 'no';
        $ldnft_mailpeot_list            = intval( $settings['mailpeot_list'] );
        ob_start();
        ?>
            <table class="setting-table-wrapper">
                <tbody class="ldnft-table-content">
                    <tr> 
                        <td align="left" valign="top" width="42%">
						    <strong><label align="left" for="ldnft_disable_webhooks"><?php _e( 'Disable Webhooks:', LDNFT_TEXT_DOMAIN ); ?></label></strong>
					    </td width="58%">
                        <td>
                            <input type="checkbox" id="ldnft_disable_webhooks" <?php echo $ldnft_disable_webhooks=='yes'?'checked':''; ?> name="ldnft_disable_webhooks" value="yes"> <strong><label align="left" for="ldnft_disable_webhooks"><?php _e( 'Yes', LDNFT_TEXT_DOMAIN ); ?></label></strong>
                        </td>    
                    </tr> 
                    <?php if (defined('MAILPOET_VERSION')) { ?>  
                        <tr> 
                            <td align="left" valign="top">
                                <strong><label align = "left" for="ldnft_mailpoet_subscription"><?php _e( 'Mailpoet subscription for new customers:', LDNFT_TEXT_DOMAIN ); ?></label></strong>
                            </td>
                            <td>
                                <input type="checkbox" id="ldnft_mailpoet_subscription" <?php echo $ldnft_mailpoet_subscription=='yes'?'checked':''; ?> name="ldnft_mailpoet_subscription" value="yes"> <strong><label align="left" for="ldnft_mailpoet_subscription"><?php _e( 'Yes', LDNFT_TEXT_DOMAIN ); ?></label></strong>
                            </td>    
                        </tr> 
                        <tr> 
                            <td align="left" valign="top">
                                <strong><label align = "left" for="ldnft_mailpoet_subscription"><?php _e( 'Mailpoet List:', LDNFT_TEXT_DOMAIN ); ?></label></strong>
                            </td>
                            <td>
                                <?php
                                    
                                        $table_name = $wpdb->prefix.'mailpoet_segments';
                                        $list = $wpdb->get_results('select id, name from '.$table_name.'');
                                        $is_list_available = true;
                                        if( is_array($list) && count( $list ) > 0 ) {
                                            echo '<select id="ldnft_mailpeot_list" name="ldnft_mailpeot_list">';
                                            echo '<option value="">'.__( 'Select List', LDNFT_TEXT_DOMAIN ).'</option>';
                                            foreach( $list as $item ) {
                                                echo '<option value="'.$item->id.'" '.($ldnft_mailpeot_list == $item->id?'selected':"" ).'>'.$item->name.'</option>';
                                            }
                                            echo '</select>';
                                        }
                                    
                                ?>
                            </td>
                        </tr>
                    <?php } else {
                        echo '<tr><td></td><td><span class="mailpoet_unable_to_import">'.__( 'Activate the mailpoet plugin for customer subscriptions.', LDNFT_TEXT_DOMAIN ).'</span></td></tr>';
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
            $errormsg = __('Freemius product is required for import.', LDNFT_TEXT_DOMAIN);
            $response = ['added'=>0, 'exists'=>0, 'message'=>'', 'errors'=> [$errormsg], 'errormsg'=> $errormsg ];
            echo json_encode($response);exit;
        }

        $ldnft_mailpeot_list    = sanitize_text_field( $_POST['ldnft_mailpeot_list'] );
        if( ! isset( $_POST['ldnft_mailpeot_list'] ) || empty($ldnft_mailpeot_list) ) {
            $errormsg = __('Mailpoet list is required for import.', LDNFT_TEXT_DOMAIN);
            $response = ['added'=>0, 'exists'=>0, 'message'=>'', 'errors'=> [$errormsg], 'errormsg'=> $errormsg ];
            echo json_encode($response);exit;
        }

        $ldnft_mailpeot_ctype    = sanitize_text_field( $_POST['ldnft_mailpeot_ctype'] );
        if (!is_plugin_active('mailpoet/mailpoet.php')) {
            $errormsg = __('This section requires MailPoet to be installed and configured.', LDNFT_TEXT_DOMAIN);
            $response = [ 'added' => 0, 'exists' => 0, 'message' => '', 'errors' => [$errormsg], 'errormsg' => $errormsg ];
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

                    $exists++;
                } catch(\MailPoet\API\MP\v1\APIException $exception) {
                    if($exception->getCode() == 4 || $exception->getCode() == '4' ) {
                        try {
                            $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, [], $options); // Add to default mailing list
                            
                            if( !empty( $subscriber['id'] ) ) {
                                $sql = "update `".$wpdb->prefix."mailpoet_subscribers` set status='".$status."' WHERE id='".$subscriber['id']."'";
                                $wpdb->query( $sql );
    
                                $sql = "insert into `".$wpdb->prefix."mailpoet_subscriber_segment`(subscriber_id, segment_id, status, created_at, updated_at) values('".$subscriber['id']."', '".$ldnft_mailpeot_list."', '".$status."', now(), now() )";
                                $wpdb->query( $sql );
                                $count++;
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
                'icon' => 'fa-cogs',
            );

            $settings_sections['shortcodes'] =  array(
                'title' => __( 'Shortcodes', LDNFT_TEXT_DOMAIN ),
                'icon' => 'fa-code',
            );

            $settings_sections['webhook'] =  array(
                'title' => __( 'Webhooks', LDNFT_TEXT_DOMAIN ),
                'icon' => 'fa-book',
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