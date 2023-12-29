<?php
/**
 * reviews shortcode template.
 */

if( intval( $product_id ) < 1 ) {
    ?> <div class="ldnft-reviews-not-found-wrapper">
        <div class="ldnft-reviews-not-found">    
            <?php echo __( 'To display product reviews, you need to attach product id with the shortcode', 'ldninjas-freemius-toolkit' ); ?>
        </div>
    </div> <?php 
    return false; 
} ?>

<div class="ldnft-reviews-wrapper">
<?php 

$results = LDNFT_Reviews_Shortcode::get_reviews( $product_id, $limit, 0 );
switch ( $listing_type ) {

    case 'slider':
        include( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews/slider.php' );
        break;
    case 'onetime':
        include( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews/onetime.php' );
        break;
    default: 
        include( LDNFT_SHORTCODES_TEMPLATES_DIR . 'reviews/pagination.php' );
}
?>
</div>