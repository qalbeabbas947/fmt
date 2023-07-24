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
        $table_name = $wpdb->prefix.'ldnft_transactions';
        $where_interval = '';
        
        switch( $interval ) {
            case "current_week":
                $where_interval = " and YEARWEEK(created) = YEARWEEK(NOW());";
                break;
            case "last_week":
                $where_interval = ' and Date(created) between date_sub(now(),INTERVAL 1 WEEK) and now();';
                break;
            case "current_month":
                $where_interval = ' and MONTH(created) = MONTH(now()) and YEAR(created) = YEAR(now())';
                break;
            case "last_month":
                $where_interval = ' and Date(created) between Date((now() - interval 1 month)) and Date(now());';
                break;
            default:
                $where_interval = " and Date(created) = '".date('Y-m-d')."'";
                break;
        }
        
        $gross = $wpdb->get_var($wpdb->prepare("SELECT sum(gross) FROM $table_name where plugin_id=%d ".$where_interval, $plugin_id ));
        ?>
            <div class="ldfmt-gross-sales-box ldfmt-sales-box">
                <label><?php echo __('Gross Sales', 'mailpoet');?></label>
                <div class="ldnft_points"><?php echo number_format( floatval($gross), 2);?></div>
            </div>
        <?php

        $gateway_fee = $wpdb->get_var($wpdb->prepare("SELECT sum(gateway_fee) FROM $table_name where plugin_id=%d ".$where_interval, $plugin_id ));
        ?>
            <div class="ldfmt-gross-gateway-box ldfmt-sales-box">
                <label><?php echo __('Gateway Fee', 'mailpoet');?></label>
                <div class="ldnft_gateway_fee"><?php echo number_format( floatval($gateway_fee), 2);?></div>
            </div>
        <?php

        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name where plugin_id=%d ".$where_interval, $plugin_id ));
        if( is_array($results) && count( $results ) > 0 ) {
            ?>
                <table class="ldfmt-sales-list">
                    <tr>
                        <th><?php echo __('Name', 'mailpoet');?></th>
                        <th><?php echo __('Email', 'mailpoet');?></th>
                        <th><?php echo __('Gross', 'mailpoet');?></th>
                        <th><?php echo __('Gateway Fee', 'mailpoet');?></th>
                        <th><?php echo __('Created', 'mailpoet');?></th>
                        <th><?php echo __('Renewal?', 'mailpoet');?></th>
                        <th><?php echo __('Type', 'mailpoet');?></th>
                        <th><?php echo __('Country', 'mailpoet');?></th>
                    </tr>

            <?php

            foreach($results as $result) {
                ?>
                    <tr>
                        <td><?php echo $result->username;?></td>
                        <td><?php echo $result->useremail;?></td>
                        <td><?php echo $result->gross;?></td>
                        <td><?php echo $result->gateway_fee;?></td>
                        <td><?php echo $result->created;?></td>
                        <td><?php echo $result->is_renewal;?></td>
                        <td><?php echo $result->type;?></td>
                        <td><?php echo $result->country_code;?></td>
                    </tr>
                <?php
            }
            echo '<table>';
        } else {
            echo '<div class="ldfmt-no-results">'.__('No review(s) found.', 'mailpoet').'</div>';
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
                                <option value="today"><?php echo __( 'Today', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="current_week"><?php echo __( 'Current Week', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="last_week"><?php echo __( 'Last Week', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="current_month"><?php echo __( 'Current Month', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="last_month"><?php echo __( 'Last Month', LDNFT_TEXT_DOMAIN );?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ldmft-filter-sales">
                        <!-- <div class="review-container">
                            <img src="/w3images/bandmember.jpg" alt="Avatar" style="width:90px">
                            <p><span>Chris Fox.</span> CEO at Mighty Schools.</p>
                            <p>John Doe saved us from a web disaster.</p>
                        </div> -->
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