<?php
/**
 * Abort if this file is accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$plugins = LDNFT_Freemius::$products;
// $ldnft_settings = get_option( 'ldnft_settings' ); 
// $api_scope      = isset( $ldnft_settings['api_scope'] ) ? sanitize_text_field( $ldnft_settings['api_scope'] ) : 'developer';
// $dev_id         = isset( $ldnft_settings['dev_id'] ) ? sanitize_text_field( $ldnft_settings['dev_id'] ) : '';
// $public_key     = isset( $ldnft_settings['public_key'] ) ? sanitize_text_field( $ldnft_settings['public_key'] ): '';
// $secret_key     = isset( $ldnft_settings['secret_key'] ) ? sanitize_text_field( $ldnft_settings['secret_key'] ): '';
$default_id = 0;
?>
<div  id="general_settings" class="cs_ld_tabs">
    <div class="ldfmt-tab-data-heading"><span class="fa fa-cog ldfmt-icon"></span><?php _e( ' Settings', LDNFT_TEXT_DOMAIN ); ?>
    </div> 
    <form id="ldnft-save-setting-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <table class="setting-table-wrapper">
            <tbody class="ldnft-table-content">
                <tr> 
                    <td width="20%" align="left" valign="top">
						<strong><label align="left" for="ldnft_dev_id"><?php _e( 'Filter by Plugin:', LDNFT_TEXT_DOMAIN ); ?></label></strong>
					</td>
                    <td width="80%">
                        <form method="post">
                            <?php
                                if( is_array($plugins) && count( $plugins ) > 0 ) {
                                    echo '<select id="ldnft_webhook_plugin" name="ldnft_webhook_plugin">';
                                    foreach( $plugins as $plugin ) {
                                        if( $default_id == 0 ) {
                                            $default_id = $plugin->id;
                                        }
                                        ?>
                                            <option value="<?php echo $plugin->id; ?>"><?php echo $plugin->title; ?></option>
                                        <?php   
                                    }
                                    echo '</select>';
                                } else {
                                    echo '<span class="mailpoet_unable_to_import">'.__( 'No freemius product available for webhook settings.', LDNFT_TEXT_DOMAIN ).'</span>';
                                }
                            ?>
                            <input type="button"class="button button-primary ldnft-filter-webhook-button" value="<?php _e( 'Filter', LDNFT_TEXT_DOMAIN ); ?>">    
                        </form>
                        
                    </td>    
                </tr>
                <tr>
                    <td colspan="2">   
                        <div class="ldnft-filter-webhook-settings">

                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="ldnft-submit-button submit-button" style="padding-top:10px">
            <a href="javascript:;" class="button button-primary ldnft-save-setting"><?php _e( 'Test & Save', LDNFT_TEXT_DOMAIN ); ?></a>
        </div>
    </form>
</div>