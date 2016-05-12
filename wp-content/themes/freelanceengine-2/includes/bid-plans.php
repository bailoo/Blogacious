<?php
/**
 * this file contain all function related to places
 */
add_action('init', 'fre_init_bid_plan');
function fre_init_bid_plan() {
    
    register_post_type('bid_plan', array(
        'labels' => array(
            'name' => __('Bid plan', ET_DOMAIN) ,
            'singular_name' => __('Bid plan', ET_DOMAIN) ,
            'add_new' => __('Add New', ET_DOMAIN) ,
            'add_new_item' => __('Add New Bid plan', ET_DOMAIN) ,
            'edit_item' => __('Edit Bid plan', ET_DOMAIN) ,
            'new_item' => __('New Bid plan', ET_DOMAIN) ,
            'all_items' => __('All Bid plans', ET_DOMAIN) ,
            'view_item' => __('View Bid plan', ET_DOMAIN) ,
            'search_items' => __('Search Bid plans', ET_DOMAIN) ,
            'not_found' => __('No Bid plan found', ET_DOMAIN) ,
            'not_found_in_trash' => __('No Bid plans found in Trash', ET_DOMAIN) ,
            'parent_item_colon' => '',
            'menu_name' => __('Bid plans', ET_DOMAIN)
        ) ,
        'public' => false,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => true,
        
        'capability_type' => 'post',
        // 'capabilities' => array(
        //     'manage_options'
        // ) ,
        'has_archive' => 'packs',
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array(
            'title',
            'editor',
            'author',
            'custom-fields'
        )
    ));    
    $package = new AE_Pack('bid_plan',array(
            'sku',
            'et_price',
            'et_number_posts',
            'order',
            'et_featured'
        ),
        array(
            'backend_text' => array(
                'text' => __('%s  for %d credits', ET_DOMAIN) ,
                'data' => array(
                    'et_price',
                    'et_number_posts'
                )
            )
        ));
    global $ae_post_factory;
    $ae_post_factory->set('bid_plan', $package);
}



if(!function_exists('fre_order_bid_plan_by_menu_order')) {
    /**
     * filter posts order by to order bid_plan post by menu order
     * @param string $orderby The query orderby string
     * @param object $query Current wp_query object
     * @since 1.4
     * @author Dakachi
     */
    function fre_order_bid_plan_by_menu_order($orderby, $query){
        global $wpdb;
        if ($query->query_vars['post_type'] != 'bid_plan') return $orderby;
        $orderby = "{$wpdb->posts}.menu_order ASC";
        return $orderby;
    }
    add_filter('posts_orderby', 'fre_order_bid_plan_by_menu_order', 10, 2);
}
/**
 * process payment for bid
 *
 * @since version 1.5.4
 * @author Tambh
 */
add_action( 'ae_select_process_payment', 'fre_setup_bid_payment', 10, 2 );
function fre_setup_bid_payment( $payment_return, $data){
    global $user_ID, $ae_post_factory;
    extract($data);
    if (!$payment_return['ACK']) return false;
    $order_pay = $data['order']->get_order_data();     
    if( isset($payment_return['payment_status']) ){
        if( $payment_return['payment_status'] == 'Completed' && $payment_return['payment'] != 'cash' ){
            $packs = AE_Package::get_instance();
            $products = $order_pay['products'];      
            $sku = $order_pay['payment_package'];
            $pack = $packs->get_pack($sku, $products[$sku]['TYPE']);
            if( isset( $pack->et_number_posts ) && (int)$pack->et_number_posts > 0 ){
                update_credit_number( $user_ID, (int)$pack->et_number_posts );
                $payment_return['bid_msg'] = sprintf( __("You've successfully purchased %d credits.", ET_DOMAIN), $pack->et_number_posts);
                return $payment_return;
            }

        }   
    }
    return $payment_return;
}
/**
 * update credit number for user
 *
 * @param int $user_ID
 * @param int $credit_number credit number of package
 * @return bool true if update user meta success
 * @since version 1.5.4
 * @author Tambh
 */
function update_credit_number( $user_ID, $credit_number ){
    // if( ae_get_option( 'pay_to_bid', false ) ){
        $user_credit = get_user_credit_number( $user_ID );        
        $user_credit += $credit_number;
        if($user_credit < 0 ){
            $user_credit = 0;
        }
        $result = update_user_meta($user_ID, 'credit_number', $user_credit);
    // }
}
/**
 * Get user credit number
 * @param int $user_ID
 * @return int user's credit number
 * @since version 1.5.4
 */
function get_user_credit_number( $user_ID ){
    return (int)get_user_meta( $user_ID, 'credit_number', true );
}
/**
 * add package post type to list
 *
 * @since version 1.5.4
 * @author Tambh
 */
add_filter( 'ae_pack_post_types', 'fre_pack_post_type' );
function fre_pack_post_type( $pack_post_type ){
    return wp_parse_args( array( 'pack', 'bid_plan' ), $pack_post_type );
}
/**
 * check user can or can't bid a project
 * @param int $user_ID the user's ID
 * @return bool true if user can bid / false if user can't bid
 * @since version 1.5.4
 * @author Tambh
 *
 */
function can_user_bid( $user_ID ){
    global $user_ID;    
    if( ae_get_option( 'pay_to_bid', false ) ){
        $free_bid = (int)get_user_meta($user_ID, 'ae_user_free_bid', true);        
        $op_free_bid = ae_get_option( 'ae_free_bid_number', 0 );        
        if( $op_free_bid > 0 ){          
            if( ($op_free_bid - $free_bid) > 0 ){
                return true;
            }
        }        
        $user_credits = get_user_credit_number( $user_ID );
        $bid_credit = get_credit_to_pay();
        $user_credits = $user_credits - $bid_credit;
        if( $user_credits >= 0  ){
            return true;
        }
        return false;
    }
    return true;
}
function get_credit_to_pay(){
    $bid_credit = (int)ae_get_option( 'ae_credit_number', 0 );
    $bid_credit = apply_filters( 'fre_credit_to_pay', $bid_credit );
    return $bid_credit;
}
/**
 * This function will auto update user's credits after admin approved cash payment
 *
 * @since 1.5.4
 *
 * @author Tambh
 */
add_action( 'save_post', 'cash_upproved', 10, 2 );
function cash_upproved( $post_ID, $post )
{        
    if( current_user_can('manage_options') ){
       if( $post->post_type == 'order' && $post->post_status == 'publish' ){
            $order = new AE_Order($post_ID);        
            $order_pay = $order->get_order_data();           
            if( isset($order_pay['payment'] ) && $order_pay['payment'] == 'cash' ){            
                $products = $order_pay['products'];
                $sku = $order_pay['payment_package'];
                $packs = AE_Package::get_instance();
                $pack = $packs->get_pack($sku, 'bid_plan');
                if( isset( $pack->et_number_posts ) && (int)$pack->et_number_posts > 0 ){                    
                    update_credit_number( $post->post_author, (int)$pack->et_number_posts );            
                }       
            }
       }
   }
}
/**
 * Add enable pay to bid to ae_global 
 *
 *
 * @since version 1.5.4
 * @author Tambh
 */
add_filter('ae_globals', 'fre_new_ae_global');
function fre_new_ae_global($vars){
    global $user_ID;
    $vars['pay_to_bid'] = ae_get_option('pay_to_bid', false);
    $bid_dis = (int)ae_get_option( 'ae_credit_number', 0 );
    $credits = get_user_credit_number($user_ID);
    $credits_left = $credits - $bid_dis;
    if( $credits_left < 0 ){
        $credits_left = 0;
    }
    $vars['bid_success_msg'] = sprintf( __('You have %d credits left', ET_DOMAIN), $credits_left );
    $vars['yes'] = __('Yes', ET_DOMAIN);
    $vars['no'] = __('No', ET_DOMAIN);
    $vars['search_result_msg'] = __(' RESULT OF  ', ET_DOMAIN);
    $vars['search_result_msgs'] = __(' RESULTS OF ', ET_DOMAIN);
    return $vars;
}
/**
 * Update user's free bids number 
 * @param integer $plus
 * @return void
 * @since void
 * @package void
 * @category void
 * @author Tambh
 */
function fre_update_user_free_bid_number($plus = 1){
    global $user_ID;
    $free_bid = (int)get_user_meta($user_ID, 'ae_user_free_bid', true);           
    $free_bid += $plus;    
    update_user_meta($user_ID, 'ae_user_free_bid', $free_bid);
}