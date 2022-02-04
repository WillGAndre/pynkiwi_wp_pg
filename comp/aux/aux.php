<?php
// Copyright 2021 - PYNKIWI

// ###### Auxilary ######

// TODO: Sanitize hidden inputs

// function sanitize_off_id() {
//     // if (preg_match('/off_/'))
// }

# off_0000AF43kediPS9lzMLM1I
# off_0000AF43keceTPJ1w3qVMd
# off_0000AF43keceTPJ1w3qVMc

/**
 * Class used to parse html
 * code as string which is 
 * used in conjuncture with js.
 * All whitespaces are
 * replaced with a single ' ' 
 * and " or ' are replaced with \\'.
 */
class HTML_PARSER {
    private $file_name;
    private $args_to_replace;
    private $args_to_add;

    public function __construct($file_name, $args_to_replace, $args_to_add) {
        $this->file_name = $file_name;
        $this->args_to_add = $args_to_add;
        $this->args_to_replace = $args_to_replace;
    }

    public function parse() {
        $html = file_get_contents(plugin_dir_url(__FILE__) . '/html/' . $this->file_name);
        $html = str_replace($this->args_to_replace, $this->args_to_add, $html);
        $html = preg_replace('/\s+/',' ',$html);
        $html = preg_replace(array('/"/', '/\'/'), '\'', $html);
        return $html;
    }
}

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
            'Authorization: Bearer duffel_test_l1kTCQBeHzONzb71C-EXMWH7OO404PYjH6yOEMESyPD'
        );
    }

    public function set_cancel_order_duffel_header() {
        $this->header = array(
            'Accept-Encoding: gzip',
            'Accept: application/json',
            'Duffel-Version: beta',
            'Authorization: Bearer duffel_test_l1kTCQBeHzONzb71C-EXMWH7OO404PYjH6yOEMESyPD'
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
        // debug_headers($ch)
        if ($err = curl_error($ch)) {
            console_log('[*] Error sending request to Duffel - ' . $err);
        } else {
            $response = gzdecode($res);
            $resp_dec = json_decode($response);
            // var_dump($resp_dec);
        }
        curl_close($ch);
        return $resp_dec;
    }

    /**
     * When reporting bug to Duffel, 
     * refer 'request-x-id' so that 
     * they can actively check the 
     * request and response
     */
    public function debug_headers($ch) {
        // -- Debug response header
        $headers = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        var_dump($headers);
        // ---
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
