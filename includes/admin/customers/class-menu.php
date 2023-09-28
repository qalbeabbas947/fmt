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
                <div id="icon-users" class="icon32"><br/></div>
                <h2><?php _e( 'Customers', LDNFT_TEXT_DOMAIN ); ?></h2>
                
                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                <form id="ldnft-customer-filter" method="get">
                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <!-- Now we can render the completed list table -->
                    <?php $testListTable->display() ?>
                </form>
                
            </div>
        <?php
    }
}

new LDNFT_Customers_Menu();