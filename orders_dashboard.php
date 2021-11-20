<?php

/*
    TODO:
    - Add Order information
    - Add Order refund

    - Issue #33: Remove hidden inputs from DOM
*/

/**
 * Triggers init script for
 * showing orders.
 */
if (isset($_GET['init_show_orders'])) {
    add_action('init', 'init_show_orders');
}

function init_show_orders() {
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    header('Location: https://pynkiwi.com/?' . http_build_query(array(
        'page_id' => 3294,
        'show_orders' => 1,
        'user_id' => $current_user_id
    )));
}

if (isset($_GET['show_orders'])) {
    $user_id = $_GET['user_id'];
    $orders = new Orders($user_id);
    // Remove comment
    $orders->show_orders();

    

    // debug
    // imp
    $orders->debug_get_orders();
    $orders->delete_orders();
}

/**
 * Action type for available orders,
 * (1) cancel the selected order,
 * (2) pay the selected order.
 */
if (isset($_GET['action_type'])) {
    $action_type = $_GET['action_type'];
    $order_id = $_GET['order_id'];
    
    if ($action_type === "1") { // TODO: Refund
        $flag = cancel_order($order_id);
        if ($flag) {
            add_action(
                'init',
                function() use ($order_id) {
                    $current_user = wp_get_current_user();
                    $user_id = $current_user->ID;
                    $orders = new Orders($user_id);
                    $orders->delete_order_meta($order_id);
                }
            );
        }
    } else if ($action_type === "2") {
        $info = get_updated_order($order_id);
        $total_amount = explode(' ', $info[0]);
        $data = new stdClass();
        $payment = new stdClass();
        $payment->type = "balance";
        $payment->currency = $total_amount[1];
        $payment->amount = $total_amount[0];
        $data->payment = $payment;
        $data->order_id = $order_id;
        $resp_data = create_payment($order_id, $data);
        if ($resp_data !== null) {
            add_action(
                'init',
                function() use ($order_id, $resp_data) {
                    $current_user = wp_get_current_user();
                    $user_id = $current_user->ID;
                    $orders = new Orders($user_id);
                    $orders->update_order_payment_meta($order_id, $resp_data->created_at, $resp_data->id);
                    $orders->update_order_stripe_payment_meta($order_id);
                    $orders->debug_get_orders_meta();
                }
            );
            $stripe_total_amount = $total_amount[0] + ($total_amount[0] * 0.15);
            $stripe_total_amount_str = $stripe_total_amount . ' ' . $total_amount[1];
            header('Location: https://pynkiwi.com/?' . http_build_query(array(
                'page_id' => 3721,
                'order_id' => $order_id,
                'stripe_total_amount' => $stripe_total_amount_str
            )));
        }
    }
}

?>