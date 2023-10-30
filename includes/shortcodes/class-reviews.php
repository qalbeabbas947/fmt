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
        add_shortcode( 'LDNFT_Reviews', [ $this, 'reviews_shortcode_cb' ] );
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
        wp_enqueue_style( 'ldnft-font-awesome-css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [], LDNFT_VERSION, null );
        wp_enqueue_style( 'ldnft-bxslider-css', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.css', [], LDNFT_VERSION, null );
        wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend.css', [], LDNFT_VERSION, null );
        wp_enqueue_script('ldnft-bxslider-js', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js', ['jquery'], LDNFT_VERSION, true);
    }

    /**
     * Enqueue frontend scripte
     */
    public function load_reviews() {
        
        global $wpdb;

        $plugin_id      = sanitize_text_field($_POST['plugin_id']);
        $per_page       = sanitize_text_field($_POST['per_page']);
        $listing_type   = sanitize_text_field($_POST['type']); //pagination, onetime, slider
        $offset         = sanitize_text_field($_POST['offset']);
        
        $table_name = $wpdb->prefix.'ldnft_reviews r inner join '.$wpdb->prefix.'ldnft_customers c on (r.user_id=c.id)'; 
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT r.*, c.email as useremail FROM $table_name where is_featured = 1 and r.plugin_id = %d ORDER BY r.id LIMIT %d OFFSET %d", $plugin_id, $per_page, $offset ) );
        
        if( is_array($results) && count( $results ) > 0 ) {
            switch( $listing_type ) {
                case "onetime":
                    require_once( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews/onetime.php' );
                    break;
                case "slider":
                    require_once( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews/slider.php' );
                    break;
                case "pagination":
                    require_once( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews/pagination.php' );
                    break;
            }
            
        } else if( $offset == 0 ) {
            if( $listing_type == 'pagination' ) {
                echo '<input type="hidden" id="ldnft-is-loadmore-link" value="no" />';
            }
            echo '<div class="ldfmt-no-results">'.__('No review(s) found.', LDNFT_TEXT_DOMAIN).'</div>';
        }
        
        exit;
    }

    /**
     * Create shorcode to display reset progress option
     * 
     * @param $atts
     */
    public function reviews_shortcode_cb( $atts ) {
        
        $attributes = shortcode_atts( array(
            'product_id' => 0,
            'listing_type'   => 'pagination',  //pagination, onetime, slider
            'limit'   => 10
        ), $atts );

        ob_start();
        
        require_once( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews.php' );

        $content = ob_get_contents();
        ob_get_clean();

        return $content;
    }

}

LDNFT_Reviews_Shortcode::instance();