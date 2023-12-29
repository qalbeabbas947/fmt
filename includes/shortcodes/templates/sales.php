<?php
/**
 * reviews shortcode template.
 */

if( intval( $product_id ) > 0 ) { ?>
    <div class="ldmft_wrapper">
        <div style="display:none" class="ldfmt-loader-div"><img width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL.'images/spinner-2x.gif';?>" /></div>
        <div class="ldmft-filter-sales"></div>
        <div class="ldfmt-load-more-sales-btn"><a href="javascript:;">
            <?php echo __( 'Load More', 'ldninjas-freemius-toolkit' );?></a>
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
                <?php echo __( 'To display product sales, you need to attach product id with the shortcode', 'ldninjas-freemius-toolkit' );?>
            </div>
        </div>
    <?php
}