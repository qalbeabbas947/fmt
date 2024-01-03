<?php
/**
 * Checkout Shortcode class
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class LDNFT_Checkout_Shortcode
 */
class LDNFT_Checkout_Shortcode {

    /**
     * Class instance
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LDNFT_Checkout_Shortcode ) ) {

            self::$instance = new self;

            self::$instance->hooks();
        }
        
        return self::$instance;
    }

    /**
     * Define hooks
     */
    private function hooks() {
        
        add_shortcode( 'ldnft_checkout', [ $this, 'checkout_shortcode_cb' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_scripts' ] );
    }

    /**
     * Enqueue frontend scripte
     */
    public function enqueue_front_scripts() {

        global $post;
        if($post) {
            if( !has_shortcode( $post->post_content, 'ldnft_checkout' ) ) {
                return false;
            }

            /**
             * Enqueue frontend css
             */
            wp_enqueue_style( 'dashicons' );
            wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend/frontend.css', [], LDNFT_VERSION, null );
            
            /**
             * Enqueue frontend js
             */
            wp_enqueue_script( 'jquery' ); 
            wp_enqueue_script( 'ldnft-checkout.freemius.com-js', 'https://checkout.freemius.com/checkout.min.js', [ 'jquery' ], LDNFT_VERSION, true ); 
            wp_enqueue_script( 'ldnft-frontend-checkout-js', LDNFT_ASSETS_URL . 'js/frontend/checkout.js', [ 'jquery', 'ldnft-checkout.freemius.com-js' ], LDNFT_VERSION, true ); 
            
            wp_localize_script( 'ldnft-frontend-js', 'LDNFT', [ 
                'ajaxURL' => admin_url( 'admin-ajax.php' ),
            ] );
        }
    }

    /**
     * Create shorcode to display reset progress option
     * 
     * @param $atts
     */
    public function checkout_shortcode_cb( $atts ) {
        
        $attributes = shortcode_atts( array(
            'product_id' => 0,
            'plan_id'   => 0,
            'display'   => 'detailed',
            'image' => ''
        ), $atts );

        $plugin_id  = sanitize_text_field( $attributes['product_id'] );
        $plan_id    = sanitize_text_field( $attributes['plan_id'] );
        $display    = sanitize_text_field( $attributes['display'] );

        if( empty( $plugin_id ) || intval( $plugin_id ) < 1 ) {
            return '<div class="ldnft-error-message">'.__( 'Product ID is a required parameter.', 'ldninjas-freemius-toolkit' ).'</div>';
        }

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        if( $plan_id == 0 ) {
            
            $result = $api->Api( 'plugins/' . $plugin_id . '/plans.json', 'GET', [] );
            if( !isset( $result->plans ) || !is_array( $result->plans ) || count( $result->plans ) == 0 ) {
                return '<div class="ldnft-error-message">'.__('Please, configure a plan before visiting this page.', 'ldninjas-freemius-toolkit').'</div>';
            }

            $plan = $result->plans[0];
            $plan_id = $plan->id;
        }
       
        $presult = $api->Api( 'plugins/'. $plugin_id .'/plans/'.$plan_id.'/pricing.json', 'GET', [] );
        if( !isset( $presult->pricing ) || !is_array( $presult->pricing ) || count( $presult->pricing ) == 0 ) {

            return '<div class="ldnft-error-message">'.__( 'Please configure the product pricing before visiting this page.', 'ldninjas-freemius-toolkit' ).'</div>';
        }

        $ldnft_settings = get_option( 'ldnft_settings' ); 
        $public_key     = isset( $ldnft_settings['public_key'] ) ? sanitize_text_field( $ldnft_settings['public_key'] ): '';

        ob_start();
        
        include( LDNFT_SHORTCODES_TEMPLATES_DIR . 'checkout.php' );

        $content = ob_get_contents();
        ob_get_clean();

        return $content;
    }
}

/**
 * Class instance.
 */
LDNFT_Checkout_Shortcode::instance();