<?php
/**
 * RCPL template for front-end shortcodes
 *
 * Do not allow directly accessing this file.
 */

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class LDMFT_Sales_Shortcode
 */
class LDMFT_Sales_Shortcode {

    private static $instance = null;

    /**
     * @since 1.0
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof LDMFT_Sales_Shortcode ) ) {

            self::$instance = new self;

            self::$instance->hooks();
        }
        
        return self::$instance;
    }

    /**
     * Define hooks
     */
    private function hooks() {
        
        add_shortcode( 'LDFMT_Sales', [ $this, 'sales_shortcode_cb' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_scripts' ] );
        add_action( 'wp_ajax_ldnft_load_sales', [ $this, 'load_sales' ], 100 );
        
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

            $gross = 0;//$wpdb->get_var($wpdb->prepare("SELECT sum(gross) FROM $table_name where plugin_id=%d ".$where_interval, $plugin_id ));
            ?>
                <div class="ldfmt-gross-sales-box ldfmt-sales-box">
                    <label><?php echo __('Gross Sales', LDNFT_TEXT_DOMAIN);?></label>
                    <div class="ldnft_points"><?php echo number_format( floatval($gross_total), 2);?></div>
                </div>
            <?php
    
            $gateway_fee = 0;//$wpdb->get_var($wpdb->prepare("SELECT sum(gateway_fee) FROM $table_name where plugin_id=%d ".$where_interval, $plugin_id ));
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
        wp_enqueue_style( 'ldnft-jqueryui-css', 'https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css', [], LDNFT_VERSION, null );
        wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend.css', [], LDNFT_VERSION, null );
        
        /**
         * Enqueue frontend js
         */
        wp_enqueue_script('ldnft-jqueryui-js', 'https://code.jquery.com/ui/1.10.4/jquery-ui.js', ['jquery'], LDNFT_VERSION, true);
        wp_enqueue_script( 'ldnft-frontend-js', LDNFT_ASSETS_URL . 'js/frontend.js', [ 'jquery' ], LDNFT_VERSION, true ); 
        
        wp_localize_script( 'ldnft-frontend-js', 'LDNFT', array( 
            'ajaxURL' => admin_url( 'admin-ajax.php' ),
        ) );
    }

    /**
     * Create shorcode to display reset progress option
     * 
     * @param $atts
     */
    public function sales_shortcode_cb( $atts ) {
        $user_id = isset( $atts['user_id'] ) ? $atts['user_id'] : get_current_user_id();
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        
        $plugins = $api->Api('plugins.json?fields=id,title', 'GET', ['fields'=>'id,title']);
        $content = '';
        if( isset( $plugins->plugins ) &&  count($plugins->plugins) > 0 ) {
            $plugins = $plugins->plugins;
            $plugin = $plugins[0];
            ob_start();
            ?>
                <div class="ldmft_wrapper">
                    <div class="ldmft_filters">
                        <input type="hidden" id="ldfmt-sales-show-type" value="<?php echo $atts['show'];?>" />
                        <div class="ldmft_filter">
                            <label><?php echo __( 'Select a Plugin:', LDNFT_TEXT_DOMAIN );?></label>
                            <select name="ldfmt-sales-plugins-filter" class="ldfmt-sales-plugins-filter">
                                <?php
                                    foreach( $plugins as $plugin ) {
                                            
                                        $selected = '';
                                        // if( $selected_plugin_id == $plugin->id ) {
                                        //     $selected = ' selected = "selected"';   
                                        // }
                                        ?>
                                            <option value="<?php echo $plugin->id; ?>" <?php echo $selected; ?>><?php echo $plugin->title; ?></option>
                                        <?php   
                                    }
                                ?>
                                
                            </select>
                        </div>
                        <div class="ldmft_filter">
                            <label><?php echo __( 'Select Interval:', LDNFT_TEXT_DOMAIN );?></label>
                            <select name="ldfmt-sales-interval-filter" class="ldfmt-sales-interval-filter">
                                <option value=""><?php echo __( 'All Time', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="1"><?php echo __( 'Monthly', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="12"><?php echo __( 'Annual', LDNFT_TEXT_DOMAIN );?></option>
                            </select>
                        </div>
                    </div>
                    <div style="display:none" class="ldfmt-loader-div"><img width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL.'images/spinner-2x.gif';?>" /></div>
                    <div class="ldmft-filter-sales">
                        <!-- <div class="review-container">
                            <img src="/w3images/bandmember.jpg" alt="Avatar" style="width:90px">
                            <p><span>Chris Fox.</span> CEO at Mighty Schools.</p>
                            <p>John Doe saved us from a web disaster.</p>
                        </div> -->
                    </div>
                    <div class="ldfmt-load-more-sales-btn"><a href="javascript:;">
                        <?php echo __( 'Load More', LDNFT_TEXT_DOMAIN );?></a>
                        <div style="display:none" class="ldfmt-loader-div-btm"><img width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL.'images/spinner-2x.gif';?>" /></div>
                    </div>
                </div>
            <?php
            $content = ob_get_contents();
            ob_get_clean();
        }
        

        return $content;
    }
}

/**
 * Class instance.
 */
LDMFT_Sales_Shortcode::instance();