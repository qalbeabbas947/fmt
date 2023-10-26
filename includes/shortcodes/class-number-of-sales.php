<?php
/**
 * LDNFT_Number_of_Sales_Shortcode shortcode class
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
        
        global  $wpdb;

        $attributes = shortcode_atts( array(
            'product_id' => 0,
        ), $atts );

        $plugin_id  = sanitize_text_field($attributes['product_id']);
        $table_name = $wpdb->prefix.'ldnft_subscription';  
        $total_sales = $wpdb->get_var($wpdb->prepare("SELECT count(id) as id FROM $table_name where plugin_id=%d", $plugin_id));

        
        return $total_sales;
    }
}

/**
 * Class instance.
 */
LDNFT_Number_of_Sales_Shortcode::instance();