<?php
/**
 * reviews shortcode template to display the records with pagination.
 */

$slides         = '';
$slide_ctrl     = '';
$slide_index = 0;
foreach( $results as $review ) {

    echo LDNFT_Reviews_Shortcode::instance()->display_review_item( $review ); 
}

$result_check = $wpdb->get_results( $wpdb->prepare( "SELECT r.*, c.email as useremail FROM $table_name where is_featured = 1 and r.plugin_id = %d ORDER BY r.id LIMIT %d OFFSET %d", $plugin_id, $per_page, ( $offset+$per_page ) ) );
if( is_array( $result_check ) && count( $result_check ) > 0 ) {
    echo '<input type="hidden" id="ldnft-is-loadmore-link" value="yes" />';
} else {
    echo '<input type="hidden" id="ldnft-is-loadmore-link" value="no" />';
}