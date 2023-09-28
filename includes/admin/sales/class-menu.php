<?php
/**
 * LDNFT_Sales_Menu class manages the admin side Sales menu of freemius Sales.
 */

 if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * LDNFT_Sales Menu class
 */
class LDNFT_Sales_Menu { 

    private $default_hidden_columns;

    /** ************************************************************************
     * REQUIRED. Set up a constructor.
     ***************************************************************************/

	function __construct() {

        $this->default_hidden_columns = [ 
            'created', 
            'currency',
            'country_code', 
            'id', 
            'user_id',  
            'bound_payment_id', 
            'vat', 
            'install_id', 
            'plan_id', 
            'pricing_id', 
            'ip', 
            'zip_postal_code', 
            'vat_id', 
            'coupon_id', 
            'user_card_id', 
            'plugin_id', 
            'external_id'
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
                    __( 'Sales', LDNFT_TEXT_DOMAIN ),
                    __( 'Sales', LDNFT_TEXT_DOMAIN ),
                    'manage_options',
                    'freemius-sales',
                    [ $this,'sales_page'],
                    1
                );
                
                if( get_user_option( 'sales_hidden_columns_set', $user_id) != 'Yes' ) {
                    update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-salescolumnshidden', $default_hidden_columns );
                    update_user_option( $user_id, 'sales_hidden_columns_set', 'Yes' );
                }

                add_action( "load-$hook", function () {
                    
                    global $ldnftSalesListTable;
                    
                    $option = 'per_page';
                    $args = [
                            'label' => 'Sales Per Page',
                            'default' => 10,
                            'option' => 'sales_per_page'
                        ];
                    add_screen_option( $option, $args );
                    $ldnftSalesListTable = new LDNFT_Sales();
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
    public static function sales_page( ) {
        
        /**
         * Create an instance of our package class... 
         */
        $testListTable = new LDNFT_Sales();
        
        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $testListTable->prepare_items();
        ?>
            <div class="wrap">
                
                <div id="icon-users" class="icon32"><br/></div>
                <h2><?php _e( 'Sales', LDNFT_TEXT_DOMAIN ); ?></h2>
                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                <form id="sales-filter" method="get">
                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <!-- Now we can render the completed list table -->
                    <?php $testListTable->display() ?>
                </form>
                
            </div>
        <?php
    }
}

new LDNFT_Sales_Menu();