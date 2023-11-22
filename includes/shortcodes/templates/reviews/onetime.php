<?php
/**
 * reviews shortcode template to display fixed number of records with no pagination.
 */

foreach( $results as $review ) {

    echo LDNFT_Reviews_Shortcode::instance()->display_review_item( $review ); 
}