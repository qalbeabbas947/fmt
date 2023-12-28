<?php
/**
 * LDNFT_Customers_Menu class manages the admin side Customers menu of freemius Customers.
 */

 if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * LDNFT_Customers_Menu class
 */
class LDNFT_Customers_Menu {

    /**
     * Default hidden columns
     */
    private $default_hidden_columns;

    /** ************************************************************************
     * REQUIRED. Set up a constructor.
     ***************************************************************************/
	function __construct() {

        $this->default_hidden_columns = [ 
            'is_marketing_allowed', 
            'is_verified'
        ];

        add_action( 'admin_menu', [ $this, 'admin_menu_page' ] );
		add_action('wp_ajax_ldnft_customers_display', 		[ $this, 'ldnft_customers_display' ], 100 );
	}
	
	/**
     * Action wp_ajax for fetching the first time table structure
     */
    public function ldnft_customers_display() {
        
        $wp_list_table = new LDNFT_Customers();
        $wp_list_table->prepare_items();

        ob_start();
        $wp_list_table->display();
        $display = ob_get_clean();

        die(
            json_encode([
                "display" => $display
            ])
        );
    }

    /**
     * Add Reset Course Progress submenu page under learndash menus
     */
    public function admin_menu_page() { 
        
        $user_id = get_current_user_id();
        
        $hook = add_submenu_page( 
            'ldnft-freemius',
            __( 'Customers', LDNFT_TEXT_DOMAIN ),
            __( 'Customers', LDNFT_TEXT_DOMAIN ),
            'manage_options',
            'freemius-customers',
            [ $this,'customers_page'],
            2 
        );

        if( get_user_option( 'customers_hidden_columns_set', $user_id) != 'Yes' ) {
            update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-customerscolumnshidden', $this->default_hidden_columns );
            update_user_option( $user_id, 'customers_hidden_columns_set', 'Yes' );
        }

        add_action( "load-$hook", function () {
            
            global $ldnftCustomersListTable;
            
            $option = 'per_page';
            $args = [
                    'label' => 'Customers Per Page',
                    'default' => 10,
                    'option' => 'customers_per_page'
                ];
            add_screen_option( $option, $args );
            $ldnftCustomersListTable = new LDNFT_Customers();
        } );
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function customers_page( ) {

        global $wpdb;
        
		
        $table_name = $wpdb->prefix.'ldnft_customers';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            ?> 
                <div class="wrap">
                    <h2><?php _e( 'Customers', LDNFT_TEXT_DOMAIN ); ?></h2>
                    <p id="ldnft-dat-not-imported-message"><?php _e( 'Customers are not imported yet. Please, click <a href="admin.php?page=freemius-settings&tab=freemius-api">here</a> to open the setting page and start the import process automatically.', LDNFT_TEXT_DOMAIN ); ?></p>
                </div>
            <?php

            return;
        }
		
        if( !FS__HAS_PLUGINS ) {
            ?>
                <div class="wrap">
                    <h2><?php _e( 'Customers', LDNFT_TEXT_DOMAIN ); ?></h2>
                    <p id="ldnft-dat-not-imported-message"><?php _e( 'No product(s) exists in your freemius account. Please, add a product on freemius and reload the page.', LDNFT_TEXT_DOMAIN ); ?></p>
                </div>
            <?php

            return;
        }
		
		$selected_plugin_id         = isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ? intval( $_GET['ldfmt_plugins_filter'] ) : ''; 
        $selected_status            = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'active'; 
        $search                     = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : ''; 
        $selected_marketing         = isset( $_GET['marketing'] ) ? sanitize_text_field( $_GET['marketing'] ) : ''; 
        $selected_paymentstatus     = isset( $_GET['pmtstatus'] ) ? sanitize_text_field( $_GET['pmtstatus'] ) : ''; 

		$products = LDNFT_Freemius::$products;
		
        /**
         * Create an instance of our package class... 
         */
        $testListTable = new LDNFT_Customers();

        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $testListTable->prepare_items();
		
        ?>
            <div class="wrap">
                <h2><?php _e( 'Customers', LDNFT_TEXT_DOMAIN ); ?></h2>
                
                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                
                    <div class="ldnft_filters_top">
                        <form id="ldnft-customer-filter" method="get">
                            <div class="ldnft-filter-handler alignleft actions bulkactions">
                                <span class="ldnft_filter_labels"><?php _e( 'Filters:', LDNFT_TEXT_DOMAIN ); ?></span>
                                <select name="ldfmt-plugins-filter" class="ldfmt-plugins-filter ldfmt-plugins-customers-filter" >
                                    <option value=""><?php echo __( 'All Plugin/Product', LDNFT_TEXT_DOMAIN );?></option>
                                    <?php
                                        foreach( $products as $plugin ) {
                                            
                                            $selected = '';
                                            if( $selected_plugin_id == $plugin->id ) {
                                                $selected = ' selected = "selected"';   
                                            }
                                            ?>
                                                <option value="<?php echo $plugin->id; ?>" <?php echo $selected; ?>><?php echo $plugin->title; ?></option>
                                            <?php   
                                        }
                                    ?>
                                </select>&nbsp;&nbsp;
                                <select name="ldfmt-plugins-status" class="ldfmt-plugins-customers-status">
                                    <option value=""><?php _e('All Statuses', LDNFT_TEXT_DOMAIN);?></option>
                                    <option value="1" <?php echo $selected_status=='1'?'selected':''; ?>><?php _e('Verified', LDNFT_TEXT_DOMAIN);?></option>
                                    <option value="0" <?php echo $selected_status=='0'?'selected':''; ?>><?php _e('Unverified', LDNFT_TEXT_DOMAIN);?></option>
                                </select>
                                <select name="ldfmt-plugins-marketing" class="ldfmt-plugins-customers-marketing">
                                    <option value=""><?php _e('All', LDNFT_TEXT_DOMAIN);?></option>
                                    <option value="1" <?php echo $selected_marketing=='1'?'selected':''; ?>><?php _e('Is Marketing Allowed', LDNFT_TEXT_DOMAIN);?></option>
                                    <option value="0" <?php echo $selected_marketing=='0'?'selected':''; ?>><?php _e('Marketing Not Allowed', LDNFT_TEXT_DOMAIN);?></option>
                                </select>
                                <select name="ldfmt-payment-status" class="ldfmt-payment-status">
                                    <option value=""><?php _e('All Payment Status', LDNFT_TEXT_DOMAIN);?></option>
                                    <option value="paid" <?php echo $selected_paymentstatus=='paid'?'selected':''; ?>><?php _e('Paid', LDNFT_TEXT_DOMAIN);?></option>
                                    <option value="free" <?php echo $selected_paymentstatus=='free'?'selected':''; ?>><?php _e('Free', LDNFT_TEXT_DOMAIN);?></option>
                                </select>
                                <!-- <input type="text" value="<?php echo $search;?>" name="ldnft-customers-general-search" class="form-control ldnft-customers-general-search" placeholder="<?php _e('Search', LDNFT_TEXT_DOMAIN);?>"> -->
                                <input type="button" name="ldnft-customer-search-button" value="<?php _e('Filter', LDNFT_TEXT_DOMAIN);?>" class="btn button ldnft-customer-search-button" />
                            </div>
                        </form>
                        <form id="ldnft-reviews-filter-text" method="post">
                            <input type="text" value="<?php echo $search;?>" name="ldnft-customers-general-search" class="form-control ldnft-customers-general-search" placeholder="<?php _e('Search', LDNFT_TEXT_DOMAIN);?>">
                            <input type="submit" name="ldnft-customer-search-button-txt" value="<?php _e('Search', LDNFT_TEXT_DOMAIN);?>" class="btn button ldnft-customer-search-button-txt" />
                        </form>
                		<div id="ldnft_customers_data">
							<!-- Now we can render the completed list table -->
							<?php $testListTable->display() ?>
						</div>
					</div>
					<input type="hidden" class="ldnft-freemius-page" name="page" value="1" />
                    <input type="hidden" class="ldnft-freemius-order" name="order" value="id" />
                    <input type="hidden" class="ldnft-freemius-orderby" name="orderby" value="asc" />
					<input type="hidden" class="ldnft-script-freemius-type" name="ldnft-script-freemius-type" value="customers" />
                </form>
            </div>
        <?php
    }
}

new LDNFT_Customers_Menu();