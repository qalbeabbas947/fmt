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
     * 
     * Return Paginated review HTML
     */
    public static function paginated_review( $results ) {

        /**
         * 
         * Enqueue specific js and css
         */
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'ldnft-frontend-js' );

        if ( !empty( $results ) && is_array( $results ) ) {
            
            foreach( $results as $review ) {

                $client_name        = isset( $review->name ) ? $review->name : __( 'anonymous', 'ldninjas-freemius-toolkit' );
                $rating             = isset( $review->rate ) ? $review->rate : '';
                $title              = isset( $review->title ) ? $review->title : '';
                $description        = isset( $review->text ) ? $review->text : '';
                $client_profile_pic = isset( $review->profile_url ) ? $review->profile_url : ''; 
                
                include( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews/pagination.php' );
            }
        }
    }

    /**
     * Define hooks
     */
    private function hooks() {
        
        add_action( 'wp_ajax_ldnft_review_load_more', [ $this, 'load_reviews' ] );
        add_action( 'wp_ajax_nopriv_ldnft_review_load_more', [ $this, 'load_reviews' ] );
        add_shortcode( 'ldnft_reviews', [ $this, 'reviews_shortcode_cb' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_scripts' ] );
    }

    /**
     * 
     * Return more reviews with offset
     */
    public function load_reviews() {

        $response = [];
        if( ! wp_verify_nonce( $_POST['security'], 'ldnft_review_load_more' ) ) {

            $response['status'] = false;
            $response['data'] = 'nounce error';

            echo json_encode( $response, true );
            exit;
        }

        $limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 10;
        $offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : $limit;
        $plugin_id = isset( $_POST['plugin_id'] ) ? intval( $_POST['plugin_id'] ) : 0;

        if( $limit < 1 ) {

            $response['status'] = false;
            $response['data'] = 'limit is not correct.';

            echo json_encode( $response, true );
            exit;
        }

        if( $plugin_id < 1 ) {

            $response['status'] = false;
            $response['data'] = 'plugin id not found';

            echo json_encode( $response, true );
            exit;
        }

        
        ob_start();
        $results = LDNFT_Reviews_Shortcode::get_reviews( $plugin_id, $limit, $offset );
        if ( !empty( $results ) && is_array( $results ) ) {
            
            foreach( $results as $review ) {

                $client_name        = isset( $review->name ) ? $review->name : __( 'anonymous', 'ldninjas-freemius-toolkit' );
                $rating             = isset( $review->rate ) ? $review->rate : '';
                $title              = isset( $review->title ) ? $review->title : '';
                $description        = isset( $review->text ) ? $review->text : '';
                $client_profile_pic = isset( $review->profile_url ) ? $review->profile_url : ''; 
                
                include( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews/pagination.php' );
            }
        }
        
        $content = ob_get_contents();
        ob_get_clean();

        $response['status'] = true;
        $response['data'] = $content;
        echo json_encode( $response, true );
        exit;
    }

    /**
     * Enqueue frontend scripte
     */
    public function enqueue_front_scripts() {

        global $post;
        if($post) {
            if( !has_shortcode( $post->post_content, 'ldnft_reviews' ) ) {
                return false;
            }

            /**
             * Enqueue frontend css
             */
            wp_enqueue_style( 'dashicons' );
            wp_register_style( 'ldnft-bxslider-css', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.css', [], LDNFT_VERSION, null );
            wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend/frontend.css', [], LDNFT_VERSION, null );
            
            wp_register_script('ldnft-bxslider-js', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js', [], LDNFT_VERSION, true );
            wp_register_script( 'ldnft-frontend-js', LDNFT_ASSETS_URL.'js/frontend/frontend.js', ['jquery'], LDNFT_VERSION, true );
            wp_localize_script( 'ldnft-frontend-js', 'LDNFT', [ 
                'ajaxURL' => admin_url( 'admin-ajax.php' ),
                'security'  => wp_create_nonce( 'ldnft_review_load_more' )
            ] );
        }
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
        
        $product_id = isset( $atts['product_id'] ) ? $atts['product_id'] : 0 ;
        $listing_type = isset( $atts['listing_type'] ) ? $atts['listing_type'] : '';
        $limit = isset( $atts['limit'] ) ? $atts['limit'] : 10 ;

        ob_start();
        include( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews.php' );
        $content = ob_get_contents();
        ob_get_clean();
        
        return $content;
    }
}

LDNFT_Reviews_Shortcode::instance();