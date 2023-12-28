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
     * Current gateway
     */
    public $selected_gateway;
    
    /**
     * Current status
     */
    public $selected_status;

    /**
     * Current search
     */
    public $selected_search;

    /**
     * Freemius API object
     */
    public $api;

    /**
     * Current selected Interval
     */
    public $selected_interval;

    /**
     * Current selected country 
     */
    public $selected_country;

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
        
        $this->selected_plugin_id = ( isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) ? intval( $_GET['ldfmt_plugins_filter'] ) : '';
        $this->selected_interval = ( isset( $_GET['interval'] ) ) ? sanitize_text_field( $_GET['interval'] ) : 12; 
        $this->selected_country = ( isset( $_GET['country'] )  ) ? sanitize_text_field( $_GET['country'] ) : ''; 
        $this->selected_plan_id = ( isset( $_GET['plan_id'] ) && intval( $_GET['plan_id'] ) > 0 ) ? sanitize_text_field( $_GET['plan_id'] ) : '';

        $this->selected_gateway = isset( $_GET['gateway'] ) ? sanitize_text_field( $_GET['gateway'] ) : ''; 
        $this->selected_search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
        $this->selected_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

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
 
            return '<a data-action="ldnft_subscribers_view_detail" data-user_id="'.$item['user_id'].'" data-install_id="'.$item['install_id'].'"  data-coupon_id="'.$item['coupon_id'].'"  data-updated_at="'.$item['updated_at'].'" data-external_id="'.$item['external_id'].'"  data-plan_id="'.$item['plan_id'].'"  data-pricing_id="'.$item['pricing_id'].'" data-renewals_discount="'.$item['renewals_discount'].'"  data-license_id="'.$item['license_id'].'" data-plugin_id="'.$item['plugin_id'].'" data-id="'.$item['id'].'" data-username="'.$item['username'].'" data-useremail="'.$item['useremail'].'" data-amount_per_cycle="'.$item['amount_per_cycle'].'" data-discount="'.$item['discount'].'" data-billing_cycle="'.$item['billing_cycle'].'" data-gross="'.$item['gross'].'" data-gateway="'.$item['gateway'].'" data-renewal_amount="'.$item['renewal_amount'].'" data-outstanding_balance="'.$item['outstanding_balance'].'" data-failed_payments="'.$item['failed_payments'].'" data-trial_ends="'.$item['trial_ends'].'" data-created="'.$item['created'].'" data-initial_amount="'.$item['initial_amount'].'" data-next_payment="'.$item['next_payment'].'" data-canceled_at="'.$item['canceled_at'].'" data-currency="'.$item['currency'].'" data-country_code="'.$item['country_code'].'" data-status="'.$item['status'].'" class="ldnft_subscribers_view_detail" href="javascript:;">'.__('Get More', LDNFT_TEXT_DOMAIN).'</a>';
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
            'billing_cycle'         => __( 'Billing Cycle (months)',LDNFT_TEXT_DOMAIN ),
            'gross'                 => __( 'Total Amount',LDNFT_TEXT_DOMAIN ),
            'gateway'               => __( 'Gateway',LDNFT_TEXT_DOMAIN ),
            'renewal_amount'        => __( 'Next Renewal Amount', LDNFT_TEXT_DOMAIN ),
            'outstanding_balance'   => __( 'Balance',LDNFT_TEXT_DOMAIN ), 
            'failed_payments'       => __( 'Failed Attempt',LDNFT_TEXT_DOMAIN ), 
            'trial_ends'            => __( 'Trial End',LDNFT_TEXT_DOMAIN ), 
            'created'               => __( 'Payment Date',LDNFT_TEXT_DOMAIN ), 
            'initial_amount'        => __( 'First Payment',LDNFT_TEXT_DOMAIN ), 
            'next_payment'          => __( 'Next Renewal Date',LDNFT_TEXT_DOMAIN ), 
            'canceled_at'           => __( 'Canceled Date',LDNFT_TEXT_DOMAIN ),
            'status'                => __( 'Status',LDNFT_TEXT_DOMAIN ),
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
        
        if( array_key_exists( $column_name, $item ) ) {
            return $item[$column_name]; 
        }
		
        return ''; 
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

        $sortable_columns = array(
            'id'     => array('id',false),     //true means it's already sorted
            'plugin_title'    => array('plugin_title',false),
            'user_id'  => array('user_id',false),
            'username'  => array('username',false),
            'useremail'  => array('useremail',false),
            'amount_per_cycle'    => array('amount_per_cycle',false),
            'billing_cycle'  => array('billing_cycle',false),
            'gross'  => array('gross',false),
            'outstanding_balance'  => array('outstanding_balance',false),
            'failed_payments'    => array('failed_payments',false),
            'gateway'  => array('gateway',false),
            'next_payment'  => array('next_payment',false),
            'currency'  => array('currency',false),
            'country_code'  => array('country_code',false),
            'initial_amount'  => array('initial_amount',false),
            'renewal_amount'  => array('renewal_amount',false),
            'status'  => array('status',false),
            'canceled_at'  => array('canceled_at',false),
            'created'  => array('created',false)
        );

        return $sortable_columns;
	}

	/**
	 * @Override of prepare_items method
	 *
	 */

	public function prepare_items() {
        
        global $wpdb; //This is used only if making any database queries

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
                    'gross'                 => LDNFT_Admin::get_bar_preloader(), 
                    'gateway'               => LDNFT_Admin::get_bar_preloader(), 
                    'renewal_amount'        => LDNFT_Admin::get_bar_preloader(), 
                    'outstanding_balance'   => LDNFT_Admin::get_bar_preloader(), 
                    'failed_payments'       => LDNFT_Admin::get_bar_preloader(), 
                    'trial_ends'            => LDNFT_Admin::get_bar_preloader(), 
                    'canceled_at'           => LDNFT_Admin::get_bar_preloader(), 
                    'created'               => LDNFT_Admin::get_bar_preloader(), 
                    'initial_amount'        => LDNFT_Admin::get_bar_preloader(),  
                    'next_payment'          => LDNFT_Admin::get_bar_preloader(), 
                    'currency'              => LDNFT_Admin::get_bar_preloader(), 
                    'country_code'          => LDNFT_Admin::get_bar_preloader(),  
                    'view'                  => LDNFT_Admin::get_bar_preloader(),     
                ]
            ];

            $this->set_pagination_args( [
                'total_items'   => 1,
                'per_page'      => 1,
                'paged'         => 1,
                'current_recs'  => 1,
                'total_pages'   => 1
            ] );

			return;
		}

        $paged = isset( $_REQUEST['paged'] ) && intval( $_REQUEST['paged'] ) > 0 ? intval( $_REQUEST['paged'] ) : 1;
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         */
        $columns = $this->get_columns();
        $screen = WP_Screen::get( 'freemius-toolkit_page_freemius-subscriptions' ); 
        $hidden   = get_hidden_columns( $screen );
        if( empty( $hidden ) ) {
            $hidden = get_user_meta( get_current_user_id(), 'manage' . $screen->id . 'columnshidden', true );
            if( empty( $hidden ) ) {
                $hidden = get_user_meta( get_current_user_id(), $wpdb->prefix.'manage' . $screen->id . 'columnshidden', true );
            }
        }

        if( empty( $hidden ) ) {
            $hidden = [];
        }
        
        $sortable = $this->get_sortable_columns();
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         */
        $this->_column_headers = [ $columns, $hidden, $sortable ];
        
        $table_name = $wpdb->prefix.'ldnft_subscription t inner join '.$wpdb->prefix.'ldnft_customers c on (t.user_id=c.id)';  
        $where = " where 1 = 1";
        if( ! empty( $this->selected_plugin_id )) {
            $where .= " and t.plugin_id='".$this->selected_plugin_id."'";
        }
        
        if( !empty( $this->selected_interval )) {
            switch( $this->selected_interval ) {
                case "current_week":
                    $where .= " and YEARWEEK(t.created) = YEARWEEK(NOW())";
                    break;
                case "last_week":
                    $where .= ' and Date(t.created) between date_sub(now(),INTERVAL 1 WEEK) and now()';
                    break;
                case "current_month":
                    $where .= ' and MONTH(t.created) = MONTH(now()) and YEAR(t.created) = YEAR(now())';
                    break;
                case "last_month":
                    $where .= ' and Date(t.created) between Date((now() - interval 1 month)) and Date(now())';
                    break;
                default:
                    $where .= " and Date(t.created) = '".date('Y-m-d')."'";
                    break;
            }
        }

        if( !empty($this->selected_country) ) {  
            $where .= " and t.country_code='".$this->selected_country."'";
        }

        if( !empty($this->selected_plan_id) && intval($this->selected_plan_id) > 0 ) {  
            $where .= ' and plan_id='.$this->selected_plan_id;
        }

        $where .= $this->selected_gateway != ''? " and t.gateway='".sanitize_text_field( $_GET['gateway'] )."' " : '';
        $where .= $this->selected_status != ''? " and t.status='".sanitize_text_field( $_GET['status'] )."' " : '';
        if( ! empty( $this->selected_search )) {
            $where   .= " and ( t.id like '%".$this->selected_search."%' or t.user_id like '%".$this->selected_search."%' or lower(c.email) like '%".strtolower($this->selected_search)."%' or lower(c.first) like '%".strtolower($this->selected_search)."%' or lower(c.last) like '%".strtolower($this->selected_search)."%' )";
        }
        
        $total_items = $wpdb->get_var("SELECT COUNT(t.id) FROM $table_name".$where);

        // prepare query params, as usual current page, order by and order direction
        $offset = isset($paged) ? (intval($paged) -1) * $per_page : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? sanitize_text_field( $_REQUEST['order'] ) : 'desc';
       
        $orderby_prefix = "t.";
        if( in_array( $orderby, [ 'username', 'useremail' ] ) ) {
            $orderby_prefix = "";
        }
        
        $result = $wpdb->get_results( "SELECT t.*, concat(c.first, ' ', c.last) as username, c.email as useremail FROM $table_name $where ORDER BY $orderby_prefix$orderby $order LIMIT ".$per_page." OFFSET ".$offset );
        
        $data = [];
        $count = 0;
        if( isset($result) && is_array($result) && count($result) > 0 ) {
            foreach( $result as $subscription ) {
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
                
                $data[$count]['country_code']   = LDNFT_Freemius::get_country_name_by_code( strtoupper( $subscription->country_code ) );
                $data[$count]['discount']       = '-';
    
                if( !empty( $subscription->renewals_discount ) && floatval( $subscription->renewals_discount ) > 0 ) {
                    if( strtolower( $subscription->renewals_discount_type ) == 'percentage' ) {
                        $data[$count]['discount']  = ''.$subscription->renewals_discount.'% - (' .number_format( ( $subscription->renewals_discount*$subscription->gross ) / 100, 2 ).$subscription->currency.')';
                    } else {
                        $data[$count]['discount']  = __( 'Fixed - ', LDNFT_TEXT_DOMAIN ).'('.$subscription->renewals_discount.$subscription->currency.')';
                    }
                }
                 
                $count++;   
            }
        }
        
        
        $this->items = $data;
 
        $this->set_pagination_args( [
            'total_items'   => $total_items,
            'per_page'      => $per_page,
            'paged'         => $paged,
            'current_recs'  => count($this->items),
            'total_pages'   => ceil($total_items / $per_page)
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

        $total_items     = $this->_pagination_args['total_items'];
        $total_pages     = $this->_pagination_args['total_pages'];
        $per_page       = $this->_pagination_args['per_page'];
		$paged          = $this->_pagination_args['paged'];
        $current_recs   = $this->_pagination_args['current_recs'];

        $infinite_scroll = false;
        if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }
    
        if ( 'top' === $which && $total_pages > 1 ) {
            $this->screen->render_screen_reader_content( 'heading_pagination' );
        }
    
        $output = '<span class="displaying-num">' . sprintf(
            /* translators: %s: Number of items. */
            _n( '%s item', '%s items', $total_items ),
            number_format_i18n( $total_items )
        ) . '</span>';
    
        $current              = $this->get_pagenum();
        $removable_query_args = wp_removable_query_args();
    
        $current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
    
        $current_url = remove_query_arg( $removable_query_args, $current_url );
    
        $page_links = array();
    
        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';
    
        $disable_first = false;
        $disable_last  = false;
        $disable_prev  = false;
        $disable_next  = false;
    
        if ( 1 == $current ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $total_pages == $current ) {
            $disable_last = true;
            $disable_next = true;
        }
    
        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            
            $page_links[] = sprintf( 
                "<a  data-action='ldnft_subscriber_check_next' data-plugin_id='%d' data-country='%s' data-plan_id='%s' data-interval='%d' data-gateway='%s' data-status='%s' data-search='%s' data-per_page='%d'  data-paged='1' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_country,
                $this->selected_plan_id,
                $this->selected_interval,
                $this->selected_gateway,
                $this->selected_status,
                $this->selected_search,
                $per_page,
                $current_recs,
                /* translators: Hidden accessibility text. */
                __( 'First page' ),
                '&laquo;'
            );
        }
    
        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a  data-action='ldnft_subscriber_check_next' data-plugin_id='%d' data-country='%s' data-plan_id='%s' data-interval='%d' data-gateway='%s' data-status='%s' data-search='%s' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_country,
                $this->selected_plan_id,
                $this->selected_interval,
                $this->selected_gateway,
                $this->selected_status,
                $this->selected_search,
                $per_page,
                intval($paged)>1?intval($paged)-1:1,
                $current_recs,
                /* translators: Hidden accessibility text. */
                __( 'Previous page' ),
                '&lsaquo;'
            );

        }
    
        $html_current_page  = $current;
        $total_pages_before = sprintf(
            '<span class="screen-reader-text">%s</span>' .
            '<span id="table-paging" class="paging-input">' .
            '<span class="tablenav-paging-text">',
            /* translators: Hidden accessibility text. */
            __( 'Current Page' )
        );
    
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
    
        $page_links[] = $total_pages_before . sprintf(
            /* translators: 1: Current page, 2: Total pages. */
            _x( '%1$s of %2$s', 'paging' ),
            $html_current_page,
            $html_total_pages
        ) . $total_pages_after;
    
        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a  data-action='ldnft_subscriber_check_next' data-plugin_id='%d' data-country='%s' data-plan_id='%s' data-interval='%d' data-gateway='%s' data-status='%s' data-search='%s' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_country,
                $this->selected_plan_id,
                $this->selected_interval,
                $this->selected_gateway,
                $this->selected_status,
                $this->selected_search,
                $per_page,
                $paged+1,
                $current_recs,
                /* translators: Hidden accessibility text. */
                __( 'Next page' ),
                '&rsaquo;'
            );
        }
    
        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a data-action='ldnft_subscriber_check_next' data-plugin_id='%d' data-country='%s' data-plan_id='%s' data-interval='%d' data-gateway='%s' data-status='%s' data-search='%s' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_country,
                $this->selected_plan_id,
                $this->selected_interval,
                $this->selected_gateway,
                $this->selected_status,
                $this->selected_search,
                $per_page,
                $total_pages,
                $current_recs,
                /* translators: Hidden accessibility text. */
                __( 'Last page' ),
                '&raquo;'
            );
        }
    
        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) ) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . implode( "\n", $page_links ) . '</span>';
    
        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";
    
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