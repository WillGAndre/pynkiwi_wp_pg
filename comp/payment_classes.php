<?php
// Copyright 2021 - PYNKIWI

/*
    *1 Legal notice - https://help.duffel.com/hc/en-gb/articles/360021056640
*/

/*
    TODO:
        Make type and order_id args optional,
        upon a get_order, parameters should be updated
        if not set.
*/
// https://pynkiwi.wpcomstaging.com/?page_id=3294
class Order {
    private $user_id;
    private $wp_key = 'ord_';

    private $pay_type;
    private $order_id;

    public function __construct($user_id, $type, $order_id)
    {
        $this->user_id = strval($user_id);
        $this->wp_key = $this->wp_key . $this->user_id;
        $this->pay_type = $type;
        $this->order_id = $order_id;
    }

    public function add_order() {
        add_action('init', array($this, 'add_order_meta'));
    }

    public function add_order_meta() {
        $params = array(
            'ord_id' => $this->order_id,
            'type' => $this->pay_type
        );
        console_log('user_id: '.$this->user_id.' order_id: '.$this->order_id);
        $meta_id = add_user_meta($this->user_id, $this->wp_key, $params);
        if ($meta_id === false) {
            console_log('\t- Unable to save user meta data');
        } else {
            console_log('\t- Successfully saved order');
        }
    }

    public function get_order() {
        add_action('init', array($this, 'get_order_meta'));
    }

    public function get_order_meta() {
        $arr = get_user_meta($this->user_id, $this->wp_key, true);
        if (count($arr)) {
            var_dump($arr);
        } else {
            console_log('Order array empty');
        }
    }

    public function delete_order() {
        add_action('init', array($this, 'delete_order_meta'));
    }

    public function delete_order_meta() {
        $meta = delete_user_meta($this->user_id, $this->wp_key);
        if ($meta) {
            console_log('\t- Successfully deleted order');
        } else {
            console_log('\t- Not able to delete order');
        }
    }

    public function print_html() {
        $show_order_info = 'function show_'.$this->order_id.'() { let elem = document.getElementById("'.$this->order_id.'_info"); elem.style.display == "none" ? elem.style.display = "flex" : elem.style.display = "none" } ';
        $init_code = '<script> '. $show_order_info .'document.addEventListener("DOMContentLoaded", function(event) { ';
        $code = $init_code . 'document.getElementById("order_dash").innerHTML += "'; // "; '

        $order_entry = '<div class=\'order_entry\'>';
        $order_entry = $order_entry . '<div id=\'order_id\' class=\'dist\'>Order '.$this->order_id.'</div>';
        if ($this->pay_type === "instant") {
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

    public function create_order($user_id) {
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
            $order = new Order($user_id, $this->type, $data->id);
            // -*-
        }
        curl_close($ch);
        return $order;
    }
}

// ###### EOF ######
?>