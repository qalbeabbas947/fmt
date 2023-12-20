<?php
/**
 * Abort if this file is accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$plugins = LDNFT_Freemius::$products;

$default_id = 0;
?>
<div  id="general_settings" class="cs_ld_tabs">
    <div class="ldfmt-tab-data-heading"><span class="fa fa-book ldfmt-icon"></span> <?php _e( 'Webhook URL:', LDNFT_TEXT_DOMAIN ); ?> <strong><?php echo site_url(); ?>/wp-json/ldnft/v1/webhooks</strong>&nbsp;&nbsp;&nbsp;<a href="https://youtube.com" target="_blank"><strong>How to configure webhooks?</strong></a>
    </div> 
    <table class="setting-table-wrapper">
        <tr>
            <td width="55%" valign="top">
                <form id="ldnft-save-webhook-setting-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <table class="setting-table-wrapper">
                        <tbody class="ldnft-table-content">
                            <tr> 
                                <td width="20%" align="left" valign="top" class="ldnft_webhook_plugin_label">
                                    <strong><label align="left" for="ldnft_webhook_plugin_label"><?php _e( 'Filter by Plugin:', LDNFT_TEXT_DOMAIN ); ?></label></strong>
                                </td>
                                <td width="80%" align="left" valign="top">
                                    <?php
                                        if( is_array($plugins) && count( $plugins ) > 0 ) {
                                            echo '<select id="ldnft_webhook_plugin_ddl" name="ldnft_webhook_plugin_ddl">';
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
                                    <input type="button"class="button button-primary ldnft-load-webhook-settings-button" value="<?php _e( 'Load Settings', LDNFT_TEXT_DOMAIN ); ?>">    
                                    <div class="ldnft-plugin-ddl-loader" style="display:none"><img width="32px" class="" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" /></div>
                                </td>     
                            </tr>
                            <tr>
                                <td colspan="2">   
                                    <div class="ldnft-webhook-message" style="display:none"></div>
                                    <div class="ldnft-webhook-settings-fields"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="ldnft-submit-button submit-button" style="padding-top:10px">
                        <input class="form-control" type="hidden" name="action" value="ldnft_save_webhook_setting" />
                        <input type="submit" disabled="disabled" class="button button-primary ldnft-save-webhook-setting" value="<?php _e( 'Save Settings', LDNFT_TEXT_DOMAIN ); ?>" >
                    </div>
                </form>
            </td>
            <td width="45%" valign="top">
                <div class="ldnft-webhook-instruction-wapper">
                    <div class="ldnft-webhook-content">
                        <span class="dashicons dashicons-info"></span>
                        <span class="ldnft-instruction-title"><?php echo __( 'Disable Webhooks:', LDNFT_TEXT_DOMAIN ); ?></span>
                        <span class="ldnft-instruction-description"><?php echo __( '(not recommended) Disabling the freemius webhook events for the selected plugin/product will not sync new sales/subscription data and you will have to manually sync them every time.', LDNFT_TEXT_DOMAIN ); ?></span>
                    </div>
                    <div class="ldnft-webhook-content">
                        <span class="dashicons dashicons-info"></span>
                        <span class="ldnft-instruction-title"><?php echo __( 'Webhooks:', LDNFT_TEXT_DOMAIN ); ?></span>
                        <span class="ldnft-instruction-description"><?php echo __( 'Our script supports user.created, review.created, review.updated, payment.created, subscription.created, subscription.cancelled and plan.created webhooks only.', LDNFT_TEXT_DOMAIN ); ?></span>
                    </div>
                    <div class="ldnft-webhook-content">
                        <span class="dashicons dashicons-info"></span>
                        <span class="ldnft-instruction-title"><?php echo __( 'MailPoet:', LDNFT_TEXT_DOMAIN ); ?></span>
                        <span class="ldnft-instruction-description"><?php echo __( '(Optional) you can add your new customers to the selected mailpoet list.', LDNFT_TEXT_DOMAIN ); ?></span>
                    </div>
                    
                </div>
            </td>
        </tr>
    </table>
    
</div>