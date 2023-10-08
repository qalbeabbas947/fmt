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
        
        add_shortcode( 'LDNFT_Checkout', [ $this, 'checkout_shortcode_cb' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_scripts' ] );
       // add_action( 'wp_ajax_ldnft_load_sales', [ $this, 'load_sales' ], 100 );
        
    }

    public function load_sales() {
        
        global $wpdb;

        $plugin_id  = sanitize_text_field($_POST['plugin_id']);
        $interval   = sanitize_text_field($_POST['interval']);
        $show       = sanitize_text_field($_POST['show']);
        $per_page   = sanitize_text_field($_POST['per_page']);
        $offset     = sanitize_text_field($_POST['offset']);

        $interval_str = '';
        if( !empty($interval) ) {
           $interval_str = '&billing_cycle='.$interval;
        }
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        

        if( empty($show) ) {
            $show = 'both';
        }
        
        if( ($show == 'both' || $show=='summary' ) && $offset == 0) {
            
            $tem_per_page = 50;
            $tem_offset = 0;
            $result = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str, 'GET', []);
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
                    $result = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str, 'GET', []);
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
            $results = $api->Api('plugins/'.$plugin_id.'/subscriptions.json?count='.$per_page.'&offset='.$offset.$interval_str, 'GET', []);
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
                foreach($results->subscriptions as $result) {
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
        wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend.css', [], LDNFT_VERSION, null );
        
        /**
         * Enqueue frontend js
         */
        wp_enqueue_script( 'ldnft-frontend-js', LDNFT_ASSETS_URL . 'js/frontend.js', [ 'jquery' ], LDNFT_VERSION, true ); 
        
        wp_localize_script( 'ldnft-frontend-js', 'LDNFT', [ 
            'ajaxURL' => admin_url( 'admin-ajax.php' ),
        ] );
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
            'image' => ''
        ), $atts );

        $plugin_id  = sanitize_text_field($attributes['product_id']);
        $plan_id    = sanitize_text_field($attributes['plan_id']);

        if( empty( $plugin_id ) || intval($plugin_id) < 1 ) {
            return '<div class="ldnft-error-message">'.__('Product ID is a required parameter.', LDNFT_TEXT_DOMAIN).'</div>';
        }

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        if( $plan_id == 0 ) {
            
            $result = $api->Api('plugins/'.$plugin_id.'/plans.json', 'GET', []);
            if( !isset( $result->plans ) || !is_array( $result->plans ) || count( $result->plans ) == 0 ) {
                return '<div class="ldnft-error-message">'.__('Please, configure a plan before visiting this page.', LDNFT_TEXT_DOMAIN).'</div>';
            }

            $plan = $result->plans[0];
            $plan_id = $plan->id;
        }
        
       
        $presult = $api->Api('plugins/'.$plugin_id.'/plans/'.$plan_id.'/pricing.json', 'GET', []);
        if( !isset( $presult->pricing ) || !is_array( $presult->pricing ) || count( $presult->pricing ) == 0 ) {

            return '<div class="ldnft-error-message">'.__('Please configure the product pricing before visiting this page.', LDNFT_TEXT_DOMAIN).'</div>';
        }

        $ldnft_settings = get_option( 'ldnft_settings' ); 
        $public_key     = isset( $ldnft_settings['public_key'] ) ? sanitize_text_field( $ldnft_settings['public_key'] ): '';

        ob_start();
        ?>
            <div class="ld-ninjas-buy-now-widget">
                <div class="ld_price_options ld_single_mode">
                    <ul style="list-style:none;font-size: 20px;padding-left:0;">
                        <?php 
                            $index = 0; 
                            foreach( $presult->pricing as $price_item ) { 
                                $price = $price_item->monthly_price;
                                if( floatval( $price ) <= 0 ) {
                                    $price = $price_item->annual_price;
                                } 
                                
                                if( floatval( $price ) <= 0 ) {
                                    $price = $price_item->lifetime_price;
                                }
                        ?>
                        <li>
                            <label for="ld_price_option_<?php echo $index;?>" class="selected">
                                <span class="radio-button"></span>
                                <input type="radio" checked="checked" name="ld_licenses_options" id="ld_price_option_<?php echo $price_item->licenses;?>" class="ld_price_option_<?php echo $price_item->licenses;?>" value="<?php echo $price_item->licenses;?>">&nbsp;
                                <span class="ld_price_option_name"><?php echo intval($price_item->licenses)==1?__( 'Single Site', LDNFT_TEXT_DOMAIN ):$price_item->licenses.' '.__( 'site(s)', LDNFT_TEXT_DOMAIN );?></span>
                                <span class="ld_price_option_sep">&nbsp;–&nbsp;</span>
                                <span class="ld_price_option_price">$<?php echo $price;?></span>
                            </label>
                        </li>
                        <?php 
                                $index++; 
                            } 
                        ?>
                    </ul>
                </div>

                <p class='ld-licence-description' style="margin-top:20px;">
                    ⓘ <span><?php echo __( 'A license entitles you to 1 year of updates and support. Each installation of the add-on will require a license key in order for you to receive updates and support.', LDNFT_TEXT_DOMAIN );?></span>
                    <br><br>
                    <span><input type="checkbox" checked="checked" disabled="disabled"> <?php echo __( 'Purchasing this add-on confirms you to be notified with the future updates..', LDNFT_TEXT_DOMAIN );?></span>
                </p>	 

                <div class="elementor-element elementor-element-6a0f461 elementor-align-justify elementor-widget elementor-widget-button" style="margin-bottom:0;" data-id="6a0f461" data-element_type="widget" data-widget_type="button.default">
                    <div class="elementor-widget-container">
                        <div class="elementor-button-wrapper">
                            <a href="https://docs.ldninjas.com/plugin/custom-tabs-for-learndash/" target="_self" id="purchase" style="margin-top: 30px;margin-bottom: 10px;border-radius: 25px 25px 25px 25px;" class="elementor-button-link elementor-button elementor-size-md" role="button">
                                <span class="elementor-button-content-wrapper">
                                    <span class="elementor-button-icon elementor-align-icon-left">
                                        <i aria-hidden="true" class="fas fa-cart-arrow-down fas button-icon-left"></i>
                                    </span>
                                    <span class="elementor-button-text"><?php echo __( 'BUY NOW', LDNFT_TEXT_DOMAIN );?></span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
                <script src="https://checkout.freemius.com/checkout.min.js"></script>
                <script>
                    var handler = FS.Checkout.configure({
                        plugin_id: '<?php echo $plugin_id;  ?>',
                        plan_id: '<?php echo $plan_id;   ?>',
                        public_key: '<?php echo $public_key;  ?>',
                        image: '<?php echo $attributes['image']  ?>',
                    });
                    $('#purchase').on('click', function(e) {
                        handler.open({
                            name: 'Custom Tabs for LearnDash',
                            licenses: $('input[name="ld_licenses_options"]:checked').val(),
                            // You can consume the response for after purchase logic.
                            purchaseCompleted: function(response) {
                                // The logic here will be executed immediately after the purchase confirmation.                                // alert(response.user.email);
                            },
                            success: function(response) {
                                // The logic here will be executed after the customer closes the checkout, after a successful purchase.                                // alert(response.user.email);
                            }
                        });
                        e.preventDefault();
                    });
                </script>
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
LDNFT_Checkout_Shortcode::instance();