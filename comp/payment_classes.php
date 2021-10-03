<?php
// Copyright 2021 - PYNKIWI

/*
    *1 Legal notice - https://help.duffel.com/hc/en-gb/articles/360021056640
*/

/*
    TODO:
        Create Order 
            -> type of payment (instant/hold)
            -> order_id;

        In construct: Save order_id based on logged in user in WP.
*/

class Order {
    private $user_id;

    private $pay_type;
    private $order_id;



    public function __construct($user_id, $type, $order_id)
    {
        $this->user_id = $user_id;
        $this->pay_type = $type;
        $this->order_id = $order_id;
        add_action('init', array($this, 'add_order')); // save_user
    }

    public function add_order() {
        $params = array(
            'type' => $this->pay_type
        );
        // distinguish between "instant"/"hold"
        //return add_user_meta($this->user_id, ''.$this->order_id.'', $params);
        console_log('add order: ' . add_user_meta($this->user_id, ''.$this->order_id.'', $params));
    }

    public function get_order() {
        add_action('init', array($this, 'get_order_meta')); // get_order
    }

    public function get_order_meta() {
        console_log( 'get order: ' . get_user_meta($this->user_id, ''.$this->order_id.'', true) );
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
            $order = new Order($user_id, $this->type, $data->id);
            // -*-
        }
        curl_close($ch);
        return $order;
    }
}

// ###### EOF ######
?>