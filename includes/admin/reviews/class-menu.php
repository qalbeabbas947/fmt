<?php
/**
 * LDNFT_Reviews_Menu class manages the admin side Reviews menu of freemius Reviews.
 */

 if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * LDNFT_Reviews_Menu class
 */
class LDNFT_Reviews_Menu {

    /**
     * Default hidden columns
     */
    private $default_hidden_columns;

    /** ************************************************************************
     * REQUIRED. Set up a constructor.
     ***************************************************************************/

	function __construct() {

        $this->default_hidden_columns = [ 
            'username',
            'name',
            'job_title',
            'company_url',
            'picture',
            'profile_url',
            'is_verified',
            'ID',
            'sharable_img', 
            'company', 
            'created', 						
        ];

        add_action( 'admin_menu', 								[ $this, 'admin_menu_page' ] );
		add_action( 'wp_ajax_ldnft_reviews_display', 			[ $this, 'ldnft_reviews_display' ], 100 );
        add_action( 'wp_ajax_ldnft_reviews_enable_disable',     [ $this, 'reviews_enable_disable' ], 100 );
        add_action( 'admin_enqueue_scripts',                    [ $this, 'admin_enqueue_scripts_callback' ] );
	}
	
    /**
     * Action wp_ajax for fetching the first time table structure
     */
    public function admin_enqueue_scripts_callback() {
        $screen = get_current_screen(); 
        if( $screen->id == 'freemius-toolkit_page_freemius-reviews' ) {

            /**
             * enqueue admin css
             */
            wp_enqueue_style( 'ldnft-backend-css', LDNFT_ASSETS_URL . 'css/backend/backend.css', [], LDNFT_VERSION, null );
            
            /**
             * enqueue admin js
             */
            wp_enqueue_script( 'ldnft-backendcookie-js', LDNFT_ASSETS_URL . 'js/backend/jquery.cookie.js', [ 'jquery' ], LDNFT_VERSION, true ); 
            
            wp_enqueue_script( 'ldnft-reviews-backend-js', LDNFT_ASSETS_URL . 'js/backend/ldnft-reviews.js', [ 'jquery' ], LDNFT_VERSION, true ); 
            wp_localize_script( 'ldnft-reviews-backend-js', 'LDNFT', [  
                'ajaxURL'                       => admin_url( 'admin-ajax.php' ),
                'loader'                        => LDNFT_ASSETS_URL .'images/spinner-2x.gif',
                'preloader_gif_img'             => LDNFT_Admin::get_bar_preloader()
            ] );
        }
    }
    
    /**
     * Action wp_ajax for fetching the first time table structure
     */
    public function reviews_enable_disable() {
        
        global $wpdb;
        
        header('Content-Type: application/json; charset=utf-8');

        $id         = isset( $_REQUEST['id'] ) ? sanitize_text_field( $_REQUEST['id'] ) : 0;
        $plugin_id  = isset( $_REQUEST['plugin_id'] ) ? sanitize_text_field( $_REQUEST['plugin_id'] ) : 0;
        $status     = $_REQUEST['status'] == "true" ? true : false;

        if( intval( $id ) == 0 ) {
            echo json_encode([ 'status'=> 'error', 'message' => __('Invalid review id.', 'ldninjas-freemius-toolkit') ]);
            exit();
        } 

        $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        $result = $api->Api('plugins/'.$plugin_id.'/reviews/'.$id.'.json', 'PUT', [ 'is_featured' => $status ] );
        
        $table_name = $wpdb->prefix.'ldnft_reviews';
        $wpdb->update($table_name, 
                        array(
                            'is_featured'                => $status
                        ), array('id'=>$id));
        $result = $api->Api('plugins/'.$plugin_id.'/reviews/'.$id.'.json', 'PUT', [ 'is_featured' => $status ] );
        echo json_encode([ 'status'=> 'success', 's'=> $status,  'data'=> $result, 'message' => __('Record is updated.', 'ldninjas-freemius-toolkit') ]);
        exit;
    }

	/**
     * Action wp_ajax for fetching the first time table structure
     */
    public function ldnft_reviews_display() {
        
        $wp_list_table = new LDNFT_Reviews();
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
            __( 'Reviews', 'ldninjas-freemius-toolkit' ),
            __( 'Reviews', 'ldninjas-freemius-toolkit' ),
            'manage_options',
            'freemius-reviews',
            [ $this,'reviews_page']
        );
        
        if( get_user_option( 'reviews_hidden_columns_set', $user_id) != 'Yes' ) {
            update_user_option( $user_id, 'managefreemius-toolkit_page_freemius-reviewscolumnshidden', $this->default_hidden_columns );
            update_user_option( $user_id, 'reviews_hidden_columns_set', 'Yes' );
        }

        add_action( "load-$hook", function () {
            
            global $ldnftReviewsListTable;
            
            $option = 'per_page';
            $args = [
                    'label' => 'Reviews Per Page',
                    'default' => 10,
                    'option' => 'reviews_per_page'
                ];
            add_screen_option( $option, $args );
            $ldnftReviewsListTable = new LDNFT_Reviews();
        } );
    }

    /**
     * Add setting page Tabs
     *
     * @param $current
     */
    public static function reviews_page( ) {
        
        global $wpdb;

        $table_name = $wpdb->prefix.'ldnft_reviews';
        if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {
            ?> 
                <div class="wrap">
                    <h2><?php _e( 'Reviews', 'ldninjas-freemius-toolkit' ); ?></h2>
                    <p id="ldnft-dat-not-imported-message"><?php _e( 'Reviews are not imported yet. Please, click <a href="admin.php?page=freemius-settings&tab=freemius-api">here</a> to open the setting page and start the import process automatically.', 'ldninjas-freemius-toolkit' ); ?></p>
                </div>
            <?php

            return;
        }
		
		if( !FS__HAS_PLUGINS ) {
            ?> 
                <div class="wrap">
                    <h2><?php _e( 'Reviews', 'ldninjas-freemius-toolkit' ); ?></h2>
                    <p id="ldnft-dat-not-imported-message"><?php _e( 'No product(s) exists in your freemius account. Please, add a product on freemius and reload the page.', 'ldninjas-freemius-toolkit' ); ?></p>
                </div>
            <?php

            return;
        }

		$products = LDNFT_Freemius::$products;
		
        $selected_plugin_id = isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0  ? intval( $_GET['ldfmt_plugins_filter'] ) : ''; 
        $selected_verified  = isset( $_GET[ 'verified' ] ) && intval( $_GET[ 'verified' ] ) > 0 ? intval( $_GET[ 'verified' ] ) : ''; 
        $selected_featured  = isset( $_GET['featured'] ) && intval( $_GET['featured'] ) > 0 ? intval( $_GET['featured'] ) : ''; 
        $search             = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : ''; 

        /**
         * Create an instance of our package class... 
         */
        $testListTable = new LDNFT_Reviews();
        
        /**
         * Fetch, prepare, sort, and filter our data... 
         */
        $testListTable->prepare_items();
        
        ?>
            <div class="wrap">
                
                <h2><?php _e( 'Reviews', 'ldninjas-freemius-toolkit' ); ?></h2>                
                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                
                <div class="ldnft_filters_top">
                    <form id="ldnft-reviews-filter" method="get">
						<div class="ldnft-filter-handler alignleft actions bulkactions">
							<span class="ldnft_filter_labels"><?php _e( 'Filters:', 'ldninjas-freemius-toolkit' ); ?></span>
							<select name="ldfmt-plugins-filter" class="ldfmt-plugins-filter ldfmt-plugins-reviews-filter">
                                <option value=""><?php echo __( 'All Plugins/Products', 'ldninjas-freemius-toolkit' );?></option>
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
							</select>
                            <select name="ldfmt-plugins-status" class="ldfmt-plugins-reviews-verified">
								<option value=""><?php _e('All', 'ldninjas-freemius-toolkit');?></option>
								<option value="1" <?php echo $selected_verified=='1'?'selected':''; ?>><?php _e('Verified', 'ldninjas-freemius-toolkit');?></option>
								<option value="0" <?php echo $selected_verified=='0'?'selected':''; ?>><?php _e('Unverified', 'ldninjas-freemius-toolkit');?></option>
							</select>
                            <select name="ldfmt-plugins-featured" class="ldfmt-plugins-reviews-featured">
                                <option value=""><?php _e('All', 'ldninjas-freemius-toolkit');?></option>
                                <option value="1" <?php echo $selected_featured=='1'?'selected':''; ?>><?php _e('Featured', 'ldninjas-freemius-toolkit');?></option>
                                <option value="0" <?php echo $selected_featured=='0'?'selected':''; ?>><?php _e('Not Featured', 'ldninjas-freemius-toolkit');?></option>
                            </select>
                            <!-- <input class="form-control" type="text" value = "<?php echo $search;?>" name = "ldnft-reviews-general-search" id = "ldnft-reviews-general-search" placeholder="<?php _e('Search', 'ldninjas-freemius-toolkit');?>"> -->
                            <input type="button" name="ldnft-reviews-search-button" value="<?php _e('Filter', 'ldninjas-freemius-toolkit');?>" class="btn button ldnft-reviews-search-button ldnft-reviews-search-button-filter" />
						</div>
                    </form>
                    <form id="ldnft-reviews-filter-text" method="post">
                        <input class="form-control" type="text" value = "<?php echo $search;?>" name = "ldnft-reviews-general-search" id = "ldnft-reviews-general-search" placeholder="<?php _e('Search', 'ldninjas-freemius-toolkit');?>">
                        <input type="submit" name="ldnft-reviews-search-button-text" value="<?php _e('Search', 'ldninjas-freemius-toolkit');?>" class="btn button ldnft-reviews-search-button ldnft-reviews-search-button-text" />
						 
                    </form>
                    <div id="ldnft_reviews_data">	
                        <!-- Now we can render the completed list table -->
                        <?php $testListTable->display() ?>
                    </div>
                </div>	
                <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                <input type="hidden" class="ldnft-freemius-order" name="order" value="id" />
                <input type="hidden" class="ldnft-freemius-orderby" name="orderby" value="asc" />
                <input type="hidden" class="ldnft-freemius-page" name="page" value="1" />
                <input type="hidden" class="ldnft-script-freemius-type" name="ldnft-script-freemius-type" value="reviews" />
                <input type="hidden" class="ldnft-display-review-type" name="review-type" value="filter" />
                
                <div id="ldnft-admin-modal" class="ldnft-admin-modal">
					<!-- Modal content -->
					<div class="ldnft-admin-modal-content">
						<div class="ldnft-admin-modal-header">
						<span class="ldnft-admin-modal-close">&times;</span>
							<h2><?php echo __( 'Review Detail', 'ldninjas-freemius-toolkit' );?></h2>
						</div>
						<div class="ldnft-admin-modal-body">
							<table id="ldnft-reviews-popup" width="100%" cellpadding="5" cellspacing="1">
								<tbody>
									<tr>
										<th><?php _e('Transaction', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-transaction-id"></td>
										<th><?php _e('User ID', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-user_id"></td>
									</tr>
									<tr>
										<th><?php _e('Name', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-name"></td>
										<th><?php _e('Transaction', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-useremail"></td>
									</tr>
									<tr>
										<th><?php _e('Company', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-company"></td>
										<th><?php _e('Company URL', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-company_url"></td>
									</tr>
									<tr>
										<th><?php _e('Job Title', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-job_title"></td>
										<th><?php _e('Posted At', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-created"></td>
									</tr>
									<tr>
										<th><?php _e('Picture', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-picture"></td>
										<th><?php _e('Profile URL', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-profile_url"></td>
									</tr>
									<tr>
										<th><?php _e('Rate', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-rate"></td>
										<th><?php _e('Sharable Image', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-sharable_img"></td>
									</tr>
									<tr>
										<th><?php _e('Is Verified', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-is_verified"></td>
										<th><?php _e('Is Featured', 'ldninjas-freemius-toolkit')?></th>
										<td id = "ldnft-review-coloumn-is_featured"></td>
									</tr>
									<tr>
										<th><?php _e('Title', 'ldninjas-freemius-toolkit')?></th>
										<td colspan="3" id = "ldnft-review-coloumn-title"></td>
									</tr>
									<tr>
										<td colspan="4" id = "ldnft-review-coloumn-text"></td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="ldnft-popup-loader"><img class="" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" /></div>
					</div>
				</div>
            </div>
        <?php
    }
}

new LDNFT_Reviews_Menu();