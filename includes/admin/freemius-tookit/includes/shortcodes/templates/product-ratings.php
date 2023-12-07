<?php
/**
 * Product ratings shortcode template
 */

$plugin_id      = sanitize_text_field( $attributes['product_id'] );
$table_name     = $wpdb->prefix.'ldnft_reviews'; 
$total_ratings  = $wpdb->get_var( $wpdb->prepare( "SELECT sum(rate) as rate FROM $table_name where plugin_id = %d", $plugin_id ) );
$total_reviews  = $wpdb->get_var( $wpdb->prepare( "SELECT count(id) as rate FROM $table_name where plugin_id = %d", $plugin_id ) );
?>
<div class="ldnft-rating-div">
    <?php 
        $rates = 0;
        if( $total_reviews > 0 && $total_ratings > 0 ) {
            $rates = intval($total_ratings/$total_reviews);
        }

        for($i=1; $i<=5; $i++) {
            $selected = '';
            if( $i*20 <= $rates ) {
                $selected = 'ldnft-checked';
            }
            echo '<span class="fa fa-star '.$selected.'"></span>';
        }
    ?>
    <span class="ldnft-rate-count">(<?php echo $total_reviews;?>)</span>
</div>
       