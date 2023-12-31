<?php
/**
 * Checkout shortcode template
 */

 $plugin_name = '';
 $product = LDNFT_Freemius::$products;
 if( !empty( $product ) && is_array( $product ) ) {
    foreach( $product as $prod ) {
        if(  $plugin_id == $prod->id ) {
            $plugin_name = $prod->title;
            break;
        }
    }
 }
?>
<div class="ldnft-buy-now-widget">
    <?php if( $display == 'detailed' ) { ?> 
        <div class="ld_price_options ld_single_mode">
            <ul class="ldnft-option-wrap" style="list-style:none;font-size: 20px;padding-left:0;">
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
                        <input type="radio" <?php echo ( $index == 0 ) ? 'checked="checked"' : ''; ?> name="ld_licenses_options" id="ld_price_option_<?php echo $price_item->licenses;?>" class="ld_price_option_<?php echo $price_item->licenses;?>" value="<?php echo $price_item->licenses;?>">&nbsp;
                        <span class="ld_price_option_name"><?php echo intval($price_item->licenses)==1?__( 'Single Site', 'ldninjas-freemius-toolkit' ):$price_item->licenses.' '.__( 'site(s)', 'ldninjas-freemius-toolkit' );?></span>
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
        <p class='ld-licence-description'>
            <span class="dashicons dashicons-info"></span> <span><?php echo __( 'A license entitles you to 1 year of updates and support. Each installation of the add-on will require a license key in order for you to receive updates and support.', 'ldninjas-freemius-toolkit' );?></span>
            <br><br>
            <span><input type="checkbox" checked="checked" disabled="disabled"> <?php echo __( 'Purchasing this add-on confirms you to be notified with the future updates..', 'ldninjas-freemius-toolkit' );?></span>
        </p>   
        <?php } ?>  
        <div class="ldnft-purchase-button">
            <button type="submit" id="ldnft-purchase" class="button button-primary" role="button">
                <span class="dashicons dashicons-cart"></span>
                <?php echo __( 'BUY NOW', 'ldninjas-freemius-toolkit' );?>
            </button>
        </div>
</div>
<input type="hidden" id="ldnft-checkout-plugin_id" value="<?php echo $plugin_id;  ?>" />
<input type="hidden" id="ldnft-checkout-plan_id" value="<?php echo $plan_id;   ?>" />
<input type="hidden" id="ldnft-checkout-public_key" value="<?php echo $public_key;  ?>" />
<input type="hidden" id="ldnft-checkout-plugin_name" value="<?php echo $plugin_name;  ?>" />
<input type="hidden" id="ldnft-checkout-image" value="<?php echo $attributes['image']  ?>" />