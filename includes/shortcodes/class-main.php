<?php
/**
 * LDMFT template for front-end shortcodes
 *
 * Do not allow directly accessing this file.
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class LDMFT_Sales
 */
class LDMFT_Main_Shortcode {

    /**
     * Define hooks
     */
    private function __construct() {
        
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_scripts' ] );
    }

    /**
     * Enqueue frontend scripte
     */
    public function enqueue_front_scripts() {

        /**
         * Enqueue frontend css
         */
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'ldmft-jqueryui-css', 'https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css', [], LDNFT_VERSION, null );
        wp_enqueue_style( 'ldmft-front-css', LDNFT_ASSETS_URL . 'css/frontend.css', [], LDNFT_VERSION, null );
        
        /**
         * Enqueue frontend js
         */
        wp_enqueue_script('ldmft-jqueryui-js', 'https://code.jquery.com/ui/1.10.4/jquery-ui.js', ['jquery'], LDNFT_VERSION, true);
        wp_enqueue_script( 'ldmft-frontend-js', LDNFT_ASSETS_URL . 'js/frontend.js', [ 'jquery' ], LDNFT_VERSION, true ); 
        
        wp_localize_script( 'ldmft-backend-js', 'LDNFT', array( 
            'ajaxURL' => admin_url( 'admin-ajax.php' ),
        ) );
    }
}