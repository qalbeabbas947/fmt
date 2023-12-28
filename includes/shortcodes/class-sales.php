<?php
/**
 * LDMFT_Sales shortcode class
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class LDNFT_Sales_Shortcode
 */
class LDNFT_Sales_Shortcode {

    /**
     * Class instance
     */
    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LDNFT_Sales_Shortcode ) ) {

            self::$instance = new self;

            self::$instance->hooks();
        }
        
        return self::$instance;
    }

    /**
     * Define hooks
     */
    private function hooks() {
        
        add_shortcode( 'LDNFT_Sales', [ $this, 'sales_shortcode_cb' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_scripts' ] );
        add_action( 'wp_ajax_ldnft_load_sales', [ $this, 'load_sales' ], 100 );
    }

    /**
     * load the sales records via ajax call
     */
    public function load_sales() {
        
        global $wpdb;

        $plugin_id  = sanitize_text_field( $_POST['plugin_id'] );
        $show       = ! empty( $show ) ? sanitize_text_field( $_POST['show'] ) : 'both';
        $per_page   = sanitize_text_field( $_POST['per_page'] );
        $offset     = sanitize_text_field( $_POST['offset'] );
        
        ob_start();
        
        if( ( $show == 'both' || $show=='summary' ) && $offset == 0 ) {
            $table_name     = $wpdb->prefix.'ldnft_subscription';
            $gross_total    = $wpdb->get_var($wpdb->prepare("SELECT sum(gross) as total FROM $table_name where plugin_id=%d", $plugin_id));
            $gateway_total  = $wpdb->get_var($wpdb->prepare("SELECT sum(gateway) as total FROM $table_name where plugin_id=%d", $plugin_id));
            ?>
                <div class="ldfmt-gross-sales-box ldfmt-sales-box">
                    <label><?php echo __('Gross Sales', LDNFT_TEXT_DOMAIN);?></label>
                    <div class="ldnft_points"><?php echo number_format( floatval($gross_total), 2);?></div>
                </div>
                <div class="ldfmt-gross-gateway-box ldfmt-sales-box">
                    <label><?php echo __('Gateway Fee', LDNFT_TEXT_DOMAIN);?></label>
                    <div class="ldnft_gateway_fee"><?php echo number_format( floatval($gateway_total), 2);?></div>
                </div>
            <?php
            echo '<div class="ldfmt-clear-div">&nbsp;</div>';
        }
        
        if( $show == 'both' || $show == 'listing' ) {
            
            $table_name = $wpdb->prefix.'ldnft_subscription t inner join '.$wpdb->prefix.'ldnft_customers c on (t.user_id=c.id)'; 
            $results    = $wpdb->get_results( $wpdb->prepare( "SELECT t.*, concat(c.first, ' ', c.last) as username, c.email FROM $table_name where t.plugin_id=%d LIMIT %d OFFSET %d", $plugin_id, $per_page, $offset ) );
        
            if( is_array( $results ) && count( $results ) > 0 ) {
                if(  $offset == 0 ) {
                    ?>
                        <table class="ldfmt-sales-list">
                            <tr>
                                <th><?php echo __('Name', LDNFT_TEXT_DOMAIN);?></th>
                                <th><?php echo __('Gross', LDNFT_TEXT_DOMAIN);?></th>
                                <th><?php echo __('Tax Rate', LDNFT_TEXT_DOMAIN);?></th>
                                <th><?php echo __('Created', LDNFT_TEXT_DOMAIN);?></th>
                                <th><?php echo __('Next Payment', LDNFT_TEXT_DOMAIN);?></th>
                            </tr>

                    <?php
                }

                foreach( $results as $result ) {
                    ?>
                        <tr>
                            <td><?php echo $result->username;?><br>(<?php echo $result->email;?>)</td>
                            <td><?php echo $result->total_gross;?></td>
                            <td><?php echo $result->tax_rate;?></td>
                            <td><?php echo $result->created;?></td>
                            <td><?php echo $result->next_payment;?></td>
                        </tr>
                    <?php
                }

                if(  $offset == 0 ) {
                    echo '<table>';
                }

            } elseif(  $offset == 0 ) {

                echo '<div class="ldfmt-no-results">'.__('No sale record(s) found.', LDNFT_TEXT_DOMAIN).'</div>';
            }
        }
        
        $content = ob_get_contents();
        ob_get_clean();

        echo $content;
        exit;
    }

    /**
     * Enqueue frontend scripte
     */
    public function enqueue_front_scripts() {

        /**
         * Enqueue frontend css
         */
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'ldnft-jqueryui-css', 'https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css', [], LDNFT_VERSION, null );
        wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend.css', [], LDNFT_VERSION, null );
        
        /**
         * Enqueue frontend js
         */
        wp_enqueue_script('ldnft-jqueryui-js', 'https://code.jquery.com/ui/1.10.4/jquery-ui.js', ['jquery'], LDNFT_VERSION, true);
        wp_enqueue_script( 'ldnft-frontend-js', LDNFT_ASSETS_URL . 'js/frontend.js', [ 'jquery' ], LDNFT_VERSION, true ); 
        
        wp_localize_script( 'ldnft-frontend-js', 'LDNFT', [ 
            'ajaxURL' => admin_url( 'admin-ajax.php' ),
        ] );
    }

    /**
     * shorcode to display sales data
     * 
     * @param $atts
     */
    public function sales_shortcode_cb( $attributes ) {
        
		$atts = shortcode_atts( array(
            'product_id' => 0,
            'user_id'   => 0,
            'show' => ''
        ), $attributes );

        $user_id = isset( $atts['user_id'] ) && intval( $atts['user_id'] ) > 0 ? $atts['user_id'] : get_current_user_id();
		$product_id = isset( $atts['product_id'] ) ? $atts['product_id'] : 0;
        $content = '';
        if( FS__HAS_PLUGINS ) {
            ob_start();

            require_once( LDNFT_SHORTCODES_TEMPLATES_DIR . 'sales.php' );
            
            $content = ob_get_contents();
            ob_get_clean();
				
        }

        return $content;
    }
}

/**
 * Class instance.
 */
LDNFT_Sales_Shortcode::instance();