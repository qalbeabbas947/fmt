<?php
/**
 * LDNFT_Product_Rating shortcode class
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class LDNFT_Product_Rating
 */
class LDNFT_Product_Rating {

    /**
     * Class instance
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LDNFT_Product_Rating ) ) {

            self::$instance = new self;

            self::$instance->hooks();
        }
        
        return self::$instance;
    }

    /**
     * Define hooks
     */
    private function hooks() {
        
        add_shortcode( 'LDNFT_Product_Rating', [ $this, 'rating_shortcode_cb' ] );
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
        wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend.css', [], LDNFT_VERSION, null );
    }

    /**
     * Create shorcode to display reset progress option
     * 
     * @param $atts
     */
    public function rating_shortcode_cb( $atts ) {
        
        global $wpdb;

        $attributes = shortcode_atts( array(
            'product_id' => 0,
        ), $atts );

        $plugin_id      = sanitize_text_field( $attributes['product_id'] );
        $table_name     = $wpdb->prefix.'ldnft_reviews'; 
        $total_ratings  = $wpdb->get_var( $wpdb->prepare( "SELECT sum(rate) as rate FROM $table_name where plugin_id = %d", $plugin_id ) );
        $total_reviews  = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) as rate FROM $table_name where plugin_id = %d", $plugin_id ) );

        ob_start();
        ?>
            <div class="ldnft-rating-div">
                <?php 
                    $rates = 0;
                    if( $total_reviews > 0 && $total_ratings > 0 ) {
                        $rates = intval($total_ratings/$total_reviews);
                    }

                    for($i=1; $i<=5; $i++) {
                        $selected = '';
                        if( $i*20 <= $rates ) {
                            $selected = 'ldnft-checked';
                        }
                        echo '<span class="fa fa-star '.$selected.'"></span>';
                    }
                ?>
                <span class="ldnft-rate-count">(<?php echo $total_reviews;?>)</span>
            </div>
        <?php
        
        $content = ob_get_contents();
        ob_get_clean();

        return $content;         
    }
}

/**
 * Class instance.
 */
LDNFT_Product_Rating::instance();