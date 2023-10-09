<?php
/**
 * LDNFT_Subscriptions class manages the admin side listing of freemius subscriptions.
 */

 if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * LDNFT_Subscriptions class 
 */
class LDNFT_Subscriptions extends WP_List_Table {

	/**
     * Current Selected Plugin
     */
    public $selected_plugin_id;

    /**
     * Current Selected plan
     */
    public $selected_plan_id;

    /**
     * Freemius API object
     */
    public $api;

    /**
     * Current selected Interval
     */
    public $selected_interval;

    /**
     * Current selected status
     */
    public $selected_status;

    /**
     * Plugins list
     */
    public $plugins;

    /**
     * Class Constrcutor 
     */
	function __construct() {

		global $status, $page;
        
        $this->api = new Freemius_Api_WordPress( FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY );
        $this->plugins = LDNFT_Freemius::$products;
        $this->plugins = $this->plugins->plugins;
        $this->selected_plugin_id = ( isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) ? intval( $_GET['ldfmt_plugins_filter'] ) : $this->plugins[0]->id;
        $this->selected_interval = ( isset( $_GET['interval'] ) ) ? $_GET['interval'] : 12; 
        $this->selected_status = ( isset( $_GET['status'] )  ) ? $_GET['status'] : 'active'; 
        $this->selected_plan_id = ( isset( $_GET['plan_id'] ) && intval( $_GET['plan_id'] ) > 0 ) ? $_GET['plan_id'] : '';

		parent::__construct(
			[
				'singular'  => 'subcription',
				'plural'    => 'subcriptions',
				'ajax'      => true
            ]
		);
	}

	/**
     * Will display a link to show popup for the subscription detail.
     */
    public function column_view( $item ){
        
        if( !empty( intval( strip_tags( $item['id'] ) ) ) ) {
            return '<a data-action="ldnft_subscribers_view_detail" data-user_id="'.$item['user_id'].'" data-plugin_id="'.$item['plugin_id'].'" data-id="'.$item['id'].'" class="ldnft_subscribers_view_detail" href="javascript:;">'.__('Get More', LDNFT_TEXT_DOMAIN).'</a>';
        } else {
            return LDNFT_Admin::get_bar_preloader();
        }    
    }

	/**
	 * @return array
	 *
	 * The array is associative :
	 * keys are slug columns
	 * values are description columns
	 *
	 */

	public function get_columns() {

		$columns = [
            'id'                    => __( 'Transaction ID',LDNFT_TEXT_DOMAIN ), 
            'user_id'               => __( 'User ID',LDNFT_TEXT_DOMAIN ),  
            'username'              => __( 'Name',LDNFT_TEXT_DOMAIN ),
            'useremail'             => __( 'Email',LDNFT_TEXT_DOMAIN ),
            'amount_per_cycle'      => __( 'Price',LDNFT_TEXT_DOMAIN ),
            'discount'              => __( 'Discount', LDNFT_TEXT_DOMAIN ),
            'billing_cycle'         => __( 'Billing Cycle',LDNFT_TEXT_DOMAIN ),
            'total_gross'           => __( 'Total Amount',LDNFT_TEXT_DOMAIN ),
            'gateway'               => __( 'Gateway',LDNFT_TEXT_DOMAIN ),
            'renewal_amount'        => __( 'Next Renewal Amount', LDNFT_TEXT_DOMAIN ),
            'outstanding_balance'   => __( 'Balance',LDNFT_TEXT_DOMAIN ), 
            'failed_payments'       => __( 'Failed Attempt',LDNFT_TEXT_DOMAIN ), 
            'trial_ends'            => __( 'Trial End',LDNFT_TEXT_DOMAIN ), 
            'created'               => __( 'Payment Date',LDNFT_TEXT_DOMAIN ), 
            'initial_amount'        => __( 'First Payment',LDNFT_TEXT_DOMAIN ), 
            'next_payment'          => __( 'Next Renewal Date',LDNFT_TEXT_DOMAIN ), 
            'currency'              => __( 'Currency',LDNFT_TEXT_DOMAIN ),
            'country_code'          => __( 'Country',LDNFT_TEXT_DOMAIN ), 
            'view'                  => __( 'Action', LDNFT_TEXT_DOMAIN ),
        ];
        
        return $columns;
	}

	/**
	 * @param $item
	 * @param $column_name
	 *
	 * @return mixed
	 *
	 * Method column_default let at your choice the rendering of everyone of column
	 *
	 */

	public function column_default( $item, $column_name ) {

		return $item[$column_name]; 
	}
	
	/**
	 * @return array
	 *
	 * The array is associative :
	 * keys are slug columns
	 * values are array of slug and a boolean that indicates if is sorted yet
	 *
	 */

	public function get_sortable_columns() {

        return [];
	}

	/**
	 * @Override of prepare_items method
	 *
	 */

	public function prepare_items() {
        
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = get_user_option(
            'subscriptions_per_page',
            get_current_user_id()
        );
        
        if( empty( $per_page ) ) {
            $per_page = 10;
        }

		if( !wp_doing_ajax() ) {
            
            $this->items = [
                [
                    'id'                    => LDNFT_Admin::get_bar_preloader(), 
                    'user_id'               => LDNFT_Admin::get_bar_preloader(), 
                    'username'              => LDNFT_Admin::get_bar_preloader(), 
                    'useremail'             => LDNFT_Admin::get_bar_preloader(), 
                    'amount_per_cycle'      => LDNFT_Admin::get_bar_preloader(), 
                    'discount'              => LDNFT_Admin::get_bar_preloader(), 
                    'billing_cycle'         => LDNFT_Admin::get_bar_preloader(), 
                    'total_gross'           => LDNFT_Admin::get_bar_preloader(), 
                    'gateway'               => LDNFT_Admin::get_bar_preloader(), 
                    'renewal_amount'        => LDNFT_Admin::get_bar_preloader(), 
                    'outstanding_balance'   => LDNFT_Admin::get_bar_preloader(), 
                    'failed_payments'       => LDNFT_Admin::get_bar_preloader(), 
                    'trial_ends'            => LDNFT_Admin::get_bar_preloader(), 
                    'created'               => LDNFT_Admin::get_bar_preloader(), 
                    'initial_amount'        => LDNFT_Admin::get_bar_preloader(),  
                    'next_payment'          => LDNFT_Admin::get_bar_preloader(), 
                    'currency'              => LDNFT_Admin::get_bar_preloader(), 
                    'country_code'          => LDNFT_Admin::get_bar_preloader(),  
                    'view'                  => LDNFT_Admin::get_bar_preloader(),     
                ]
            ];

            $this->set_pagination_args( [
                'per_page'      => $per_page,
                'offset'        => 1,
                'offset_rec'    => 1,
                'current_recs'  => 1
            ] );

			return;
		}

        $offset = isset( $_REQUEST['offset'] ) && intval( $_REQUEST['offset'] ) > 0 ? intval( $_REQUEST['offset'] ) : 1;
        $offset_rec = ( $offset - 1 ) * $per_page;
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $screen = WP_Screen::get( 'freemius-toolkit_page_freemius-subscriptions' );
        $hidden   = get_hidden_columns( $screen );
        $sortable = $this->get_sortable_columns();
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = [ $columns, $hidden, $sortable ];
        
        
        $interval_str = '&billing_cycle='.$this->selected_interval;

        $status_str = '&filter='.$this->selected_status;

        $plan_str = '';
        if( !empty($this->selected_plan_id) && intval($this->selected_plan_id) > 0 ) {  
           $plan_str = '&plan_id='.$this->selected_plan_id;
        }
        
        $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/subscriptions.json?count='.$per_page.'&offset='.$offset_rec.$interval_str.$status_str.$plan_str, 'GET', []);
        $data = [];
        $count = 0;
        
        $subscriptions_total = 0;
        if( isset( $result ) && isset( $result->subscriptions ) ) {
            $subscriptions_total = count( $result->subscriptions );
            foreach( $result->subscriptions as $subscription ) {
                $user_id = 0;
                foreach( $subscription as $key => $value ) {
                    
                    if( empty( $value ) ) {
                        $value = '-';
                    }    
                    $data[$count][$key] = $value;
                    if( 'user_id' == $key ) {
                        $user_id = $value;
                    }
                } 
                
                $user = $this->api->Api('plugins/'.$this->selected_plugin_id.'/users/'.$user_id.'.json', 'GET', []);
                if( $user ) {
                    $data[$count]['username']   = $user->first.' '.$user->last;
                    if(empty(trim($data[$count]['username']))) {
                        $data[$count]['username'] = '-';
                    }
                }
    
                $data[$count]['country_code']   = LDNFT_Freemius::get_country_name_by_code( strtoupper( $subscription->country_code ) );
                $data[$count]['useremail']      = $user->email;
                $data[$count]['discount']       = '-';
    
                if( !empty( $subscription->renewals_discount ) && floatval( $subscription->renewals_discount ) > 0 ) {
                    if( strtolower( $subscription->renewals_discount_type ) == 'percentage' ) {
                        $data[$count]['discount']  = ''.$subscription->renewals_discount.'% - (' .number_format( ( $subscription->renewals_discount*$subscription->total_gross ) / 100, 2 ).$subscription->currency.')';
                    } else {
                        $data[$count]['discount']  = __( 'Fixed - ', LDNFT_TEXT_DOMAIN ).'('.$subscription->renewals_discount.$subscription->currency.')';
                    }
                }
                 
                $count++;   
            }
        }
        
        
        $this->items = $data;
 
        $this->set_pagination_args( [
            'per_page'      => $per_page,
            'offset'        => $offset,
            'offset_rec'    => $offset_rec,
            'current_recs'  => $subscriptions_total
        ] );
	}

	/**
	 * @Override of display method
	 */

	public function display() {

		parent::display();
	}

	/**
	 * @Override ajax_response method
	 */
	public function ajax_response() {

		$this->prepare_items();

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
            $this->display_rows();
        } else {
            $this->display_rows_or_placeholder();
        }
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination('top');
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination('bottom');
		$pagination_bottom = ob_get_clean();

		$response = [ 'rows' => $rows ];
		$response['pagination']['top'] = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['column_headers'] = $headers;

		if ( isset( $total_items ) )
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( json_encode( $response ) );
	}

	/**
	 * Displays the pagination.
	 *
	 * @param string $which
	 */
    public function display_tablenav( $which ) {
        
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
        }
        ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>">

                <?php if ( $this->has_items() ) : ?>
                <div class="alignleft actions bulkactions">
                    <?php $this->bulk_actions( $which ); ?>
                </div>
                    <?php
                endif;
                $this->extra_tablenav( $which );
                $this->pagination_new( $which );
                ?>

                <br class="clear" />
            </div>
        <?php

    }

    /**
	 * Displays the pagination.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which
	 */
	protected function pagination_new( $which ) {
        
		if ( empty( $this->_pagination_args ) ) {
			return;
		}
        
        $per_page       = $this->_pagination_args['per_page'];
		$offset         = $this->_pagination_args['offset'];
        $offset_rec     = $this->_pagination_args['offset_rec'];
        $current_recs   = $this->_pagination_args['current_recs'];
        $offset_rec1    = ($offset) * $per_page;
        
        $interval_str = '&billing_cycle=12';
        if( !empty($this->selected_interval) && !empty( $this->selected_interval ) ) {
            $interval_str = '&billing_cycle='.$this->selected_interval;
        }

        $status_str = 'active';
        if( !empty($this->selected_status) ) {
            $status_str = '&filter='.$this->selected_status;
        }
        
        $plan_str = '';
        if( !empty($this->selected_plan_id) && intval($this->selected_plan_id) > 0 ) {
           $plan_str = '&plan_id='.$this->selected_plan_id;
        }

        $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/subscriptions.json?count='.$per_page.'&offset='.$offset_rec1.$interval_str.$status_str.$plan_str, 'GET', []);
        
		$total_items     = $this->_pagination_args['total_items'];
		$total_pages     = $this->_pagination_args['total_pages'];
		$infinite_scroll = false;
		if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
			$infinite_scroll = $this->_pagination_args['infinite_scroll'];
		}

		$output = '';
        
		$current              = $this->get_pagenum();
		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = [];

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = false;
		$disable_prev  = false;
		$disable_next  = false;

		if ( 1 == $offset  ) {
			$disable_first = true;
			$disable_prev  = true;
		}

        if( isset($result) && isset($result->subscriptions) ) {
            if ( count($result->subscriptions) == 0 ) {
                $disable_next = true;
            }
        }
		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' data-offset='1' href='javascript:;'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				__( 'First page', LDNFT_TEXT_DOMAIN ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {

			$page_links[] = sprintf(
				"<a class='prev-page button' data-offset='%d' href='javascript:;'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>', 
				intval($offset)>1?intval($offset)-1:1,
				__( 'Previous page', LDNFT_TEXT_DOMAIN ),
				'&lsaquo;'
			);
		}
        
        if( $offset == 1 && $current_recs == 0 ) {
            $page_links[] = $total_pages_before . sprintf(
                _x( '%1$s to %2$s', 'paging' ),
                0,
                0
            ) . $total_pages_after;
        } else {
            $page_links[] = $total_pages_before . sprintf(
                _x( 'From %1$s to %2$s', 'paging' ),
                $offset_rec>0?$offset_rec+1:1,
                (intval($offset_rec)+intval($current_recs))
                ) . $total_pages_after;
        }

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a data-action='ldnft_subscriber_check_next' data-plugin_id='%d' data-status='%s' data-plan_id='%s' data-interval='%d' data-per_page='%d' data-offset='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_plan_id,
                $this->selected_interval,
                $per_page,
                $offset+1,
                $current_recs,
				__( 'Next page', LDNFT_TEXT_DOMAIN ),
				'&rsaquo;'
			);
		}
        
		$pagination_links_class = 'pagination-links';
		if ( ! empty( $infinite_scroll ) ) {
			$pagination_links_class .= ' hide-if-js';
		}
		
        $output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';

		
		$this->_pagination = "<div class='tablenav-pages'>$output</div>";

		echo $this->_pagination;

	}

    /**
	 * Displays the search filter bar.
	 *
	 * @param string $which
	 */
    public function extra_tablenav( $which ) {
        
        if ( $which == "top" ){
        
        }
    }
}