<?php
/**
 * LDNFT_Sales_Menu class manages the admin side Sales menu of freemius Sales.
 */

/**
 * LDNFT_Sales Menu class
 */
class LDNFT_Sales_Menu { 

    /**
     * Default hidden columns
     */
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
        add_action('wp_ajax_ldnft_sales_display',  [ $this, 'ldnft_sales_display' ], 100 );
        add_action( 'wp_ajax_ldnft_sales_summary', [ $this, 'sales_summary' ], 100 );
	}

    /**
     * Action wp_ajax for fetching ajax_response
     */
    public function sales_summary() {

        global $wpdb;
        
        $selected_plugin_id = 0;
        if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
            $selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        }

        $selected_interval = '';
        if( isset( $_GET['interval'] ) && !empty( $_GET['interval'] ) ) {
            $selected_interval = $_GET['interval'];
        }

        $selected_status = '';
        if( isset( $_GET['status'] )  ) {
            $selected_status = $_GET['status'];
        }
        
        $plan_str = '';
        if( isset( $_GET['plan_id'] ) && intval($_GET['plan_id']) > 0 ) {
            $plan_str = $_GET['plan_id']; 
        }

        $table_name = $wpdb->prefix.'ldnft_transactions';  
        $where = " where plugin_id='".$selected_plugin_id."'";
        
        if( $selected_status != 'all' ) {
            switch( $selected_status ) {
                case "not_refunded":
                    $where .= " and type='payment'";
                    break;
                case "refunds":
                    $where .= " and type='refund'";
                    break;
                case "chargeback":
                    $where .= " and type='chargeback'";
                    break;
                case "lost_dispute":
                    $where .= " and type='lost_dispute'";
                    break;
                        
            }
        }

        $where_interval = '';
        if( !empty( $selected_interval )) {
            switch( $selected_interval ) {
                case "current_week":
                    $where_interval = " and YEARWEEK(created) = YEARWEEK(NOW())";
                    break;
                case "last_week":
                    $where_interval = ' and Date(created) between date_sub(now(),INTERVAL 1 WEEK) and now()';
                    break;
                case "current_month":
                    $where_interval = ' and MONTH(created) = MONTH(now()) and YEAR(created) = YEAR(now())';
                    break;
                case "last_month":
                    $where_interval = ' and Date(created) between Date((now() - interval 1 month)) and Date(now())';
                    break;
                default:
                    $where_interval = " and Date(created) = '".date('Y-m-d')."'";
                    break;
            }
        }

        // $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        // $result = $api->Api('plugins/'.$selected_plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str.$status_str.$plan_str, 'GET', []);
        
        $result = $wpdb->get_results( "SELECT * FROM $table_name $where $where_interval" );
        
        $gross_total = 0; 
        $tax_rate_total = 0;
        $total_number_of_sales = 0;
        $total_new_subscriptions = 0;
        $total_new_renewals = 0;
        
        if( isset($result) && isset($result) ) {
            $has_more_records = true;
            foreach( $result as $pmt ) {
                    
                $gross_total += $pmt->gross;
                $tax_rate_total += $pmt->vat;
                $total_number_of_sales++;
                if( $pmt->is_renewal == '1' || $pmt->is_renewal == 1 ) {
                    $total_new_renewals++;
                } else {
                    $total_new_subscriptions++;
                }
            } 
        }

        $data = [
            'gross_total' => number_format($gross_total, 2),
            'tax_rate_total' => number_format($tax_rate_total, 2),
            'total_number_of_sales' => $total_number_of_sales,
            'total_new_subscriptions' => $total_new_subscriptions,
            'total_new_renewals' => $total_new_renewals
        ];
        
        die(
            json_encode($data)
        );
    }

    /**
     * Action wp_ajax for fetching the first time table structure
     */
    public function ldnft_sales_display() {
        
        $wp_list_table = new LDNFT_Sales();
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
        if( FS__API_CONNECTION  ) {
                
            $hook = add_submenu_page ( 
                'ldnft-freemius',
                __( 'Sales', LDNFT_TEXT_DOMAIN ),
                __( 'Sales', LDNFT_TEXT_DOMAIN ),
                'manage_options',
                'freemius-sales',
                [ $this,'sales_page' ],
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
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function sales_page( ) {
        
        global $wpdb;

        if( !FS__HAS_PLUGINS ) {
            ?>
                <div class="wrap">
                    <h2><?php _e( 'Sales', LDNFT_TEXT_DOMAIN ); ?></h2>
                    <p><?php _e( 'No product(s) exists in your freemius account. Please, add a product on freemius and reload the page.', LDNFT_TEXT_DOMAIN ); ?></p>
                </div>
            <?php

            return;
        }

        $table_name = $wpdb->prefix.'ldnft_transactions';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            ?> 
                <div class="wrap">
                    <h2><?php _e( 'Sales', LDNFT_TEXT_DOMAIN ); ?></h2>
                    <p id="ldnft-dat-not-imported-message"><?php _e( 'Sales are not imported yet. Please, click <a href="admin.php?page=freemius-settings&tab=freemius-api">here</a> to open the setting page and start the import process automatically.', LDNFT_TEXT_DOMAIN ); ?></p>
                </div>
            <?php

            return;
        }

        $products = LDNFT_Freemius::$products;
        
        $selected_plugin_id = 0;
        if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
            $selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        }

        $selected_interval = 'today';
        if( isset($_GET['interval'])  ) {
            $selected_interval = $_GET['interval']; 
        }
        
        $selected_filter = 'all';
        if( isset( $_GET['filter'] )  ) {
            $selected_filter = $_GET['filter']; 
        }

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
                <form id="ldnft-sales-filter" method="get">
                    
                    <div class="ldnft_filters_top">
                        <div class="alignleft actions bulkactions">
                            <h2><?php _e( 'Sales', LDNFT_TEXT_DOMAIN ); ?></h2>
                            <select name="ldfmt-plugins-filter" class="ldfmt-plugins-filter ldfmt-plugins-sales-filter">
                                <?php
                                    foreach( $products as $plugin )  {
                                        
                                        $selected = '';
                                        if( $selected_plugin_id == $plugin->id ) {
                                            $selected = ' selected = "selected"';   
                                        }
                                        ?>
                                            <option value="<?php echo $plugin->id; ?>" <?php echo $selected; ?>><?php echo $plugin->title; ?></option>
                                        <?php   
                                    }
                                ?>
                            </select>
                            <select name="ldfmt-sales-interval-filter" class="ldfmt-sales-interval-filter">
                                <option value=""><?php echo __( 'All Time', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="today" <?php echo $selected_interval=='today'?'selected':'';?>><?php echo __( 'Today', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="current_week" <?php echo $selected_interval=='current_week'?'selected':'';?>><?php echo __( 'Current Week', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="last_week" <?php echo $selected_interval=='last_week'?'selected':'';?>><?php echo __( 'Last Week', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="current_month" <?php echo $selected_interval=='current_month'?'selected':'';?>><?php echo __( 'Current Month', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="last_month" <?php echo $selected_interval=='last_month'?'selected':'';?>><?php echo __( 'Last Month', LDNFT_TEXT_DOMAIN );?></option>
                            </select>
                            <select name="ldfmt-sales-filter" class="ldfmt-sales-filter">
                                <option value="all"><?php echo __( 'All Status', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="not_refunded" <?php echo $selected_filter=='not_refunded'?'selected':'';?>><?php echo __( 'Active', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="refunds" <?php echo $selected_filter=='refunds'?'selected':'';?>><?php echo __( 'Refunds', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="chargeback" <?php echo $selected_filter=='chargeback'?'selected':'';?>><?php echo __( 'Chargeback', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="lost_dispute" <?php echo $selected_filter=='lost_dispute'?'selected':'';?>><?php echo __( 'Lost Dispute', LDNFT_TEXT_DOMAIN );?></option>
                            </select>
                            <input type="button" name="ldnft-sales-search-button" value="<?php _e('Search', LDNFT_TEXT_DOMAIN);?>" class="btn button ldnft-sales-search-button" />
                        </div>
                        <div style="clear:both">&nbsp;</div> 
                        <div class="ldfmt-sales-upper-info">
                            <div class="ldfmt-gross-sales-box ldfmt-sales-box">
                                <label><?php echo __( 'Gross Sales', LDNFT_TEXT_DOMAIN );?></label>
                                <div class="ldnft_points">
                                    <span class="ldnft_sales_points"></span>
                                    <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                                </div>
                            </div>
                            <div class="ldfmt-gross-gateway-box ldfmt-sales-box">
                                <label><?php echo __('Total Tax Rate', LDNFT_TEXT_DOMAIN);?></label>
                                <div class="ldnft_tax_fee">
                                    <span class="ldnft_sales_tax_fee"></span>
                                    <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                                </div>
                                
                            </div>
                            <div class="ldfmt-new-sales-box ldfmt-sales-box">
                                <label><?php echo __('Total Sales Count', LDNFT_TEXT_DOMAIN);?></label>
                                <div class="ldnft_new_sales_count">
                                    <span class="ldnft_sales_new_sales_count"></span>
                                    <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                                </div>
                            </div>
                            <div class="ldfmt-new-sales-box ldfmt-sales-box">
                                <label><?php echo __('New subscriptions', LDNFT_TEXT_DOMAIN);?></label>
                                <div class="ldnft_new_sales_count">
                                    <span class="ldnft_sales_new_sales_count"></span>
                                    <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                                </div>
                            </div>
                            <div class="ldfmt-renewals-count-box ldfmt-sales-box">
                                <label><?php echo __('Total Renewals', LDNFT_TEXT_DOMAIN);?></label>
                                <div class="ldnft_renewals_count">
                                    <span class="ldnft_sales_renewals_count"></span>
                                    <?php echo LDNFT_Admin::get_bar_preloader("ldnft-subssummary-loader");?>
                                </div>
                            </div>
                        </div>
                        <div id="ldnft-admin-modal" class="ldnft-admin-modal">
                            <!-- Modal content -->
                            <div class="ldnft-admin-modal-content">
                                <div class="ldnft-admin-modal-header">
                                <span class="ldnft-admin-modal-close">&times;</span>
                                    <h2><?php echo __( 'Sales Detail', LDNFT_TEXT_DOMAIN );?></h2>
                                </div>
                                <div class="ldnft-admin-modal-body">
									<table id="ldnft-reviews-popup" width="100%" cellpadding="5" cellspacing="1">
										<tbody>
 											<tr>
												<th><?php _e('Transaction', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-transaction-id"></td>
												<th><?php _e('User ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-user_id"></td>
											</tr>
											<tr>
												<th><?php _e('Name', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-username"></td>
												<th><?php _e('Transaction', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-useremail"></td>
											</tr>
											<tr>
												<th><?php _e('Subscription ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-subscription_id"></td>
												<th><?php _e('Gateway Fee', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-gateway_fee"></td>
											</tr>
											<tr>
												<th><?php _e('Total Amount', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-gross"></td>
												<th><?php _e('License', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-license_id"></td>
											</tr>
											<tr>
												<th><?php _e('Gateway', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-gateway"></td>
												<th><?php _e('Type', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-type"></td>
											</tr>
											<tr>
												<th><?php _e('Renewal?', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-is_renewal"></td>
												<th><?php _e('Country', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-country_code"></td>
											</tr>
											<tr>
												<th><?php _e('Bound Payment ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-bound_payment_id"></td>
												<th><?php _e('Payment Date', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-created"></td>
											</tr>
											<tr>
												<th><?php _e('VAT', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-vat"></td>
												<th><?php _e('Install ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-install_id"></td>
											</tr>
											
											<tr>
												<th><?php _e('IP', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-ip"></td>
												<th><?php _e('Zip/Postal Code', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-zip_postal_code"></td>
											</tr>
											<tr>
												<th><?php _e('VAT ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-vat_id"></td>
												<th><?php _e('Coupon ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-coupon_id"></td>
											</tr>
											<tr>
												<th><?php _e('Card ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-user_card_id"></td>
												<th><?php _e('Product ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-plugin_id"></td>
											</tr>
											<tr>
												<th><?php _e('External ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-external_id"></td>
												<th><?php _e('Currency', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-currency"></td>
											</tr>
											<tr>
												<th><?php _e('User Name', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-username"></td>
												<th><?php _e('Email', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-useremail"></td>
											</tr>
										</tbody>
									</table>
								</div>
                            </div>
                        </div>
                    </div>
                    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                    
                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                    <div id="ldnft_sales_data">
                        <!-- Now we can render the completed list table -->
                        <?php $testListTable->display() ?>
                    </div>
                    <input type="hidden" class="ldnft-freemius-order" name="order" value="id" />
                    <input type="hidden" class="ldnft-freemius-orderby" name="orderby" value="asc" />
                    <input type="hidden" class="ldnft-freemius-page" name="page" value="1" />
					<input type="hidden" class="ldnft-script-freemius-type" name="ldnft-script-freemius-type" value="sales" />
                </form>
                
            </div>
        <?php
    }
}

new LDNFT_Sales_Menu();