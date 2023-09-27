<?php
/**
 * LDNFT_Reviews creates admin side listing
 */

if( ! defined( 'ABSPATH' ) ) exit;

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class LDNFT_Reviews
 */
class LDNFT_Reviews extends WP_List_Table {

    /**
     * Current select Plugin
     */
    public $selected_plugin_id;

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

        /**
         * Set parent defaults
         */
        parent::__construct( [
            'singular'  => 'freemius_customer',
            'plural'    => 'freemius_customers',
            'ajax'      => false
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
            case 'title':
            case 'text':
            case 'user_id':
            case 'useremail':
            case 'job_title':
            case 'company_url':
            case 'picture':
            case 'profile_url':
            case 'is_verified':
            case 'is_featured':
            case 'sharable_img':
            case 'created':
            case 'id':
                return $item[$column_name];
            case 'rate':
                if( !empty($item['sharable_img']) )
                    return '<a target="_blank" href="'.$item['sharable_img'].'">'.$item[$column_name].'</a>';
                else
                    return $item[$column_name];
            case 'name':
                $pic = '';
                if( !empty($item['picture']) )
                    $pic = '<img src="'.$item['picture'].'" width="100px" /><br>';
                if( !empty($item['profile_url']) )
                    return $pic.'<a target="_blank" href="'.$item['profile_url'].'">'.$item[$column_name].'</a>';
                else
                    return $pic.$item[$column_name];
            case 'company':
                if( !empty($item['company_url']) )
                    return '<a target="_blank" href="'.$item['company_url'].'">'.$item[$column_name].'</a>';
                else
                    return $item[$column_name];
            default:
                return print_r($item,true);
        }
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
    public function column_title( $item ) {
        
        return $item['title'];
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
            'id'                => __( 'ID', LDNFT_TEXT_DOMAIN ),
            'user_id'           => __( 'User ID', LDNFT_TEXT_DOMAIN ),
            'useremail'         => __( 'Email',LDNFT_TEXT_DOMAIN ),
            'job_title'         => __( 'Job Title', LDNFT_TEXT_DOMAIN ),
            'company_url'       => __( 'Company URL', LDNFT_TEXT_DOMAIN ),
            'picture'           => __( 'Picture', LDNFT_TEXT_DOMAIN ),
            'profile_url'       => __( 'Profile URL', LDNFT_TEXT_DOMAIN ),
            'is_verified'       => __( 'Is Verified', LDNFT_TEXT_DOMAIN ),
            'is_featured'       => __( 'Is Featured', LDNFT_TEXT_DOMAIN ),
            'sharable_img'      => __( 'Sharable Image', LDNFT_TEXT_DOMAIN ),
            'title'             => __( 'Review Title', LDNFT_TEXT_DOMAIN ),
            'text'              => __( 'Comment', LDNFT_TEXT_DOMAIN ),
            'name'              => __( 'Name', LDNFT_TEXT_DOMAIN ),
            'rate'              => __( 'Rating', LDNFT_TEXT_DOMAIN ),
            'company'           => __( 'Company', LDNFT_TEXT_DOMAIN ),
            'created'           => __( 'Joined', LDNFT_TEXT_DOMAIN ),
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
            'reviews_per_page',
            get_current_user_id()
        );
        
        if( empty($per_page) ) {
            $per_page = 10;
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
        $screen = get_current_screen();
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
        $results = $this->api->Api('plugins/'.$this->selected_plugin_id.'/reviews.json?is_featured=true&count='.$per_page.'&offset='.$offset_rec, 'GET', ['is_featured'=>'false','is_verified'=>'false', 'enriched'=>'true' ]);
        
        $data = [];
        $count = 0;
        foreach( $results->reviews as $review ) {
            
            foreach( $review as $key=>$value ) {
                
                if( empty( $value ) ) 
                    $value = '-';
                
                $data[$count][$key] = $value;
                
                if( 'user_id' == $key ) {
                    $user_id = $value;
                }
            } 

            $user = $this->api->Api('plugins/'.$this->selected_plugin_id.'/users/'.$user_id.'.json', 'GET', []);
            if( $user ) {
                $data[$count]['useremail']      = $user->email;
                if(empty(trim($data[$count]['useremail']))) {
                    $data[$count]['useremail'] = '-';
                }
            }

            $count++;   
        }
        
        /**
         * This checks for sorting input and sorts the data in our array accordingly.
         * 
         * In a real-world situation involving a database, you would probably want 
         * to handle sorting by passing the 'orderby' and 'order' values directly 
         * to a custom query. The returned data will be pre-sorted, and this array
         * sorting technique would be unnecessary.
         */
        function usort_reorder($a,$b){

            /**
             * If no sort, default to title
             */
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'id';

            /**
             * If no order, default to asc
             */
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';

            /**
             * Determine sort order
             */
            $result = strcmp($a[$orderby], $b[$orderby]);

            /**
             * Send final sort direction to usort
             */
            return ($order==='asc') ? $result : -$result;
        }
        usort($data, 'usort_reorder');
        
        /***********************************************************************
         * ---------------------------------------------------------------------
         * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
         * 
         * In a real-world situation, this is where you would place your query.
         *
         * For information on making queries in WordPress, see this Codex entry:
         * http://codex.wordpress.org/Class_Reference/wpdb
         * 
         * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
         * ---------------------------------------------------------------------
         **********************************************************************/
        
                
        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = $this->get_pagenum();
        
        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = count($data);
        
        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( [
            'per_page'      => $per_page,
            'offset'        => $offset,
            'offset_rec'    => $offset_rec,
            'current_recs'  => count($results->reviews)
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
        
        
        $result = $this->api->Api('plugins/'.$this->selected_plugin_id.'/reviews.json?is_featured=true&count='.$per_page.'&offset='.$offset_rec1, 'GET', []);
        
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


		if ( is_array($result->reviews) && count($result->reviews) == 0 ) {
			//$disable_next = true;
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
				"<a data-action='ldnft_reviews_check_next' data-plugin_id='%d' data-per_page='%d' data-offset='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='%s'>" .
					"<span class='screen-reader-text'>%s</span>" .
					"<span aria-hidden='true'>%s</span>" .
				'</a>',
                $this->selected_plugin_id,
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

    public function extra_tablenav( $which ) {
        
        if ( $which == "top" ){
            ?>
            <div class="alignleft actions bulkactions">
                <select onchange="document.location='admin.php?page=freemius-reviews&ldfmt_plugins_filter='+this.value" name="ldfmt-plugins-filter" class="ldfmt-plugins-filter">
                    <option value="">Filter by Plugin</option>
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
                
            </div>
            <?php
        }

    }
}