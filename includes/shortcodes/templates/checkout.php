<?php
/**
 * Checkout shortcode template
 */
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
                <a href="https://docs.ldninjas.com/plugin/custom-tabs-for-learndash/" target="_self" id="ldnft-purchase" style="margin-top: 30px;margin-bottom: 10px;border-radius: 25px 25px 25px 25px;" class="elementor-button-link elementor-button elementor-size-md" role="button">
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
    <input class="form-control" type="hidden" id="ldnft-checkout-plugin_id" value="<?php echo $plugin_id;  ?>" />
    <input class="form-control" type="hidden" id="ldnft-checkout-plan_id" value="<?php echo $plan_id;   ?>" />
    <input class="form-control" type="hidden" id="ldnft-checkout-public_key" value="<?php echo $public_key;  ?>" />
    <input class="form-control" type="hidden" id="ldnft-checkout-image" value="<?php echo $attributes['image']  ?>" />
</div>