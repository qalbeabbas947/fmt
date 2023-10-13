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

/**
 * Add an information title 
 *
 * @param String    $info_text  Information text to display
 */
function ldfmt_add_info_title( $info_text ) {
?>
    <div class="ldfmt-div-info ldfmt-info">
        <span class="dashicons dashicons-info"></span> 
        <i><?php _e( $info_text, LDNFT_TEXT_DOMAIN ); ?></i>
    </div>
<?php
}
?>

<div id="general_settings" class="cs_ld_tabs"> 
    <div class="ldfmt-tab-data-heading"><span class="dashicons dashicons-shortcode ldfmt-icon"></span><?php _e( 'Shortcodes', LDNFT_TEXT_DOMAIN ); ?></div>
    <div class="ldfmt-tab-shortcode-data">
        <code> [LDNFT_Reviews product_id="?" listing_type="[ pagination | onetime  slider ]" limit="?" ] </code>
        <?php echo ldfmt_add_info_title( "Displays attached product's reviews on the frontend. List type parameters allows you to switch the display from  listing to slider. Limit parameter allows you to restrict the display of records at a time in screen." ); ?>
    </div>
    <div class="ldfmt-tab-shortcode-data">
        <code> [LDNFT_Sales product_id = "?" show="[ summary  |  listing  |  both ]"] </code>
        <?php echo ldfmt_add_info_title( 'This shortcode displays the plugin sales summary and listing based on the attached product/plugin id on the frontend. Show parameter allows the user to control the display. Default value of the show parameter is both.' ); ?>
    </div>
    <div class="ldfmt-tab-shortcode-data">
        <code> [LDNFT_Number_of_Sales product_id="?"] </code>
        <?php echo ldfmt_add_info_title( 'This shortcode displays the total number of plugin/products sales based on the product_id/plugin_id.' ); ?>
    </div>
    <div class="ldfmt-tab-shortcode-data">
        <code> [LDNFT_Product_Rating product_id="?"] </code>
        <?php echo ldfmt_add_info_title( 'This shortcode displays the average rating of plugin/products based on the product_id/plugin_id.' ); ?>
    </div>
    <div class="ldfmt-tab-shortcode-data">
        <code> [LDNFT_Checkout product_id="?" plan_id="?" image="?"] </code>
        <?php echo ldfmt_add_info_title( 'This shortcode displays the checkout popup form of plugin/products based on the product_id/plugin_id. Prices will display based on the provided plan_id. By default, first premius plan will be used. You can also add the plugin/addon image in the shortocde that will be displayed on the checkout.' ); ?>
    </div>
</div>