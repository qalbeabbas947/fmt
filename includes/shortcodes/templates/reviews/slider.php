<?php
/**
 * reviews shortcode template to display slider.
 */

$slide_index = 0;
foreach( $results as $review ) {

    echo LDNFT_Reviews_Shortcode::instance()->display_review_item( $review, $slide_index );
}
?>