<?php
/**
 * reviews shortcode template.
 */
$attributes = shortcode_atts( array(
    'product_id' => 0,
    'listing_type'   => 'pagination',  //pagination, onetime, slider
    'limit'   => 10
), $atts );

$listing_type = isset( $atts['listing_type'] ) ? $atts['listing_type'] : 'pagination';
$limit = isset( $atts['limit'] ) ? $atts['limit'] : 10;
$product_id = isset( $atts['product_id'] ) ? $atts['product_id'] : 0;

if( intval( $product_id ) > 0 ) { ?>
    <link rel="stylesheet" href="<?php echo LDNFT_ASSETS_URL;?>lightbox/css/lightbox.min.css">
    <script src="<?php echo LDNFT_ASSETS_URL;?>lightbox/js/lightbox-plus-jquery.min.js"></script>
    
    <div class="ldmft_wrapper">
        <div class="filter">
            <input type="hidden" value="<?php echo $product_id;?>" name="ldfmt-plugins-filter" class="ldfmt-plugins-filter">
            <input type="hidden" value="<?php echo $listing_type;?>" name="ldfmt-listing_type" class="ldfmt-listing_type">
            <input type="hidden" value="<?php echo $limit;?>" name="ldfmt-page-limit" class="ldfmt-page-limit">
            <input type="hidden" value="0" name="ldfmt-page-offset" class="ldfmt-page-offset" />
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