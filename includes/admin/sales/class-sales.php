<?php
/**
 * LDFMT Pro admin template
 *
 * Do not allow directly accessing this file.
 */

if( ! defined( 'ABSPATH' ) ) exit;

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class LDNFT_Sales
 */
class LDNFT_Sales extends WP_List_Table {

    /**
     * Current select Plugin
     */
    public $selected_plugin_id;

    /**
     * Selected Filter
     */
    public $selected_status;

     /**
     * Current selected Interval
     */
    public $selected_interval;

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

        $this->selected_plugin_id = ( isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) ? intval( $_GET['ldfmt_plugins_filter'] ) : $this->plugins[0]->id;
        $this->selected_interval = ( isset( $_GET['interval'] ) ) ? sanitize_text_field( $_GET['interval'] ) : 12; 
        $this->selected_status = ( isset( $_GET['status'] )  ) ? sanitize_text_field( $_GET['status'] ) : 'all'; 
        
        /**
         * Set parent defaults
         */
        parent::__construct( [
            'singular'  => 'sale',
            'plural'    => 'sales',  
            'ajax'      => true  
        ] );
        
    }

    /**
     * Will display a link to show popup for the subscription detail.
     */
    public function column_view( $item ) {
        
        if( !empty( intval( strip_tags( $item['id'] ) ) ) ) {
            return '<a class="ldnft_sales_view_detail" data-action="ldnft_sales_view_detail" data-username="'.$item['username'].'" data-useremail="'.$item['useremail'].'" data-subscription_id="'.$item['subscription_id'].'" data-gateway_fee="'.$item['gateway_fee'].'" data-gross="'.$item['gross'].'" data-license_id="'.$item['license_id'].'" data-gateway="'.$item['gateway'].'" data-country_code="'.$item['country_code'].'" data-is_renewal="'.$item['is_renewal'].'" data-type="'.$item['type'].'" data-bound_payment_id="'.$item['bound_payment_id'].'" data-created="'.$item['created'].'" data-vat="'.$item['vat'].'" data-install_id="'.$item['install_id'].'" data-plan_id="'.$item['plan_id'].'"  data-coupon_id="'.$item['coupon_id'].'"  data-external_id="'.$item['external_id'].'" data-user_id="'.$item['user_id'].'" data-plugin_id="'.$item['plugin_id'].'" data-id="'.$item['id'].'" class="ldnft_sales_view_detail" href="javascript:;">'.__('Get More', LDNFT_TEXT_DOMAIN).'</a>';

 			
			
        } else {
            return LDNFT_Admin::get_bar_preloader();
        }    
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
        return $item[$column_name];
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
            'id'                    => __( 'Tansaction ID',LDNFT_TEXT_DOMAIN ), 
            'user_id'               => __( 'User ID',LDNFT_TEXT_DOMAIN ), 
            'username'              => __( 'Name',LDNFT_TEXT_DOMAIN ), 
            'useremail'             => __( 'Email',LDNFT_TEXT_DOMAIN ), 
            'subscription_id'       => __( 'Subscription ID',LDNFT_TEXT_DOMAIN ), 
            'gateway_fee'           => __( 'Gateway Fee',LDNFT_TEXT_DOMAIN ), 
            'gross'                 => __( 'Total Amount',LDNFT_TEXT_DOMAIN ), 
            'license_id'            => __( 'License',LDNFT_TEXT_DOMAIN ), 
            'gateway'               => __( 'Gateway',LDNFT_TEXT_DOMAIN ), 
            'country_code'          => __( 'Country',LDNFT_TEXT_DOMAIN ), 
            'is_renewal'            => __( 'Renewal?',LDNFT_TEXT_DOMAIN ), 
            'type'                  => __( 'Type',LDNFT_TEXT_DOMAIN ), 
            'bound_payment_id'      => __( 'Bound Payment ID',LDNFT_TEXT_DOMAIN ), 
            'created'               => __( 'Payment Date',LDNFT_TEXT_DOMAIN ), 
            'vat'                   => __( 'VAT',LDNFT_TEXT_DOMAIN ), 
            'install_id'            => __( 'Install ID',LDNFT_TEXT_DOMAIN ), 
            'plan_id'               => __( 'Plan ID',LDNFT_TEXT_DOMAIN ), 
            'coupon_id'             => __( 'Coupon ID',LDNFT_TEXT_DOMAIN ), 
            'plugin_id'             => __( 'Product ID',LDNFT_TEXT_DOMAIN ), 
            'external_id'           => __( 'External ID',LDNFT_TEXT_DOMAIN ), 
            'username'              => __( 'User Name',LDNFT_TEXT_DOMAIN ), 
            'useremail'             => __( 'Email',LDNFT_TEXT_DOMAIN ), 
			'view'					=> __( 'Action',LDNFT_TEXT_DOMAIN ) 
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
            'id'                => array('id',false),     //true means it's already sorted
            'plugin_title'      => array('plugin_title',false),
            'user_id'           => array('user_id',false),
            'username'          => array('username',false),
            'useremail'         => array('useremail',false),
            'subscription_id'   => array('subscription_id',false),
            'gateway_fee'       => array('gateway_fee',false),
            'gross'             => array('gross',false),
            'gateway'           => array('gateway',false),
            'license_id'        => array('license_id',false),
            'gateway'           => array('gateway',false),
            'country_code'      => array('country_code',false),
            'created'           => array('created',false),
            'is_renewal'        => array('is_renewal',false),
            'type'              => array('type',false),
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
        
        global $wpdb;
        ini_set('display_errors', 'On');
        error_reporting(E_ALL);
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = get_user_option(
            'sales_per_page',
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
                    'subscription_id'       => LDNFT_Admin::get_bar_preloader(), 
                    'gateway_fee'           => LDNFT_Admin::get_bar_preloader(), 
                    'gross'                 => LDNFT_Admin::get_bar_preloader(), 
                    'license_id'            => LDNFT_Admin::get_bar_preloader(), 
                    'gateway'               => LDNFT_Admin::get_bar_preloader(), 
                    'country_code'          => LDNFT_Admin::get_bar_preloader(), 
                    'is_renewal'            => LDNFT_Admin::get_bar_preloader(), 
                    'type'                  => LDNFT_Admin::get_bar_preloader(), 
                    'bound_payment_id'      => LDNFT_Admin::get_bar_preloader(), 
                    'created'               => LDNFT_Admin::get_bar_preloader(), 
                    'vat'                   => LDNFT_Admin::get_bar_preloader(),  
                    'install_id'            => LDNFT_Admin::get_bar_preloader(), 
                    'plan_id'               => LDNFT_Admin::get_bar_preloader(), 
                    'coupon_id'             => LDNFT_Admin::get_bar_preloader(),
                    'plugin_id'             => LDNFT_Admin::get_bar_preloader(),
                    'external_id'           => LDNFT_Admin::get_bar_preloader(),
                    'view'                  => LDNFT_Admin::get_bar_preloader()
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
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $screen = WP_Screen::get( 'freemius-toolkit_page_freemius-sales' );
        $hidden   = get_hidden_columns( $screen );
        $sortable = $this->get_sortable_columns();
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = [ $columns, $hidden, $sortable ];
        
        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();
        
        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example 
         * package slightly different than one you might build on your own. In 
         * this example, we'll be using array manipulation to sort and paginate 
         * our data. In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
        $table_name = $wpdb->prefix.'ldnft_transactions t inner join '.$wpdb->prefix.'ldnft_customers c on (t.user_id=c.id)';  
        $where = " where t.plugin_id='".$this->selected_plugin_id."'";
        
        if( $this->selected_status != 'all' ) {
            switch( $this->selected_status ) {
                case "not_refunded":
                    $where .= " and t.type='payment' and ( t.bound_payment_id  = '' or t.bound_payment_id is NULL)";
                    break;
                case "refunds":
                    $where .= " and t.type='refund'";
                    break;
                case "chargeback":
                    $where .= " and t.type='chargeback'";
                    break;
                case "lost_dispute":
                    $where .= " and t.type='lost_dispute'";
                    break;
                        
            }
        } else {
            $where .= " and t.type in ('payment', 'refund' ) ";
        }

        $where_interval = '';
        if( !empty( $this->selected_interval )) {
            switch( $this->selected_interval ) {
                case "current_week":
                    $where_interval = " and YEARWEEK(t.created) = YEARWEEK(NOW())";
                    break;
                case "last_week":
                    $where_interval = ' and Date(t.created) between date_sub(now(),INTERVAL 1 WEEK) and now()';
                    break;
                case "current_month":
                    $where_interval = ' and MONTH(t.created) = MONTH(now()) and YEAR(t.created) = YEAR(now())';
                    break;
                case "last_month":
                    $where_interval = ' and Date(t.created) between Date((now() - interval 1 month)) and Date(now())';
                    break;
                default:
                    $where_interval = " and Date(t.created) = '".date('Y-m-d')."'";
                    break;
            }
        }

        $total_items = $wpdb->get_var("SELECT COUNT(t.id) FROM $table_name".$where.$where_interval);
 
        // prepare query params, as usual current page, order by and order direction
        $offset = isset($paged) ? (intval($paged) -1) * $per_page : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? sanitize_text_field( $_REQUEST['order'] ) : 'asc';

        $orderby_prefix = "t.";
        if( in_array( $orderby, [ 'username', 'useremail' ] ) ) {
            $orderby_prefix = "";
        }
        $result = $wpdb->get_results($wpdb->prepare("SELECT t.*, concat(c.first, ' ', c.first) as username, c.email as useremail FROM $table_name $where $where_interval ORDER BY $orderby_prefix$orderby $order LIMIT %d OFFSET %d", $per_page, $offset));
         
        $data = [];
        $count = 0;
        if( isset($result) && is_array($result) && count($result) > 0 ) {
            foreach( $result as $payment ) {
                $user_id = 0;
    
                foreach( $payment as $key => $value ) {
	
					if( empty( $value ) ) {
                        $value = '-';
                    }

                    $data[$count][$key] = $value;
                } 
                
                $data[$count]['country_code']   = LDNFT_Freemius::get_country_name_by_code( strtoupper($payment->country_code) );
                
	
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
                "<a data-action='ldnft_sales_check_next' data-ldfmt_plugins_filter='%d' data-status='%s' data-plan_id='%s' data-interval='%d' data-per_page='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' data-paged='1' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_plan_id,
                $this->selected_interval,
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
                "<a data-action='ldnft_sales_check_next' data-ldfmt_plugins_filter='%d' data-status='%s' data-plan_id='%s' data-interval='%d' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_plan_id,
                $this->selected_interval,
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
                "<a data-action='ldnft_sales_check_next' data-ldfmt_plugins_filter='%d' data-status='%s' data-plan_id='%s' data-interval='%d' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_plan_id,
                $this->selected_interval,
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
                "<a data-action='ldnft_sales_check_next' data-ldfmt_plugins_filter='%d' data-status='%s' data-plan_id='%s' data-interval='%d' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_status,
                $this->selected_plan_id,
                $this->selected_interval,
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
    public function extra_tablenav1( $which ) {
        
        if ( $which == "top" ){ 
        }
    }
}