<?php
// Copyright 2021 - PYNKIWI

/*
    *1 Legal notice - https://help.duffel.com/hc/en-gb/articles/360021056640
*/

// TODO: Improve if statement line 14
function create_payment($order_id, $data) {
    $order_pay_request = new CURL_REQUEST('POST', 'https://api.duffel.com/air/payments', $data);
    $resp = $order_pay_request->send_duffel_request();
    // debug
    // var_dump($resp);
    if (count($resp->data) && $resp->data->id !== null) {
        console_log('\t- Successfully bought order {'.$order_id.'}');
        return $resp->data;
    }
}

function get_updated_order($order_id) {
    $order_request = new CURL_REQUEST('GET', 'https://api.duffel.com/air/orders/'.$order_id, '');
    $resp = $order_request->send_duffel_request();
    if (count($resp->data)) {
        $total_amount = $resp->data->total_amount . ' ' . $resp->data->total_currency;
        return [$total_amount];
    }
    return [];
}

/**
 * 
 * Function used to cancel a
 * specific order (given an
 * order_id). Two requests are
 * sent to Duffel, a order cancelation
 * request and a order cancelation 
 * confirm request.
 */
function cancel_order($order_id) {
    $order_canceled_flag = 0;
    $data = array(
        'order_id' => $order_id
    );
    $cancel_request = new CURL_REQUEST('POST', "https://api.duffel.com/air/order_cancellations", $data);
    $resp_data = $cancel_request->send_duffel_request();

    if (count($resp_data->data) && $resp_data->data->order_id == $order_id) {
        $order_cancel_id = $resp_data->data->id;
        // Refund via stripe
        $refund_amount = $resp_data->data->refund_amount;
        $refund_currency = $resp_data->data->refund_currency;
        $expires_at = $resp_data->data->expires_at;
        $created_at = $resp_data->data->created_at;
        $order_cancel_ttl = get_offer_ttl($created_at, $expires_at);
        console_log('Order cancel ttl: '.$order_cancel_ttl);

        $confirm_cancel_req = new CURL_REQUEST('CANCEL_ORDER', 'https://api.duffel.com/air/order_cancellations/'.$order_cancel_id.'/actions/confirm', "");
        $order_cancel_resp = $confirm_cancel_req->send_duffel_request();
        $order_cancel_confirmed_at = $order_cancel_resp->data->confirmed_at;
        if ($order_cancel_confirmed_at !== NULL) {
            console_log('\t- Order {'.$order_id.'} canceled successfully');
            $order_canceled_flag++;
        }
        // debug
        // var_dump($order_cancel_resp);
    }
    // debug
    // var_dump($resp_data);
    return $order_canceled_flag;
}

/** ###### Orders ######
 * 
 * Orders class, used to add,
 * update, get and remove all
 * or a specific order from 
 * the user meta data saved
 * in WP.
 * 
 * Schema:
 *  [wp_key] -> 
 *      array(
 *          [0] => Order#1,
 *          [1] => Order#2,
 *               (...)
 *      )
 * 
 * Note: Each interaction with
 * the user meta must be done
 * during the 'init' tag.
 */
class Orders {
    private $user_id;
    private $wp_key = 'ords_';
    private $wp_key_pen_ords = 'pen_ords_';

    // - Order addition --
    private $order_to_add;
    // ---

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->wp_key = $this->wp_key . $this->user_id;
        $this->wp_key_pen_ords = $this->wp_key_pen_ords . $this->user_id;
    }

    public function add_order($order) {
        $this->order_to_add = $order;
        add_action('init', array($this, 'add_order_meta'));
    }

    public function add_order_meta() {
        $orders = array();
        $new_order = $this->order_to_add->get_order_info();
        $user_meta_arr = get_user_meta($this->user_id, $this->wp_key, true); // saved orders
        $order_count = count($user_meta_arr);
        if ($user_meta_arr === false) {
            console_log('\t- User ID not valid');
        } else if ($order_count && !($order_count === 1 && $user_meta_arr[0] === "")) {
            $index = 0;
            while ($index < $order_count) {
                array_push($orders, $user_meta_arr[$index]);
                $index++;
            }
            array_push($orders, $new_order);
            $user_meta_update = update_user_meta($this->user_id, $this->wp_key, $orders);
            if (is_int($user_meta_update)) {
                console_log('\t- WP Key doesnt exist');
            } else if ($user_meta_update === false) {
                console_log('\t- Update failed');
            } else {
                console_log('\t- Update successful');
            }
        } else {
            array_push($orders, $new_order);
            $user_meta_add = add_user_meta($this->user_id, $this->wp_key, $orders);
            if ($user_meta_add === false) {
                console_log('\t- Failed to add order');
            } else {
                console_log('\t- Added new order successfully');
            }
        }
    }

    public function add_pending_order_meta($order) {
        $orders = array();
        $new_order = $order->get_order_info();
        $user_meta_arr = get_user_meta($this->user_id, $this->wp_key, true); // saved orders
        $order_count = count($user_meta_arr);
        if ($user_meta_arr === false) {
            console_log('\t- User ID not valid');
        } else if ($order_count && !($order_count === 1 && $user_meta_arr[0] === "")) {
            $index = 0;
            while ($index < $order_count) {
                array_push($orders, $user_meta_arr[$index]);
                $index++;
            }
            array_push($orders, $new_order);
            $user_meta_update = update_user_meta($this->user_id, $this->wp_key, $orders);
            if (is_int($user_meta_update)) {
                console_log('\t- WP Key doesnt exist');
            } else if ($user_meta_update === false) {
                console_log('\t- Update failed');
            } else {
                console_log('\t- Update successful');
            }
        } else {
            array_push($orders, $new_order);
            $user_meta_add = add_user_meta($this->user_id, $this->wp_key, $orders);
            if ($user_meta_add === false) {
                console_log('\t- Failed to add order');
            } else {
                console_log('\t- Added new order successfully');
            }
        }
    }

    public function update_order_payment_meta($order_id, $payment_created_at, $payment_id) {
        $orders = array();
        $user_meta_arr = get_user_meta($this->user_id, $this->wp_key, true);
        if ($user_meta_arr === false) {
            console_log('\t- User ID not valid');
        } else {
            $index = 0;
            while ($index < count($user_meta_arr)) {
                $order = $user_meta_arr[$index];
                if ($order['ord_id'] === $order_id && $order['type'] === "hold") {
                    $order_payment_ops = $order['payment_ops'];
                    $updated_order = array(
                        'type' => 'instant',
                        'ord_id' => $order_id,
                        'payment_ops' => array(
                            'total_amount' => $order_payment_ops['total_amount'],
                            'payment_required_by' => $order_payment_ops['payment_required_by'],
                            'payment_created_at' => $payment_created_at,
                            'payment_id' => $payment_id
                        ),
                        'booking_ref' => $order['booking_ref']
                    );

                    // debug
                    // var_dump($updated_order);
                    // --
                    array_push($orders, $updated_order);
                } else {
                    array_push($orders, $order);
                }
                $index++;
            }
            $user_meta_update = update_user_meta($this->user_id, $this->wp_key, $orders);
            if (is_int($user_meta_update)) {
                console_log('\t- WP Key doesnt exist');
            } else if ($user_meta_update === false) {
                console_log('\t- Payment update failed');
            } else {
                console_log('\t- Payment update successful');
            }
        }
    }

    public function update_order_stripe_payment_meta($order_id) {
        $orders = array();
        $user_meta_arr = get_user_meta($this->user_id, $this->wp_key, true);
        if ($user_meta_arr === false) {
            console_log('\t- User ID not valid'); 
        } else {
            $index = 0;
            while ($index < count($user_meta_arr)) {
                $order = $user_meta_arr[$index];
                if ($order['ord_id'] === $order_id) {
                    $updated_order = array(
                        'type' => 'instant',
                        'ord_id' => $order['ord_id'],
                        'payment_ops' => $order['payment_ops'],
                        'stripe_flag' => 1,
                        'booking_ref' => $order['booking_ref']
                    );
                    array_push($orders, $updated_order);
                } else {
                    array_push($orders, $order);
                }
                $index++;
            }
            $user_meta_update = update_user_meta($this->user_id, $this->wp_key, $orders);
            if (is_int($user_meta_update)) {
                console_log('\t- WP Key doesnt exist');
            } else if ($user_meta_update === false) {
                console_log('\t- Payment update failed');
            } else {
                console_log('\t- Payment update successful');
            }
        }
    }

    public function update_order_checkout_payment_meta() {
        $orders = array();
        $user_meta_arr = get_user_meta($this->user_id, $this->wp_key, true);
        if ($user_meta_arr === false) {
            console_log('\t- User ID not valid'); 
        } else {
            $index = 0;
            $alloc_flag = 1;
            while ($index < count($user_meta_arr)) {
                $order = $user_meta_arr[$index];
                if (isset($order['stripe_flag']) && $alloc_flag === 1) {
                    $updated_order = array(
                        'type' => 'instant',
                        'ord_id' => $order['ord_id'],
                        'payment_ops' => $order['payment_ops'],
                        'stripe_flag' => 2,
                        'booking_ref' => $order['booking_ref']
                    );
                    array_push($orders, $updated_order);
                    $alloc_flag--;
                } else {
                    array_push($orders, $order);
                }
                $index++;
            }
            $user_meta_update = update_user_meta($this->user_id, $this->wp_key, $orders);
            if (is_int($user_meta_update)) {
                console_log('\t- WP Key doesnt exist');
            } else if ($user_meta_update === false) {
                console_log('\t- Payment update failed');
            } else {
                console_log('\t- Payment update successful');
            }
        }
    }

    /**
     * Used for canceling a specific order,
     * there is no 'init' handler (in this file) 
     * for this function because it is called
     * from index.php.
     */
    public function delete_order_meta($order_id) {
        $flag = 0;
        $user_meta_arr = get_user_meta($this->user_id, $this->wp_key);
        $updated_orders = array();
        $orders = $user_meta_arr[0];
        $order_count = count($orders);
        $index = 0;
        
        while($index < $order_count) {
            $order = $orders[$index];
            if ($order['ord_id'] === $order_id) {
                $flag++;
            } else {
                array_push($updated_orders, $order);
            }
            $index++;
        }

        $resp = update_user_meta($this->user_id, $this->wp_key, $updated_orders);
        if (is_int($resp)) {
            console_log('\t- User meta Key doesnt exist');
        } else if ($resp === true && $flag === 1) {
            console_log('\t- Order successfully deleted');
        } else {
            console_log('\t- Failed to delete order');
        }
    }

    public function delete_orders() {
        add_action('init', array($this, 'delete_orders_meta'));
    }

    public function delete_orders_meta() {
        $meta = delete_user_meta($this->user_id, $this->wp_key);
        if ($meta) {
            console_log('\t- Successfully deleted order(s)');
        } else {
            console_log('\t- Not able to delete order(s)');
        }
    }

    public function show_orders() {
        add_action('init', array($this, 'show_orders_meta'));
    }

    public function show_orders_meta() {
        $arr = get_user_meta($this->user_id, $this->wp_key, true);
        $order_count = count($arr);
        if ($order_count && !($order_count === 1 && $arr[0] === "")) {
            $index = 0;
            while ($index < $order_count) {
                $order = $arr[$index];
                $order = new Order(
                    $order['type'], 
                    $order['ord_id'], 
                    $order['payment_ops'], 
                    $order['booking_ref'],
                    $order['info']
                );
                $order->print_html();
                $index++;
            }
        } else {
            console_log('No orders found');
        }
        return;
    }

    public function debug_get_orders() {
        add_action('init', array($this, 'debug_get_orders_meta'));
    }

    public function debug_get_orders_meta() {
        $arr = get_user_meta($this->user_id, $this->wp_key, true);
        $order_count = count($arr);
        if ($order_count && !($order_count === 1 && $arr[0] === "")) {
            var_dump($arr);
        } else {
            console_log('No orders found');
        }
        return;
    }

    // -*-

    /**
     * Add pending order: Adds offer_id associated
     * to future order. This offer_id should be added
     * to the user meta just before being prompted
     * for payment (stripe).
     */
    public function add_pending_order($offer_id, $duffel_total_amount) {
        $user_meta_update = update_user_meta($this->user_id, $this->wp_key_pen_ords, [$offer_id, $duffel_total_amount]);
        if (is_int($user_meta_update)) {
            console_log('\t- WP Key Pen Ords doesnt exist');
        } else if ($user_meta_update === false) {
            console_log('\t- Pending Order update failed');
        } else {
            console_log('\t- Pending Order update successful');
        }
    }

    public function get_pending_order() {
        $user_meta_arr = get_user_meta($this->user_id, $this->wp_key_pen_ords, true);
        if ($user_meta_arr === false) {
            console_log('\t- User ID not valid');
        } else {
            return $user_meta_arr;
        }
        return -1;
    }

    // -*-
}


/** ###### Order ######
 * 
 * Order class used to
 * save relevant Order
 * information and print
 * (echo) html and js code.
 * 
 * ATM there are three types
 * of orders:
 *  
 *  .Simple "instant":
 *   Order created and bought
 *   at moment of creation.
 * 
 *  . "hold":
 *   Order created but not 
 *   bought for at moment of
 *   creation.
 * 
 *  . "hold" -> "instant" w/ Pay ID:
 *   Order created and bought for
 *   later.
 */
class Order {
    private $pay_type;
    private $order_id;

    public $payment_ops; // + currency
    public $booking_ref;

    public function __construct($type, $order_id, $payment_ops, $booking_ref, $info)
    {
        $this->pay_type = $type;
        $this->order_id = $order_id;
        $this->payment_ops = $payment_ops;
        $this->booking_ref = $booking_ref;
        $this->info = $info;
    }

    public function get_ord_id() {
        return $this->order_id;
    }

    public function get_order_info() {
        return array(
            'type' => $this->pay_type,
            'ord_id' => $this->order_id,
            'payment_ops' => $this->payment_ops,
            'booking_ref' => $this->booking_ref,
            'info' => $this->info
        );
    }

    public function get_order_info_html() { // TODO: Update this div with Order info field
        $order_info = '<div id=\''.$this->order_id.'_info\' class=\'order_info\' style=\'display: none;\'>'; 
        $order_info = $order_info . 'Testing order info'; 
        $order_info = $order_info . '</div>';
    }

    public function print_html() {
        $script = $this->print_order_info() . 
                $this->print_cancel_order_msg_script() . 
                $this->print_cancel_order_script() . 
                $this->print_pay_order_msg_script() . 
                $this->print_pay_order_script();
        $init_code = '<script> '. $script .'document.addEventListener("DOMContentLoaded", function(event) { ';
        $code = $init_code . 'document.getElementById("order_dash").innerHTML += "'; 

        $order_entry = '<div class=\'order_entry\'>';
        $order_entry = $order_entry . '<div id=\'order_id\' class=\'dist\'>Order '.$this->print_booking_ref().'</div>';
        $order_entry = $order_entry . '<input id=\'hidden_order_id\' type=\'hidden\' value=\''.$this->order_id.'\'>';
        if ($this->pay_type === "instant") {
            $order_entry = $order_entry . '<div id=\'order_typ\' class=\'dist\'>Payment: <span class=\'instant_pay\'>●</span></div>';
        } else {
            $order_entry = $order_entry . '<div id=\'order_typ\' class=\'dist\'>Payment: <span class=\'hold_pay\'>●</span></div>';
            $order_entry = $order_entry . '<div onclick=\'show_pay_'.$this->order_id.'()\' id=\'order_pay\' class=\'dist\'>Pay Order</div>';
        }
        $order_entry = $order_entry . '<div id=\'order_cancel\' class=\'dist\' onclick=\'show_cancel_'.$this->order_id.'()\'>Cancel Order</div>';
        $order_entry = $order_entry . '<div class=\'show_order dist\' onclick=\'show_'.$this->order_id.'()\'>Show order</div>';
        $order_entry = $order_entry . '</div>';
        $code = $code . $order_entry;

        $buy_order_msg = '<div id=\'pay_'.$this->order_id.'\' class=\'buy_order_msg\' style=\'display: none;\'>';
        $buy_order_msg = $buy_order_msg . '<div id=\'order_total dist\'>Order Total: '.$this->payment_ops['total_amount'].'</div>';
        $buy_order_msg = $buy_order_msg . '<div class=\'dist\'>Order payment expires in: '.$this->print_order_hold_exp_date().'</div>';
        $buy_order_msg = $buy_order_msg . '<div onclick=\'pay_'.$this->order_id.'()\' class=\'buy_order_bt dist\'>Pay now</div>';
        // stripe payment html
        $buy_order_msg = $buy_order_msg . '</div>';
        $code = $code . $buy_order_msg;

        $order_cancel_msg = '<div id=\''.$this->order_id.'_cancel\' class=\'cancel_order_msg\' style=\'display: none;\'>';
        $order_cancel_msg = $order_cancel_msg . 'Are you sure you wan\'t to cancel your order?';
        $order_cancel_msg = $order_cancel_msg . '<div class=\'opts\'>';
        $order_cancel_msg = $order_cancel_msg . '<div onclick=\'cancel_'.$this->order_id.'()\' class=\'cancel_order_bt dist\'>Yes</div>';
        $order_cancel_msg = $order_cancel_msg . '</div></div>';
        $code = $code . $order_cancel_msg;

        $order_info = '<div id=\''.$this->order_id.'_info\' class=\'order_info\' style=\'display: none;\'>'; 
        $order_info = $order_info . 'Testing order info'; // TODO!
        $order_info = $order_info . '</div>';
        $code = $code . $order_info;
        $code = $code . '"; }); </script>';
        echo $code;
    }

    public function print_booking_ref() {
        return strval($this->booking_ref);
    }

    private function print_cancel_order_msg_script() {
        return 'function show_cancel_'.$this->order_id.'() {
            let elem = document.getElementById("'.$this->order_id.'_cancel");
            elem.style.display == "none" ? elem.style.display = "inline-block" : elem.style.display = "none"
        } ';
    }

    private function print_cancel_order_script() {
        return 'function cancel_'.$this->order_id.'() {
            let url = new URL(\'https://pynkiwi.com/?page_id=3294\');
            url.searchParams.append(\'action_type\', \'1\');
            url.searchParams.append(\'order_id\', \''.$this->order_id.'\');
            window.location.href = url;
        } ' ;
    }

    // TODO: If expired don't allow payment
    private function print_order_hold_exp_date() {
        $payment_required_by = $this->payment_ops['payment_required_by'];
        $expires_at = new DateTime(substr($payment_required_by, 0, 10) . "  " . substr($payment_required_by, 11, 5));
        $curr_date = new DateTime('now');
        return $expires_at->diff($curr_date)->format("%H:%I:%S");
    }

    private function print_pay_order_msg_script() {
        return 'function show_pay_'.$this->order_id.'() {
            let elem = document.getElementById("pay_'.$this->order_id.'");
            elem.style.display == "none" ? elem.style.display = "flex" : elem.style.display = "none" 
        } ';
    }

    private function print_pay_order_script() {
        return 'function pay_'.$this->order_id.'() {
            let url = new URL(\'https://pynkiwi.com/?page_id=3294\');
            url.searchParams.append(\'action_type\', \'2\');
            url.searchParams.append(\'order_id\', \''.$this->order_id.'\');
            window.location.href = url;
        } ' ;
    }

    private function print_order_info() {
        return 'function show_'.$this->order_id.'() { 
            let elem = document.getElementById("'.$this->order_id.'_info"); 
            elem.style.display == "none" ? elem.style.display = "flex" : elem.style.display = "none" 
        } ';
    }

    public function debug() {
        console_log('order_id: '.$this->order_id.' type: '.$this->pay_type);
        console_log('booking_ref: '.$this->booking_ref);
        var_dump($this->payment_ops);
    }
}

/** ###### Order Request ######
 *  
 *  Class used to create Order,
 *  depending on the type of 
 *  request (instant or hold)
 *  some parameters will be
 *  disregarded. Note that
 *  if the Order is of type
 *  hold then payment should
 *  be send via a different 
 *  endpoint.
 * 
 *  Docs: https://duffel.com/docs/api/payments/create-payment
 */
class Order_request {
    private $type;
    private $services;
    private $selected_offers;
    private $payments;
    private $passengers;

    public function __construct($type, $services, $selected_offers, $payments, $passengers)
    {
        $this->type = $type;
        $this->services = $services;
        $this->selected_offers = $selected_offers;
        $this->payments = $payments;
        $this->passengers = $passengers;
    }

    /**
     * Function responsible for 
     * returning the POST data used
     * to send when requesting an Order.
     * 
     * POST data varies depending on
     * the type of payment.
     */
    public function get_post_data() {
        if ($this->type === "instant") {
            return array(
                'type' => $this->type,
                'services' => $this->services,
                'selected_offers' => $this->selected_offers,
                'payments' => $this->payments,
                'passengers' => $this->passengers
            );
        } else { // "hold"
            return array(
                'type' => $this->type,
                'selected_offers' => $this->selected_offers,
                'passengers' => $this->passengers
            );
        }
    }

    public function create_order() {
        $req = new CURL_REQUEST('POST', 'https://api.duffel.com/air/orders', $this->get_post_data());
        $resp_decoded = $req->send_duffel_request();
        $data = $resp_decoded->data;
            
        if ($data->id === "" || $data === null) { // TODO: Error handeling
            var_dump($data);
            alert('Reload page');
            return;
        }
        if ($this->type === "instant") {
            $payment_opts = array(
                'total_amount' => $data->total_amount
            );
        } else {
            $payment_opts = array(
                'total_amount' => $data->total_amount,
                'payment_required_by' => $data->payment_status->payment_required_by
            );
        }
        // --
        $order_info = $this->build_order_info($data);
        // --
        $order = new Order($this->type, $data->id, $payment_opts, $data->booking_reference, $order_info);
        return $order;
    }

    public function build_order_info($data) {
        $synced_at = $data->synced_at;
        $passengers = $data->passengers;
        $services = $data->services;
        $conditions = $data->conditions;

        $slices = $data->slices;    // Array
        $slices_info = array();
        foreach($slices as $slice) {
            $segments = $slice->segments;
            $slice_conditions = $slice->conditions;
            $segment_info = array();
            foreach($segments as $segment) {
                $origin = $segment->origin->city_name;
                $destination = $segment->destination->city_name;
                array_push($segment_info, array('origin' => $origin, 'destination' => $destination));
            }
            array_push($slices_info, array('slice_conditions' => $slice_conditions, 'segment_info' => $segment_info));
        }

        $order_info = new stdClass;
        $order_info->synced_at = $synced_at;
        $order_info->passengers = $passengers;
        $order_info->services = $services;
        $order_info->conditions = $conditions;
        $order_info->slices = $slices_info;
        
        return $order_info;
    }

}
// ###### EOF ######
?>