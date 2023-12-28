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
     * Featured and Not Featured records
     */
    public $selected_featured;

    /**
     * General Search
     */
    public $selected_search;

    /**
     * General Search
     */
    public $selected_verified;

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

		$this->plugins = LDNFT_Freemius::$products;
        $this->selected_plugin_id = ( isset( $_GET['ldfmt_plugins_filter'] ) && intval( $_GET['ldfmt_plugins_filter'] ) > 0 ) ? intval( $_GET['ldfmt_plugins_filter'] ) : '';
        $this->selected_featured   = isset( $_REQUEST['featured'] ) ? sanitize_text_field( $_REQUEST['featured'] ) : '';
        $this->selected_search     = isset( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';
        $this->selected_verified    = isset( $_REQUEST['verified'] ) ? sanitize_text_field( $_REQUEST['verified'] ) : '';

        /**
         * Set parent defaults
         */
        parent::__construct( [
            'singular'  => 'freemius_review',
            'plural'    => 'freemius_reviews',
            'ajax'      => true
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
        
        if( array_key_exists( $column_name, $item ) ) {
            return $item[$column_name]; 
        }
		
        return ''; 
    }
	
	public function column_company( $item ) {
        
		if( LDNFT_Admin::get_bar_preloader() == $item['company'] ) {
			return LDNFT_Admin::get_bar_preloader();
		}
		
        if( !empty($item['company_url']) ) {
			return '<a target="_blank" href="'.$item['company_url'].'">'.$item['company'].'</a>';
		} else {
			return $item['company'];
		}
			
    }
	
	public function column_name( $item ) {
        
		if( LDNFT_Admin::get_bar_preloader() == $item['name'] ) {
			return LDNFT_Admin::get_bar_preloader();
		}
		
        $pic = '';
		if( !empty($item['picture']) && trim($item['picture']) != '-' ) {
			$pic = $item['picture'].'<br>';
		}
			
		return $pic.$item['name'];
    }
	
	public function column_rate( $item ) {
	
		if( LDNFT_Admin::get_bar_preloader() == $item['rate'] ) {
			return LDNFT_Admin::get_bar_preloader();
		}
		
		ob_start();
		
		$rates = $item['rate'];
		for($i=1; $i<=5; $i++) {
			$selected = '';
			if( $i*20 <= $rates ) {
				$selected = 'ldnft-checked';
			}
			echo '<span class="fa fa-star '.$selected.'"></span>';
		}
        
		$rating = ob_get_clean();
		
		return $rating;
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
            'is_featured'       => __( 'Is Featured', LDNFT_TEXT_DOMAIN ),
            'id'                => __( 'ID', LDNFT_TEXT_DOMAIN ),
            'user_id'           => __( 'User ID', LDNFT_TEXT_DOMAIN ),
            'useremail'         => __( 'Email',LDNFT_TEXT_DOMAIN ),
            'job_title'         => __( 'Job Title', LDNFT_TEXT_DOMAIN ),
            'company_url'       => __( 'Company URL', LDNFT_TEXT_DOMAIN ),
            'picture'           => __( 'Picture', LDNFT_TEXT_DOMAIN ),
            'profile_url'       => __( 'Profile URL', LDNFT_TEXT_DOMAIN ),
            'is_verified'       => __( 'Is Verified', LDNFT_TEXT_DOMAIN ),
            'sharable_img'      => __( 'Sharable Image', LDNFT_TEXT_DOMAIN ),
            'title'             => __( 'Review Title', LDNFT_TEXT_DOMAIN ),
            'text'              => __( 'Comment', LDNFT_TEXT_DOMAIN ),
            'name'              => __( 'Name', LDNFT_TEXT_DOMAIN ),
            'rate'              => __( 'Rating', LDNFT_TEXT_DOMAIN ),
            'company'           => __( 'Company', LDNFT_TEXT_DOMAIN ),
            'created'           => __( 'Joined', LDNFT_TEXT_DOMAIN ),
			'view'              => __( 'Action', LDNFT_TEXT_DOMAIN ),
        ];
        
        return $columns;
    }
	
	/**
	* format the is_featured column
	*/
	public function column_is_featured($item){
       //return LDNFT_Admin::get_bar_preloader().' != '. $item['is_featured'];
        if( LDNFT_Admin::get_bar_preloader() !=  $item['is_featured'] ) {
            return '<input class="ldnft_is_featured_enabled_click" type="checkbox" '.(intval( $item['is_featured'] ) == 1?'checked':'').' id="is_featured_'.$item['id'].'" data-plugin_id="'.$item['plugin_id'].'" data-id="'.$item['id'].'" name="is_featured[]" value="'.$item['is_featured'].'" />';
        } elseif( $item['is_featured'] == 1 ) {
            return '<input class="ldnft_is_featured_enabled_click" type="checkbox" '.(intval( $item['is_featured'] ) == 1?'checked':'').' id="is_featured_'.$item['id'].'" data-plugin_id="'.$item['plugin_id'].'" data-id="'.$item['id'].'" name="is_featured[]" value="'.$item['is_featured'].'" />';
        } else {
            return $item['is_featured'];
        }

	}
	
	/**
	* format the is_verified column
	*/
	public function column_is_verified( $item ){
        
		if( isset( $item['column_is_verified'] ) && intval( $item['column_is_verified'] ) == 1 ) {
			return __( 'Yes', LDNFT_TEXT_DOMAIN );
		} else {
			return __( 'No', LDNFT_TEXT_DOMAIN );
		}
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
            'id'     => array('id',false),     //true means it's already sorted
            'title'    => array('title',false),
            'text'  => array('text',false),
            'name'  => array('name',false),
            'rate'  => array('rate',false),
            'company'  => array('company',false),
            'created'  => array('created',false)
        );
 
        return $sortable_columns;
    }

	/**
     * Will display a link to show popup for the subscription detail.
     */
    public function column_view( $item ){
        
        if( !empty( intval( strip_tags( $item['id'] ) ) ) ) {
            return '<a class="ldnft_review_view_detail" data-action="ldnft_review_view_detail" data-useremail="'.$item['useremail'].'" data-title="'.$item['title'].'" data-text="'.$item['text'].'" data-job_title="'.$item['job_title'].'"  data-company_url="'.$item['company_url'].'" data-picture="'.$item['picture'].'" data-profile_url="'.$item['profile_url'].'" data-is_verified="'.$item['is_verified'].'" data-is_featured="'.$item['is_featured'].'" data-sharable_img="'.$item['sharable_img'].'" data-name="'.$item['name'].'" data-created="'.$item['created'].'" data-company="'.$item['company'].'" data-user_id="'.$item['user_id'].'" data-plugin_id="'.$item['plugin_id'].'" data-id="'.$item['id'].'" class="ldnft_review_view_detail" href="javascript:;">'.__('Get More', LDNFT_TEXT_DOMAIN).'</a>';
        } else {
            return LDNFT_Admin::get_bar_preloader();
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
        
        /**
         * First, lets decide how many records per page to show
         */
        $per_page = get_user_option(
            'reviews_per_page',
            get_current_user_id()
        );
        
        if( empty( $per_page ) ) {
            $per_page = 10;
        }

		if( !wp_doing_ajax() ) {
            
			$this->items = [
                [
                    'id'               		=> LDNFT_Admin::get_bar_preloader(), 
                    'user_id'            	=> LDNFT_Admin::get_bar_preloader(), 
                    'useremail'            	=> LDNFT_Admin::get_bar_preloader(), 
                    'title'             	=> LDNFT_Admin::get_bar_preloader(), 
                    'text'      			=> LDNFT_Admin::get_bar_preloader(), 
                    'job_title'             => LDNFT_Admin::get_bar_preloader(),
					'company_url'           => LDNFT_Admin::get_bar_preloader(),
					'picture'              	=> LDNFT_Admin::get_bar_preloader(),
					'profile_url'           => LDNFT_Admin::get_bar_preloader(),
					'is_verified'            => LDNFT_Admin::get_bar_preloader(),
					'is_featured'           => LDNFT_Admin::get_bar_preloader(),
					'sharable_img'          => LDNFT_Admin::get_bar_preloader(),
					'name'              	=> LDNFT_Admin::get_bar_preloader(),
					'company'              	=> LDNFT_Admin::get_bar_preloader(),
					'created'              	=> LDNFT_Admin::get_bar_preloader(),
					'rate'              	=> LDNFT_Admin::get_bar_preloader(),
					'view'              	=> LDNFT_Admin::get_bar_preloader(),
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
        $screen = WP_Screen::get( 'freemius-toolkit_page_freemius-reviews' );
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
        $table_name = $wpdb->prefix.'ldnft_reviews r left outer join '.$wpdb->prefix.'ldnft_customers c on (r.user_id=c.id)'; 
        $where = " where 1 = 1";
        if( ! empty( $this->selected_plugin_id )) {
            $where .= " and r.plugin_id='".$this->selected_plugin_id."'";
        }

        if( $this->selected_verified != '' ) {
            $where   .= ( ! empty( $where ) ? ' and ' : '' ). " r.is_verified='".$this->selected_verified."'";
        }

        if( $this->selected_featured != '') {
            $where   .= ( ! empty( $where ) ? ' and ' : '' ). " r.is_featured='".$this->selected_featured."'";
        }

        if( ! empty( $this->selected_search ) ) {
            $where   .= ( ! empty( $where ) ? ' and ' : '' ). " ( r.id like '%".$this->selected_search."%' or r.user_id like '%".$this->selected_search."%' or lower(r.title) like '%".strtolower($this->selected_search)."%' or lower(r.name) like '%".strtolower($this->selected_search)."%' or lower(c.email) like '%".strtolower($this->selected_search)."%' or lower(r.text) like '%".strtolower($this->selected_search)."%'  )";
        }

        $total_items = $wpdb->get_var("SELECT COUNT(r.id) FROM $table_name".$where);

        // prepare query params, as usual current page, order by and order direction
        $offset      = isset($paged) ? intval(($paged-1) * $per_page) : 0;
        $orderby    = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
        $order      = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? sanitize_text_field( $_REQUEST['order'] ) : 'desc';
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $result = $wpdb->get_results($wpdb->prepare("SELECT r.*, c.email as useremail FROM $table_name $where ORDER BY r.$orderby $order LIMIT %d OFFSET %d", $per_page, $offset), ARRAY_A);
        
        $data = [];
        $count = 0;
        if( isset($result) && is_array($result) && count($result) > 0 ) {
            foreach( $result as $review ) {
                $user_id = 0;
                foreach( $review as $key => $value ) {
                    
                    if( empty( $value ) ) {
                        $value = '-';
                    }    
                    $data[$count][$key] = $value;
                } 
                 
                $count++;   
            }
        }
        
        $this->items = $data;

       /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
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
                "<a data-ldfmt_plugins_filter='%d' data-featured='%d' data-verified='%d' data-search='%s' data-per_page='%d' class='first-page button ldnft_check_load_next' data-paged='1' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_featured,
                $this->selected_verified,
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
                "<a data-ldfmt_plugins_filter='%d' data-featured='%d' data-verified='%d' data-search='%s' data-per_page='%d' class='prev-page button ldnft_check_load_next' data-paged='%d' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_featured,
                $this->selected_verified,
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
                "<a data-ldfmt_plugins_filter='%d' data-featured='%d' data-verified='%d' data-search='%s' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='next-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_featured,
                $this->selected_verified,
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
                "<a data-ldfmt_plugins_filter='%d' data-featured='%d' data-verified='%d' data-search='%s' data-per_page='%d' data-paged='%d' data-current_recs='%d' class='last-page button ldnft_check_load_next' href='javascript:;'>" .
                    "<span class='screen-reader-text'>%s</span>" .
                    "<span aria-hidden='true'>%s</span>" .
                '</a>',
                $this->selected_plugin_id,
                $this->selected_featured,
                $this->selected_verified,
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

    public function extra_tablenav( $which ) {
        
        if ( $which == "top" ){
            
        }

    }
}