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

        $plugin_id  = sanitize_text_field($_POST['plugin_id']);
        $show       = sanitize_text_field($_POST['show']);
        $per_page   = sanitize_text_field($_POST['per_page']);
        $offset     = sanitize_text_field($_POST['offset']);

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        
        if( empty($show) ) {
            $show = 'both';
        }
        
        if( ($show == 'both' || $show=='summary' ) && $offset == 0) {
            
            $tem_per_page = 50;
            $tem_offset = 0;
            $result = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset, 'GET', []);
            $gross_total = 0;
            $tax_rate_total = 0;
            if( count( $result->subscriptions ) > 0 ) {
                $has_more_records = true;
                while($has_more_records) {
                    foreach( $result->subscriptions as $payment ) {
                        $gross_total += $payment->total_gross;
                        $tax_rate_total += $payment->tax_rate;
                    } 

                    $tem_offset += $tem_per_page;
                    $result = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset, 'GET', []);
                    if( count( $result->subscriptions ) > 0 ) {
                        $has_more_records = true;
                    } else {
                        $has_more_records = false;
                    }
                }
            }

            $gross = 0;

            ?>
                <div class="ldfmt-gross-sales-box ldfmt-sales-box">
                    <label><?php echo __('Gross Sales', LDNFT_TEXT_DOMAIN);?></label>
                    <div class="ldnft_points"><?php echo number_format( floatval($gross_total), 2);?></div>
                </div>
            <?php
    
            $gateway_fee = 0;
            ?>
                <div class="ldfmt-gross-gateway-box ldfmt-sales-box">
                    <label><?php echo __('Tax Rate', LDNFT_TEXT_DOMAIN);?></label>
                    <div class="ldnft_gateway_fee"><?php echo number_format( floatval($tax_rate_total), 2);?></div>
                </div>
            <?php
            echo '<div class="ldfmt-clear-div">&nbsp;</div>';
        }
        
        if( $show == 'both' || $show=='listing' ) {
            $results = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$per_page.'&offset='.$offset, 'GET', []);
            if( is_array($results->subscriptions) && count( $results->subscriptions ) > 0 ) {
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

                foreach( $results->subscriptions as $result ) {
                    $user = $api->Api('plugins/'.$plugin_id.'/users/'.$result->user_id.'.json', 'GET', []);
                    $username   = $user->first.' '.$user->last;
                    $useremail  = $user->email;
                    ?>
                        <tr>
                            <td><?php echo $username;?><br>(<?php echo $useremail;?>)</td>
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
            if( intval( $product_id ) > 0 ) {
            ?>
                <div class="ldmft_wrapper">
                    <div style="display:none" class="ldfmt-loader-div"><img width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL.'images/spinner-2x.gif';?>" /></div>
                    <div class="ldmft-filter-sales"></div>
                    <div class="ldfmt-load-more-sales-btn"><a href="javascript:;">
                        <?php echo __( 'Load More', LDNFT_TEXT_DOMAIN );?></a>
                        <div style="display:none" class="ldfmt-loader-div-btm ldfmt-loader-div-btm-sales"><img width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL.'images/spinner-2x.gif';?>" /></div>
                    </div>
                    <input type="hidden" id="ldfmt-sales-show-type" value="<?php echo $atts['show'];?>" />
                    <input type="hidden" id="ldfmt-sales-plugins-filter" value="<?php echo $product_id;?>" />
                </div>
            <?php } else { ?>
                    <input type="hidden" id="ldfmt-sales-show-type" value="<?php echo $atts['show'];?>" />
                    <input type="hidden" id="ldfmt-sales-plugins-filter" value="0" />
                    <div class="ldmft_wrapper">
                        <div class="ldmft-filter-reviews">    
                            <?php echo __( 'To display product sales, you need to attach product id with the shortcode', LDNFT_TEXT_DOMAIN );?>
                        </div>
                    </div>
                <?php
            }
            
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