<?php
// Copyright 2021 - PYNKIWI

/*
    *1 Legal notice - https://help.duffel.com/hc/en-gb/articles/360021056640
*/

// https://pynkiwi.wpcomstaging.com/?page_id=3294
class Orders {
    private $user_id;
    private $wp_key = 'ords_';

    private $order_to_add;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
        $this->wp_key = $this->wp_key . $this->user_id;
    }

    public function add_order($order) {
        $this->order_to_add = $order;
        add_action('init', array($this, 'add_order_meta'));
    }

    public function add_order_meta() {
        $orders = array();
        $new_order = array(
            'ord_id' => $this->order_to_add->get_order_id(),
            'type' => $this->order_to_add->get_pay_type()
        );
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
        // Print Orders
        $index = 0;
        while ($index < count($orders)) {
            $order_arr = $orders[$index];
            $order = new Order($order_arr['type'], $order_arr['ord_id']);
            $order->print_html();
            $index++;
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

    public function get_orders() {
        add_action('init', array($this, 'get_orders_meta'));
    }

    public function get_orders_meta() {
        $arr = get_user_meta($this->user_id, $this->wp_key, true);
        if (count($arr)) {
            var_dump($arr);
        } else {
            console_log('No orders found');
        }
        return;
    }
}


class Order {
    private $pay_type;
    private $order_id;

    public function __construct($type, $order_id)
    {
        $this->pay_type = $type;
        $this->order_id = $order_id;
    }

    public function get_pay_type() {
        return $this->pay_type;
    }

    public function get_order_id() {
        return $this->order_id;
    }

    public function print_html() {
        $show_order_info = 'function show_'.$this->order_id.'() { let elem = document.getElementById("'.$this->order_id.'_info"); elem.style.display == "none" ? elem.style.display = "flex" : elem.style.display = "none" } ';
        $init_code = '<script> '. $show_order_info .'document.addEventListener("DOMContentLoaded", function(event) { ';
        $code = $init_code . 'document.getElementById("order_dash").innerHTML += "'; 

        $order_entry = '<div class=\'order_entry\'>';
        $order_entry = $order_entry . '<div id=\'order_id\' class=\'dist\'>Order '.$this->order_id.'</div>';
        if ($this->type === "instant") {
            $order_entry = $order_entry . '<div id=\'order_typ\' class=\'dist\'>Payment: <span class=\'instant_pay\'>●</span></div>';
        } else {
            $order_entry = $order_entry . '<div id=\'order_typ\' class=\'dist\'>Payment: <span class=\'hold_pay\'>●</span></div>';
        }
        $order_entry = $order_entry . '<div class=\'show_order dist\' onclick=\'show_'.$this->order_id.'()\'>Show order</div>';
        $order_entry = $order_entry . '</div>';
        $code = $code . $order_entry;

        $order_info = '<div id=\''.$this->order_id.'_info\' class=\'order_info\' style=\'display: none;\'>'; 
        $order_info = $order_info . 'Testing order info'; // TODO!
        $order_info = $order_info . '</div>';
        $code = $code . $order_info;
        $code = $code . '"; }); </script>';
        echo $code;
    }

    public function debug() {
        console_log('order_id: '.$this->order_id.' type: '.$this->pay_type);
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

    public function get_post_data() {
        if ($this->type === "instant") {
            return json_encode(
                array(
                    'data' => array(
                        'type' => $this->type,
                        'services' => $this->services,
                        'selected_offers' => $this->selected_offers,
                        'payments' => $this->payments,
                        'passengers' => $this->passengers
                    )
                )
            );
        } else { // "hold"
            return json_encode(
                array(
                    'data' => array(
                        'type' => $this->type,
                        'selected_offers' => $this->selected_offers,
                        'passengers' => $this->passengers
                    )
                )
            );
        }
    }

    public function create_order() {
        $order = 0;
        $url = "https://api.duffel.com/air/orders";
        $header = array(
            'Accept-Encoding: gzip',
            'Accept: application/json',
            'Content-Type: application/json',
            'Duffel-Version: beta',
            'Authorization: Bearer duffel_test_vDBYacGBACsUsAYIRATuTQXieoIsb_TxLjcM4hAmUTl'
        );
        $data = $this->get_post_data();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($ch);
        if ($err = curl_error($ch)) {
            console_log('[*] Error creating order - ' . $err);
            curl_close($ch);
            error_msg();
        } else {
            console_log('[*] Order successfully created');
            $response = gzdecode($res);
            $resp_decoded = json_decode($response);
            
            // debug
            //var_dump($resp_decoded);

            // ---
            $data = $resp_decoded->data;
            if ($data->id === "") { // TODO: Error handeling
                alert('Reload page');
                return;
            }
            $order = new Order($this->type, $data->id);
            // -*-
        }
        curl_close($ch);
        return $order;
    }
}

// ###### EOF ######
?>