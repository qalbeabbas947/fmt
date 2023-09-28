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
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LDNFT_Freemius ) ) {
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
        
                    $test_freemius_addon = fs_dynamic_init( [
                        'id'                  => '12667',
                        'slug'                => 'coordinator-course-reset',
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
        
        if( file_exists( LDNFT_INCLUDES_DIR .'admin/customers/class-menu.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/customers/class-menu.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/customers/class-customers.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/customers/class-customers.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/reviews/class-menu.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/reviews/class-menu.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/reviews/class-reviews.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/reviews/class-reviews.php';
        }
        
        if( file_exists( LDNFT_INCLUDES_DIR .'admin/subscriptions/class-menu.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/subscriptions/class-menu.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/subscriptions/class-subscriptions.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/subscriptions/class-subscriptions.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/sales/class-menu.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/sales/class-menu.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR .'admin/sales/class-sales.php' ) ) {
            require_once LDNFT_INCLUDES_DIR . 'admin/sales/class-sales.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR.'shortcodes/class-reviews.php' ) ) {
            require_once LDNFT_INCLUDES_DIR.'shortcodes/class-reviews.php';
        }

        if( file_exists( LDNFT_INCLUDES_DIR.'shortcodes/class-sales.php' ) ) {
            require_once LDNFT_INCLUDES_DIR.'shortcodes/class-sales.php';
        }
    }

    public static function get_country_name_by_code( $country_code ) {
        
        $countries = [
            'AX' => __( 'Åland Islands', LDNFT_TEXT_DOMAIN ),
            'AF' => __( 'Afghanistan', LDNFT_TEXT_DOMAIN ),
            'AL' => __( 'Albania', LDNFT_TEXT_DOMAIN ),
            'DZ' => __( 'Algeria', LDNFT_TEXT_DOMAIN ),
            'AD' => __( 'Andorra', LDNFT_TEXT_DOMAIN ),
            'AO' => __( 'Angola', LDNFT_TEXT_DOMAIN ),
            'AI' => __( 'Anguilla', LDNFT_TEXT_DOMAIN ),
            'AQ' => __( 'Antarctica', LDNFT_TEXT_DOMAIN ),
            'AG' => __( 'Antigua and Barbuda', LDNFT_TEXT_DOMAIN ),
            'AR' => __( 'Argentina', LDNFT_TEXT_DOMAIN ),
            'AM' => __( 'Armenia', LDNFT_TEXT_DOMAIN ),
            'AW' => __( 'Aruba', LDNFT_TEXT_DOMAIN ),
            'AU' => __( 'Australia', LDNFT_TEXT_DOMAIN ),
            'AT' => __( 'Austria', LDNFT_TEXT_DOMAIN ),
            'AZ' => __( 'Azerbaijan', LDNFT_TEXT_DOMAIN ),
            'BS' => __( 'Bahamas', LDNFT_TEXT_DOMAIN ),
            'BH' => __( 'Bahrain', LDNFT_TEXT_DOMAIN ),
            'BD' => __( 'Bangladesh', LDNFT_TEXT_DOMAIN ),
            'BB' => __( 'Barbados', LDNFT_TEXT_DOMAIN ),
            'BY' => __( 'Belarus', LDNFT_TEXT_DOMAIN ),
            'PW' => __( 'Belau', LDNFT_TEXT_DOMAIN ),
            'BE' => __( 'Belgium', LDNFT_TEXT_DOMAIN ),
            'BZ' => __( 'Belize', LDNFT_TEXT_DOMAIN ),
            'BJ' => __( 'Benin', LDNFT_TEXT_DOMAIN ),
            'BM' => __( 'Bermuda', LDNFT_TEXT_DOMAIN ),
            'BT' => __( 'Bhutan', LDNFT_TEXT_DOMAIN ),
            'BO' => __( 'Bolivia', LDNFT_TEXT_DOMAIN ),
            'BQ' => __( 'Bonaire, Saint Eustatius and Saba', LDNFT_TEXT_DOMAIN ),
            'BA' => __( 'Bosnia and Herzegovina', LDNFT_TEXT_DOMAIN ),
            'BW' => __( 'Botswana', LDNFT_TEXT_DOMAIN ),
            'BV' => __( 'Bouvet Island', LDNFT_TEXT_DOMAIN ),
            'BR' => __( 'Brazil', LDNFT_TEXT_DOMAIN ),
            'IO' => __( 'British Indian Ocean Territory', LDNFT_TEXT_DOMAIN ),
            'VG' => __( 'British Virgin Islands', LDNFT_TEXT_DOMAIN ),
            'BN' => __( 'Brunei', LDNFT_TEXT_DOMAIN ),
            'BG' => __( 'Bulgaria', LDNFT_TEXT_DOMAIN ),
            'BF' => __( 'Burkina Faso', LDNFT_TEXT_DOMAIN ),
            'BI' => __( 'Burundi', LDNFT_TEXT_DOMAIN ),
            'KH' => __( 'Cambodia', LDNFT_TEXT_DOMAIN ),
            'CM' => __( 'Cameroon', LDNFT_TEXT_DOMAIN ),
            'CA' => __( 'Canada', LDNFT_TEXT_DOMAIN ),
            'CV' => __( 'Cape Verde', LDNFT_TEXT_DOMAIN ),
            'KY' => __( 'Cayman Islands', LDNFT_TEXT_DOMAIN ),
            'CF' => __( 'Central African Republic', LDNFT_TEXT_DOMAIN ),
            'TD' => __( 'Chad', LDNFT_TEXT_DOMAIN ),
            'CL' => __( 'Chile', LDNFT_TEXT_DOMAIN ),
            'CN' => __( 'China', LDNFT_TEXT_DOMAIN ),
            'CX' => __( 'Christmas Island', LDNFT_TEXT_DOMAIN ),
            'CC' => __( 'Cocos (Keeling) Islands', LDNFT_TEXT_DOMAIN ),
            'CO' => __( 'Colombia', LDNFT_TEXT_DOMAIN ),
            'KM' => __( 'Comoros', LDNFT_TEXT_DOMAIN ),
            'CG' => __( 'Congo (Brazzaville)', LDNFT_TEXT_DOMAIN ),
            'CD' => __( 'Congo (Kinshasa)', LDNFT_TEXT_DOMAIN ),
            'CK' => __( 'Cook Islands', LDNFT_TEXT_DOMAIN ),
            'CR' => __( 'Costa Rica', LDNFT_TEXT_DOMAIN ),
            'HR' => __( 'Croatia', LDNFT_TEXT_DOMAIN ),
            'CU' => __( 'Cuba', LDNFT_TEXT_DOMAIN ),
            'CW' => __( 'CuraÇao', LDNFT_TEXT_DOMAIN ),
            'CY' => __( 'Cyprus', LDNFT_TEXT_DOMAIN ),
            'CZ' => __( 'Czech Republic', LDNFT_TEXT_DOMAIN ),
            'DK' => __( 'Denmark', LDNFT_TEXT_DOMAIN ),
            'DJ' => __( 'Djibouti', LDNFT_TEXT_DOMAIN ),
            'DM' => __( 'Dominica', LDNFT_TEXT_DOMAIN ),
            'DO' => __( 'Dominican Republic', LDNFT_TEXT_DOMAIN ),
            'EC' => __( 'Ecuador', LDNFT_TEXT_DOMAIN ),
            'EG' => __( 'Egypt', LDNFT_TEXT_DOMAIN ),
            'SV' => __( 'El Salvador', LDNFT_TEXT_DOMAIN ),
            'GQ' => __( 'Equatorial Guinea', LDNFT_TEXT_DOMAIN ),
            'ER' => __( 'Eritrea', LDNFT_TEXT_DOMAIN ),
            'EE' => __( 'Estonia', LDNFT_TEXT_DOMAIN ),
            'ET' => __( 'Ethiopia', LDNFT_TEXT_DOMAIN ),
            'FK' => __( 'Falkland Islands', LDNFT_TEXT_DOMAIN ),
            'FO' => __( 'Faroe Islands', LDNFT_TEXT_DOMAIN ),
            'FJ' => __( 'Fiji', LDNFT_TEXT_DOMAIN ),
            'FI' => __( 'Finland', LDNFT_TEXT_DOMAIN ),
            'FR' => __( 'France', LDNFT_TEXT_DOMAIN ),
            'GF' => __( 'French Guiana', LDNFT_TEXT_DOMAIN ),
            'PF' => __( 'French Polynesia', LDNFT_TEXT_DOMAIN ),
            'TF' => __( 'French Southern Territories', LDNFT_TEXT_DOMAIN ),
            'GA' => __( 'Gabon', LDNFT_TEXT_DOMAIN ),
            'GM' => __( 'Gambia', LDNFT_TEXT_DOMAIN ),
            'GE' => __( 'Georgia', LDNFT_TEXT_DOMAIN ),
            'DE' => __( 'Germany', LDNFT_TEXT_DOMAIN ),
            'GH' => __( 'Ghana', LDNFT_TEXT_DOMAIN ),
            'GI' => __( 'Gibraltar', LDNFT_TEXT_DOMAIN ),
            'GR' => __( 'Greece', LDNFT_TEXT_DOMAIN ),
            'GL' => __( 'Greenland', LDNFT_TEXT_DOMAIN ),
            'GD' => __( 'Grenada', LDNFT_TEXT_DOMAIN ),
            'GP' => __( 'Guadeloupe', LDNFT_TEXT_DOMAIN ),
            'GT' => __( 'Guatemala', LDNFT_TEXT_DOMAIN ),
            'GG' => __( 'Guernsey', LDNFT_TEXT_DOMAIN ),
            'GN' => __( 'Guinea', LDNFT_TEXT_DOMAIN ),
            'GW' => __( 'Guinea-Bissau', LDNFT_TEXT_DOMAIN ),
            'GY' => __( 'Guyana', LDNFT_TEXT_DOMAIN ),
            'HT' => __( 'Haiti', LDNFT_TEXT_DOMAIN ),
            'HM' => __( 'Heard Island and McDonald Islands', LDNFT_TEXT_DOMAIN ),
            'HN' => __( 'Honduras', LDNFT_TEXT_DOMAIN ),
            'HK' => __( 'Hong Kong', LDNFT_TEXT_DOMAIN ),
            'HU' => __( 'Hungary', LDNFT_TEXT_DOMAIN ),
            'IS' => __( 'Iceland', LDNFT_TEXT_DOMAIN ),
            'IN' => __( 'India', LDNFT_TEXT_DOMAIN ),
            'ID' => __( 'Indonesia', LDNFT_TEXT_DOMAIN ),
            'IR' => __( 'Iran', LDNFT_TEXT_DOMAIN ),
            'IQ' => __( 'Iraq', LDNFT_TEXT_DOMAIN ),
            'IM' => __( 'Isle of Man', LDNFT_TEXT_DOMAIN ),
            'IL' => __( 'Israel', LDNFT_TEXT_DOMAIN ),
            'IT' => __( 'Italy', LDNFT_TEXT_DOMAIN ),
            'CI' => __( 'Ivory Coast', LDNFT_TEXT_DOMAIN ),
            'JM' => __( 'Jamaica', LDNFT_TEXT_DOMAIN ),
            'JP' => __( 'Japan', LDNFT_TEXT_DOMAIN ),
            'JE' => __( 'Jersey', LDNFT_TEXT_DOMAIN ),
            'JO' => __( 'Jordan', LDNFT_TEXT_DOMAIN ),
            'KZ' => __( 'Kazakhstan', LDNFT_TEXT_DOMAIN ),
            'KE' => __( 'Kenya', LDNFT_TEXT_DOMAIN ),
            'KI' => __( 'Kiribati', LDNFT_TEXT_DOMAIN ),
            'KW' => __( 'Kuwait', LDNFT_TEXT_DOMAIN ),
            'KG' => __( 'Kyrgyzstan', LDNFT_TEXT_DOMAIN ),
            'LA' => __( 'Laos', LDNFT_TEXT_DOMAIN ),
            'LV' => __( 'Latvia', LDNFT_TEXT_DOMAIN ),
            'LB' => __( 'Lebanon', LDNFT_TEXT_DOMAIN ),
            'LS' => __( 'Lesotho', LDNFT_TEXT_DOMAIN ),
            'LR' => __( 'Liberia', LDNFT_TEXT_DOMAIN ),
            'LY' => __( 'Libya', LDNFT_TEXT_DOMAIN ),
            'LI' => __( 'Liechtenstein', LDNFT_TEXT_DOMAIN ),
            'LT' => __( 'Lithuania', LDNFT_TEXT_DOMAIN ),
            'LU' => __( 'Luxembourg', LDNFT_TEXT_DOMAIN ),
            'MO' => __( 'Macao S.A.R., China', LDNFT_TEXT_DOMAIN ),
            'MK' => __( 'Macedonia', LDNFT_TEXT_DOMAIN ),
            'MG' => __( 'Madagascar', LDNFT_TEXT_DOMAIN ),
            'MW' => __( 'Malawi', LDNFT_TEXT_DOMAIN ),
            'MY' => __( 'Malaysia', LDNFT_TEXT_DOMAIN ),
            'MV' => __( 'Maldives', LDNFT_TEXT_DOMAIN ),
            'ML' => __( 'Mali', LDNFT_TEXT_DOMAIN ),
            'MT' => __( 'Malta', LDNFT_TEXT_DOMAIN ),
            'MH' => __( 'Marshall Islands', LDNFT_TEXT_DOMAIN ),
            'MQ' => __( 'Martinique', LDNFT_TEXT_DOMAIN ),
            'MR' => __( 'Mauritania', LDNFT_TEXT_DOMAIN ),
            'MU' => __( 'Mauritius', LDNFT_TEXT_DOMAIN ),
            'YT' => __( 'Mayotte', LDNFT_TEXT_DOMAIN ),
            'MX' => __( 'Mexico', LDNFT_TEXT_DOMAIN ),
            'FM' => __( 'Micronesia', LDNFT_TEXT_DOMAIN ),
            'MD' => __( 'Moldova', LDNFT_TEXT_DOMAIN ),
            'MC' => __( 'Monaco', LDNFT_TEXT_DOMAIN ),
            'MN' => __( 'Mongolia', LDNFT_TEXT_DOMAIN ),
            'ME' => __( 'Montenegro', LDNFT_TEXT_DOMAIN ),
            'MS' => __( 'Montserrat', LDNFT_TEXT_DOMAIN ),
            'MA' => __( 'Morocco', LDNFT_TEXT_DOMAIN ),
            'MZ' => __( 'Mozambique', LDNFT_TEXT_DOMAIN ),
            'MM' => __( 'Myanmar', LDNFT_TEXT_DOMAIN ),
            'NA' => __( 'Namibia', LDNFT_TEXT_DOMAIN ),
            'NR' => __( 'Nauru', LDNFT_TEXT_DOMAIN ),
            'NP' => __( 'Nepal', LDNFT_TEXT_DOMAIN ),
            'NL' => __( 'Netherlands', LDNFT_TEXT_DOMAIN ),
            'AN' => __( 'Netherlands Antilles', LDNFT_TEXT_DOMAIN ),
            'NC' => __( 'New Caledonia', LDNFT_TEXT_DOMAIN ),
            'NZ' => __( 'New Zealand', LDNFT_TEXT_DOMAIN ),
            'NI' => __( 'Nicaragua', LDNFT_TEXT_DOMAIN ),
            'NE' => __( 'Niger', LDNFT_TEXT_DOMAIN ),
            'NG' => __( 'Nigeria', LDNFT_TEXT_DOMAIN ),
            'NU' => __( 'Niue', LDNFT_TEXT_DOMAIN ),
            'NF' => __( 'Norfolk Island', LDNFT_TEXT_DOMAIN ),
            'KP' => __( 'North Korea', LDNFT_TEXT_DOMAIN ),
            'NO' => __( 'Norway', LDNFT_TEXT_DOMAIN ),
            'OM' => __( 'Oman', LDNFT_TEXT_DOMAIN ),
            'PK' => __( 'Pakistan', LDNFT_TEXT_DOMAIN ),
            'PS' => __( 'Palestinian Territory', LDNFT_TEXT_DOMAIN ),
            'PA' => __( 'Panama', LDNFT_TEXT_DOMAIN ),
            'PG' => __( 'Papua New Guinea', LDNFT_TEXT_DOMAIN ),
            'PY' => __( 'Paraguay', LDNFT_TEXT_DOMAIN ),
            'PE' => __( 'Peru', LDNFT_TEXT_DOMAIN ),
            'PH' => __( 'Philippines', LDNFT_TEXT_DOMAIN ),
            'PN' => __( 'Pitcairn', LDNFT_TEXT_DOMAIN ),
            'PL' => __( 'Poland', LDNFT_TEXT_DOMAIN ),
            'PT' => __( 'Portugal', LDNFT_TEXT_DOMAIN ),
            'QA' => __( 'Qatar', LDNFT_TEXT_DOMAIN ),
            'IE' => __( 'Republic of Ireland', LDNFT_TEXT_DOMAIN ),
            'RE' => __( 'Reunion', LDNFT_TEXT_DOMAIN ),
            'RO' => __( 'Romania', LDNFT_TEXT_DOMAIN ),
            'RU' => __( 'Russia', LDNFT_TEXT_DOMAIN ),
            'RW' => __( 'Rwanda', LDNFT_TEXT_DOMAIN ),
            'ST' => __( 'São Tomé and Príncipe', LDNFT_TEXT_DOMAIN ),
            'BL' => __( 'Saint Barthélemy', LDNFT_TEXT_DOMAIN ),
            'SH' => __( 'Saint Helena', LDNFT_TEXT_DOMAIN ),
            'KN' => __( 'Saint Kitts and Nevis', LDNFT_TEXT_DOMAIN ),
            'LC' => __( 'Saint Lucia', LDNFT_TEXT_DOMAIN ),
            'SX' => __( 'Saint Martin (Dutch part)', LDNFT_TEXT_DOMAIN ),
            'MF' => __( 'Saint Martin (French part)', LDNFT_TEXT_DOMAIN ),
            'PM' => __( 'Saint Pierre and Miquelon', LDNFT_TEXT_DOMAIN ),
            'VC' => __( 'Saint Vincent and the Grenadines', LDNFT_TEXT_DOMAIN ),
            'SM' => __( 'San Marino', LDNFT_TEXT_DOMAIN ),
            'SA' => __( 'Saudi Arabia', LDNFT_TEXT_DOMAIN ),
            'SN' => __( 'Senegal', LDNFT_TEXT_DOMAIN ),
            'RS' => __( 'Serbia', LDNFT_TEXT_DOMAIN ),
            'SC' => __( 'Seychelles', LDNFT_TEXT_DOMAIN ),
            'SL' => __( 'Sierra Leone', LDNFT_TEXT_DOMAIN ),
            'SG' => __( 'Singapore', LDNFT_TEXT_DOMAIN ),
            'SK' => __( 'Slovakia', LDNFT_TEXT_DOMAIN ),
            'SI' => __( 'Slovenia', LDNFT_TEXT_DOMAIN ),
            'SB' => __( 'Solomon Islands', LDNFT_TEXT_DOMAIN ),
            'SO' => __( 'Somalia', LDNFT_TEXT_DOMAIN ),
            'ZA' => __( 'South Africa', LDNFT_TEXT_DOMAIN ),
            'GS' => __( 'South Georgia/Sandwich Islands', LDNFT_TEXT_DOMAIN ),
            'KR' => __( 'South Korea', LDNFT_TEXT_DOMAIN ),
            'SS' => __( 'South Sudan', LDNFT_TEXT_DOMAIN ),
            'ES' => __( 'Spain', LDNFT_TEXT_DOMAIN ),
            'LK' => __( 'Sri Lanka', LDNFT_TEXT_DOMAIN ),
            'SD' => __( 'Sudan', LDNFT_TEXT_DOMAIN ),
            'SR' => __( 'Suriname', LDNFT_TEXT_DOMAIN ),
            'SJ' => __( 'Svalbard and Jan Mayen', LDNFT_TEXT_DOMAIN ),
            'SZ' => __( 'Swaziland', LDNFT_TEXT_DOMAIN ),
            'SE' => __( 'Sweden', LDNFT_TEXT_DOMAIN ),
            'CH' => __( 'Switzerland', LDNFT_TEXT_DOMAIN ),
            'SY' => __( 'Syria', LDNFT_TEXT_DOMAIN ),
            'TW' => __( 'Taiwan', LDNFT_TEXT_DOMAIN ),
            'TJ' => __( 'Tajikistan', LDNFT_TEXT_DOMAIN ),
            'TZ' => __( 'Tanzania', LDNFT_TEXT_DOMAIN ),
            'TH' => __( 'Thailand', LDNFT_TEXT_DOMAIN ),
            'TL' => __( 'Timor-Leste', LDNFT_TEXT_DOMAIN ),
            'TG' => __( 'Togo', LDNFT_TEXT_DOMAIN ),
            'TK' => __( 'Tokelau', LDNFT_TEXT_DOMAIN ),
            'TO' => __( 'Tonga', LDNFT_TEXT_DOMAIN ),
            'TT' => __( 'Trinidad and Tobago', LDNFT_TEXT_DOMAIN ),
            'TN' => __( 'Tunisia', LDNFT_TEXT_DOMAIN ),
            'TR' => __( 'Turkey', LDNFT_TEXT_DOMAIN ),
            'TM' => __( 'Turkmenistan', LDNFT_TEXT_DOMAIN ),
            'TC' => __( 'Turks and Caicos Islands', LDNFT_TEXT_DOMAIN ),
            'TV' => __( 'Tuvalu', LDNFT_TEXT_DOMAIN ),
            'UG' => __( 'Uganda', LDNFT_TEXT_DOMAIN ),
            'UA' => __( 'Ukraine', LDNFT_TEXT_DOMAIN ),
            'AE' => __( 'United Arab Emirates', LDNFT_TEXT_DOMAIN ),
            'GB' => __( 'United Kingdom (UK)', LDNFT_TEXT_DOMAIN ),
            'US' => __( 'United States (US)', LDNFT_TEXT_DOMAIN ),
            'UY' => __( 'Uruguay', LDNFT_TEXT_DOMAIN ),
            'UZ' => __( 'Uzbekistan', LDNFT_TEXT_DOMAIN ),
            'VU' => __( 'Vanuatu', LDNFT_TEXT_DOMAIN ),
            'VA' => __( 'Vatican', LDNFT_TEXT_DOMAIN ),
            'VE' => __( 'Venezuela', LDNFT_TEXT_DOMAIN ),
            'VN' => __( 'Vietnam', LDNFT_TEXT_DOMAIN ),
            'WF' => __( 'Wallis and Futuna', LDNFT_TEXT_DOMAIN ),
            'EH' => __( 'Western Sahara', LDNFT_TEXT_DOMAIN ),
            'WS' => __( 'Western Samoa', LDNFT_TEXT_DOMAIN ),
            'YE' => __( 'Yemen', LDNFT_TEXT_DOMAIN ),
            'ZM' => __( 'Zambia', LDNFT_TEXT_DOMAIN ),
            'ZW' => __( 'Zimbabwe', LDNFT_TEXT_DOMAIN ),
        ];

        $countries = apply_filters( 'ldnft_countries', $countries,  $country_code );

        return ( isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : false );
    }
}

/**
 * Retrieve the plugin object
 * @return bool
 */
function LDNFT() {

    return LDNFT_Freemius::instance();
}
add_action( 'plugins_loaded', 'LDNFT' );