<?php
/**
 * Abort if this file is accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


$args = array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1 );
$pages = new WP_Query( $args );

$tc_roundtable_main_page 	= get_option( 'tc_roundtable_main_page' );
$tc_roundtable_sub_page 	= get_option( 'tc_roundtable_sub_page' );
$tc_roundtable_form_page	= get_option( 'tc_roundtable_form_page' );
?>
<div id="general_settings" class="cs_ld_tabs"> 
    <table>
        <tr>
            <td><h3><?php _e( 'Shortcode:', LDNFT_TEXT_DOMAIN ); ?> [LDNFT_Reviews product_id="?"]</h3></td>
        </tr>
        <tr>
            <td clss="ldfmt-shortcode-desc"><?php _e( "Displays attached product's reviews on the frontend. User can filter the reviews based on the plugin.", LDNFT_TEXT_DOMAIN ); ?></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td><h3><?php _e( 'Shortcode:', LDNFT_TEXT_DOMAIN ); ?> [LDNFT_Sales show="[ summary  |  listing  |  both ]"]</h3></td>
        </tr>
        <tr>
            <td clss="ldfmt-shortcode-desc"><?php _e( 'This shortcode displays the plugin sales summary and listing on the frontend. Show parameter allows the user to control the display. Default value of the show parameter is both.', LDNFT_TEXT_DOMAIN ); ?></td>
        </tr>
    </table>
</div>