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
     * Freemius API object
     */
    public $api;

     /**
     * Plugins list
     */
    public $plugins;
    
    /**
     * Plugins
     */
    public $plugins_short_array;
    
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    public function __construct(){
        global $status, $page;

        $this->selected_plugin_id = 0;  
        $this->api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
        
        $this->plugins = $this->api->Api('plugins.json?fields=id,title', 'GET', ['fields'=>'id,title']);

        if( isset( $this->plugins->plugins ) &&  count($this->plugins->plugins) > 0 ) {
            $this->plugins = $this->plugins->plugins;
            $plugin = $this->plugins[0];
            if( $this->selected_plugin_id <= 0 ) {
                $this->selected_plugin_id = $plugin->id;  
            }

            foreach( $this->plugins as $plugin ) {
                $this->plugins_short_array[$plugin->id] = $plugin->title;
            }
        }

        if( isset($_GET['ldfmt_plugins_filter']) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) {
            $this->selected_plugin_id = intval( $_GET['ldfmt_plugins_filter'] ); 
        }

        if( isset($_GET['filter'])  ) {
            $this->selected_filter = $_GET['filter']; 
        }
        
        /**
         * Set parent defaults
         */
        parent::__construct( array(
            'singular'  => 'sale',
            'plural'    => 'sales',  
            'ajax'      => true  
        ) );
        
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
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&movie=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
        );
        
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
        $columns = array(
            'id'                    => 'ID',
            'user_id'               => 'User ID',
            'username'              => 'Name',
            'useremail'             => 'Email',
            'subscription_id'       => 'Subscription ID',
            'gateway_fee'           => 'Gateway Fee',
            'gross'                 => 'Gross',
            'license_id'            => 'License',
            'gateway'               => 'Gateway',
            'country_code'          => 'Country',
            'is_renewal'            => 'Renewal?',
            'type'                  => 'Type',
            'created'               => 'Created',
        );
        
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
            'id'                => array('id',false), 
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
     * @global WPDB $wpdb
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    public function prepare_items() {
        
        global $wpdb; 

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 20;
        $offset = isset($_REQUEST['offset']) && intval($_REQUEST['offset'])>0?intval($_REQUEST['offset']):0;
        
        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        /**
         * REQUIRED. Finally, we build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
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
           //$filter_str = '&filter='.$this->selected_filter;
        }
        $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/payments.json?count='.$per_page.'&offset='.$offset.$filter_str, 'GET', []);

        $data = [];
        $count = 0;
        foreach( $result->payments as $payment ) {
            $user_id = 0;

            foreach( $payment as $key => $value ) {
                $data[$count][$key] = $value;
                if( 'user_id' == $key ) {
                    $user_id = $value;
                }
            } 

            $user = $this->api->Api('plugins/'.$this->selected_plugin_id.'/users/'.$user_id.'.json', 'GET', []);
            $data[$count]['username']   = $user->first.' '.$user->last;
            $data[$count]['useremail']  = $user->email;

            $count++;   
        }

        $this->items = $data;
 
        $this->set_pagination_args( array(
           'per_page'      => $per_page,
           'offset'        => $offset ,
           'current_recs'  => count($result->payments)
        ) );
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
        $current_recs   = $this->_pagination_args['current_recs'];
        
        $filter_str = '';
        if( !empty($this->selected_filter) ) {
           // $filter_str = '&filter='.$this->selected_filter;
        }
        
        $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/payments.json?count='.$per_page.'&offset='.$offset.$filter_str, 'GET', []);
        
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

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = false;
		$disable_prev  = false;
		$disable_next  = false;

		if ( 0 == $offset  ) {
			$disable_first = true;
			$disable_prev  = true;
		}


		if ( count($result->payments) == 0 ) {
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
				esc_url( add_query_arg( 'offset', (intval($offset)-intval($per_page)), $current_url ) ),
				/* translators: Hidden accessibility text. */
				__( 'Previous page', LDNFT_TEXT_DOMAIN ),
				'&lsaquo;'
			);
		}
        
        $page_links[] = $total_pages_before . sprintf(
			/* translators: 1: Current page, 2: Total pages. */
			_x( '%1$s to %2$s', 'paging' ),
			$offset+1,
			(intval($offset)+intval($current_recs))-1
		) . $total_pages_after;


		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
				esc_url( add_query_arg( 'offset', (intval($offset)+intval($per_page)), $current_url ) ),
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
        global $wpdb;
        
        if ( $which == "top" ){
            
            $filter_str = '';
            if( !empty($this->selected_filter) ) {
                //$filter_str = '&filter='.$this->selected_filter;
            }
            $tem_per_page = 50;
            $tem_offset = 0;
            $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/payments.json?count='.$tem_per_page.'&offset='.$tem_offset.$filter_str, 'GET', []);
            $gross_total = 0;
            $gateway_fee_total = 0;
            if( count( $result->payments ) > 0 ) {
                $has_more_records = true;
                while($has_more_records) {
                    foreach( $result->payments as $payment ) {
                        $gross_total += $payment->gross;
                        $gateway_fee_total += $payment->gateway_fee;
                    } 

                    $tem_offset += $tem_per_page;
                    $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/payments.json?count='.$tem_per_page.'&offset='.$tem_offset.$filter_str, 'GET', []);
                    if( count( $result->payments ) > 0 ) {
                        $has_more_records = true;
                    } else {
                        $has_more_records = false;
                    }
                }
            }
            ?>
            <div class="ldfmt-sales-upper-info">
                <div class="ldfmt-gross-sales-box ldfmt-sales-box">
                    <label><?php echo __('Gross Sales', LDNFT_TEXT_DOMAIN);?></label>
                    <div class="ldnft_points"><?php echo number_format( floatval($gross_total), 2);?></div>
                </div>
                <div class="ldfmt-gross-gateway-box ldfmt-sales-box">
                    <label><?php echo __('Total Gateway Fee', LDNFT_TEXT_DOMAIN);?></label>
                    <div class="ldnft_gateway_fee"><?php echo number_format( floatval($gateway_fee_total), 2);?></div>
                </div>
            </div>
            <div class="alignleft actions bulkactions">
                <select onchange="document.location='admin.php?page=ldninjas-freemius-toolkit-sales&interval=<?php echo $this->selected_filter;?>&ldfmt_plugins_filter='+this.value" name="ldfmt-plugins-filter" class="ldfmt-plugins-filter">
                    <option value=""><?php _e( 'Filter by Plugin', LDNFT_TEXT_DOMAIN ); ?></option>
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
                <img style="display:none" width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                <span id="ldnft-sales-import-message"></span>
            </div>
            <?php
        }
    }
}