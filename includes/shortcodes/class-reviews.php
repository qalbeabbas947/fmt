<?php
/**
 * LDNFT_Reviews shortcode class
 */
if( ! defined( 'ABSPATH' ) ) exit;

/**
 * LDNFT_Reviews_Shortcode
 */
class LDNFT_Reviews_Shortcode {

    /**
     * Class instance
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) ) {

            self::$instance = new self;

            self::$instance->hooks();
        }
        
        return self::$instance;
    }

    /**
     * Define hooks
     */
    private function hooks() {
        
        add_action( 'wp_ajax_ldnft_load_reviews', [ $this, 'load_reviews' ], 100 );
        add_shortcode( 'ldnft_reviews', [ $this, 'reviews_shortcode_cb' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_scripts' ] );
    }

    /**
     * Enqueue frontend scripte
     */
    public function enqueue_front_scripts() {

        global $post;
        
        if( !has_shortcode( $post->post_content, 'ldnft_reviews' ) ) {
            return false;
        }

        /**
         * Enqueue frontend css
         */
        wp_enqueue_style( 'dashicons' );
        // wp_enqueue_style( 'ldnft-bxslider-css', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.css', [], LDNFT_VERSION, null );
        // wp_enqueue_style( 'ldnft-lightbox-css', LDNFT_ASSETS_URL . 'lightbox/css/lightbox.min.css', [], LDNFT_VERSION, null );
        wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend.css', [], LDNFT_VERSION, null );
        
        // wp_enqueue_script( 'jquery' );
        // wp_enqueue_script('ldnft-bxslider-js', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js', [], LDNFT_VERSION, true );
        // wp_enqueue_script('ldnft-lightbox-js', LDNFT_ASSETS_URL.'lightbox/js/lightbox-plus-jquery.min.js', ['jquery'], LDNFT_VERSION, true );
        // wp_enqueue_script('ldnft-frontend-js', LDNFT_ASSETS_URL.'js/frontend.js', ['jquery'], LDNFT_VERSION, true );
        
        // wp_localize_script( 'ldnft-frontend-js', 'LDNFT', [ 
        //     'ajaxURL' => admin_url( 'admin-ajax.php' ),
        // ] );
    }

    /**
     * Return plugin reviews
     * return false on error
     */
    public static function get_reviews( $plugin_id = 0, $per_page = 10, $offset = 0 ) {
        
        global $wpdb;

        $plugin_id      = intval( $plugin_id );
        $per_page       = intval( $per_page );
        $offset         = intval( $offset );
        
        $table_name = $wpdb->prefix.'ldnft_reviews'; 
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT name,rate,title,text,profile_url FROM $table_name where plugin_id = %d AND is_featured = 1 ORDER BY id LIMIT %d OFFSET %d", $plugin_id, $per_page, $offset ) );
        
        if( count( $results ) > 0 ) {
            return $results;
        }

        return false;
    }

    /**
     * Create shorcode to display reset progress option
     * 
     * @param $atts
     */
    public function reviews_shortcode_cb( $atts ) {
        
        $attributes = shortcode_atts( array(
            'product_id' => 0,
            'listing_type'   => 'pagination',
            'limit'   => 10
        ), $atts );
        
        $product_id = $atts['product_id'];
        $listing_type = $atts['listing_type'];
        $limit = $atts['limit'];

        ob_start();
        include( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews.php' );
        $content = ob_get_contents();
        ob_get_clean();
        
        return $content;
    }
}

LDNFT_Reviews_Shortcode::instance();