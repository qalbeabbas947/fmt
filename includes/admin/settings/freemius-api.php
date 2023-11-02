<?php
/**
 * Abort if this file is accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$ldnft_settings = get_option( 'ldnft_settings' ); 
$api_scope      = isset( $ldnft_settings['api_scope'] ) ? sanitize_text_field( $ldnft_settings['api_scope'] ) : 'developer';
$dev_id         = isset( $ldnft_settings['dev_id'] ) ? sanitize_text_field( $ldnft_settings['dev_id'] ) : '';
$public_key     = isset( $ldnft_settings['public_key'] ) ? sanitize_text_field( $ldnft_settings['public_key'] ): '';
$secret_key     = isset( $ldnft_settings['secret_key'] ) ? sanitize_text_field( $ldnft_settings['secret_key'] ): '';
$cron_status    = get_option('ldnft_run_cron_based_on_plugins');
?>
<div id="general_settings" class="cs_ld_tabs">
    <div class="ldfmt-tab-data-heading"><span class="fa fa-cog ldfmt-icon"></span><?php _e( ' Settings', LDNFT_TEXT_DOMAIN ); ?>
    </div> 
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <table class="setting-table-wrapper">
            <tbody class="ldnft-table-content">
                <tr> 
                    <td width="20%" align="left" valign="top">
						<strong><label align="left" for="ldnft_dev_id"><?php _e( 'Developer ID', LDNFT_TEXT_DOMAIN ); ?></label></strong>
					</td>
                    <td width="80%">
                        <input type="text" size="60" id="ldnft_dev_id" name="ldnft_settings[dev_id]" value="<?php echo $dev_id;?>">
                        <p class="description" style="font-weight: normal;">
                        <?php _e( 'Developer ID of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                        </p>
                    </td>    
                </tr>   
				<tr> 
                    <td align="left" valign="top">
						<strong><label align = "left" for="ldnft_public_key"><?php _e( 'Public Key', LDNFT_TEXT_DOMAIN ); ?></label></strong>
					</td>
                    <td>
                        <input type="text" size="60" id="ldnft_public_key" name="ldnft_settings[public_key]" value="<?php echo $public_key;?>">
                        <p class="description" style="font-weight: normal;">
                            <?php _e( 'Public Key of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                        </p>
                    </td>    
                </tr>
                
				<tr> 
                    <td align="left" valign="top">
						<strong><label align = "left" for="ldnft_secret_key"><?php _e( 'Secret Key', LDNFT_TEXT_DOMAIN ); ?></label></strong>
					</td>
                    <td>
                        <input type="text" size="60" id="ldnft_secret_key" name="ldnft_settings[secret_key]" value="<?php echo $secret_key;?>">
                        <p class="description" style="font-weight: normal;">
                        <?php  _e('Scret Key of the Freemius API', LDNFT_TEXT_DOMAIN ); ?>
                        </p>
                    </td>    
                </tr>
                <?php if( $cron_status != 'complete' && FS__API_CONNECTION ) { ?>
                    <tr> 
                        <td colspan="2" class="ldnft-process-freemius-data-info">
                            <div class="ldnft-process-freemius-data-plugins" style="display:none">
                                <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                                <span class="ldnft-loading-wrap"><?php _e( 'Please wait, while we are syncing the freemius plugins data.', LDNFT_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="ldnft-process-freemius-data-plans" style="display:none">
                                <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                                <span class="ldnft-loading-wrap"><?php _e( 'Please wait, while we are syncing the freemius plans data.', LDNFT_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="ldnft-process-freemius-data-customers" style="display:none">
                                <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                                <span class="ldnft-loading-wrap"><?php _e( 'Please wait, while we are syncing the freemius customers data.', LDNFT_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="ldnft-process-freemius-data-sales" style="display:none">
                                <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                                <span class="ldnft-loading-wrap"><?php _e( 'Please wait, while we are syncing the freemius sales data.', LDNFT_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="ldnft-process-freemius-data-subscription" style="display:none">
                                <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                                <span class="ldnft-loading-wrap"><?php _e( 'Please wait, while we are syncing the freemius subscription data.', LDNFT_TEXT_DOMAIN ); ?></span>
                            </div>
                            <div class="ldnft-process-freemius-data-reviews" style="display:none">
                                <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                                <span class="ldnft-loading-wrap"><?php _e( 'Please wait, while we are syncing the freemius reviews data.', LDNFT_TEXT_DOMAIN ); ?></span>
                            </div>
                        </td>    
                    </tr>
                <?php } ?>
            </tbody>
        </table>
       
        <div class="submit-button" style="padding-top:10px">
            <?php wp_nonce_field( 'ldnft_nounce', 'ldnft_nounce_field' ); ?>
            <input type="hidden" name="action" value="ldnft_submit_action" />
            <input type="hidden" id="ldnft_api_scope" name="ldnft_settings[api_scope]" value="developer">
            <input type="submit" class="button button-primary ldnft-save-setting" name="ldnft_submit_form" value="<?php _e( 'Test & Save', LDNFT_TEXT_DOMAIN ); ?>">
            <input type="button" class="button button-primary ldnft-sync-data-setting" name="ldnft_sync_data" value="<?php _e( 'Sync Data', LDNFT_TEXT_DOMAIN ); ?>">
            <div id="ldnft-settings-import-mailpoet-message" style="display:none;" class="ldnft-settings-sync-data-message"></div>
        </div>
    </form>
</div>