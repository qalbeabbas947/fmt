<?php
/**
 * Plugin Name: Freemius Toolkit
 * Description: This add-on helps you to display subscriptions, sales, reviews and customers to your website.
 * Version: 1.0
 * Author: LDninjas
 * Author URI: ldninjas.com
 * Plugin URI: https://ldninjas.com/ld-plugins/
 * Text Domain: ldninjas-freemius-toolkit
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * LDNFT_Freemius
 */
class LDNFT_Freemius {
    
    /**
     * @var version number
     */
    const VERSION = '1.0';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @var self
     */
    public static $products = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {
        
        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LDNFT_Freemius ) ) {
            self::$instance = new self;

            self::$instance->enable_freemius();
            self::$instance->setup_constants();
            self::$instance->connect_freemius();
            self::$instance->includes();
            
        }
        
        return self::$instance;
    }

    /**
     * Initiate freemius
     *
     * @return void
     */
    public function enable_freemius() {

        if ( ! function_exists( 'freemius_toolkit' ) ) {

            /**
             * Create a helper function for easy SDK access.
             */
            function freemius_toolkit() {
                global $freemius_toolkit;
        
                if ( ! isset( $freemius_toolkit ) ) {
                    
                    /**
                     * Include Freemius SDK.
                     */
                    require_once dirname(__FILE__) . '/freemius/start.php';
        
                    $freemius_toolkit = fs_dynamic_init( [
                        'id'                  => '12667',
                        'slug'                => 'freemius-toolkit',
                        'type'                => 'plugin',
                        'public_key'          => 'pk_30d13bc8bd91e0687bf2cb41b61c6',
                        'is_premium'          => false,
                        'has_addons'          => true,
                        'has_paid_plans'      => false,
                        'menu'                => [
                            'first-path'     => 'plugins.php',
                        ],
                    ] );
                }
        
                return $freemius_toolkit;
            }
        
            /**
             * Init Freemius.
             */
            freemius_toolkit();
            
            /**
             * Signal that SDK was initiated.
             */
            do_action( 'freemius_toolkit_loaded' );
        }
    }

    /**
     * Freemius connection constants.
     */
    private function connect_freemius () {

        global $wpdb;

        if( file_exists( LDNFT_DIR.'freemius/includes/sdk/FreemiusBase.php' ) ) {
            require_once LDNFT_DIR.'freemius/includes/sdk/FreemiusBase.php';
        }        
       
        if( file_exists( LDNFT_DIR.'freemius/includes/sdk/FreemiusWordPress.php' ) ) {
            require_once LDNFT_DIR.'freemius/includes/sdk/FreemiusWordPress.php';
        } 

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

        $fs_connection  = get_option( 'ldnft__freemius_connected' ) == 'yes'? true : false;
        $fs_has_plugins = get_option( 'ldnft__HAS_PLUGINS' ) == 'yes'? true : false;
        if( $fs_connection ) {
            $table_name = $wpdb->prefix.'ldnft_plugins';
            if( !is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
                self::$products = $wpdb->get_results( 'select id, title from '.$table_name); 
                if( is_array( self::$products ) && count( self::$products ) > 0  ) {
                    foreach( self::$products as $prd ) {
                        $settings = get_option( 'ldnft_webhook_settings_'.$prd->id );
                        if( empty( $settings ) ) {
                            update_option( 'ldnft_webhook_settings_'.$prd->id, [ 'mailpeot_list' => 0, 'disable_webhooks' => 'no', 'mailpoet_subscription' => 'yes' ] );
                        }
                    }
                }
            }
        }

        define( 'FS__API_CONNECTION', $fs_connection );
        define( 'FS__HAS_PLUGINS', $fs_has_plugins );
    }

    /**
     * Plugin Constants
    */
    private function setup_constants() {

        /**
         * Directory
        */
        define( 'LDNFT_DIR',                        plugin_dir_path ( __FILE__ ) );
        define( 'LDNFT_DIR_FILE',                   LDNFT_DIR . basename ( __FILE__ ) );
        define( 'LDNFT_INCLUDES_DIR',               trailingslashit ( LDNFT_DIR . 'includes' ) );
        define( 'LDNFT_SHORTCODES_DIR',             trailingslashit ( LDNFT_INCLUDES_DIR . 'shortcodes' ) );
        define( 'LDNFT_SHORTCODES_TEMPLATES_DIR',   trailingslashit ( LDNFT_SHORTCODES_DIR . 'templates' ) );
        define( 'LDNFT_BASE_DIR',                   plugin_basename(__FILE__));

        /**
         * URLs
        */
        define( 'LDNFT_URL',                        trailingslashit ( plugins_url ( '', __FILE__ ) ) );
        define( 'LDNFT_ASSETS_URL',                 trailingslashit ( LDNFT_URL . 'assets' ) );
        
        /**
         * Plugin version
         */
        define( 'LDNFT_VERSION', time() );

    }

    /**
     * Plugin requiered files
     */
    private function includes() {       

        if( file_exists( LDNFT_INCLUDES_DIR . 'admin/class-webhooks.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/class-webhooks.php';
        }        

        if( file_exists( LDNFT_INCLUDES_DIR . 'admin/class-crons.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/class-crons.php';
        }        
        
        if( is_admin() ) {
            
            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/settings.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/settings.php';
            }        

            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/class-admin.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/class-admin.php';
            }        
            
            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/customers/class-menu.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/customers/class-menu.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/customers/class-customers.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/customers/class-customers.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/reviews/class-menu.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/reviews/class-menu.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/reviews/class-reviews.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/reviews/class-reviews.php';
            }
            
            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/subscriptions/class-menu.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/subscriptions/class-menu.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/subscriptions/class-subscriptions.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/subscriptions/class-subscriptions.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/sales/class-menu.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/sales/class-menu.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'admin/sales/class-sales.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'admin/sales/class-sales.php';
            }
        }

        if( !is_admin() || strstr( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) ) {

            if( file_exists( LDNFT_INCLUDES_DIR . 'shortcodes/class-reviews.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'shortcodes/class-reviews.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'shortcodes/class-checkout.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'shortcodes/class-checkout.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'shortcodes/class-product-ratings.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'shortcodes/class-product-ratings.php';
            }

            if( file_exists( LDNFT_INCLUDES_DIR . 'shortcodes/class-number-of-sales.php' ) ) {
                require_once LDNFT_INCLUDES_DIR . 'shortcodes/class-number-of-sales.php';
            }
        }
    }

    /**
     * Return country list with code.
     */
    public static function get_country_name_by_code( $country_code  = 'list') {
        
        $countries = [
            'AX' => __( 'Åland Islands', 'ldninjas-freemius-toolkit' ),
            'AF' => __( 'Afghanistan', 'ldninjas-freemius-toolkit' ),
            'AL' => __( 'Albania', 'ldninjas-freemius-toolkit' ),
            'DZ' => __( 'Algeria', 'ldninjas-freemius-toolkit' ),
            'AD' => __( 'Andorra', 'ldninjas-freemius-toolkit' ),
            'AO' => __( 'Angola', 'ldninjas-freemius-toolkit' ),
            'AI' => __( 'Anguilla', 'ldninjas-freemius-toolkit' ),
            'AQ' => __( 'Antarctica', 'ldninjas-freemius-toolkit' ),
            'AG' => __( 'Antigua and Barbuda', 'ldninjas-freemius-toolkit' ),
            'AR' => __( 'Argentina', 'ldninjas-freemius-toolkit' ),
            'AM' => __( 'Armenia', 'ldninjas-freemius-toolkit' ),
            'AW' => __( 'Aruba', 'ldninjas-freemius-toolkit' ),
            'AU' => __( 'Australia', 'ldninjas-freemius-toolkit' ),
            'AT' => __( 'Austria', 'ldninjas-freemius-toolkit' ),
            'AZ' => __( 'Azerbaijan', 'ldninjas-freemius-toolkit' ),
            'BS' => __( 'Bahamas', 'ldninjas-freemius-toolkit' ),
            'BH' => __( 'Bahrain', 'ldninjas-freemius-toolkit' ),
            'BD' => __( 'Bangladesh', 'ldninjas-freemius-toolkit' ),
            'BB' => __( 'Barbados', 'ldninjas-freemius-toolkit' ),
            'BY' => __( 'Belarus', 'ldninjas-freemius-toolkit' ),
            'PW' => __( 'Belau', 'ldninjas-freemius-toolkit' ),
            'BE' => __( 'Belgium', 'ldninjas-freemius-toolkit' ),
            'BZ' => __( 'Belize', 'ldninjas-freemius-toolkit' ),
            'BJ' => __( 'Benin', 'ldninjas-freemius-toolkit' ),
            'BM' => __( 'Bermuda', 'ldninjas-freemius-toolkit' ),
            'BT' => __( 'Bhutan', 'ldninjas-freemius-toolkit' ),
            'BO' => __( 'Bolivia', 'ldninjas-freemius-toolkit' ),
            'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'ldninjas-freemius-toolkit' ),
            'BA' => __( 'Bosnia and Herzegovina', 'ldninjas-freemius-toolkit' ),
            'BW' => __( 'Botswana', 'ldninjas-freemius-toolkit' ),
            'BV' => __( 'Bouvet Island', 'ldninjas-freemius-toolkit' ),
            'BR' => __( 'Brazil', 'ldninjas-freemius-toolkit' ),
            'IO' => __( 'British Indian Ocean Territory', 'ldninjas-freemius-toolkit' ),
            'VG' => __( 'British Virgin Islands', 'ldninjas-freemius-toolkit' ),
            'BN' => __( 'Brunei', 'ldninjas-freemius-toolkit' ),
            'BG' => __( 'Bulgaria', 'ldninjas-freemius-toolkit' ),
            'BF' => __( 'Burkina Faso', 'ldninjas-freemius-toolkit' ),
            'BI' => __( 'Burundi', 'ldninjas-freemius-toolkit' ),
            'KH' => __( 'Cambodia', 'ldninjas-freemius-toolkit' ),
            'CM' => __( 'Cameroon', 'ldninjas-freemius-toolkit' ),
            'CA' => __( 'Canada', 'ldninjas-freemius-toolkit' ),
            'CV' => __( 'Cape Verde', 'ldninjas-freemius-toolkit' ),
            'KY' => __( 'Cayman Islands', 'ldninjas-freemius-toolkit' ),
            'CF' => __( 'Central African Republic', 'ldninjas-freemius-toolkit' ),
            'TD' => __( 'Chad', 'ldninjas-freemius-toolkit' ),
            'CL' => __( 'Chile', 'ldninjas-freemius-toolkit' ),
            'CN' => __( 'China', 'ldninjas-freemius-toolkit' ),
            'CX' => __( 'Christmas Island', 'ldninjas-freemius-toolkit' ),
            'CC' => __( 'Cocos (Keeling) Islands', 'ldninjas-freemius-toolkit' ),
            'CO' => __( 'Colombia', 'ldninjas-freemius-toolkit' ),
            'KM' => __( 'Comoros', 'ldninjas-freemius-toolkit' ),
            'CG' => __( 'Congo (Brazzaville)', 'ldninjas-freemius-toolkit' ),
            'CD' => __( 'Congo (Kinshasa)', 'ldninjas-freemius-toolkit' ),
            'CK' => __( 'Cook Islands', 'ldninjas-freemius-toolkit' ),
            'CR' => __( 'Costa Rica', 'ldninjas-freemius-toolkit' ),
            'HR' => __( 'Croatia', 'ldninjas-freemius-toolkit' ),
            'CU' => __( 'Cuba', 'ldninjas-freemius-toolkit' ),
            'CW' => __( 'CuraÇao', 'ldninjas-freemius-toolkit' ),
            'CY' => __( 'Cyprus', 'ldninjas-freemius-toolkit' ),
            'CZ' => __( 'Czech Republic', 'ldninjas-freemius-toolkit' ),
            'DK' => __( 'Denmark', 'ldninjas-freemius-toolkit' ),
            'DJ' => __( 'Djibouti', 'ldninjas-freemius-toolkit' ),
            'DM' => __( 'Dominica', 'ldninjas-freemius-toolkit' ),
            'DO' => __( 'Dominican Republic', 'ldninjas-freemius-toolkit' ),
            'EC' => __( 'Ecuador', 'ldninjas-freemius-toolkit' ),
            'EG' => __( 'Egypt', 'ldninjas-freemius-toolkit' ),
            'SV' => __( 'El Salvador', 'ldninjas-freemius-toolkit' ),
            'GQ' => __( 'Equatorial Guinea', 'ldninjas-freemius-toolkit' ),
            'ER' => __( 'Eritrea', 'ldninjas-freemius-toolkit' ),
            'EE' => __( 'Estonia', 'ldninjas-freemius-toolkit' ),
            'ET' => __( 'Ethiopia', 'ldninjas-freemius-toolkit' ),
            'FK' => __( 'Falkland Islands', 'ldninjas-freemius-toolkit' ),
            'FO' => __( 'Faroe Islands', 'ldninjas-freemius-toolkit' ),
            'FJ' => __( 'Fiji', 'ldninjas-freemius-toolkit' ),
            'FI' => __( 'Finland', 'ldninjas-freemius-toolkit' ),
            'FR' => __( 'France', 'ldninjas-freemius-toolkit' ),
            'GF' => __( 'French Guiana', 'ldninjas-freemius-toolkit' ),
            'PF' => __( 'French Polynesia', 'ldninjas-freemius-toolkit' ),
            'TF' => __( 'French Southern Territories', 'ldninjas-freemius-toolkit' ),
            'GA' => __( 'Gabon', 'ldninjas-freemius-toolkit' ),
            'GM' => __( 'Gambia', 'ldninjas-freemius-toolkit' ),
            'GE' => __( 'Georgia', 'ldninjas-freemius-toolkit' ),
            'DE' => __( 'Germany', 'ldninjas-freemius-toolkit' ),
            'GH' => __( 'Ghana', 'ldninjas-freemius-toolkit' ),
            'GI' => __( 'Gibraltar', 'ldninjas-freemius-toolkit' ),
            'GR' => __( 'Greece', 'ldninjas-freemius-toolkit' ),
            'GL' => __( 'Greenland', 'ldninjas-freemius-toolkit' ),
            'GD' => __( 'Grenada', 'ldninjas-freemius-toolkit' ),
            'GP' => __( 'Guadeloupe', 'ldninjas-freemius-toolkit' ),
            'GT' => __( 'Guatemala', 'ldninjas-freemius-toolkit' ),
            'GG' => __( 'Guernsey', 'ldninjas-freemius-toolkit' ),
            'GN' => __( 'Guinea', 'ldninjas-freemius-toolkit' ),
            'GW' => __( 'Guinea-Bissau', 'ldninjas-freemius-toolkit' ),
            'GY' => __( 'Guyana', 'ldninjas-freemius-toolkit' ),
            'HT' => __( 'Haiti', 'ldninjas-freemius-toolkit' ),
            'HM' => __( 'Heard Island and McDonald Islands', 'ldninjas-freemius-toolkit' ),
            'HN' => __( 'Honduras', 'ldninjas-freemius-toolkit' ),
            'HK' => __( 'Hong Kong', 'ldninjas-freemius-toolkit' ),
            'HU' => __( 'Hungary', 'ldninjas-freemius-toolkit' ),
            'IS' => __( 'Iceland', 'ldninjas-freemius-toolkit' ),
            'IN' => __( 'India', 'ldninjas-freemius-toolkit' ),
            'ID' => __( 'Indonesia', 'ldninjas-freemius-toolkit' ),
            'IR' => __( 'Iran', 'ldninjas-freemius-toolkit' ),
            'IQ' => __( 'Iraq', 'ldninjas-freemius-toolkit' ),
            'IM' => __( 'Isle of Man', 'ldninjas-freemius-toolkit' ),
            'IL' => __( 'Israel', 'ldninjas-freemius-toolkit' ),
            'IT' => __( 'Italy', 'ldninjas-freemius-toolkit' ),
            'CI' => __( 'Ivory Coast', 'ldninjas-freemius-toolkit' ),
            'JM' => __( 'Jamaica', 'ldninjas-freemius-toolkit' ),
            'JP' => __( 'Japan', 'ldninjas-freemius-toolkit' ),
            'JE' => __( 'Jersey', 'ldninjas-freemius-toolkit' ),
            'JO' => __( 'Jordan', 'ldninjas-freemius-toolkit' ),
            'KZ' => __( 'Kazakhstan', 'ldninjas-freemius-toolkit' ),
            'KE' => __( 'Kenya', 'ldninjas-freemius-toolkit' ),
            'KI' => __( 'Kiribati', 'ldninjas-freemius-toolkit' ),
            'KW' => __( 'Kuwait', 'ldninjas-freemius-toolkit' ),
            'KG' => __( 'Kyrgyzstan', 'ldninjas-freemius-toolkit' ),
            'LA' => __( 'Laos', 'ldninjas-freemius-toolkit' ),
            'LV' => __( 'Latvia', 'ldninjas-freemius-toolkit' ),
            'LB' => __( 'Lebanon', 'ldninjas-freemius-toolkit' ),
            'LS' => __( 'Lesotho', 'ldninjas-freemius-toolkit' ),
            'LR' => __( 'Liberia', 'ldninjas-freemius-toolkit' ),
            'LY' => __( 'Libya', 'ldninjas-freemius-toolkit' ),
            'LI' => __( 'Liechtenstein', 'ldninjas-freemius-toolkit' ),
            'LT' => __( 'Lithuania', 'ldninjas-freemius-toolkit' ),
            'LU' => __( 'Luxembourg', 'ldninjas-freemius-toolkit' ),
            'MO' => __( 'Macao S.A.R., China', 'ldninjas-freemius-toolkit' ),
            'MK' => __( 'Macedonia', 'ldninjas-freemius-toolkit' ),
            'MG' => __( 'Madagascar', 'ldninjas-freemius-toolkit' ),
            'MW' => __( 'Malawi', 'ldninjas-freemius-toolkit' ),
            'MY' => __( 'Malaysia', 'ldninjas-freemius-toolkit' ),
            'MV' => __( 'Maldives', 'ldninjas-freemius-toolkit' ),
            'ML' => __( 'Mali', 'ldninjas-freemius-toolkit' ),
            'MT' => __( 'Malta', 'ldninjas-freemius-toolkit' ),
            'MH' => __( 'Marshall Islands', 'ldninjas-freemius-toolkit' ),
            'MQ' => __( 'Martinique', 'ldninjas-freemius-toolkit' ),
            'MR' => __( 'Mauritania', 'ldninjas-freemius-toolkit' ),
            'MU' => __( 'Mauritius', 'ldninjas-freemius-toolkit' ),
            'YT' => __( 'Mayotte', 'ldninjas-freemius-toolkit' ),
            'MX' => __( 'Mexico', 'ldninjas-freemius-toolkit' ),
            'FM' => __( 'Micronesia', 'ldninjas-freemius-toolkit' ),
            'MD' => __( 'Moldova', 'ldninjas-freemius-toolkit' ),
            'MC' => __( 'Monaco', 'ldninjas-freemius-toolkit' ),
            'MN' => __( 'Mongolia', 'ldninjas-freemius-toolkit' ),
            'ME' => __( 'Montenegro', 'ldninjas-freemius-toolkit' ),
            'MS' => __( 'Montserrat', 'ldninjas-freemius-toolkit' ),
            'MA' => __( 'Morocco', 'ldninjas-freemius-toolkit' ),
            'MZ' => __( 'Mozambique', 'ldninjas-freemius-toolkit' ),
            'MM' => __( 'Myanmar', 'ldninjas-freemius-toolkit' ),
            'NA' => __( 'Namibia', 'ldninjas-freemius-toolkit' ),
            'NR' => __( 'Nauru', 'ldninjas-freemius-toolkit' ),
            'NP' => __( 'Nepal', 'ldninjas-freemius-toolkit' ),
            'NL' => __( 'Netherlands', 'ldninjas-freemius-toolkit' ),
            'AN' => __( 'Netherlands Antilles', 'ldninjas-freemius-toolkit' ),
            'NC' => __( 'New Caledonia', 'ldninjas-freemius-toolkit' ),
            'NZ' => __( 'New Zealand', 'ldninjas-freemius-toolkit' ),
            'NI' => __( 'Nicaragua', 'ldninjas-freemius-toolkit' ),
            'NE' => __( 'Niger', 'ldninjas-freemius-toolkit' ),
            'NG' => __( 'Nigeria', 'ldninjas-freemius-toolkit' ),
            'NU' => __( 'Niue', 'ldninjas-freemius-toolkit' ),
            'NF' => __( 'Norfolk Island', 'ldninjas-freemius-toolkit' ),
            'KP' => __( 'North Korea', 'ldninjas-freemius-toolkit' ),
            'NO' => __( 'Norway', 'ldninjas-freemius-toolkit' ),
            'OM' => __( 'Oman', 'ldninjas-freemius-toolkit' ),
            'PK' => __( 'Pakistan', 'ldninjas-freemius-toolkit' ),
            'PS' => __( 'Palestinian Territory', 'ldninjas-freemius-toolkit' ),
            'PA' => __( 'Panama', 'ldninjas-freemius-toolkit' ),
            'PG' => __( 'Papua New Guinea', 'ldninjas-freemius-toolkit' ),
            'PY' => __( 'Paraguay', 'ldninjas-freemius-toolkit' ),
            'PE' => __( 'Peru', 'ldninjas-freemius-toolkit' ),
            'PH' => __( 'Philippines', 'ldninjas-freemius-toolkit' ),
            'PN' => __( 'Pitcairn', 'ldninjas-freemius-toolkit' ),
            'PL' => __( 'Poland', 'ldninjas-freemius-toolkit' ),
            'PT' => __( 'Portugal', 'ldninjas-freemius-toolkit' ),
            'QA' => __( 'Qatar', 'ldninjas-freemius-toolkit' ),
            'IE' => __( 'Republic of Ireland', 'ldninjas-freemius-toolkit' ),
            'RE' => __( 'Reunion', 'ldninjas-freemius-toolkit' ),
            'RO' => __( 'Romania', 'ldninjas-freemius-toolkit' ),
            'RU' => __( 'Russia', 'ldninjas-freemius-toolkit' ),
            'RW' => __( 'Rwanda', 'ldninjas-freemius-toolkit' ),
            'ST' => __( 'São Tomé and Príncipe', 'ldninjas-freemius-toolkit' ),
            'BL' => __( 'Saint Barthélemy', 'ldninjas-freemius-toolkit' ),
            'SH' => __( 'Saint Helena', 'ldninjas-freemius-toolkit' ),
            'KN' => __( 'Saint Kitts and Nevis', 'ldninjas-freemius-toolkit' ),
            'LC' => __( 'Saint Lucia', 'ldninjas-freemius-toolkit' ),
            'SX' => __( 'Saint Martin (Dutch part)', 'ldninjas-freemius-toolkit' ),
            'MF' => __( 'Saint Martin (French part)', 'ldninjas-freemius-toolkit' ),
            'PM' => __( 'Saint Pierre and Miquelon', 'ldninjas-freemius-toolkit' ),
            'VC' => __( 'Saint Vincent and the Grenadines', 'ldninjas-freemius-toolkit' ),
            'SM' => __( 'San Marino', 'ldninjas-freemius-toolkit' ),
            'SA' => __( 'Saudi Arabia', 'ldninjas-freemius-toolkit' ),
            'SN' => __( 'Senegal', 'ldninjas-freemius-toolkit' ),
            'RS' => __( 'Serbia', 'ldninjas-freemius-toolkit' ),
            'SC' => __( 'Seychelles', 'ldninjas-freemius-toolkit' ),
            'SL' => __( 'Sierra Leone', 'ldninjas-freemius-toolkit' ),
            'SG' => __( 'Singapore', 'ldninjas-freemius-toolkit' ),
            'SK' => __( 'Slovakia', 'ldninjas-freemius-toolkit' ),
            'SI' => __( 'Slovenia', 'ldninjas-freemius-toolkit' ),
            'SB' => __( 'Solomon Islands', 'ldninjas-freemius-toolkit' ),
            'SO' => __( 'Somalia', 'ldninjas-freemius-toolkit' ),
            'ZA' => __( 'South Africa', 'ldninjas-freemius-toolkit' ),
            'GS' => __( 'South Georgia/Sandwich Islands', 'ldninjas-freemius-toolkit' ),
            'KR' => __( 'South Korea', 'ldninjas-freemius-toolkit' ),
            'SS' => __( 'South Sudan', 'ldninjas-freemius-toolkit' ),
            'ES' => __( 'Spain', 'ldninjas-freemius-toolkit' ),
            'LK' => __( 'Sri Lanka', 'ldninjas-freemius-toolkit' ),
            'SD' => __( 'Sudan', 'ldninjas-freemius-toolkit' ),
            'SR' => __( 'Suriname', 'ldninjas-freemius-toolkit' ),
            'SJ' => __( 'Svalbard and Jan Mayen', 'ldninjas-freemius-toolkit' ),
            'SZ' => __( 'Swaziland', 'ldninjas-freemius-toolkit' ),
            'SE' => __( 'Sweden', 'ldninjas-freemius-toolkit' ),
            'CH' => __( 'Switzerland', 'ldninjas-freemius-toolkit' ),
            'SY' => __( 'Syria', 'ldninjas-freemius-toolkit' ),
            'TW' => __( 'Taiwan', 'ldninjas-freemius-toolkit' ),
            'TJ' => __( 'Tajikistan', 'ldninjas-freemius-toolkit' ),
            'TZ' => __( 'Tanzania', 'ldninjas-freemius-toolkit' ),
            'TH' => __( 'Thailand', 'ldninjas-freemius-toolkit' ),
            'TL' => __( 'Timor-Leste', 'ldninjas-freemius-toolkit' ),
            'TG' => __( 'Togo', 'ldninjas-freemius-toolkit' ),
            'TK' => __( 'Tokelau', 'ldninjas-freemius-toolkit' ),
            'TO' => __( 'Tonga', 'ldninjas-freemius-toolkit' ),
            'TT' => __( 'Trinidad and Tobago', 'ldninjas-freemius-toolkit' ),
            'TN' => __( 'Tunisia', 'ldninjas-freemius-toolkit' ),
            'TR' => __( 'Turkey', 'ldninjas-freemius-toolkit' ),
            'TM' => __( 'Turkmenistan', 'ldninjas-freemius-toolkit' ),
            'TC' => __( 'Turks and Caicos Islands', 'ldninjas-freemius-toolkit' ),
            'TV' => __( 'Tuvalu', 'ldninjas-freemius-toolkit' ),
            'UG' => __( 'Uganda', 'ldninjas-freemius-toolkit' ),
            'UA' => __( 'Ukraine', 'ldninjas-freemius-toolkit' ),
            'AE' => __( 'United Arab Emirates', 'ldninjas-freemius-toolkit' ),
            'GB' => __( 'United Kingdom (UK)', 'ldninjas-freemius-toolkit' ),
            'US' => __( 'United States (US)', 'ldninjas-freemius-toolkit' ),
            'UY' => __( 'Uruguay', 'ldninjas-freemius-toolkit' ),
            'UZ' => __( 'Uzbekistan', 'ldninjas-freemius-toolkit' ),
            'VU' => __( 'Vanuatu', 'ldninjas-freemius-toolkit' ),
            'VA' => __( 'Vatican', 'ldninjas-freemius-toolkit' ),
            'VE' => __( 'Venezuela', 'ldninjas-freemius-toolkit' ),
            'VN' => __( 'Vietnam', 'ldninjas-freemius-toolkit' ),
            'WF' => __( 'Wallis and Futuna', 'ldninjas-freemius-toolkit' ),
            'EH' => __( 'Western Sahara', 'ldninjas-freemius-toolkit' ),
            'WS' => __( 'Western Samoa', 'ldninjas-freemius-toolkit' ),
            'YE' => __( 'Yemen', 'ldninjas-freemius-toolkit' ),
            'ZM' => __( 'Zambia', 'ldninjas-freemius-toolkit' ),
            'ZW' => __( 'Zimbabwe', 'ldninjas-freemius-toolkit' ),
        ];

        if( $country_code == 'list' ) {
            return $countries;
        } else {
            $countries = apply_filters( 'ldnft_countries', $countries,  $country_code );

            return ( isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : false );
        }
        
    }
}

/**
 * Retrieve the plugin object
 * @return bool
 */
return LDNFT_Freemius::instance();