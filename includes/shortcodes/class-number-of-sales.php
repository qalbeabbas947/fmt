<?php
/**
 * LDMFT_Sales shortcode class
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class LDNFT_Number_of_Sales_Shortcode
 */
class LDNFT_Number_of_Sales_Shortcode {

    /**
     * Class instance
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LDNFT_Number_of_Sales_Shortcode ) ) {

            self::$instance = new self;

            self::$instance->hooks();
        }
        
        return self::$instance;
    }

    /**
     * Define hooks
     */
    private function hooks() {
        
        add_shortcode( 'LDNFT_Number_of_Sales', [ $this, 'sales_shortcode_cb' ] );
    }
    
    /**
     * Create shorcode to display number of sales
     * 
     * @param $atts
     */
    public function sales_shortcode_cb( $atts ) {
        
        $attributes = shortcode_atts( array(
            'product_id' => 0,
        ), $atts );

        $plugin_id  = sanitize_text_field($attributes['product_id']);
        $tem_per_page = 50;
        $tem_offset = 0;
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset, 'GET', []);
        $total_sales = 0;
        if( count( $result->subscriptions ) > 0 ) {
            $has_more_records = true;
            while($has_more_records) {
                $total_sales += count($result->subscriptions);

                $tem_offset += $tem_per_page;
                $result = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset, 'GET', []);
                if( count( $result->subscriptions ) > 0 ) {
                    $has_more_records = true;
                } else {
                    $has_more_records = false;
                }
            }
        }
        
        return $total_sales;
    }
}

/**
 * Class instance.
 */
LDNFT_Number_of_Sales_Shortcode::instance();