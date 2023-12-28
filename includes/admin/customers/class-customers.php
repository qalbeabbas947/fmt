<?php
/**
 * LDNFT Pro admin template
 */

if( ! defined( 'ABSPATH' ) ) exit;

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class LDNFT_Customers
 */
class LDNFT_Customers extends WP_List_Table {

    /**
     * Current select Plugin
     */
    public $selected_plugin_id;

    /**
     * Customer status
     */
    public $selected_status;

    /**
     * Customer Seach
     */
    public $selected_search;

    /**
     * Customer marketing
     */
    public $selected_marketing;
      
    /**
     * Customer marketing
     */
    public $selected_paymentstatus;

    
    /**
     * Freemius API object
     */
    public $api;

    /**
     * Plugins list
     */
    public $plugins;
    
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    public function __construct(){
        
        global $status, $page;

		$this->api = new Freemius_Api_WordPress( FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY );
        $this->plugins = LDNFT_Freemius::$products;
        $default_plugin_id = is_array( $this->plugins) && count( $this->plugins ) > 0 ? $this->plugins[0]->id : 0;

        $this->selected_plugin_id       = ( isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) ? intval( $_GET['ldfmt_plugins_filter'] ) : '' ;
        $this->selected_status          = ( isset( $_GET['status'] )  ) ? sanitize_text_field( $_GET['status'] ) : ''; 
        $this->selected_marketing       = ( isset( $_GET['marketing'] )  ) ? sanitize_text_field( $_GET['marketing'] ) : ''; 
        $this->selected_search          = ( isset( $_GET['search'] )  ) ? sanitize_text_field( $_GET['search'] ) : ''; 
        $this->selected_paymentstatus   = ( isset( $_GET['pmtstatus'] )  ) ? sanitize_text_field( $_GET['pmtstatus'] ) : ''; 
        
        /**
         * Set parent defaults
         */
        parent::__construct( [
            'singular'      => 'freemius_customer',
            'plural'        => 'freemius_customers',
            'ajax'          => true
        ] );
        
    }


    /** ************************************************************************
     * Recommended. This method is called when the parent class can't find a method
     * specifically build for a given column. Generally, it's recommended to include
     * one method for each column you want to render, keeping your package class
     * neat and organized. For example, if the class needs to process a column
     * named 'title', it would first see if a method named $this->column_title() 
     * exists - if it does, that method will be used. If it doesn't, this one will
     * be used. Generally, you should try to use custom column methods as much as 
     * possible. 
     * 
     * Since we have defined a column_title() method later on, this method doesn't
     * need to concern itself with any column with a name of 'title'. Instead, it
     * needs to handle everything else.
     * 
     * For more detailed insight into how columns are handled, take a look at 
     * WP_List_Table::single_row_columns()
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    public function column_default($item, $column_name){
        
        switch($column_name){
            case 'email':
            case 'first':
            case 'last':
            case 'is_verified':
            case 'id':
            case 'is_marketing_allowed':
            case 'products':
            case 'created':
                return $item[$column_name];
            default:
                return print_r($item,true);
        }
    }

    /**
	 * format the is_verified column
	 */
	public function column_products($item){

        global $wpdb;
		if( intval( $item['id'] ) > 0 ) {
            
            $table_name = $wpdb->prefix.'ldnft_plugins as p inner join '.$wpdb->prefix.'ldnft_customer_meta as m on (p.id=m.plugin_id) '; 
            $results    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name where m.customer_id='%d'", $item['id'] ) );

            $plugins_str = '';
            foreach( $results as $result ) {
                $plugins_str .= ! empty( $plugins_str ) ? ', ': '';
                $plugins_str .= $result->title;
            }

			return $plugins_str;
		} else {

			return '-';
		}
	}

	/**
	 * format the is_verified column
	 */
	public function column_is_verified($item){
		
        if( intval( $item['is_verified'] ) == 1 ) {
			return __( 'Yes', LDNFT_TEXT_DOMAIN );
		} else {
			return __( 'No', LDNFT_TEXT_DOMAIN );
		}
	}


   /**
	* format the is_marketing_allowed column
	*/
	public function column_is_marketing_allowed($item){
		
        if( intval( $item['is_marketing_allowed'] ) == 1 ) {
			return __( 'Yes', LDNFT_TEXT_DOMAIN );
		} else {
			return __( 'No', LDNFT_TEXT_DOMAIN );
		}
	}


    /** ************************************************************************
     * REQUIRED! This method dictates the table's columns and titles. This should
     * return an array where the key is the column slug (and class) and the value 
     * is the column's title text. If you need a checkbox for bulk actions, refer
     * to the $columns array below.
     * 
     * The 'cb' column is treated differently than the rest. If including a checkbox
     * column in your table you must create a column_cb() method. If you don't need
     * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    public function get_columns(){
        
        $columns = [
            'id'                        => __( 'ID', LDNFT_TEXT_DOMAIN ),
            'email'                     => __( 'Email', LDNFT_TEXT_DOMAIN ),
            'first'                     => __( 'First Name', LDNFT_TEXT_DOMAIN ),
            'last'                      => __( 'Last Name', LDNFT_TEXT_DOMAIN ),
            'is_verified'               => __( 'Verified?', LDNFT_TEXT_DOMAIN ),
            'created'                   => __( 'Joined', LDNFT_TEXT_DOMAIN ),
            'is_marketing_allowed'      => __( 'Is Marketing Allowed?', LDNFT_TEXT_DOMAIN ),
            'products'                  => __( 'Products', LDNFT_TEXT_DOMAIN ),
        ];

        return $columns;
    }


    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by. Often, the key and value will be the same, but this is not always
     * the case (as the value is a column name from the database, not the list table).
     * 
     * This method merely defines which columns should be sortable and makes them
     * clickable - it does not handle the actual sorting. You still need to detect
     * the ORDERBY and ORDER querystring variables within prepare_items() and sort
     * your data accordingly (usually by modifying your query).
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    public function get_sortable_columns() {
        
        $sortable_columns = array(
            'id'            => array( 'id', false ),
            'email'         => array( 'email', false ),
            'first'         => array( 'first', false ),
            'last'          => array( 'last', false ),
            'is_verified'   => array( 'is_verified', false ),
            'created'       => array( 'created', false )
        );
        
        return $sortable_columns;
    }


    /** ************************************************************************
     * Optional. If you need to include bulk actions in your list table, this is
     * the place to define them. Bulk actions are an associative array in the format
     * 'slug'=>'Visible Title'
     * 
     * If this method returns an empty value, no bulk action will be rendered. If
     * you specify any bulk actions, the bulk actions box will be rendered with
     * the table automatically on display().
     * 
     * Also note that list tables are not automatically wrapped in <form> elements,
     * so you will need to create those manually in order for bulk actions to function.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    public function get_bulk_actions() {
       
        $actions = [];
        return $actions;
    }


    /** ************************************************************************
     * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
     * For this example package, we will handle it in the class to keep things
     * clean and organized.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    public function process_bulk_action() {
        
        /**
         * Detect when a bulk action is being triggered...
         */
        if( 'delete'===$this->current_action() ) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
    }


    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    public function prepare_items() {
        
        global $wpdb; //This is used only if making any database queries

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = get_user_option(
            'customers_per_page',
            get_current_user_id()
        );
        
        if( empty( $per_page ) ) {
            $per_page = 10;
        }

		if( !wp_doing_ajax() ) {
            
			$this->items = [
                [
                    'id'               		=> LDNFT_Admin::get_bar_preloader(), 
                    'email'            		=> LDNFT_Admin::get_bar_preloader(), 
                    'first'            		=> LDNFT_Admin::get_bar_preloader(), 
                    'last'             		=> LDNFT_Admin::get_bar_preloader(), 
                    'is_verified'      		=> LDNFT_Admin::get_bar_preloader(), 
                    'created'              	=> LDNFT_Admin::get_bar_preloader(), 
                    'is_marketing_allowed'  => LDNFT_Admin::get_bar_preloader()    
                ]
            ];

            $this->set_pagination_args( [
                'total_items'   => 1,
                'per_page'      => 1,
                'paged'        => 1,
                'current_recs'  => 1,
                'total_pages'   => 1
            ] );

			return;
		}

        $paged = isset( $_REQUEST['paged'] ) && intval( $_REQUEST['paged'] ) > 0 ? intval( $_REQUEST['paged'] ) : 1;
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $screen = WP_Screen::get( 'freemius-toolkit_page_freemius-customers' );
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
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = [ $columns, $hidden, $sortable ];
        
        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example 
         * package slightly different than one you might build on your own. In 
         * this example, we'll be using array manipulation to sort and paginate 
         * our data. In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */

        $table_name = $wpdb->prefix.'ldnft_customers as c'; 
        $where = " where 1 = 1";
       
        if( ! empty( $this->selected_plugin_id ) || ! empty( $this->selected_paymentstatus ) ) {
            $table_name = $wpdb->prefix.'ldnft_customers as c inner join '.$wpdb->prefix.'ldnft_customer_meta as m on (c.id=m.customer_id) '; 
            
            if( ! empty( $this->selected_plugin_id ) ) {
                $where .= " and m.plugin_id='".$this->selected_plugin_id."'";
            }
            
            if( ! empty( $this->selected_paymentstatus ) ) {
                switch( $this->selected_paymentstatus ) {
                    case "paid":
                        $where .= " and m.status='payment'";
                        break;
                    case "free":
                        $where .= " and ( m.status is Null or m.status='' )";
                        break;
                }
            }
            
        }

        $where .= $this->selected_status != '' ? " and c.is_verified='".$this->selected_status."' " : '';
        $where .= $this->selected_marketing != '' ? ( $this->selected_marketing == '1'? " and c.is_marketing_allowed='1' ": " and ( c.is_marketing_allowed='0' or c.is_marketing_allowed is Null) ") : '';
        $where .= ! empty( $this->selected_search ) ? " and ( c.id like '%".$this->selected_search."%' or lower(c.email) like '%".strtolower($this->selected_search)."%' or lower(c.first) like '%".strtolower($this->selected_search)."%' or lower(c.last) like '%".strtolower($this->selected_search)."%' )" : '';

        $total_items = $wpdb->get_var("SELECT COUNT(c.id) FROM $table_name".$where);

        // prepare query params, as usual current page, order by and order direction
        $offset      = isset($paged) ? intval(($paged-1) * $per_page) : 0;
        $orderby    = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'created';
        $order      = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? sanitize_text_field( $_REQUEST['order'] ) : 'desc';
        $result     = $wpdb->get_results( "SELECT c.* FROM $table_name $where ORDER BY c.$orderby $order LIMIT $per_page OFFSET $offset", ARRAY_A );
        
        $data = [];
        $count = 0;
        if( isset($result) && is_array($result) && count($result) > 0 ) {
            foreach( $result as $customer ) {
                $user_id = 0;
                foreach( $customer as $key => $value ) {
                    
                    if( empty( $value ) ) {
                        $value = '-';
                    }    
                    $data[$count][$key] = $value;
                } 
                 
                $count++;   
            }
        }
        
        
        $this->items = $data;

        $this->set_pagination_args( [
            'total_items'   => $total_items,
            'per_page'      => $per_page,
            'paged'         => $paged,
            'current_recs'  => count( $this->items ),
            'total_pages'   => ceil( $total_items / $per_page )
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
                "<a data-ldfmt_plugins_filter='%d' data-status='%s' data-marketing='%d' data-pmtstatus='%d' data-search='%s' data-per_page='%d' class='first-page button ldnft_check_load_next' data-paged='1' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_marketing,
                $this->selected_paymentstatus,
                $this->selected_search,
                $per_page,
                /* translators: Hidden accessibility text. */
                __( 'First page' ),
                '&laquo;'
            );
        }
        
        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<a data-ldfmt_plugins_filter='%d' data-status='%s' data-marketing='%d' data-pmtstatus='%d' data-search='%s' data-per_page='%d' class='prev-page button ldnft_check_load_next' data-paged='%d' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_marketing,
                $this->selected_paymentstatus,
                $this->selected_search,
                $per_page,
                intval($paged)>1?intval($paged)-1:1,
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
                "<a data-ldfmt_plugins_filter='%d' data-status='%s' data-marketing='%d' data-pmtstatus='%d' data-search='%s' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_marketing,
                $this->selected_paymentstatus,
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
                "<a data-ldfmt_plugins_filter='%d' data-status='%s' data-marketing='%d' data-pmtstatus='%d' data-search='%s' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='last-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_marketing,
                $this->selected_paymentstatus,
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
}