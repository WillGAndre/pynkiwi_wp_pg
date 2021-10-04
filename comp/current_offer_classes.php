<?php
// Copyright 2021 - PYNKIWI

/** ###### Single Offer ######
 *  Prerequisite: Offer id
 * 
 *  Class utilized to get single offer
 *  from duffel as well as printing the
 *  returned data back to WP.
 *  Also creates an Offer and Offer_Payment_Info class.
 */
class Single_Offer
{
    private $user_id;

    private $offer_id;
    private $offer;
    private $offer_payment_info;
    private $passenger_ids;

    public function __construct($offer_id, $user_id)
    {
        $this->user_id = $user_id;
        $this->offer_id = $offer_id;
        $this->passenger_ids = array();
    }

    public function get_single_offer()
    {
        $url = "https://api.duffel.com/air/offers/" . $this->offer_id . "?return_available_services=true";
        $header = array(
            'Accept-Encoding: gzip',
            'Accept: application/json',
            'Content-Type: application/json',
            'Duffel-Version: beta',
            'Authorization: Bearer duffel_test_vDBYacGBACsUsAYIRATuTQXieoIsb_TxLjcM4hAmUTl'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($ch);
        if ($err = curl_error($ch)) {
            console_log('[*] Error getting single offer - ' + $err);
        } else {
            console_log('[*] Updated Offer');
            $response = gzdecode($res);
            $resp_decoded = json_decode($response);

            // debug 
            // var_dump($resp_decoded);

            // TODO!
            if ($resp_decoded->meta->status === 422 && $resp_decoded->errors[0]->title === "Requested offer is no longer available") {
                alert('Please reload your page');
                exit();
            } else {
                $data = $resp_decoded->data;
                $this->walk_data($data);
            }
        }
    }

    /**
     * Function used to walk the data
     * returned by Duffel. 
     * Using this data, passenger info
     * is saved and one Offer and Offer_Payment_Info
     * class is created to print the data.
     */
    private function walk_data($data)
    {
        $this->allocate_passenger_ids($data->passengers);   

        $total_amount = $data->total_amount . ' ' . $data->total_currency;
        $tax_amount = $data->tax_amount . ' ' . $data->total_currency;
        $payment_req = $data->payment_requirements;
        $pass_id_doc_req = $data->passenger_identity_documents_required;
        $conds = $data->conditions;
        $base_amount = $data->base_amount . ' ' . $data->base_currency;
        $available_services = $data->available_services;
        $allowed_pass_id_doc_types = $data->allowed_passenger_identity_document_types;

        $this->offer = $this->create_offer($data->id, $data->slices, $data->created_at, $data->expires_at);
        $this->offer_payment_info = new Offer_Payment_Info(
            $this->get_segment_ids($data->slices),
            $this->passenger_ids, 
            $total_amount, $tax_amount, $payment_req, 
            $pass_id_doc_req, $conds, $base_amount,
            $available_services, $allowed_pass_id_doc_types
        );
    }

    /**
     * Function to create an updated Offer,
     * data->slices holds every information
     * necessary to create an offer.
     */
    private function create_offer($offer_id, $slices, $created_at, $expires_at) 
    {
        // ### Offer Data ###
        $offer_id = "";
        $source_iata_code = array();
        $source_airport = array();
        $source_terminal = array();
        $destination_iata_code = array();
        $destination_airport = array();
        $destination_terminal = array();
        $departing_at = array();
        $arriving_at = array();
        $total_amount = "";
        $airline = array();
        $baggage_per_sli = array();
        // ---    ####    ---
        foreach ($slices as $_ => $v3) {
            // ### Baggages Data ###
            $sli_id = "";
            $seg_ids = array();
            $pas_ids = array();
            $baggages = array();
            // ---    ####    ---
            foreach ($v3 as $k4 => $v4) {
                if ($k4 === "segments") {
                    foreach ($v4 as $_ => $v5) {
                        foreach ($v5 as $k6 => $v6) {
                            if ($k6 === "passengers") {
                                foreach ($v6 as $_ => $passenger) {
                                    // ### Baggage Data ###
                                    $pas_id = "";
                                    $types = array();
                                    $quans = array();
                                    // ---    ####    ---
                                    foreach ($passenger as $pass_key => $pass_val) {
                                        if ($pass_key === "passenger_id") {
                                            $pas_id = $pass_val;
                                            array_push($pas_ids, $pas_id);
                                        } else if ($pass_key === "baggages") {
                                            foreach ($pass_val as $_ => $baggage) {
                                                foreach ($baggage as $bag_key => $bag_val) {
                                                    if ($bag_key === "type") {
                                                        array_push($types, $bag_val);
                                                    } else if ($bag_key === "quantity") {
                                                        array_push($quans, $bag_val);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $baggage = new Baggage($pas_id, $types, $quans);
                                    array_push($baggages, $baggage);
                                }
                            } else if ($k6 === "origin_terminal") {
                                array_push($source_terminal, $v6);
                            } else if ($k6 === "origin") {
                                foreach ($v6 as $k7 => $v7) {
                                    if ($k7 === "name") {
                                        array_push($source_airport, $v7);
                                    } else if ($k7 === "iata_code") {
                                        array_push($source_iata_code, $v7);
                                    }
                                }
                            } else if ($k6 === "destination_terminal") {
                                array_push($destination_terminal, $v6);
                            } else if ($k6 === "destination") {
                                foreach ($v6 as $k7 => $v7) {
                                    if ($k7 === "name") {
                                        array_push($destination_airport, $v7);
                                    } else if ($k7 === "iata_code") {
                                        array_push($destination_iata_code, $v7);
                                    }
                                }
                            } else if ($k6 === "departing_at") {
                                array_push($departing_at, $v6);
                            } else if ($k6 === "arriving_at") { 
                                array_push($arriving_at, $v6);
                            } else if ($k6 === "operating_carrier") {
                                foreach ($v6 as $k7 => $v7) {
                                    if ($k7 === "name") {
                                        array_push($airline, $v7);
                                    }
                                }
                            } else if ($k6 === "id") {
                                array_push($seg_ids, $v6);
                            }
                        }
                    }
                } else if ($k4 === "id") {
                    $sli_id = $v4;
                }
            }
            $offer_baggage = new Baggages($sli_id, $seg_ids, $pas_ids, $baggages);
            array_push($baggage_per_sli, $offer_baggage);
        }

        return new Offer(
            $offer_id,
            get_offer_ttl($created_at, $expires_at),
            $source_iata_code,
            $source_airport,
            $source_terminal,
            $destination_iata_code,
            $destination_airport,
            $destination_terminal,
            $departing_at,
            $arriving_at,
            $this->cabin_class,
            $total_amount,
            $airline,
            $baggage_per_sli
        );
    }

    /**
     * Sets passenger ids in $this->passenger_ids,
     * prints passenger id/type in (hidden) div and
     * checks for infants (0 or 1) if there are any
     * then an input checkbox will be shown. This
     * checkbox should only be set if passenger is 
     * an adult (js).
     */
    private function allocate_passenger_ids($passengers) {
        $code = '<script> document.addEventListener("DOMContentLoaded", function(event) { document.getElementById("pass_ids").innerHTML += "';
        $index = 0;
        $infant_flag = 0;
        foreach($passengers as $_ => $content) {
            if ($content->type === "infant_without_seat") {
                $infant_flag = 1;
            }

            array_push($this->passenger_ids, $content->id);
            $code = $code . '<div id=\'pass_' . $index . '_id\'>' . $content->id . '</div>';
            $code = $code . '<div id=\'pass_' . $index . '_type\'>' . $content->type . '</div>';
            $index++;
        }
        $code = $code . '"; ';
        if ($infant_flag) {
            $code = $code . 'document.getElementById("pass_disclaimer").innerHTML += "<div id=\'infant-discl\' class=\'entry top\'><input id=\'infant-input\' type=\'checkbox\' name=\'infant-checkbox\'><label for=\'infant-checkbox\' style=\'font-size: small;\'>Passenger responsible for infant.</label></div>"; ';
        }
        $code = $code . ' }); </script>';
        echo $code;
    }

    // TODO: Refactor code
    public function get_segment_ids($slices) {
        $index = 0;
        $arr = [];

        foreach($slices as $index => $entry) {
            foreach($entry as $key => $value) {
                if ($key === "segments") {
                    foreach($value as $key2 => $value2) {
                        foreach($value2 as $key3 => $value3) {
                            if ($key3 === "id") {
                                $arr[$index] = $value3;
                                $index++;
                            }
                        }
                    }
                }
            }
        }

        return $arr;
    }

    public function get_offer() {
        return $this->offer;
    }

    public function get_offer_payment_info() {
        return $this->offer_payment_info;
    }

    public function print_single_offer_html() {
        return $this->offer->print_html(1,0);
    }

    public function print_single_offer_opts_html() {
        return $this->offer_payment_info->print_html();
    }

    public function print_user() {
        echo '<div id=\'user_id\' style=\'display: none;\'>'.$this->user_id.'</div>';
    }
}

/** ###### Offer payment info ######
 *  Class that holds payment/conditions/services
 *  information about an offer, used mainly under
 *  /account in WP.
 */
class Offer_Payment_Info
{
    private $segment_ids;
    private $passenger_ids;

    private $total_amount;  // includes taxes and currency
    private $tax_amount; // plus currency
    private $payment_requirements;
    private $passenger_identity_documents_required;
    private $conditions;
    private $base_amount; // plus currency
    private $allowed_passenger_identity_document_types; // currently only supported passport (by Duffel)

    public const MAX_SERVICES = 4;
    private $available_services;

    public function __construct(
    $segment_ids, $passenger_ids, $total_amount, $tax_amount, $payment_req, 
    $passenger_id_doc_req, $conds, $base_amount, $services, $allowed_pass_id_docs)
    {
        $this->segment_ids = $segment_ids;
        $this->passenger_ids = $passenger_ids;
        $this->total_amount = $total_amount;
        $this->tax_amount = $tax_amount;
        $this->payment_requirements = $payment_req;
        $this->passenger_identity_documents_required = $passenger_id_doc_req;
        $this->conditions = $conds;
        $this->base_amount = $base_amount;
        $this->available_services = $services;
        $this->allowed_passenger_identity_document_types = $allowed_pass_id_docs;
    }

    /**
     * Function to print html (echo from php) in /account
     * TODO:
     *     *-> Add support for seat selection.
     */
    public function print_html() {
        $init_script = '<script> document.addEventListener("DOMContentLoaded", function(event) { ';
        $script = $this->get_refund_change_scripts($init_script);
        $script = $script . $this->hide_user_dashboard();
        $script = $script . $this->get_passenger_count_script();
        $script = $script . $this->get_payment_requirement_scripts();
        $script = $script . $this->check_doc_required();
        $script = $script . $this->get_additional_baggage_scripts();
        $script = $script . $this->get_total_amount() . '}); </script>';
        echo $script;
    }

    /**
     * Hide user dashboard (id: user-registration)
     */
    private function hide_user_dashboard() {
        return 'document.getElementById("user-registration").style.display = "none"; ';
    }

    /**
     * Check if document info is required
     * in order to book flights.
     */
    private function check_doc_required() {
        if ($this->passenger_identity_documents_required == false) {
            console_log('\t- Id docs required: 0');
            return 'document.getElementById("passport-info").style.display = "none"; ';
        }
        console_log('\t- Id docs required: 1');
        return '';
    }

    private function get_total_amount() {
        return 'document.getElementById("offer_payment").innerHTML = "' . $this->total_amount . '"; ';
    }

    // TODO: Testing, offer with more than two sub flights.
    /**
     * Get js additional baggage scripts.
     * Checks if all flights support
     * additional baggages, if so
     * count of array diff will be 0.
     */
    private function get_additional_baggage_scripts() {
        $flag_add_baggage = 0;
        $input_type = 0; // 0 -> input for all flights | 1 -> various inputs
        $printed_service = array();
        $single_flights_init =
        'document.getElementById("add-bags_text").innerHTML = "<span style=\'color:red\'>*</span> Supported flights"; ';
        $service_ids = 
        'document.getElementById("seg_ids").innerHTML += "';
        $code = 'document.getElementById("add_baggage").innerHTML += "';

        foreach($this->available_services as $_ => $content) {
            if ($content->type === "baggage" && count($printed_service) != $this->MAX_SERVICES) {
                $returned_pas_id = $content->passenger_ids[0];

                if (in_array($returned_pas_id, $this->passenger_ids)) {
                    $service_ids = $service_ids . $content->id . ';';
                    $flag_add_baggage = 1;
                    $max_quantity = $content->maximum_quantity;
                    $returned_seg_ids = $content->segment_ids;

                    if (count(array_diff($this->segment_ids, $returned_seg_ids)) == 0) {
                        $code = $code . '<div class=\'segments_available\'><p class=\'p-title\'>All flights</p>';
                        $code = $code . '<select id=\'quan-' . $content->id . '\' class=\'input-text\' name=\'baggage\'>';
                        $i = 0;
                        while ($i <= $max_quantity) {
                            $code = $code . '<option>' . $i . '</option>';
                            $i++;
                        }
                        $code = $code . '</select><p id=\'price-' . $content->id . '\' class=\'p-title\'>' . $content->total_amount . ' ' . $content->total_currency . '</p></div>';
                        $printed_service = array_merge($printed_service, $returned_seg_ids);
                    } else {
                        $input_type = 1;
                        $code = $single_flights_init . $code;
                        foreach($returned_seg_ids as $_ => $returned_seg_id) {
                            if (in_array($returned_seg_id, $this->segment_ids) && !in_array($returned_seg_id, $printed_service)) {
                                $flight_number = array_search($returned_seg_id, $this->segment_ids) + 1;
                                $code = $code . '<div class=\'segments_available\'><p class=\'p-title\'>Flight Nº' . $flight_number . '</p>';
                                $code = $code . '<select id=\'quan-' . $content->id . '\' class=\'input-text\' name=\'baggage\'>';
                                $i = 0;
                                while($i <= $max_quantity) {
                                    $code = $code . '<option>' . $i . '</option>';
                                    $i++;
                                }
                                $code = $code . '</select><p id=\'price-' . $content->id . '\' class=\'p-title\'>' . $content->total_amount . ' ' . $content->total_currency . '</p></div>';
                                array_push($printed_service, $returned_seg_id);
                            }
                        }
                    }
                }
            }
        }

        if ($input_type) {
            $code = $code . '"; ' . $this->set_baggage_to_flight($printed_service);
        } else {
            $code = $code . '"; ';
        }
        $code = $service_ids . '"; ' . $code;
        if ($flag_add_baggage === 0) {
            $clear = ' document.getElementById("bags-title").style.display = "none"; document.getElementById("entry-add-bag").style.display = "none"; ';
            $code = $code . $clear;
        }
        console_log('\t- Additional Bags: ' . $flag_add_baggage);
        return $code;
    }

    /**
     * Set red asterisk in flight
     * that supports additional baggage.
     * Indexation (of the flights) in 
     * $this->segment_ids is the same
     * as the one specified in the current offer.
     */
    private function set_baggage_to_flight($printed_service) {
        $code = '';
        while(count($printed_service)) {
            $index = array_search(array_pop($printed_service), $this->segment_ids);
            $code = $code . 'document.getElementById("flight_' . $index . '").innerHTML += "<div class=\'entry top\'><span style=\'color:red\'>*</span></div>"; ';
        }
        return $code;
    }

    // TODO:Add button interaction
    private function get_refund_change_scripts($script) {
        $refund_before_departure = $this->conditions->refund_before_departure;
        $change_before_departure = $this->conditions->change_before_departure;

        $flag_ref = 0;
        if ($refund_before_departure->allowed) {$flag_ref = 1;}
        console_log('\t- Refunds: ' . $flag_ref);
        if ($refund_before_departure !== NULL && $refund_before_departure->allowed) {
            $refund_penalty_amount = $refund_before_departure->penalty_amount . ' ' . $refund_before_departure->penalty_currency;
            $script = $script . 'document.getElementById("entry-ref_price").innerHTML += "' . $refund_penalty_amount . '";';
        } else {
            $script = $script . 'document.getElementById("entry-ref").style.display = "none";';
        }

        $flag_chg = 0;
        if ($change_before_departure->allowed) {$flag_chg = 1;}
        console_log('\t- Changes: ' . $flag_chg);
        if ($change_before_departure !== NULL && $change_before_departure->allowed) {
            $change_penalty_amount = $change_before_departure->penalty_amount . ' ' . $change_before_departure->penalty_currency;
            $script = $script . 'document.getElementById("entry-chg_price").innerHTML += "' . $change_penalty_amount . '";';
        } else {
            $script = $script . 'document.getElementById("entry-chg").style.display = "none";';
        }
        
        return $script;
    }

    /**
     * Returns number of passengers in current offer.
     * Will be use by element with id: pass_count.
     */
    private function get_passenger_count_script() {
        return 'document.getElementById("pass_count").innerHTML = "0/' . count($this->passenger_ids) . ' Passengers"; ';
    }

    private function get_payment_requirement_scripts() {
        if ($this->payment_requirements->requires_instant_payment) {
            console_log('\t- Instant payment required: 1');
            return 'document.getElementById("entry-payment").style.display = "none"; document.getElementById("pay_later_discl").style.display = "none"; ';
        } else {
            console_log('\t- Instant payment required: 0');
            $code = 'document.getElementById("entry-payment").innerHTML += "';
            $date_payment_req_by = format_date($this->payment_requirements->payment_required_by);    
            $code = $code . '<div class=\'text imp\'>Payment required by: '.$date_payment_req_by.'</div>';

            if ($this->payment_requirements->price_guarantee_expires_at != null) {
                $date_price_guarantee_exp = format_date($this->payment_requirements->price_guarantee_expires_at);
                $code = $code . '<div class=\'text imp\'>Price guarantee expires at: '.$date_price_guarantee_exp.'</div>';
            }
            $code = $code . '<div class=\'text imp\'><input type=\'checkbox\' id=\'input_pay_later\' style=\'margin-right: 1.2px;\'/>Pay later?<span style=\'color: red; transform: translateX(21em); margin-top: 2em;\'>*1</span></div>';
            return $code . '"; ';
        }
    }
}

?>