<?php

if( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class new LDNFT_Webhooks
 */
class LDNFT_Webhooks {

    /**
     * Constructor function
     */
    public function __construct() {

        $this->register_routes();

    }

    public function register_routes() {
        
        
          add_action( 'rest_api_init', function () {
            register_rest_route( 'lfnft/v1', '/webhooks', array(
                'methods' => 'POST',
                'callback' => [ $this, 'webhooks_callback' ],
                ) );
            } ); 
    }

    function webhooks_callback( WP_REST_Request $request ) {
        
        global $wpdb;

        $type = $request->get_param( 'type' );
        switch( $type ) {
            case "user.created":
                $user_id = $request->get_param( 'user_id' );
                $plugin_id = $request->get_param( 'plugin_id' );
                $objects = $request->get_param( 'objects' );
                if( is_array($objects ) && array_key_exists( 'user', $objects ) ) {
                    $user = $objects['user'];
                    $table_name = $wpdb->prefix.'ldnft_customers';
                    if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) ) {

                        $wpdb->query( "CREATE TABLE $table_name (
                            `id` int(11) NOT NULL,
                            `email` varchar(255) DEFAULT NULL,
                            `first` varchar(255) DEFAULT NULL,
                            `last` varchar(255) DEFAULT NULL,
                            `is_verified` tinyint(1) DEFAULT NULL,
                            `is_marketing_allowed` tinyint(1) DEFAULT NULL,
                            `created` datetime DEFAULT NULL, 
                            PRIMARY KEY (`id`)
                        )" );     
                    }

                    $meta_table_name = $wpdb->prefix.'ldnft_customer_meta';
                    if( is_null( $wpdb->get_var( "SHOW TABLES LIKE '$meta_table_name'" ) ) ) {

                        $wpdb->query( "CREATE TABLE $meta_table_name (
                            `plugin_id` int(11) NOT NULL,
                            `customer_id` int(11) NOT NULL,
                            `status` varchar(20) DEFAULT NULL,
                            `created` datetime DEFAULT NULL, 
                            PRIMARY KEY ( `plugin_id`, `customer_id` )
                        )" );     
                    }

                    $res = $wpdb->get_results( $wpdb->prepare("select * from ".$table_name." where id=%d", $user_id ));
                
                    if( count( $res ) == 0 ) {
                        
                        $wpdb->insert(
                            $table_name,
                            array(
                                'id'                    => $user_id,
                                'email'                 => $user['email'],
                                'first'                 => $user['first'],
                                'last'                  => $user['last'],
                                'is_verified'           => $user['is_verified'],
                                'created'               => $user['updated'],
                                'is_marketing_allowed'  => $user['is_marketing_allowed'],
                            )
                        );
                        
                        $wpdb->insert(
                            $meta_table_name,
                            array(
                                'plugin_id'               => $plugin_id,
                                'customer_id'             => $user_id,
                                'created'                 => $user['created'],
                                'status'                  => ''
                            )
                        );
                        error_log('inserted new customer with user_id:'.$user_id);
                    } else {
                        
                        $wpdb->update( $table_name, 
                            array(
                                'email'                 => $user['email'],
                                'first'                 => $user['first'],
                                'last'                  => $user['last'],
                                'is_verified'           => $user['is_verified'],
                                'created'               => $user['updated'],
                                'is_marketing_allowed'  => $user['is_marketing_allowed'],
                            ), array( 'id' => $user->id ) );
                        
                        $res = $wpdb->get_results( $wpdb->prepare("select * from ".$meta_table_name." where customer_id=%d and plugin_id=%d", $user->id, $plugin_id ));

                        if( count( $res ) == 0 ) {

                            $wpdb->insert(
                                $meta_table_name,
                                array(
                                    'plugin_id'               => $plugin_id,
                                    'customer_id'             => $user_id,
                                    'created'                 => $user['created'],
                                    'status'                  => ''
                                )
                            );
                        }  
                        error_log('updated the customer with user_id:'.$user_id);
                    }
                }
                $user = $request->get_param( 'user' );
                error_log('user_id:'.$user_id);
                error_log(print_r( $objects , true));
                error_log('plugin_id:'.$plugin_id);
                error_log(print_r( $user , true));
                break;
            case "user.email.changed":

                break;
            case "user.email.verified":

                break;
            case "user.name.changed":

                break;
            case "review.created":

                break;
            case "review.deleted":

                break;
            case "review.updated":

                break;
            case "subscription.cancelled":

                break;
                    
            case "subscription.created":

                break;
                        
            case "plugin.created":

                break;
            case "plugin.updated":

                break;
            case "cart.completed": //sale

                break;           
            default:
                error_log('type:'.$type);
                error_log(print_r( $request , true));    
                break;
        }
        error_log('type:'.$type);
        error_log(print_r( $request , true)); 
        exit;
    }
}

new LDNFT_Webhooks();

    function my_awesome_func( WP_REST_Request $request ) {
        // You can access parameters via direct array access on the object:
        $param = $request['some_param'];
      
        // Or via the helper method:
        $param = $request->get_param( 'some_param' );
      
        // You can get the combined, merged set of parameters:
        $parameters = $request->get_params();
        print_r($parameters);exit;
        // The individual sets of parameters are also available, if needed:
        $parameters = $request->get_url_params();
        $parameters = $request->get_query_params();
        $parameters = $request->get_body_params();
        $parameters = $request->get_json_params();
        $parameters = $request->get_default_params();
      
        // Uploads aren't merged in, but can be accessed separately:
        $parameters = $request->get_file_params();
    }
