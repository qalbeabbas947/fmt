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
    public $selected_filter;

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
        $this->plugins = $this->plugins->plugins;

        $this->selected_plugin_id = ( isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) ? intval( $_GET['ldfmt_plugins_filter'] ) : $this->plugins[0]->id;
        $this->selected_interval = ( isset( $_GET['interval'] ) ) ? $_GET['interval'] : 12; 
        $this->selected_filter = ( isset( $_GET['filter'] )  ) ? $_GET['filter'] : 'all'; 
        

        // $this->selected_plugin_id = 0;  
        // $this->api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        
        // $this->plugins = $this->api->Api('plugins.json?fields=id,title', 'GET', ['fields'=>'id, title']);

        // if( isset( $this->plugins->plugins ) &&  count($this->plugins->plugins) > 0 ) {
        //     $this->plugins = $this->plugins->plugins;
        //     $plugin = $this->plugins[0];
        //     if( $this->selected_plugin_id <= 0 ) {
        //         $this->selected_plugin_id = $plugin->id;  
        //     }

        //     foreach( $this->plugins as $plugin ) {
        //         //$this->plugins_short_array[$plugin->id] = $plugin->title;
        //     }
        // }

        // if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
        //     $this->selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        // }

        // if( isset($_GET['filter'])  ) {
        //     $this->selected_filter = $_GET['filter']; 
        // }
        
        // $this->selected_interval = 12; 
        // if( isset($_GET['interval'])  ) {
        //     $this->selected_interval = $_GET['interval']; 
        // }
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
            return '<a data-action="ldnft_sales_view_detail" data-user_id="'.$item['user_id'].'" data-plugin_id="'.$item['plugin_id'].'" data-id="'.$item['id'].'" class="ldnft_sales_view_detail" href="javascript:;">'._e('Get More', LDNFT_TEXT_DOMAIN).'</a>';
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
     * Recommended. This is a custom column method and is responsible for what
     * is rendered in any column with a name/slug of 'title'. Every time the class
     * needs to render a column, it first looks for a method named 
     * column_{$column_title} - if it exists, that method is run. If it doesn't
     * exist, column_default() is called instead.
     * 
     * This example also illustrates how to implement rollover actions. Actions
     * should be an associative array formatted as 'slug'=>'link html' - and you
     * will need to generate the URLs yourself. You could even ensure the links
     * 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    public function column_title($item){
        
        /**
         * Build row actions 
         */
        $actions = [
            'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&movie=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
        ];
        
        /**
         * Return the title contents 
         */
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['title'],
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }


    /** ************************************************************************
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    public function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  
            /*$2%s*/ $item['ID']               
        );
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
            'pricing_id'            => __( 'Pricing ID',LDNFT_TEXT_DOMAIN ), 
            'ip'                    => __( 'ID',LDNFT_TEXT_DOMAIN ), 
            'zip_postal_code'       => __( 'Zip/Postal Code',LDNFT_TEXT_DOMAIN ), 
            'vat_id'                => __( 'VAT ID',LDNFT_TEXT_DOMAIN ), 
            'coupon_id'             => __( 'Coupon ID',LDNFT_TEXT_DOMAIN ), 
            'user_card_id'          => __( 'Card ID',LDNFT_TEXT_DOMAIN ), 
            'plugin_id'             => __( 'Product ID',LDNFT_TEXT_DOMAIN ), 
            'external_id'           => __( 'External ID',LDNFT_TEXT_DOMAIN ), 
            'currency'              => __( 'Currency',LDNFT_TEXT_DOMAIN ), 
            'username'              => __( 'User Name',LDNFT_TEXT_DOMAIN ), 
            'useremail'             => __( 'Email',LDNFT_TEXT_DOMAIN ), 
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
        
        return [];
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
                    'pricing_id'            => LDNFT_Admin::get_bar_preloader(),
                    'ip'                    => LDNFT_Admin::get_bar_preloader(),
                    'zip_postal_code'       => LDNFT_Admin::get_bar_preloader(),
                    'vat_id'                => LDNFT_Admin::get_bar_preloader(),
                    'coupon_id'             => LDNFT_Admin::get_bar_preloader(),
                    'user_card_id'          => LDNFT_Admin::get_bar_preloader(),
                    'plugin_id'             => LDNFT_Admin::get_bar_preloader(),
                    'external_id'           => LDNFT_Admin::get_bar_preloader(),
                    'currency'              => LDNFT_Admin::get_bar_preloader(),
                    'view'                  => LDNFT_Admin::get_bar_preloader()
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

        $offset = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):1;
        $offset_rec = ($offset - 1) * $per_page;
        
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
         
        $filter_str = '';
        if( !empty($this->selected_filter) ) {
           $filter_str = '&filter='.$this->selected_filter;
        }

        $interval_str = '';//'&billing_cycle=12';
        if( !empty($this->selected_interval) ) {
         //  $interval_str = '&billing_cycle='.$this->selected_interval;
        }
        //echo 'plugins/'.$this->selected_plugin_id.'/payments.json?count='.$per_page.'&offset='.$offset_rec.$filter_str.$interval_str;
        $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/payments.json?count='.$per_page.'&offset='.$offset.$filter_str.$interval_str, 'GET', []);
        //print_r($result);
        $data = [];
        $count = 0;
        $total_recs = 0;
        if( isset($result->payments) && is_array($result->payments) && count($result->payments) > 0 ) {
            $total_recs = count($result->payments);
            foreach( $result->payments as $payment ) {
                $user_id = 0;
    
                foreach( $payment as $key => $value ) {
                    $data[$count][$key] = $value;
                    if( 'user_id' == $key ) {
                        $user_id = $value;
                    }
                } 
                $data[$count]['country_code']   = LDNFT_Freemius::get_country_name_by_code( strtoupper($payment->country_code) );
                $user = $this->api->Api('plugins/'.$this->selected_plugin_id.'/users/'.$user_id.'.json', 'GET', []);
                $data[$count]['username']   = $user->first.' '.$user->last;
                $data[$count]['useremail']  = $user->email;
    
                $count++;   
            }
        }
        
        
        $this->items = $data;
 
        $this->set_pagination_args( [
            'per_page'      => $per_page,
            'offset'        => $offset,
            'offset_rec'    => $offset_rec,
            'current_recs'  => $total_recs
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
        
        $per_page       = $this->_pagination_args['per_page'];
		$offset         = $this->_pagination_args['offset'];
        $offset_rec     = $this->_pagination_args['offset_rec'];
        $current_recs   = $this->_pagination_args['current_recs'];
        $offset_rec1    = ($offset) * $per_page;
        
        $interval_str = '&billing_cycle=12';
        if( !empty($this->selected_interval) ) {
            $interval_str = '&billing_cycle='.$this->selected_interval;
        }

        $status_str = '';
        if( !empty($this->selected_status) ) {
            $status_str = '&filter='.$this->selected_status;
        }
        
        $plan_str = '';
        if( !empty($this->selected_plan_id) ) {
           $plan_str = '&plan_id='.$this->selected_plan_id;
        }

        $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/payments.json?count='.$per_page.'&offset='.$offset_rec1.$interval_str.$status_str.$plan_str, 'GET', []);
        
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

		if ( isset($result->payments) && is_array( $result->payments ) && count($result->payments) == 0 ) {
			$disable_next = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( remove_query_arg( 'offset', $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'First page', LDNFT_TEXT_DOMAIN ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {

			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>', 
				esc_url( add_query_arg( 'offset', intval($offset)>1?intval($offset)-1:1, $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'Previous page', LDNFT_TEXT_DOMAIN ),
				'&lsaquo;'
			);
		}
        
        if( $offset == 1 && $current_recs == 0 ) {
            $page_links[] = $total_pages_before . sprintf(
                /* translators: 1: Current page, 2: Total pages. */
                _x( '%1$s to %2$s', 'paging' ),
                0,
                0
            ) . $total_pages_after;
        } else {
            $page_links[] = $total_pages_before . sprintf(
                /* translators: 1: Current page, 2: Total pages. */
                _x( 'From %1$s to %2$s', 'paging' ),
                $offset_rec>0?$offset_rec+1:1,
                (intval($offset_rec)+intval($current_recs))
                ) . $total_pages_after;
        }

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a data-action='ldnft_sales_check_next' data-plugin_id='%d' data-status='%s' data-plan_id='%s' data-interval='%d' data-per_page='%d' data-offset='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='%s'>" .
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
				esc_url( add_query_arg( 'offset', intval($offset)+1, $current_url ) ),
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
    public function extra_tablenav1( $which ) {
        
        if ( $which == "top" ){ 
            
            $filter_str = '';
            if( !empty($this->selected_filter) ) {
                $filter_str = '&filter='.$this->selected_filter;
            }
            
            $interval_str = '&billing_cycle=12';
            if( !empty($this->selected_interval) ) {
                $interval_str = '&billing_cycle='.$this->selected_interval;
            }

            $tem_per_page = 50;
            $tem_offset = 0;
            $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/payments.json?count='.$tem_per_page.'&offset='.$tem_offset.$filter_str.$interval_str, 'GET', []);
            $gross_total = 0;
            $gateway_fee_total = 0;
            $total_number_of_sales = 0;
            $total_new_subscriptions = 0;
            $total_new_renewals = 0;

            if( isset($result->payments) && is_array( $result->payments ) && count( $result->payments ) > 0 ) {
                $has_more_records = true;
                while($has_more_records) {
                    foreach( $result->payments as $payment ) {
                        $gross_total += $payment->gross;
                        $gateway_fee_total += $payment->gateway_fee;

                        $total_number_of_sales++;
                        if( $payment->is_renewal == '1' || $payment->is_renewal == 1 ) {
                            $total_new_renewals++;
                        } else {
                            $total_new_subscriptions++;
                        }
                    } 
 
                    $tem_offset += $tem_per_page;
                    $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/payments.json?count='.$tem_per_page.'&offset='.$tem_offset.$filter_str.$interval_str, 'GET', []);
                    if( count( $result->payments ) > 0 ) {
                        $has_more_records = true;
                    } else {
                        $has_more_records = false;
                    }
                }
            }
            ?>
            <div class="ldnft_filters_top">
                <div class="alignleft actions bulkactions">
                    <select onchange="document.location='admin.php?page=freemius-sales&interval=<?php echo $this->selected_filter;?>&ldfmt_plugins_filter='+this.value" name="ldfmt-plugins-filter" class="ldfmt-plugins-filter">
                        <?php
                            foreach( $this->plugins as $plugin ) {
                                
                                $selected = '';
                                if( $this->selected_plugin_id == $plugin->id ) {
                                    $selected = ' selected = "selected"';   
                                }
                                ?>
                                    <option value="<?php echo $plugin->id; ?>" <?php echo $selected; ?>><?php echo $plugin->title; ?></option>
                                <?php   
                            }
                        ?>
                    </select>
                    <select onchange="document.location='admin.php?page=freemius-sales&ldfmt_plugins_filter=<?php echo $this->selected_plugin_id;?>&filter=<?php echo $this->selected_filter;?>&interval='+this.value" name="ldfmt-sales-interval-filter" class="ldfmt-sales-interval-filter">
                        <option value=""><?php echo __( 'All Time', LDNFT_TEXT_DOMAIN );?></option>
                        <option value="1" <?php echo $this->selected_interval=='1'?'selected':'';?>><?php echo __( 'Monthly', LDNFT_TEXT_DOMAIN );?></option>
                        <option value="12" <?php echo $this->selected_interval=='12'?'selected':'';?>><?php echo __( 'Annual', LDNFT_TEXT_DOMAIN );?></option>
                    </select>
                    <select onchange="document.location='admin.php?page=freemius-sales&ldfmt_plugins_filter=<?php echo $this->selected_plugin_id;?>&interval=<?php echo $this->selected_interval;?>&filter='+this.value" name="ldfmt-sales-filter" class="ldfmt-sales-filter">
                        <option value="all"><?php echo __( 'All Status', LDNFT_TEXT_DOMAIN );?></option>
                        <option value="not_refunded" <?php echo $this->selected_filter=='not_refunded'?'selected':'';?>><?php echo __( 'Not Refunded', LDNFT_TEXT_DOMAIN );?></option>
                        <option value="refunds" <?php echo $this->selected_filter=='refunds'?'selected':'';?>><?php echo __( 'Refunds', LDNFT_TEXT_DOMAIN );?></option>
                    </select>
                    <img style="display:none" width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                    <span id="ldnft-sales-import-message"></span>
                </div>
                <div style="clear:both">&nbsp;</div>
                <div class="ldfmt-sales-upper-info">
                    <div class="ldfmt-gross-sales-box ldfmt-sales-box">
                        <label><?php echo __('Gross Sales', LDNFT_TEXT_DOMAIN);?></label>
                        <div class="ldnft_points"><?php echo number_format( floatval($gross_total), 2);?></div>
                    </div>
                    <div class="ldfmt-gross-gateway-box ldfmt-sales-box">
                        <label><?php echo __('Total Gateway Fee', LDNFT_TEXT_DOMAIN);?></label>
                        <div class="ldnft_gateway_fee"><?php echo number_format( floatval($gateway_fee_total), 2);?></div>
                    </div>
                    <div class="ldfmt-new-sales-box ldfmt-sales-box">
                        <label><?php echo __('Total Sales Count', LDNFT_TEXT_DOMAIN);?></label>
                        <div class="ldnft_new_sales_count"><?php echo $total_number_of_sales;?></div>
                    </div>
                    <div class="ldfmt-new-subscriptions-box ldfmt-sales-box">
                        <label><?php echo __('New subscriptions', LDNFT_TEXT_DOMAIN);?></label>
                        <div class="ldnft_new_subscriptions_count"><?php echo $total_new_subscriptions;?></div>
                    </div>
                    <div class="ldfmt-renewals-count-box ldfmt-sales-box">
                        <label><?php echo __('Total Renewals', LDNFT_TEXT_DOMAIN);?></label>
                        <div class="ldnft_renewals_count"><?php echo $total_new_renewals;?></div>
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
                        
                    </div>
                    <div class="ldnft-popup-loader"><img class="" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" /></div>
                </div>
            </div>
            <?php
        }
    }
}