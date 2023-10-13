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
     * Define hooks
     */
    private function hooks() {
        add_action( 'wp_ajax_ldnft_load_reviews', [ $this, 'load_reviews' ], 100 );
        add_shortcode( 'LDNFT_Reviews', [ $this, 'reviews_shortcode_cb' ] );
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
        wp_enqueue_style( 'ldnft-bxslider-css', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.css', [], LDNFT_VERSION, null );
        wp_enqueue_style( 'ldnft-front-css', LDNFT_ASSETS_URL . 'css/frontend.css', [], LDNFT_VERSION, null );
        wp_enqueue_script('ldnft-bxslider-js', 'https://cdn.jsdelivr.net/bxslider/4.2.12/jquery.bxslider.min.js', ['jquery'], LDNFT_VERSION, true);
    }

    /**
     * Enqueue frontend scripte
     */
    public function load_reviews() {
        
        $plugin_id      = sanitize_text_field($_POST['plugin_id']);
        $per_page       = sanitize_text_field($_POST['per_page']);
        $listing_type   = sanitize_text_field($_POST['type']); //pagination, onetime, slider
        $offset         = sanitize_text_field($_POST['offset']);
        
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $results = $api->Api('plugins/'.$plugin_id.'/reviews.json?is_featured=true&count='.$per_page.'&offset='.$offset, 'GET', [ ]);
        
        if( is_array($results->reviews) && count( $results->reviews ) > 0 ) {
            $slides         = '';
            $slide_ctrl     = '';
            $slide_index = 0;
            foreach($results->reviews as $review) { 
                if( $listing_type == 'pagination' || $listing_type == 'onetime' ) {
                ?>
                    <div class="review-container">
                        <?php if(!empty($review->picture)) { ?>
                            <a class="ldfmt_review_image-link" href="<?php echo $review->profile_url;?>"><img class="ldfmt_review_image" src="<?php echo $review->picture;?>" alt="Avatar" style="width:90px"></a>
                        <?php } ?>
                        <h3 class="ldfmt_review_title"><a class="ldfmt_review_title-link" href="<?php echo $review->sharable_img;?>" data-lightbox="ldfmt-set" data-title="<?php echo $review->title;?>"><?php echo $review->title;?></a></h3>
                        <p class="ldfmt_review_user"><span><?php echo $review->name;?></span> of <?php echo !empty($review->company)?'<a href="'.$review->company_url.'">'.$review->company.'</a>':''; ?></p>
                        <p class="ldfmt_review_description"><?php echo $review->text;?></p>
                        <p class="ldfmt_review_time_wrapper">
                            <div class="ldfmt_review_time"><?php echo $review->created;?></div>
                            <div class="ldfmt_review_rate">
                                <?php echo __('Rate:', LDNFT_TEXT_DOMAIN);?> 
                                <div class="ldnft-rating-div">
                                    <?php 
                                        $rates = $review->rate;
                                        for($i=1; $i<=5; $i++) {
                                            $selected = '';
                                            if( $i*20 <= $rates ) {
                                                $selected = 'ldnft-checked';
                                            }
                                            echo '<span class="fa fa-star '.$selected.'"></span>';
                                        }
                                    ?>
                                </div>    
                            </div>
                        </p>
                    </div>
                <?php
                } elseif ( $listing_type == 'slider' ) {
                    $slides          .= '<div class="slider-item '.($slide_index == 0?'active':'').'"><img src="'.$review->sharable_img.'" width="100%" /></div>';
                    //$slide_ctrl      .= '<i class="fas fa-circle indicator '.($slide_index == 0?'active':'').'" data-slide-to="'.($slide_index++).'"></i>';
                    
                }
            }

            if( $listing_type == 'slider' ) {
                echo $slides;
                ?>
                    <!-- <div class="slider-panel">
                        <div class="slider-panel__navigation">
                            <?php echo $slide_ctrl;?>
                        </div>
                        <div class="slider-panel__controls">
                            <i class="far fa-arrow-alt-circle-left" id="previous"></i>
                            <i class="far fa-pause-circle" id="pause-play"></i>
                            <i class="far fa-arrow-alt-circle-right" id="next"></i>
                        </div>
                    </div> -->
                <?php
            }

            if( $listing_type == 'pagination' ) {
                $result_check = $api->Api('plugins/'.$plugin_id.'/reviews.json?is_featured=true&count='.$per_page.'&offset='.($offset+$per_page), 'GET', [ ]);
                if( is_array($result_check->reviews) && count( $result_check->reviews ) > 0 ) {
                    echo '<input type="hidden" id="ldnft-is-loadmore-link" value="yes" />';
                } else {
                    echo '<input type="hidden" id="ldnft-is-loadmore-link" value="no" />';
                }
            }
        } else if( $offset == 0 ) {
            if( $listing_type == 'pagination' ) {
                echo '<input type="hidden" id="ldnft-is-loadmore-link" value="no" />';
            }
            echo '<div class="ldfmt-no-results">'.__('No review(s) found.', LDNFT_TEXT_DOMAIN).'</div>';
        }
        
        exit;
    }

    /**
     * Create shorcode to display reset progress option
     * 
     * @param $atts
     */
    public function reviews_shortcode_cb( $atts ) {
        
        $attributes = shortcode_atts( array(
            'product_id' => 0,
            'listing_type'   => 'pagination',  //pagination, onetime, slider
            'limit'   => 10
        ), $atts );

        $listing_type = isset( $atts['listing_type'] ) ? $atts['listing_type'] : 'pagination';
        $limit = isset( $atts['limit'] ) ? $atts['limit'] : 10;
        $product_id = isset( $atts['product_id'] ) ? $atts['product_id'] : 0;
        $content = '';
        ob_start();
        if( intval( $product_id ) > 0 ) { ?>
            <link rel="stylesheet" href="<?php echo LDNFT_ASSETS_URL;?>lightbox/css/lightbox.min.css">
            <script src="<?php echo LDNFT_ASSETS_URL;?>lightbox/js/lightbox-plus-jquery.min.js"></script>
            
            <div class="ldmft_wrapper">
                <div class="filter">
                    <input type="hidden" value="<?php echo $product_id;?>" name="ldfmt-plugins-filter" class="ldfmt-plugins-filter">
                    <input type="hidden" value="<?php echo $listing_type;?>" name="ldfmt-listing_type" class="ldfmt-listing_type">
                    <input type="hidden" value="<?php echo $limit;?>" name="ldfmt-page-limit" class="ldfmt-page-limit">
                </div>
                <div style="display:none" class="ldfmt-loader-div"><img width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL.'images/spinner-2x.gif';?>" /></div>
                <div class="ldmft-filter-reviews <?php if( $listing_type=='slider' ) { ?>ldmft-filter-reviews-slider<?php } ?>">    
                    
                </div>
                
                <div class="ldfmt-load-more-btn">
                    <?php if( $listing_type=='pagination' ) { ?>
                        <a href="javascript:;" style="display:none;"><?php echo __( 'Load More', LDNFT_TEXT_DOMAIN );?></a>
                    <?php } ?>
                    <div style="display:none" class="ldfmt-loader-div-btm ldfmt-loader-div-btm-reviews"><img width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL.'images/spinner-2x.gif';?>" /></div>
                </div>
            </div>
        <?php } else { ?>
            <div class="ldmft_wrapper">
                <div class="ldmft-filter-reviews">    
                    <?php echo __( 'To display product reviews, you need to attach product id with the shortcode', LDNFT_TEXT_DOMAIN );?>
                </div>
            </div>
        <?php
        }

        $content = ob_get_contents();
        ob_get_clean();

        return $content;
    }

}

LDNFT_Reviews_Shortcode::instance();