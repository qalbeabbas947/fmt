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
 * Class LDFMT_Subscribers
 */
class LDFMT_Subscribers extends WP_List_Table {

    public $selected_plugin_id;

    public $api;

    public $plugins;
    
    public $plugins_short_array;
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
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

        
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'subscriber',     //singular name of the listed records
            'plural'    => 'subscribers',    //plural name of the listed records
            'ajax'      => true             //does this table support ajax?
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
    function column_default($item, $column_name){
        return $item[$column_name]; //Show the whole array for troubleshooting purposes
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
    function column_title($item){
        
        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&movie=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
        );
        
        //Return the title contents
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
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
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
    function get_columns(){
        $columns = array(
            'id'                    => 'ID',
            //'plugin_id'             => 'Plugin ID',
            'plugin_title'          => 'Plugin',
            'user_id'               => 'User ID',
            'username'              => 'Name',
            'useremail'             => 'Email',
            //'install_id'            => 'Install ID',
            'amount_per_cycle'      => 'Cycle',
            'billing_cycle'         => 'Billing Cycle',
            'gross'                 => 'Gross',
            'outstanding_balance'   => 'Balance',
            'failed_payments'       => 'Failed',
            'gateway'               => 'Gateway',
            //'coupon_id'             => 'Coupon ID',
            'trial_ends'            => 'Trial Ends',
            'next_payment'          => 'Next Payment',
            'created'               => 'Created',
            //'updated_at'            => 'Updated',
            'currency'              => 'Currency',
            //'external_id'           => 'External ID',
            //'plan_id'               => 'Plan ID',
            'country_code'          => 'Country',
            //'pricing_id'            => 'Pricing ID',
            'initial_amount'        => 'Initial',
            'renewal_amount'        => 'Renewal',
            //'renewals_discount'     => 'Discount',
            //'renewals_discount_type'=> 'Discount Type',
            //'license_id'            => 'License',
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
    function get_sortable_columns() {
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
            'created'  => array('created',false)
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
    function get_bulk_actions() {
        // $actions = array(
        //     'delete'    => 'Delete'
        // );
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
    function process_bulk_action() {
        
        //Detect when a bulk action is being triggered...
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
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 10;
        
        
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
       // $this->process_bulk_action();
        
        
        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example 
         * package slightly different than one you might build on your own. In 
         * this example, we'll be using array manipulation to sort and paginate 
         * our data. In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
         // will be used in pagination settings
         $table_name = $wpdb->prefix.'ldnft_subscription';
         $where = " where plugin_id='".$this->selected_plugin_id."'";
         $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name".$where);

         // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged'] - 1) * $per_page) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';
 
         // [REQUIRED] define $items array
         // notice that last argument is ARRAY_A, so we will retrieve array
         $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
 
         // [REQUIRED] configure pagination
         $this->set_pagination_args(array(
             'total_items' => $total_items, // total items defined above
             'per_page' => $per_page, // per page constant defined at top of method
             'total_pages' => ceil($total_items / $per_page) // calculate pages count
         ));
    }

    function extra_tablenav( $which ) {
        global $wpdb;
        
        if ( $which == "top" ){
            ?>
            <div class="alignleft actions bulkactions">
                <select onchange="document.location='admin.php?page=ldninjas-freemius-toolkit-subscriptions&ldfmt_plugins_filter='+this.value" name="ldfmt-plugins-filter" class="ldfmt-plugins-filter">
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
                <button type="button" id="ldnft-update-subscriptions" class="ldnft-update-subscriptions button action"><?php _e( 'Sync Subscription with Freemius', LDNFT_TEXT_DOMAIN ); ?></button>
                <img style="display:none" width="30px" class="ldfmt-data-loader" src="<?php echo LDNFT_ASSETS_URL .'images/spinner-2x.gif'; ?>" />
                <span id="ldnft-subscription-import-message"></span>
            </div>
            <?php
        }
        if ( $which == "bottom" ){
            //The code that goes after the table is there
    
        }
    }
}