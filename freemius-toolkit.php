<?php
/**
 * Plugin Name: Freemius Toolkit
 * Description: This add-on helps you to display subscriptions, sales, reviews and customers on our website.
 * Version: 1.0
 * Author: LDninjas
 * Author URI: ldninjas.com
 * Plugin URI: https://ldninjas.com/ld-plugins/
 * Text Domain: ldninjas-freemius-toolkit
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * LdNinjas_Freemius_Toolkit
 */
class LdNinjas_Freemius_Toolkit {

    /**
     * @var version number
     */
    const VERSION = '1.0';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LdNinjas_Freemius_Toolkit ) ) {
            self::$instance = new self;

            self::$instance->setup_constants();
            self::$instance->includes();
            self::$instance->enable_freemius();
        }
        
        return self::$instance;
    }

    /**
     * Initiate freemius
     *
     * @return void
     */
    public function enable_freemius() {

        if ( ! function_exists( 'test_freemius_addon' ) ) {

            /**
             * Create a helper function for easy SDK access.
             */
            function test_freemius_addon() {
                global $test_freemius_addon;
        
                if ( ! isset( $test_freemius_addon ) ) {
                    
                    /**
                     * Include Freemius SDK.
                     */
                    require_once dirname(__FILE__) . '/freemius/start.php';
        
                    $test_freemius_addon = fs_dynamic_init( array(
                        'id'                  => '12667',
                        'slug'                => 'coordinator-course-reset',
                        'type'                => 'plugin',
                        'public_key'          => 'pk_30d13bc8bd91e0687bf2cb41b61c6',
                        'is_premium'          => false,
                        'has_addons'          => true,
                        'has_paid_plans'      => false,
                        'menu'                => array(
                            'first-path'     => 'plugins.php',
                        ),
                    ) );
                }
        
                return $test_freemius_addon;
            }
        
            /**
             * Init Freemius.
             */
            test_freemius_addon();
            
            /**
             * Signal that SDK was initiated.
             */
            do_action( 'test_freemius_addon_loaded' );
        }
    }

    /**
     * Plugin Constants
    */
    private function setup_constants() {

        /**
         * Directory
        */
        define( 'LDNFT_DIR', plugin_dir_path ( __FILE__ ) );
        define( 'LDNFT_DIR_FILE', LDNFT_DIR . basename ( __FILE__ ) );
        define( 'LDNFT_INCLUDES_DIR', trailingslashit ( LDNFT_DIR . 'includes' ) );
        define( 'LDNFT_BASE_DIR', plugin_basename(__FILE__));

        /**
         * URLs
        */
        define( 'LDNFT_URL', trailingslashit ( plugins_url ( '', __FILE__ ) ) );
        define( 'LDNFT_ASSETS_URL', trailingslashit ( LDNFT_URL . 'assets' ) );
        
        /**
         * Plugin version
         */
        //define( 'LDNFT_VERSION', self::VERSION );
        define( 'LDNFT_VERSION', time() );
        /**
         * Text Domain
         */
        define( 'LDNFT_TEXT_DOMAIN', 'ldninjas-freemius-toolkit' );

        /**
         * Take the api settings to access the freemius api
         */
        $ldnft_settings = get_option( 'ldnft_settings' );
        $api_scope      = 'developer';
        $dev_id         = isset( $ldnft_settings['dev_id'] ) ? sanitize_text_field( $ldnft_settings['dev_id'] ) : '';
        $public_key     = isset( $ldnft_settings['public_key'] ) ? sanitize_text_field( $ldnft_settings['public_key'] ): '';
        $secret_key     = isset( $ldnft_settings['secret_key'] ) ? sanitize_text_field( $ldnft_settings['secret_key'] ): '';

        define( 'FS__API_SCOPE', $api_scope ); 
        define( 'FS__API_DEV_ID', $dev_id );
        define( 'FS__API_PUBLIC_KEY', $public_key );
        define( 'FS__API_SECRET_KEY', $secret_key );
    }

    /**
     * Plugin requiered files
     */
    private function includes() {
        
        if( file_exists( LDNFT_DIR.'freemius/includes/sdk/FreemiusBase.php' ) ) {
            require_once LDNFT_DIR.'freemius/includes/sdk/FreemiusBase.php';
        }        
       
        if( file_exists( LDNFT_DIR.'freemius/includes/sdk/FreemiusWordPress.php' ) ) {
            require_once LDNFT_DIR.'freemius/includes/sdk/FreemiusWordPress.php';
        }        

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/class-admin.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/class-admin.php';
        }        
        
        if( file_exists( LDNFT_INCLUDES_DIR .'admin/listings/class-customers.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/listings/class-customers.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/listings/class-reviews.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/listings/class-reviews.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/listings/class-subscribers.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/listings/class-subscribers.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/listings/class-sales.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/listings/class-sales.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR.'shortcodes/class-reviews.php' ) ) {
            require_once LDNFT_INCLUDES_DIR.'shortcodes/class-reviews.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR.'shortcodes/class-sales.php' ) ) {
            require_once LDNFT_INCLUDES_DIR.'shortcodes/class-sales.php';
        }
    }
}

/**
 * Retrieve the plugin object
 * @return bool
 */
function LDNFT() {

    return LdNinjas_Freemius_Toolkit::instance();
}
add_action( 'plugins_loaded', 'LDNFT' );