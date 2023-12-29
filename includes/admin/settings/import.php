<?php
/**
 * Abort if this file is accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$api        = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
$plugins    = LDNFT_Freemius::$products;
?>
<div id="general_settings" class="cs_ld_tabs">
    <div class="ldfmt-tab-data-heading"><span class="fa fa-cogs ldfmt-icon"></span> <?php _e( 'Mailpoet Import', 'ldninjas-freemius-toolkit' ); ?></div> 
    <form class="ldnft-settings-mailpoet" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
        <?php 
            $mail_not_active = false;
            if (!is_plugin_active('mailpoet/mailpoet.php')) {
                $mail_not_active = true;
            }  
            
            $allow_import = true;
        ?>
        
        <table class="setting-table-wrapper" width="100%">
            <tbody class="ldnft-table-content">
                <tr>
                    <td colspan="2">
                        <div id="ldnft-settings-import-mailpoet-message" style="display:none"></div>
                        <div id="ldnft-settings-import-mailpoet-errmessage" style="display:none"></div>
                    </td>
                </tr>
                <tr> 
                    <td width="20%" align="left" valign="top">
						<strong><label align="left" for="ldnft_dev_id"><?php _e( 'Mailpoet List', 'ldninjas-freemius-toolkit' ); ?></label></strong>
					</td>
                    <td width="80%">
                        <?php
                            if (defined('MAILPOET_VERSION')) {
								$table_name = $wpdb->prefix.'mailpoet_segments';
								$list = $wpdb->get_results('select id, name from '.$table_name.'');
								$is_list_available = true;
								if( is_array($list) && count( $list ) > 0 ) {
									echo '<select id="ldnft_mailpeot_list" name="ldnft_mailpeot_list">';
									foreach( $list as $item ) {
										echo '<option value="'.$item->id.'">'.$item->name.'</option>';
									}
									echo '</select>';
								} else {
									echo '<span class="mailpoet_unable_to_import">'.__( 'No mailpoet list for import.', 'ldninjas-freemius-toolkit' ).'</span>';
									$allow_import = false;
									$is_list_available = false;
								}
							} else {
								echo '<span class="mailpoet_unable_to_import">'.__( 'Activate the mailpoet plugin.', 'ldninjas-freemius-toolkit' ).'</span>';
								$allow_import = false;
								$is_list_available = false;
							}
                        ?>
                        <?php if($is_list_available) { ?>
                            <p>
                                <?php _e( 'Select a list before import the actual subscribers.', 'ldninjas-freemius-toolkit' ); ?>
                            </p>
                        <?php } ?>
                    </td>    
                </tr>
				<tr> 
                    <td align="left" valign="top">
						<strong><label align = "left" for="ldnft_public_key"><?php _e( 'Plugin:', 'ldninjas-freemius-toolkit' ); ?></label></strong>
					</td>
                    <td>
                        <?php
                            $is_plugins_available = true;
                            if( is_array($plugins) && count( $plugins ) > 0 ) {
                                echo '<select id="ldnft_mailpeot_plugin" name="ldnft_mailpeot_plugin">';
                                foreach( $plugins as $plugin ) {
                                    ?>
                                        <option value="<?php echo $plugin->id; ?>"><?php echo $plugin->title; ?></option>
                                    <?php   
                                }
                                echo '</select>';
                            } else {
                                echo '<span class="mailpoet_unable_to_import">'.__( 'No freemius product available for import.', 'ldninjas-freemius-toolkit' ).'</span>';
                                $allow_import = false;
                                $is_plugins_available = false;
                            }
                        ?>
                        <?php if($is_plugins_available) { ?>
                            <p>
                                <?php _e( 'Select a product whose subscribers needs to be imported.', 'ldninjas-freemius-toolkit' ); ?>
                            </p>
                        <?php } ?> 
                    </td>    
                </tr>
                <tr> 
                    <td align="left" valign="top">
						<strong><label align = "left" for="ldnft_public_key"><?php _e( 'Customers Type:', 'ldninjas-freemius-toolkit' ); ?></label></strong>
					</td>
                    <td>
                        <?php
                            if( is_array($plugins) && count( $plugins ) > 0 ) {
                                echo '<select id="ldnft_mailpeot_ctype" name="ldnft_mailpeot_ctype">';
                                ?>
                                    <option value=""><?php _e( 'All Customers', 'ldninjas-freemius-toolkit' ); ?></option>
                                    <option value="paid"><?php _e( 'Paid Customers', 'ldninjas-freemius-toolkit' ); ?></option>
                                    <option value="free"><?php _e( 'Free Customers', 'ldninjas-freemius-toolkit' ); ?></option>
                                <?php   
                                echo '</select>';
                            }
                        ?>
                        
                    </td>    
                </tr>
            </tbody>
        </table>
        <div class="submit-button" style="padding-top:10px">
            <?php wp_nonce_field( 'ldnft_mailpoet_nounce', 'ldnft_mailpoet_nounce_field' ); ?>
            <input type="hidden" name="action" value="ldnft_mailpoet_submit_action" />
            <div class="ldnft-success-message">
                <img class="ldnft-success-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                <span class="ldnft-loading-wrap"><?php _e( 'Please wait! Import is being processed.', 'ldninjas-freemius-toolkit' ); ?></span>
            </div>
            <?php if (!is_plugin_active('mailpoet/mailpoet.php')) { ?>
                <div id="ldnft-settings-import-mailpoet-general">
                    <?php _e( 'This section requires MailPoet to be installed and configured.', 'ldninjas-freemius-toolkit' ); ?>
                </div>    
            <?php } ?>
            <input type="submit" <?php if(!$allow_import || $mail_not_active) { ?> disabled="disabled" <?php } ?> class="button button-primary ldnft-mailpoet-save-setting_import" name="ldnft_mailpoet_submit_form_import" value="<?php _e( 'Import Subscribers', 'ldninjas-freemius-toolkit' ); ?>">
        </div>
    </form>
</div>