<?php
/**
 * Frontend template for reset results
 * 
 * Do not allow directly accessing this file.
 */

if( ! defined( 'ABSPATH' ) ) exit;
ini_set('display_errors', 'On');
        error_reporting(E_ALL);
/**
 * ldFMT_Reviews_Shortcode
 */
class ldFMT_Reviews_Shortcode {

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
        add_shortcode( 'LDFMT_Reviews', [ $this, 'reviews_shortcode_cb' ] );
    }

    public function load_reviews() {
        
        $plugin_id = sanitize_text_field($_POST['plugin_id']);
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        
        $results = $api->Api('plugins/'.$plugin_id.'/reviews.json', 'GET', ['is_featured'=>'false','is_verified'=>'false', 'enriched'=>'true', 'count'=>'50' ]);
        if( is_array($results->reviews) && count( $results->reviews ) ) {
            foreach($results->reviews as $review) {
            ?>
                <div class="review-container">
                    <?php if(!empty($review->picture)) { ?>
                        <a class="ldfmt_review_image" href="<?php echo $review->profile_url;?>"><img src="<?php echo $review->picture;?>" alt="Avatar" style="width:90px"></a>
                    <?php } ?>
                    <h3 class="ldfmt_review_title"><a href="<?php echo $review->sharable_img;?>"><?php echo $review->title;?></a></h3>
                    <p class="ldfmt_review_user"><span><?php echo $review->name;?></span> of <?php echo !empty($review->company)?'<a href="'.$review->company_url.'">'.$review->company.'</a>':''; ?></p>
                    <p class="ldfmt_review_description"><?php echo $review->text;?></p>
                    <p class="ldfmt_review_time_wrapper"><div class="ldfmt_review_time"><?php echo $review->created;?></div><div class="ldfmt_review_rate"><?php echo __('Rate:', 'mailpoet');?> <?php echo $review->rate;?></div></p>
                </div>
            <?php
            }
        } else {
            echo '<div class="no-results">'.__('No review(s) found.', 'mailpoet').'</div>';
        }
        exit;
    }

    /**
     * Enqueue frontend scripte
     */
    public function ldmft_enqueue_front_scripts() {

        /**
         * Enqueue frontend css
         */
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'rcpl-jqueryui-css', 'https://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css', [], RCPL_VERSION, null );
        wp_enqueue_style( 'rcpl-front-css', RCPL_ASSETS_URL . 'css/frontend.css', [], RCPL_VERSION, null );
        wp_enqueue_style( 'front-select-min-css', RCPL_ASSETS_URL .'css/select2.min.css' );
        wp_enqueue_style( 'rcpl-custom-popup-css-link', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.css', [], RCPL_VERSION, null );

        /**
         * Enqueue frontend js
         */
        wp_enqueue_script('rcpl-jqueryui-js', 'https://code.jquery.com/ui/1.10.4/jquery-ui.js', ['jquery'], RCPL_VERSION, true);
        wp_enqueue_script( 'front-select2-jquery-js', RCPL_ASSETS_URL. 'js/select2.full.min.js', ['jquery'], RCPL_VERSION, true );
        wp_enqueue_script( 'rcpl-select2-addition', RCPL_ASSETS_URL . 'js/backend-select2-addition.js', [ 'jquery' , 'front-select2-jquery-js' ], RCPL_VERSION, true ); 
        wp_enqueue_script( 'wt-custom-bootstrap-pop-up-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js', ['jquery'], RCPL_VERSION, true );
        
        //
        wp_enqueue_script( 'rcpl-backend-js', RCPL_ASSETS_URL . 'js/backend.js', [ 'jquery' , 'front-select2-jquery-js' ], RCPL_VERSION, true ); 
        wp_enqueue_script( 'rcpl-backend-additional-js', RCPL_ASSETS_URL . 'js/backend-additional.js', [ 'jquery' ], RCPL_VERSION, true ); 
        wp_enqueue_script( 'rcpl-frontend-js', RCPL_ASSETS_URL . 'js/frontend.js', [ 'jquery' ], RCPL_VERSION, true ); 
        wp_enqueue_script( 'rcpl-schedule-js', RCPL_ASSETS_URL . 'js/rcpl-schedule.js', [ 'jquery' ], RCPL_VERSION, true ); 

        wp_localize_script( 'rcpl-backend-js', 'RCPL', array( 
            'ajaxURL' => admin_url( 'admin-ajax.php' ),
        ) );

        wp_localize_script( 'rcpl-backend-js', 'RcplNonce', array( 
            'security' => wp_create_nonce( 'ldmft_ajax_nonce' )
        ) );
    }

    /**
     * Create shorcode to display reset progress option
     * 
     * @param $atts
     */
    public function reviews_shortcode_cb( $atts ) {
        
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
                    <div class="filter">
                        <label><?php echo __( 'select a Plugin:', LDNFT_TEXT_DOMAIN );?></label>
                        <select name="ldfmt-plugins-filter" class="ldfmt-plugins-filter">
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
                    <div class="ldmft-filter-reviews">
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

ldFMT_Reviews_Shortcode::instance();