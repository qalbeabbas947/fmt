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
        add_action( 'wp_ajax_ldnft_customers_check_next',      [ $this, 'customers_check_next' ], 100 );
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
     * checks if there are subscribers records
     */
    public function customers_check_next() {
        
        $per_page       = isset($_REQUEST['per_page']) && intval($_REQUEST['per_page'])>0?intval($_REQUEST['per_page']):10;
        $offset         = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
        $current_recs   = isset($_REQUEST['current_recs']) && intval($_REQUEST['current_recs'])>0?intval($_REQUEST['current_recs']):0;

        $plugin_id      = isset($_REQUEST['plugin_id']) && intval($_REQUEST['plugin_id'])>0?intval($_REQUEST['plugin_id']):0;
        $interval       = isset($_REQUEST['status']) && intval($_REQUEST['status'])>0?intval($_REQUEST['status']):'';
        $offset_rec     = ($offset-1) * $per_page;

        $status = "";
        if( !empty( $this->selected_status ) ) {
            $status = "&filter=".$this->selected_status;
        }
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$plugin_id.'/users.json?count='.$per_page.'&offset='.$offset_rec.$status, 'GET', []);
        if( ! is_array( $result->users ) || count( $result->users ) == 0) {
            echo __('No more record(s) found.', LDNFT_TEXT_DOMAIN);
        }
        exit;
    }

    /**
     * Add Reset Course Progress submenu page under learndash menus
     */
    public function admin_menu_page() { 
        
        $user_id = get_current_user_id();
        
        $api = new Freemius_Api_WordPress( FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        try {
            
            $plugins = $api->Api('plugins.json?fields=id,title', 'GET', []);
            
            if( ! isset( $plugins->error )  ) {
                
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
                    update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-customerscolumnshidden', $default_hidden_columns );
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
        } catch(Exception $e) {
            
        }
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function customers_page( ) {

		if( !FS__HAS_PLUGINS ) {
            ?>
                <div class="wrap">
                    <h2><?php _e( 'Customers', LDNFT_TEXT_DOMAIN ); ?></h2>
                    <p><?php _e( 'No product(s) exists in your freemius account. Please, add a product on freemius and reload the page.', LDNFT_TEXT_DOMAIN ); ?></p>
                </div>
            <?php

            return;
        }
		
		$selected_plugin_id = 0;
        if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
            $selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        }
		
		$selected_status = 'active';
        if( isset( $_GET['status'] )  ) {
            $selected_status = $_GET['status']; 
        }
		
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
                <form id="ldnft-customer-filter" method="get">
                    <div class="ldnft_filters_top">
						<div class="alignleft actions bulkactions">
							<span class="ldnft_filter_labels"><?php _e( 'Filters:', LDNFT_TEXT_DOMAIN ); ?></span>
							<select name="ldfmt-plugins-filter" class="ldfmt-plugins-filter ldfmt-plugins-customers-filter" >
								<?php
									foreach( $products->plugins as $plugin ) {
										
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
								<option value=""><?php _e('Filter by status', LDNFT_TEXT_DOMAIN);?></option>
								<option value="active" <?php echo $selected_status=='active'?'selected':''; ?>><?php _e('Active', LDNFT_TEXT_DOMAIN);?></option>
								<option value="never_paid" <?php echo $selected_status=='never_paid'?'selected':''; ?>><?php _e('Free Users', LDNFT_TEXT_DOMAIN);?></option>
								<option value="paid" <?php echo $selected_status=='paid'?'selected':''; ?>><?php _e('Customers', LDNFT_TEXT_DOMAIN);?></option>
								<option value="paying" <?php echo $selected_status=='paying'?'selected':''; ?>><?php _e('Currently Customers', LDNFT_TEXT_DOMAIN);?></option>
							</select>
						</div>
                		<div id="ldnft_customers_data">
							<!-- Now we can render the completed list table -->
							<?php $testListTable->display() ?>
						</div>
					</div>
					<input type="hidden" class="ldnft-freemius-page" name="page" value="1" />
					<input type="hidden" class="ldnft-script-freemius-type" name="ldnft-script-freemius-type" value="customers" />
                </form>
            </div>
        <?php
    }
}

new LDNFT_Customers_Menu();