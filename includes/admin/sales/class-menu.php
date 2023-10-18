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
        
        $selected_plugin_id = 0;
        if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
            $selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        }
    

        $interval_str = '';
        if( isset( $_GET['interval'] ) && !empty( $_GET['interval'] ) ) {
            $interval_str = '&billing_cycle='.$_GET['interval'];
        }

        $status_str = '';
        if( isset( $_GET['status'] )  ) {
            $status_str = '&filter='.$_GET['status'];
        }
        
        $plan_str = '';
        if( isset( $_GET['plan_id'] ) && intval($_GET['plan_id']) > 0 ) {
            $plan_str = '&plan_id='.$_GET['plan_id']; 
        }

        $tem_per_page = 50;
        $tem_offset = 0;
        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$selected_plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str.$status_str.$plan_str, 'GET', []);
        $gross_total = 0;
        $tax_rate_total = 0;
        $total_number_of_sales = 0;
        $total_new_subscriptions = 0;
        $total_new_renewals = 0;
        
        if( isset($result) && isset($result->subscriptions) ) {
            $has_more_records = true;
            while($has_more_records) {
                foreach( $result->subscriptions as $payment ) {
                    
                    $pmts = $api->Api('plugins/'.$selected_plugin_id.'/subscriptions/'.$payment->id.'/payments.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str, 'GET', []);
                    foreach($pmts->payments as $pmt) {
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

                $tem_offset += $tem_per_page;
                $result = $api->Api('plugins/'.$selected_plugin_id.'/subscriptions.json?count='.$tem_per_page.'&offset='.$tem_offset.$interval_str, 'GET', []);
                if( count( $result->subscriptions ) > 0 ) {
                    $has_more_records = true;
                } else {
                    $has_more_records = false;
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
        
        if( !FS__HAS_PLUGINS ) {
            ?>
                <div class="wrap">
                    <h2><?php _e( 'Sales', LDNFT_TEXT_DOMAIN ); ?></h2>
                    <p><?php _e( 'No product(s) exists in your freemius account. Please, add a product on freemius and reload the page.', LDNFT_TEXT_DOMAIN ); ?></p>
                </div>
            <?php

            return;
        }

        $products = LDNFT_Freemius::$products;
        
        $selected_plugin_id = 0;
        if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
            $selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        }

        $selected_interval = '12';
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
                                    foreach( $products->plugins as $plugin )  {
                                        
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
                                <option value="1" <?php echo $selected_interval=='1'?'selected':'';?>><?php echo __( 'Monthly', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="12" <?php echo $selected_interval=='12'?'selected':'';?>><?php echo __( 'Annual', LDNFT_TEXT_DOMAIN );?></option>
                            </select>
                            <select name="ldfmt-sales-filter" class="ldfmt-sales-filter">
                                <option value="all"><?php echo __( 'All Status', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="not_refunded" <?php echo $selected_filter=='not_refunded'?'selected':'';?>><?php echo __( 'Active', LDNFT_TEXT_DOMAIN );?></option>
                                <option value="refunds" <?php echo $selected_filter=='refunds'?'selected':'';?>><?php echo __( 'Refunds', LDNFT_TEXT_DOMAIN );?></option>
                            </select>
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
												<th><?php _e('Plan ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-plan_id"></td>
												<th><?php _e('Pricing ID', LDNFT_TEXT_DOMAIN)?></th>
												<td id = "ldnft-review-coloumn-pricing_id"></td>
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
                    <input type="hidden" class="ldnft-freemius-page" name="page" value="1" />
					<input type="hidden" class="ldnft-script-freemius-type" name="ldnft-script-freemius-type" value="sales" />
                </form>
                
            </div>
        <?php
    }
}

new LDNFT_Sales_Menu();