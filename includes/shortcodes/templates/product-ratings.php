<?php
/**
 * Product ratings shortcode template
 */

$plugin_id      = sanitize_text_field( $attributes['product_id'] );
$table_name     = $wpdb->prefix.'ldnft_reviews'; 
$total_ratings  = $wpdb->get_results( $wpdb->prepare( "SELECT sum(rate) as rate, count(id) as count FROM $table_name where plugin_id = %d AND is_featured = 1", $plugin_id ) );
?>
<div class="ldnft-rating-div">
    <?php 
        $rates = 0;
        if( $total_ratings ) {
            $rates = intval($total_ratings[0]->rate/$total_ratings[0]->count);
        }

        $odd = false;
        for( $i = 1; $i <= 5; $i++ ) {

            if( $rates%20 > 0 && $i * 20 > $rates && !$odd ) {
                $selected = 'half';
                $odd = true;
            } elseif( $i * 20 <= $rates ) {
                $selected = 'filled';
            } elseif( $odd || $i * 20 > $rates ) {
                $selected = 'empty';    
            }

            echo '<span class="ldnft-rating-star dashicons dashicons-star-'.$selected.'"></span>';
        }
    ?>
    <span class="ldnft-rate-count">(<?php echo $total_ratings[0]->count; ?>)</span>
</div>
       