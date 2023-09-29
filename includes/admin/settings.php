<?php
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Tickera_Customization_Settings
 */
class Tickera_Customization_Settings {

	private $page_tab;
    
    /**
     * Constructor function
     */
    public function __construct() {

        $this->page_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'freemius-api';
        add_action( 'admin_menu',                               [ $this, 'setting_menu' ], 1001 );
        add_action( 'admin_post_ldnft_submit_action',           [ $this, 'save_settings' ] );
        add_action( 'admin_notices',                            [ $this, 'ldnft_admin_notice' ] );
        add_action( 'wp_ajax_ldnft_mailpoet_submit_action',     [ $this, 'mailpoet_submit_action' ], 100 );
    }

    /**
     * display admin notice
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

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $results = $api->Api('plugins/'.$ldnft_mailpeot_plugin.'/users.json?filter=paid', 'GET', []);
        $response = [];
        $count = 0;
        $exists = 0;
        $errors = [];
        foreach( $results->users as $user ) { 
            
            $subscriber_data = [
                'email' => $user->email,
                'first_name' => $user->first,
                'last_name' => $user->last,
            ];
              
            $options = [
                'send_confirmation_email' => false // default: true
                //'schedule_welcome_email' => false
            ];

            try {
                $subscriber = \MailPoet\API\API::MP('v1')->getSubscriber($subscriber_data['email']);
                $exists++;
            } 
            catch(\MailPoet\API\MP\v1\APIException $exception) {
                if( $exception->getMessage() == 'This subscriber does not exist.' ) {
                    try {
                        $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, [$ldnft_mailpeot_list], $options); // Add to default mailing list
                        
                        if( !empty( $subscriber['id'] ) ) {
                            $sql = "update `".$wpdb->prefix."mailpoet_subscribers` set status='".SubscriberEntity::STATUS_SUBSCRIBED."' WHERE id='".$subscriber['id']."'";
                            $wpdb->query( $sql );
                        }
                        
                        $count++;
                    } 
                    catch(\MailPoet\API\MP\v1\APIException $exception) {
                        $errors[$exception->getMessage()] = $exception->getMessage();
                        
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
        
        $message = '';
        if( $count > 0 ) {
            $message = sprintf( __('%d subscriber(s) imported.', LDNFT_TEXT_DOMAIN),$count );
        } 
        if( $exists > 0 ) {
            $message .= sprintf( __('%d subscriber(s) already exists.', LDNFT_TEXT_DOMAIN),$exists );
        } 
        
        $errormsg = '';
        if( count( $errors ) > 0 ) {
            $errormsg = __('Errors:', LDNFT_TEXT_DOMAIN).'<br>'.implode('<br>', $errors );
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
						<a href="?page=freemius-settings-page&tab=<?php echo $key; ?>"
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
                        include( 'views/' . $key . '.php' );
                    }
                }
			?>
		</div>
        <?php
    }
}

new Tickera_Customization_Settings();