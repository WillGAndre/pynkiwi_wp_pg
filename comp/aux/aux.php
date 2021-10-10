<?php
// Copyright 2021 - PYNKIWI

// ###### Auxilary ######

/**
 * Class used to send
 * curl request to Duffel,
 * header and body vary depending
 * on the type of request.
 * 
 * Possible types atm:
 *  POST, GET, CANCEL_ORDER (POST
 *  request with no payload)
 */
class CURL_REQUEST {
    public $type;
    public $url;
    public $header;
    public $data; // array

    public function __construct($type, $url, $data)
    {
        $this->type = $type;
        $this->url = $url;
        if ($type === "POST") {
            $this->set_duffel_payload($data);
        }
    }

    public function set_duffel_header() {
        $this->header = array(
            'Accept-Encoding: gzip',
            'Accept: application/json',
            'Content-Type: application/json',
            'Duffel-Version: beta',
            'Authorization: Bearer duffel_test_vDBYacGBACsUsAYIRATuTQXieoIsb_TxLjcM4hAmUTl'
        );
    }

    public function set_cancel_order_duffel_header() {
        $this->header = array(
            'Accept-Encoding: gzip',
            'Accept: application/json',
            'Duffel-Version: beta',
            'Authorization: Bearer duffel_test_vDBYacGBACsUsAYIRATuTQXieoIsb_TxLjcM4hAmUTl'
        );
    }

    public function set_duffel_payload($data) {
        $this->data = json_encode(
            array(
                'data' => $data
            )
        );
    }

    public function send_duffel_request() {
        $resp_dec = "";
        if ($this->type === "CANCEL_ORDER") {
            $this->type = "POST";
            $this->set_cancel_order_duffel_header();
        } else {
            $this->set_duffel_header();
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->type);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        if ($err = curl_error($ch)) {
            console_log('[*] Error sending request to Duffel - ' . $err);
        } else {
            $response = gzdecode($res);
            $resp_dec = json_decode($response);
        }
        curl_close($ch);
        return $resp_dec;
    }
}

function debug_log($iata_code_from, $iata_code_to, $first_date, $second_date, $slices_list, $passengers_list)
{
    console_log($iata_code_from . ' , ' . $iata_code_to);
    console_log($first_date . ' , ' . $second_date);
    var_dump($slices_list);
    var_dump($passengers_list);
}

function debug_offer_data($source_iata_code, $destination_iata_code, $departing_at, $arriving_at, $airline, $total_amount)
{
    var_dump($source_iata_code);
    var_dump($destination_iata_code);
    var_dump($departing_at);
    var_dump($arriving_at);
    var_dump($airline);
    echo $total_amount;
}

function error_msg()
{
    alert('Please refresh your page and try again');
    exit(0);
}

function console_log($msg)
{
    echo '<script type="text/javascript">';
    echo 'console.log(\'' . $msg . '\')';
    echo '</script>';
}

function alert($msg)
{
    echo '<script type="text/javascript">';
    echo 'alert(\'' . $msg . '\')';
    echo '</script>';
}
// ###### EOF ######
