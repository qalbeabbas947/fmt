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
            register_rest_route( 'ldnft/v1', '/webhooks', array(
                'methods' => 'POST',
                'callback' => [ $this, 'webhooks_callback' ],
                ) );
            } ); 
    }

    /**
	 * process customers data.
	 */
    function customer_webhook_callback( $user_id, $plugin_id, $user ) {
        
        global $wpdb;
        
        $table_name = $wpdb->prefix.'ldnft_customers';
        $meta_table_name = $wpdb->prefix.'ldnft_customer_meta';
        
        $res = $wpdb->get_results( $wpdb->prepare("select id from ".$table_name." where id=%d", $user_id ));
    
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
                ), array( 'id' => $user_id ) );
            
            $res = $wpdb->get_results( $wpdb->prepare("select plugin_id from ".$meta_table_name." where customer_id=%d and plugin_id=%d", $user_id, $plugin_id ));

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

    /**
	 * process sales data.
	 */
    function sales_webhook_callback( $id, $user_id, $plugin_id, $created, $user, $payment ) {
        
        global $wpdb;
        
        $table_name = $wpdb->prefix.'ldnft_transactions';
        
        $res = $wpdb->get_results($wpdb->prepare("select id from ".$table_name." where id=%d", $payment['id'] ));
        if( count( $res ) == 0 ) {

            $wpdb->insert(
                $table_name,
                array(
                    'id'                        => $payment['id'],
                    'ip'                        => $payment['ip'],
                    'pricing_id'                => $payment['pricing_id'],
                    'zip_postal_code'           => $payment['zip_postal_code'],
                    'source'                    => $payment['source'],
                    'environment'               => $payment['environment'],
                    'currency'                  => $payment['currency'],
                    'plugin_id'                 => $plugin_id,
                    'user_id'                   => $user_id,
                    'country_code'              => $payment['country_code'],
                    'subscription_id'           => $payment['subscription_id'],
                    'plan_id'                   => $payment['plan_id'],
                    'gross'                     => $payment['gross'],
                    'bound_payment_id'          => $payment['bound_payment_id'],
                    'external_id'               => $payment['external_id'],
                    'gateway'                   => $payment['gateway'],
                    'gateway_fee'               => $payment['gateway_fee'],
                    'vat'                       => $payment['vat'],
                    'vat_id'                    => $payment['vat_id'],
                    'type'                      => $payment['type'],
                    'is_renewal'                => $payment['is_renewal'],
                    'coupon_id'                 => $payment['coupon_id'],
                    'install_id'                => $payment['install_id'],
                    'license_id'                => $payment['license_id'],
                    'created'                   => $payment['created'],
                    'updated'                   => $payment['updated']
                )
            );
            
            error_log('inserted new sale with id:'.$payment['id']);
        } else {

            $wpdb->update($table_name, 
                array(
                    'ip'                        => $payment['ip'],
                    'pricing_id'                => $payment['pricing_id'],
                    'zip_postal_code'           => $payment['zip_postal_code'],
                    'source'                    => $payment['source'],
                    'environment'               => $payment['environment'],
                    'currency'                  => $payment['currency'],
                    'plugin_id'                 => $plugin_id,
                    'user_id'                   => $user_id,
                    'country_code'              => $payment['country_code'],
                    'subscription_id'           => $payment['subscription_id'],
                    'plan_id'                   => $payment['plan_id'],
                    'gross'                     => $payment['gross'],
                    'bound_payment_id'          => $payment['bound_payment_id'],
                    'external_id'               => $payment['external_id'],
                    'gateway'                   => $payment['gateway'],
                    'gateway_fee'               => $payment['gateway_fee'],
                    'vat'                       => $payment['vat'],
                    'vat_id'                    => $payment['vat_id'],
                    'type'                      => $payment['type'],
                    'is_renewal'                => $payment['is_renewal'],
                    'coupon_id'                 => $payment['coupon_id'],
                    'install_id'                => $payment['install_id'],
                    'license_id'                => $payment['license_id'],
                    'created'                   => $payment['created'],
                    'updated'                   => $payment['updated']
                ), array('id'=>$payment['id']));

            error_log('updated new sale with id:'.$payment['id']);
        }

        $meta_table_name = $wpdb->prefix.'ldnft_customer_meta';
        $wpdb->update( $meta_table_name, 
                array(
                    'status'                => $payment['type']
                ), array( 'plugin_id' => $plugin_id, 'customer_id' => $user_id  ) );

    }
    
    /**
	 * process subscription data from the freemius.
	 */
    function subscription_created_webhook_callback( $id, $license_id, $user_id, $plugin_id, $subscription ) {

        global $wpdb;
        
        $table_name = $wpdb->prefix.'ldnft_subscription';
        
        $res = $wpdb->get_results($wpdb->prepare("select id from ".$table_name." where id=%d", $id ));
        
        $status = '';
        if( !empty( $subscription['canceled_at'] ) ) {
            $status = 'cancelled';
        } else if( !empty( $subscription['next_payment'] ) ) {
            if( strtotime( $subscription['next_payment'] ) > time() ) {
                $status = 'active';
            } else {
                $status = 'expired';
            }
        }
        if( count( $res ) == 0 ) {

            $wpdb->insert(
                $table_name,
                array(
                    'id'                        => $id,
                    'plugin_id'                 => $plugin_id,
                    'user_id'                   => $user_id,
                    'tax_rate'                  => $subscription['tax_rate'],
                    'ip'                        => $subscription['ip'],
                    'zip_postal_code'           => $subscription['zip_postal_code'],
                    'vat_id'                    => $subscription['vat_id'],
                    'source'                    => $subscription['source'],
                    'user_card_id'              => $subscription['user_card_id'],
                    'environment'               => $subscription['environment'],
                    'install_id'                => $subscription['install_id'],
                    'amount_per_cycle'          => $subscription['amount_per_cycle'],
                    'billing_cycle'             => $subscription['billing_cycle'],
                    'gross'                     => $subscription['total_gross'],
                    'outstanding_balance'       => $subscription['outstanding_balance'],
                    'failed_payments'           => $subscription['failed_payments'],
                    'gateway'                   => $subscription['gateway'],
                    'coupon_id'                 => $subscription['coupon_id'],
                    'trial_ends'                => $subscription['trial_ends'],
                    'next_payment'              => $subscription['next_payment'],
                    'canceled_at'               => $subscription['canceled_at'],
                    'created'                   => $subscription['created'],
                    'updated_at'                => $subscription['updated'],
                    'currency'                  => $subscription['currency'],
                    'pricing_id'                => $subscription['pricing_id'],
                    'country_code'              => $subscription['country_code'],
                    'plan_id'                   => $subscription['plan_id'],
                    'external_id'               => $subscription['external_id'],
                    'initial_amount'            => $subscription['initial_amount'],
                    'renewal_amount'            => $subscription['renewal_amount'],
                    'renewals_discount'         => $subscription['renewals_discount'],
                    'renewals_discount_type'    => $subscription['renewals_discount_type'],
                    'license_id'                => $subscription['license_id'],
                    'status'                    => $status
                )
            );

            error_log('added new subscription with id:'.$id);
        } else {

            $wpdb->update($table_name, 
                array(
                    'plugin_id'                 => $plugin_id,
                    'user_id'                   => $user_id,
                    'tax_rate'                  => $subscription['tax_rate'],
                    'ip'                        => $subscription['ip'],
                    'zip_postal_code'           => $subscription['zip_postal_code'],
                    'vat_id'                    => $subscription['vat_id'],
                    'source'                    => $subscription['source'],
                    'user_card_id'              => $subscription['user_card_id'],
                    'environment'               => $subscription['environment'],
                    'install_id'                => $subscription['install_id'],
                    'amount_per_cycle'          => $subscription['amount_per_cycle'],
                    'billing_cycle'             => $subscription['billing_cycle'],
                    'gross'                     => $subscription['total_gross'],
                    'outstanding_balance'       => $subscription['outstanding_balance'],
                    'failed_payments'           => $subscription['failed_payments'],
                    'gateway'                   => $subscription['gateway'],
                    'coupon_id'                 => $subscription['coupon_id'],
                    'trial_ends'                => $subscription['trial_ends'],
                    'next_payment'              => $subscription['next_payment'],
                    'canceled_at'               => $subscription['canceled_at'],
                    'created'                   => $subscription['created'],
                    'updated_at'                => $subscription['updated'],
                    'currency'                  => $subscription['currency'],
                    'pricing_id'                => $subscription['pricing_id'],
                    'country_code'              => $subscription['country_code'],
                    'plan_id'                   => $subscription['plan_id'],
                    'external_id'               => $subscription['external_id'],
                    'initial_amount'            => $subscription['initial_amount'],
                    'renewal_amount'            => $subscription['renewal_amount'],
                    'renewals_discount'         => $subscription['renewals_discount'],
                    'renewals_discount_type'    => $subscription['renewals_discount_type'],
                    'license_id'                => $subscription['license_id'],
                    'status'                    => $status
                ), array('id'=>$id));
                
            error_log('updated the subscription with id:'.$id);
        }
    }

    /**
     * Process the reviews webhooks
     */
    function review_webhook_callback( $review_id, $plugin_id, $review ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix.'ldnft_reviews';
        
        $res = $wpdb->get_results($wpdb->prepare("select id from ".$table_name." where id=%d", $review_id ));
                
        if( count( $res ) == 0 ) {

            $re = $wpdb->insert(
                $table_name,
                array(
                    'id'                        => $review_id,
                    'plugin_id'                 => $review->plugin_id,
                    'user_id'                   => $review->user_id,
                    'external_id'               => $review->external_id,
                    'rate'                      => $review->rate,
                    'title'                     => $review->title,
                    'text'                      => $review->text,
                    'name'                      => $review->name,
                    'job_title'                 => $review->job_title,
                    'company'                   => $review->company,
                    'company_url'               => $review->company_url,
                    'picture'                   => $review->picture,
                    'profile_url'               => $review->profile_url,
                    'license_id'                => $review->license_id,
                    'is_verified'               => $review->is_verified,
                    'is_featured'               => $review->is_featured,
                    'environment'               => $review->environment,
                    //'sharable_img'              => $review->sharable_img,
                    'created'                   => $review->created,
                    'updated'                   => $review->updated
                )
            );
            
            error_log('insert the review with id:'.$review_id);
        } else {

            $wpdb->update($table_name, 
                array(
                    'plugin_id'                 => $review->plugin_id,
                    'user_id'                   => $review->user_id,
                    'external_id'               => $review->external_id,
                    'rate'                      => $review->rate,
                    'title'                     => $review->title,
                    'text'                      => $review->text,
                    'name'                      => $review->name,
                    'job_title'                 => $review->job_title,
                    'company'                   => $review->company,
                    'company_url'               => $review->company_url,
                    'picture'                   => $review->picture,
                    'profile_url'               => $review->profile_url,
                    'license_id'                => $review->license_id,
                    'is_verified'               => $review->is_verified,
                    'is_featured'               => $review->is_featured,
                    'environment'               => $review->environment,
                    //'sharable_img'            => $review->sharable_img,
                    'created'                   => $review->created,
                    'updated'                   => $review->updated
                ), array( 'id' => $review_id ));
            
            error_log('updated the review with id:'.$review_id);    
        }
    }

    /**
     * Process the plans webhooks
     */
    function plan_webhook_callback( $plan_id, $plugin_id, $plan ) {
        
        global $wpdb;
        
        $table_name = $wpdb->prefix.'ldnft_plans';

        $res = $wpdb->get_results($wpdb->prepare("select id from ".$table_name." where id=%d", $plan_id ));
        if( count( $res ) == 0 ) {

            $wpdb->insert(
                $table_name,
                array(
                    'id'                        => $plan->id,
                    'title'                     => $plan->title,
                    'name'                      => $plan->name,
                    'description'               => $plan->description,
                    'plugin_id'                 => $plan->plugin_id,
                    'is_free_localhost'         => $plan->is_free_localhost,
                    'is_block_features'         => $plan->is_block_features,
                    'is_block_features_monthly' => $plan->is_block_features_monthly,
                    'license_type'              => $plan->license_type,
                    'is_https_support'          => $plan->is_https_support,
                    'trial_period'              => $plan->trial_period,
                    'is_require_subscription'   => $plan->is_require_subscription,
                    'support_kb'                => $plan->support_kb,
                    'support_forum'             => $plan->support_forum,
                    'support_email'             => $plan->support_email,
                    'support_phone'             => $plan->support_phone,
                    'support_skype'             => $plan->support_skype,
                    'is_success_manager'        => $plan->is_success_manager,
                    'is_featured'               => $plan->is_featured,
                    'is_hidden'                 => $plan->is_hidden,
                    'created'                   => $plan->created
                )
            );
            
            error_log('insert the subscription with id:'.$plan->id);
        } else {

            $wpdb->update($table_name, 
                array(
                    'title'                     => $plan->title,
                    'name'                      => $plan->name,
                    'description'               => $plan->description,
                    'plugin_id'                 => $plan->plugin_id,
                    'is_free_localhost'         => $plan->is_free_localhost,
                    'is_block_features'         => $plan->is_block_features,
                    'is_block_features_monthly' => $plan->is_block_features_monthly,
                    'license_type'              => $plan->license_type,
                    'is_https_support'          => $plan->is_https_support,
                    'trial_period'              => $plan->trial_period,
                    'is_require_subscription'   => $plan->is_require_subscription,
                    'support_kb'                => $plan->support_kb,
                    'support_forum'             => $plan->support_forum,
                    'support_email'             => $plan->support_email,
                    'support_phone'             => $plan->support_phone,
                    'support_skype'             => $plan->support_skype,
                    'is_success_manager'        => $plan->is_success_manager,
                    'is_featured'               => $plan->is_featured,
                    'is_hidden'                 => $plan->is_hidden,
                ), array('id'=>$plan->id));
            
            error_log('updated the subscription with id:'.$plan->id);
        }
    }

    /**
	 * process webhooks from the freemius.
	 */
    function webhooks_callback( WP_REST_Request $request ) {
        
        global $wpdb;
 
        $type = $request->get_param( 'type' );
        switch( $type ) {
            case "user.created":
                $user_id                        = $request->get_param( 'user_id' );
                
                $plugin_id                      = $request->get_param( 'plugin_id' );
                $settings                       = get_option( 'ldnft_webhook_settings_'.$plugin_id );
                $ldnft_disable_webhooks         = isset( $settings['disable_webhooks'] ) && $settings['disable_webhooks']=='yes' ? 'yes': 'no';
                if( $ldnft_disable_webhooks != 'yes' ) {
                    $objects = $request->get_param( 'objects' );
                    if( is_array($objects ) && array_key_exists( 'user', $objects ) ) {
                        
                        $user = $objects['user'];
                        $this->customer_webhook_callback( $user_id, $plugin_id, $user );
                        
                        $ldnft_mailpoet_subscription    = isset( $settings['mailpoet_subscription'] ) && $settings['mailpoet_subscription']=='yes' ? 'yes': 'no';
                        $ldnft_mailpeot_list            = intval( $settings['mailpeot_list'] );
                        if( !empty( $user['email'] ) && $ldnft_mailpoet_subscription == 'yes' && intval( $ldnft_mailpeot_list ) > 0 ) {
                            
                                $status = $user['is_marketing_allowed'] == "1"? 'subscribed' : 'unconfirmed';
                                $subscriber_data = [
                                    'email'         => $user['email'],
                                    'first_name'    => $user['first'],
                                    'last_name'     => $user['last'],
                                ];

                                $options = [
                                    'send_confirmation_email' => false // default: true
                                    //'schedule_welcome_email' => false
                                ];
                
                                $subscriber_id = 0;
                                try {
                                    $subscriber = \MailPoet\API\API::MP('v1')->getSubscriber( $subscriber_data['email'] );
                                    if( !empty( $subscriber['id'] ) ) {
                                        $list_ids = $wpdb->get_results( $wpdb->prepare("select id from `".$wpdb->prefix."mailpoet_subscriber_segment` where subscriber_id=%d and segment_id=%d", $subscriber['id'], $ldnft_mailpeot_list) );
                                        if( count($list_ids) == 0 ) {
                                            $sql = "insert into `".$wpdb->prefix."mailpoet_subscriber_segment`(subscriber_id, segment_id, status, created_at, updated_at) values('".$subscriber['id']."', '".$ldnft_mailpeot_list."', $status, now(), now() )";
                                            $wpdb->query( $sql );
                                        }
                                    }
                
                                    $exists++;
                                } catch(\MailPoet\API\MP\v1\APIException $exception) {
                                    if($exception->getCode() == 4 || $exception->getCode() == '4' ) {
                                        try {
                                            $subscriber = \MailPoet\API\API::MP('v1')->addSubscriber($subscriber_data, [], $options); // Add to default mailing list
                                            
                                            if( !empty( $subscriber['id'] ) ) {
                                                $sql = "update `".$wpdb->prefix."mailpoet_subscribers` set status='".$status."' WHERE id='".$subscriber['id']."'";
                                                $wpdb->query( $sql );
                    
                                                $sql = "insert into `".$wpdb->prefix."mailpoet_subscriber_segment`(subscriber_id, segment_id, status, created_at, updated_at) values('".$subscriber['id']."', '".$ldnft_mailpeot_list."', $status, now(), now() )";
                                                $wpdb->query( $sql );
                                                $count++;
                                            }
                                        } catch(\MailPoet\API\MP\v1\APIException $exception) {
                                            if($exception->getCode() == 6 || $exception->getCode() == '6' ) {
                                                $exists++;
                                                
                                                $errors[$exception->getMessage()] = $exception->getMessage();
                                            }
                                        } catch( Exception $exception ) {
                                            $errors[$exception->getMessage()] = $exception->getMessage();
                                        }
                                    }
                                } catch( Exception $exception ) {
                                    $errors[$exception->getMessage()] = $exception->getMessage();
                                }
                        }
                    }
                }
                break;
            
            case "review.created":
            case "review.updated":
                $plugin_id                  = $request->get_param( 'plugin_id' );
                $settings                   = get_option( 'ldnft_webhook_settings_'.$plugin_id );
                $ldnft_disable_webhooks     = isset( $settings['disable_webhooks'] ) && $settings['disable_webhooks']=='yes' ? 'yes': 'no';
                if( $ldnft_disable_webhooks != 'yes' ) {
                    
                    $review_id               = $request->get_param( 'data' );
                    $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
                    $reviewobj = $api->Api('plugins/'.$plugin_id.'/reviews/'.$review_id.'.json', 'GET', []);
                    
                    if( ! isset( $reviewobj->error )  ) {
                        $this->review_webhook_callback( $review_id, $plugin_id, $reviewobj );
                    }

                    error_log(print_r( $reviewobj , true)); 
                }
                
                break;
            case "payment.created":
                $user_id    = $request->get_param( 'user_id' );
                $plugin_id  = $request->get_param( 'plugin_id' );
                
                $settings = get_option( 'ldnft_webhook_settings_'.$plugin_id );
                $ldnft_disable_webhooks          = isset( $settings['disable_webhooks'] ) && $settings['disable_webhooks']=='yes' ? 'yes': 'no';
                if( $ldnft_disable_webhooks != 'yes' ) {
                    $id         = $request->get_param( 'id' );
                    $created    = $request->get_param( 'created' );
                    $objects = $request->get_param( 'objects' );
                    if( is_array( $objects ) && array_key_exists( 'user', $objects ) ) {
                        $user = $objects['user'];
                        $payment = $objects['payment'];
                        $this->sales_webhook_callback( $id, $user_id, $plugin_id, $created, $user, $payment );
                    }
                }
                break;
            
            case "subscription.created":
            case "subscription.cancelled":   
                $user_id            = $request->get_param( 'user_id' );
                $plugin_id          = $request->get_param( 'plugin_id' );
                $settings = get_option( 'ldnft_webhook_settings_'.$plugin_id );
                $ldnft_disable_webhooks          = isset( $settings['disable_webhooks'] ) && $settings['disable_webhooks']=='yes' ? 'yes': 'no';
                if( $ldnft_disable_webhooks != 'yes' ) {
                    $data               = $request->get_param( 'data' );
                    $subscription_id    = $data['subscription_id'];
                    $license_id         = $data['license_id'];
                    $id = $request->get_param( 'id' );
                    $created = $request->get_param( 'created' );
                    $objects = $request->get_param( 'objects' );
                    if( is_array($objects ) && array_key_exists( 'user', $objects ) && array_key_exists( 'subscription', $objects ) ) {
                        $user = $objects['user'];
                        $subscription = $objects['subscription'];
                        $this->customer_webhook_callback( $user_id, $plugin_id, $user );
                        $this->subscription_created_webhook_callback( $subscription_id, $license_id, $user_id, $plugin_id, $subscription );
                    }
                }
                break;
                
            case "plan.created":
            case "plan.updated":
                $plugin_id          = $request->get_param( 'plugin_id' );
                $settings = get_option( 'ldnft_webhook_settings_'.$plugin_id );
                $ldnft_disable_webhooks          = isset( $settings['disable_webhooks'] ) && $settings['disable_webhooks']=='yes' ? 'yes': 'no';
                if( $ldnft_disable_webhooks != 'yes' ) {
                    
                    $plan_id               = $request->get_param( 'data' );
                    $api = new Freemius_Api_WordPress(FS__API_SCOPE, FS__API_DEV_ID, FS__API_PUBLIC_KEY, FS__API_SECRET_KEY);
                    $planobj = $api->Api('plugins/'.$plugin_id.'/plans/'.$plan_id.'.json', 'GET', []);
                    
                    if( ! isset( $planobj->error )  ) {
                        $this->plan_webhook_callback( $plan_id, $plugin_id, $planobj );
                    }
                }
                break;
            default:
                error_log('type:'.$type);
                error_log(print_r( $request , true));    
                break;
        }
        
        exit;
    }
}

new LDNFT_Webhooks();