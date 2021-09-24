<?php
// Copyright 2021 - PYNKIWI
// WordPress --> WP

/*
    TODO:
        --> Add offer payment + special options (baggage, choosing seats, etc..)

            Continue to add info in account page, when payment button clicked.
            (Offer -> print_html())

            *1 Legal notice - https://help.duffel.com/hc/en-gb/articles/360021056640


        --> When offer request is created, passenger information should
            be double checked with passenger info returned in offers.
*/

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
    private $offer_id;
    private $offer;
    private $offer_payment_info;
    private $passenger_ids;

    public function __construct($offer_id)
    {
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
            'Authorization: Bearer duffel_test__CCC-2IpAyTjsoCzktXw_9Aaf3BPq8O26Tff5rzc1F0'
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
        // ---    ####    ---
        foreach ($slices as $_ => $v3) {
            foreach ($v3 as $k4 => $v4) {
                if ($k4 === "segments") {
                    foreach ($v4 as $_ => $v5) {
                        foreach ($v5 as $k6 => $v6) {
                            if ($k6 === "origin_terminal") {
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
                            } else if ($k6 === "arriving_at") { // Last entry scanned 
                                array_push($arriving_at, $v6);
                            } else if ($k6 === "operating_carrier") {
                                foreach ($v6 as $k7 => $v7) {
                                    if ($k7 === "name") {
                                        array_push($airline, $v7);
                                    }
                                }
                            }
                        }
                    }
                }
            }
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
            $airline
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

    public function print_single_offer_html() {
        return $this->offer->print_html(1);
    }

    public function print_single_offer_opts_html() {
        return $this->offer_payment_info->print_html();
    }

    public function get_offer_payment_info() {
        return $this->offer_payment_info;
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
     *     *-> Add offer payment support (also in wordpress).
     *          \
     *           --> Reformat passenger form and use data to send payment options.
     */
    public function print_html() {
        $init_script = '<script> document.addEventListener("DOMContentLoaded", function(event) { ';
        $script = $this->get_refund_change_scripts($init_script) . $this->get_passenger_count_script();
        $script = $script . $this->check_doc_required();
        $script = $script . $this->get_additional_baggage_scripts();
        $script = $script . $this->get_total_amount() . '}); </script>';
        echo $script;
    }

    /**
     * Check if document info is required
     * in order to book flights.
     */
    private function check_doc_required() {
        $code = '';
        if ($this->passenger_identity_documents_required == false) {
            $code = $code . 'document.getElementById("passport-info").style.display = "none"; ';
        }
        return $code;
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
        console_log('\t- Services -> Additional Bags: ' . $flag_add_baggage);
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

    /**
     * TODO: 
     *     *-> Add button interaction
     */
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
}

/** ###### Offer ######
 * Offer class used to show
 * offers received from Offer_request
 * (walk_data and create_offer) in
 * WordPress (/flight-booking).
 * 
 * print_html holds three possible
 * echo's in which two may be agrupated 
 * as a single offer (updated version of a single offer,
 * single echo) or a new freshly received offer (recieved
 * from Offer_request, two possible echos).
 * 
 * Single/Updated Offer --> print_html(1)
 * New Offer --> print_html(0) 
 */
class Offer
{
    private $offer_id;
    private $ttl;

    private $source_iata_code;
    private $source_airport;
    private $source_terminal;
    private $destination_iata_code;
    private $destination_airport;
    private $destination_terminal;
    private $departing_at;  // Date and Time
    private $arriving_at;
    private $flight_class;
    private $flight_price;
    private $airline;

    public function __construct(
        $offer_id,
        $ttl,
        $source_iata_code,
        $source_airport,
        $source_terminal,
        $destination_iata_code,
        $destination_airport,
        $destination_terminal,
        $departing_at,
        $arriving_at,
        $flight_class,
        $flight_price,
        $airline
    ) {
        $this->offer_id = $offer_id;
        $this->ttl = $ttl;
        $this->source_iata_code = $source_iata_code;
        $this->source_airport = $source_airport;
        $this->source_terminal = $source_terminal;
        $this->destination_iata_code = $destination_iata_code;
        $this->destination_airport = $destination_airport;
        $this->destination_terminal = $destination_terminal;
        $this->departing_at = $departing_at;
        $this->arriving_at = $arriving_at;
        $this->flight_class = $flight_class;
        $this->flight_price = $flight_price;
        $this->airline = $airline;
    }

    public function get_offer_id() 
    {
        return $this->offer_id;
    }

    // 0 -> normal offer | 1 -> single offer
    public function print_html($single_offer)
    {
        $trips = count($this->source_iata_code);
        if ($this->flight_class == "Premium_Economy") {
            $this->flight_class = "P.Economy";
        }

        if ($single_offer):
            console_log('[*] Printing Single Offer | Offer ttl: ' . $this->ttl);

            $init_script = '<script> document.addEventListener("DOMContentLoaded", function(event) { ';

            if ($trips === 1) {
                $init_script = $init_script . 'document.getElementById("sub_flights").style.display = "none"; ';
            } else {    // TODO: Refactor code (Instead of printing index 0 and then using while, set index to 0).
                // subtrip
                $index = 1;
                $sub_flights_code = 'document.getElementById("sub_flights").onclick = function(event) { ';
                while ($index < $trips) {
                    $depart_date_string = substr($this->departing_at[$index], 0, 10) . "  " . substr($this->departing_at[$index], 11, 5);
                    $arrive_date_string = substr($this->arriving_at[$index], 0, 10) . "  " . substr($this->arriving_at[$index], 11, 5);
                    $flight_duration = $this->get_flight_duration($depart_date_string, $arrive_date_string);
                    $init_script = $init_script . 'document.getElementById("flight_info").innerHTML += "<div id=\'flight_' . $index . '\' class=\'flight_info_content\'><div class=\'entry top\'><div class=\'title\'>Source:</div><div id=\'entry-source\' class=\'text imp\'>' . $this->source_iata_code[$index] . '</div></div><div class=\'entry top\'><div class=\'title\'>Destination:</div><div id=\'entry-dest\' class=\'text imp\'>' . $this->destination_iata_code[$index] . '</div></div><div class=\'entry top\'><div class=\'title\'>Dep Date:</div><div id=\'entry-dep_date\' class=\'text imp\'>' . $depart_date_string . '</div></div><div class=\'entry top\'><div class=\'title\'>Arr Date:</div><div id=\'entry-arr_date\' class=\'text imp\'>' . $arrive_date_string . '</div></div><div class=\'entry top\'><div class=\'title\'>Flight time:</div><div id=\'entry-flight_time\' class=\'text imp\'>' . $flight_duration . '</div></div></div>"; document.getElementById("flight_' . $index . '").style.display = "none"; ';
                    $sub_flights_code = $sub_flights_code . 'document.getElementById("flight_' . $index . '").style.display = "flex"; ';
                    $index++;
                }
                $sub_flights_code = $sub_flights_code . 'document.getElementById("sub_flights").style.display = "none"; }; ';
                $init_script = $init_script . $sub_flights_code;
            }

            $depart_date_string = substr($this->departing_at[0], 0, 10) . "  " . substr($this->departing_at[0], 11, 5);
            $arrive_date_string = substr($this->arriving_at[0], 0, 10) . "  " . substr($this->arriving_at[0], 11, 5);
            $flight_duration = $this->get_flight_duration($depart_date_string, $arrive_date_string);

            echo $init_script . 'document.getElementById("entry-source").innerHTML += "' . $this->source_iata_code[0] . '"; document.getElementById("entry-dest").innerHTML += "' . $this->destination_iata_code[0] . '"; document.getElementById("entry-dep_date").innerHTML += "' . $depart_date_string . '"; document.getElementById("entry-arr_date").innerHTML += "' . $arrive_date_string . '"; document.getElementById("entry-flight_time").innerHTML += "' . $flight_duration . '"; }); </script>';
        else:
            if ($trips === 1) { // One-way
                $airline = $this->get_airlines_div($this->airline);
                $departing_time = substr($this->departing_at[0], 11, 5);
                $flight_duration = $this->get_flight_duration($this->departing_at[0], $this->arriving_at[0]);

                console_log('[*] Printing flight from: ' . $this->source_iata_code[0] . '  to  ' . $this->destination_iata_code[0] . ' | Offer ttl: ' . $this->ttl);

                echo
                '<link rel="stylesheet" href="./style_results.css">
                <script>
                        document.addEventListener("DOMContentLoaded", function(event) {
                            document.getElementById("flightResults").innerHTML += "<div class=\'flightResult vcenter\'><div class=\'flightNo infoDiv\'>' . $airline . '</div><div class=\'flightDisplay vcenter\'><div class=\'location infoDiv\'><div class=\'label\'>SOURCE</div><div class=\'value\'>' . $this->source_iata_code[0] . '</div></div><div class=\'timeline\'><div class=\'symbol center\'><img src=\'https://i.imgrpost.com/imgr/2018/09/08/airplane.png\' alt=\'airplane.png\' border=\'0\' /></div><div class=\'duration center\'>' . $flight_duration . '</div></div><div class=\'location infoDiv\'><div class=\'label\'>DESTINATION</div><div class=\'value\'>' . $this->destination_iata_code[0] . '</div></div></div><div class=\'flightInfo infoDiv\'><div class=\'label\'>FLIGHT TIME</div><div class=\'value\'>' . $departing_time . '</div><div class=\'label\'>SEAT CLASS</div><div class=\'value\'>' . $this->flight_class . '</div></div><form method=\'post\'><div class=\'flightInfo infoDiv\'><input type=\'submit\' class=\'flight-price\' name=\'flight-price\' value=\'' . $this->flight_price . '\' style=\'background: #5B2A4C;\' /><input type=\'hidden\' name=\'offer_submit\' value=\'' . $this->offer_id  . '\'></div></form></div>";
                            console.log(document.getElementById("flightResults"));
                        });
                </script>';
            } else {    // Multiple trips and return flights (TODO: Integrate Multi trip flights).
                $div_id = rand();
                $flight_tag = rand();
                $departing_time = substr($this->departing_at[0], 11, 5);
                $airlines = $this->get_airlines_div(array_unique($this->airline));
                $middle_flights = $this->get_intermediate_flights($flight_tag);
                $middle_flights_scripts = $this->get_intermediate_flights_scripts($flight_tag);

                console_log('[*] Printing flight from: ' . IATA_FROM . '  to  ' . IATA_TO . ' + subflights | Offer ttl: ' . $this->ttl);

                echo
                '<link rel="stylesheet" href="./style_results.css">
                <script>
                        function showDiv' . $div_id . '() {
                            let elem = document.getElementById("subflight' . $div_id . '");
                            elem.style.display == "block" ? elem.style.display = "none" : elem.style.display = "block"; 
                        }

                        ' . $middle_flights_scripts . '

                        document.addEventListener("DOMContentLoaded", function(event) {
                            document.getElementById("flightResults").innerHTML += "<div class=\'flightResult vcenter\'><div id=\'airlines\' class=\'flightNo infoDiv\'>' . $airlines . '</div><div class=\'flightDisplay vcenter\'><div class=\'location infoDiv\'><div class=\'label\'>SOURCE</div><div class=\'value\'>' . IATA_FROM . '</div></div><div class=\'timeline\'><div class=\'symbol center\'><img src=\'https://i.imgrpost.com/imgr/2018/09/08/airplane.png\' alt=\'airplane.png\' border=\'0\' /></div><div class=\'center\'><input class=\'flightsBt\' type=\'button\' name=\'answer\' value=\'Show Flights\' onclick=\'showDiv' . $div_id . '()\' /></div></div><div class=\'location infoDiv\'><div class=\'label\'>DESTINATION</div><div class=\'value\'>' . IATA_TO . '</div></div></div><div class=\'flightInfo infoDiv\'><div class=\'label\'>FLIGHT TIME</div><div class=\'value\'>' . $departing_time . '</div><div class=\'label\'>SEAT CLASS</div><div class=\'value\'>' . $this->flight_class . '</div></div><form method=\'post\'><div class=\'flightInfo infoDiv\'><input type=\'submit\' class=\'flight-price\' name=\'flight-price\' value=\'' . $this->flight_price . '\' style=\'background: #5B2A4C;\' /><input type=\'hidden\' name=\'offer_submit\' value=\'' . $this->offer_id  . '\'></div></form></div><div id=\'subflight' . $div_id . '\' class=\'flightResults\' style=\'display:none; margin-left: 2.6em; width: -moz-fit-content; width: fit-content;\'>' . $middle_flights . '</div>";
                            console.log(document.getElementById("flightResults"));
                            console.log(document.getElementById("subflight' . $div_id . '"));
                        });
                </script>';
            }
        endif;
    }

    // Func. to compare $input_airline with airlines (private class array). 
    public function compare_airline($input_airline) 
    {
        $index = 0;
        while($index < count($this->airline)) {
            // console_log('Offer airline: ' . $this->airline[$index]);
            if ($this->airline[$index] !== $input_airline) {
                return 0;
            }
            $index++;
        }
        return 1;
    }

    /**
     *  Get flight duration 
    */
    private function get_flight_duration($departing_at, $arriving_at)
    {
        $depart_date_string = substr($departing_at, 0, 10) . "  " . substr($departing_at, 11, 5);
        $arrive_date_string = substr($arriving_at, 0, 10) . "  " . substr($arriving_at, 11, 5);
        $departing_date = new DateTime($depart_date_string);
        $arriving_date = new DateTime($arrive_date_string);

        $interval = $departing_date->diff($arriving_date);
        return $interval->format("%H:%I:%S");
    }

    /**
     *  Func. that returns a html img tag based on the airlines given (input). 
    */
    private function get_airlines_div($airlines)
    {
        $div = "";
        while (count($airlines) !== 0) { 
            $airline = array_pop($airlines);    // https://pynkiwi.wpcomstaging.com/wp-content/uploads/2021/09/' . $airline_logo_name . '.png
            $airline_logo_name = $this->get_airline_logo_name($airline);
            
            if ($airline_logo_name !== '') {
                $img_tag = '<img title=\'' . $airline . '\' height=\'24px\' width=\'24px\' src=\'https://pynkiwi.wpcomstaging.com/wp-content/uploads/2021/09/' . $airline_logo_name . '.png\'>';
                $div = $div . $img_tag;
            }
        }
        return $div;
    }

    /**
     * Aux. func. to get_airlines_div
     */
    private function get_airline_logo_name($airline) {
        switch ($airline) {
            case 'Duffel Airways':
                return 'ZZ';
            case 'Aegean Airlines':
                return 'A3';
            case 'Olympic Air':
                return 'OA';
            case 'American Airlines':
                return 'AA';
            case 'British Airways':
                return 'BA';
            case 'Copa Airlines':
                return 'CM';
            case 'easyjet':
                return 'U2';
            case 'Iberia':
                return 'IB';
            case 'Iberia Airlines':
                return 'IB';
            case 'Air Nostrum':
                return 'IB';
            case 'Iberia Express':
                return 'I2';
            case 'Lufthansa':
                return 'LH';
            case 'SWISS':
                return 'LX';
            case 'Westjet':
                return 'WS';
            case 'Vueling':
                return 'VY';
            case 'Level':
                return 'LV';
            case 'Transavia':
                return 'TO';
            case 'Singapore Airlines':
                return 'SQ';
            case 'Qatar Airways':
                return 'QR';
            case 'Austrian':
                return 'OS';
            case 'Brussels Airlines':
                return 'SN';
            case 'Eurowings Discover':
                return '4Y';
            case 'Hahn Air':
                return 'HR';
            case 'Aer Lingus':
                return 'EL';
            case 'Turkish Airlines':
                return 'TK';
            default:
                console_log('Airline Logo name not found');
                return '';
        }
    }

    /**
     * Returns html code that represents the intermediate
     * flights shown.
     */
    private function get_intermediate_flights($flight_tag)
    {
        $div = "";
        $index = 0;
        while (count($this->source_iata_code) !== $index) {
            $departing_date = substr($this->departing_at[$index], 0, 10);
            $departing_time = substr($this->departing_at[$index], 11, 5);
            $arriving_date = substr($this->arriving_at[$index], 0, 10);
            $arriving_time = substr($this->arriving_at[$index], 11, 5);
            $flight_duration = $this->get_flight_duration($this->departing_at[$index], $this->arriving_at[$index]);
            $flight_id = $flight_tag . '_' . $index;
            $flight_info_id = $flight_id . '_c';
            $div = $div . '<div id=\'flight' . $flight_id . '\' class=\'flightResult vcenter\' style=\'cursor: pointer;\' onclick=\'showDiv' . $flight_id . '()\' ><div class=\'flightNo infoDiv\'><div class=\'value\'>' . $this->airline[$index] . '</div></div><div class=\'flightDisplay vcenter\'><div class=\'location infoDiv\'><div class=\'label\'>SOURCE</div><div class=\'value\'>' . $this->source_iata_code[$index] . '</div></div><div class=\'timeline\'><div class=\'symbol center\'><img src=\'https://i.imgrpost.com/imgr/2018/09/08/airplane.png\' alt=\'airplane.png\' border=\'0\' /></div><div class=\'duration center\'>' . $flight_duration . '</div></div><div class=\'location infoDiv\'><div class=\'label\'>DESTINATION</div><div class=\'value\'>' . $this->destination_iata_code[$index] . '</div></div></div><div class=\'flightInfo infoDiv\'><div class=\'label\'>FLIGHT TIME</div><div class=\'value\'>' . $departing_time . '</div><div class=\'label\'>SEAT CLASS</div><div class=\'value\'>' . $this->flight_class . '</div></div></div><div id=\'flight' . $flight_info_id . '\' class=\'flightResultInfo vcenter\' style=\'display: none; cursor: pointer;\' onclick=\'showDiv' . $flight_info_id . '()\' ><div id=\'set-left\' class=\'flightInfo infoDiv\'><div class=\'labelc\'>DEP. DATE</div><div class=\'valuec\'>' . $departing_date . '</div><div class=\'labelc\'>DEP. TIME</div><div class=\'valuec\'>' . $departing_time . '</div></div><div class=\'location infoDiv\'><div class=\'labelc\'>TERMINAL</div><div class=\'valuec\'>' . $this->source_terminal[$index] . '</div><div class=\'valuec\'>' . $this->source_airport[$index] . '</div></div><div class=\'location infoDiv\'><div class=\'labelc\'>TERMINAL</div><div class=\'valuec\'>' . $this->destination_terminal[$index] . '</div><div class=\'valuec\'>' . $this->destination_airport[$index] . '</div></div><div id=\'set-right\' class=\'flightInfo infoDiv\'><div class=\'labelc\'>ARR. DATE</div><div class=\'valuec\'>' . $arriving_date . '</div><div class=\'labelc\'>ARR. TIME</div><div class=\'valuec\'>' . $arriving_time . '</div></div></div>';
            $index++;
        }
        return $div;
    }

    /**
     * Returns js code for the intermediate flights.
     */
    private function get_intermediate_flights_scripts($flight_tag) 
    {
        //  Subflight ID -> $flight_tag_$index
        //  Subflight Info ID -> $flight_tag_$index_c
        $div = "";
        $index = 0;
        while (count($this->source_iata_code) !== $index) {
            $flight_id = $flight_tag . '_' . $index;
            $flight_info_id = $flight_id . '_c';

            $div = $div . ' function showDiv' . $flight_id . '() { let elem1 = document.getElementById(\'flight' . $flight_id . '\'); elem1.style.display != \'none\' ? elem1.style.display = \'none\' : elem1.style.display = \'flex\'; let elem2 = document.getElementById(\'flight' . $flight_info_id . '\'); elem2.style.display == \'none\' ? elem2.style.display = \'flex\' : elem2.style.display = \'none\'; } function showDiv' . $flight_info_id . '() { let elem1 = document.getElementById(\'flight' . $flight_info_id  . '\'); elem1.style.display != \'none\' ? elem1.style.display = \'none\' : elem1.style.display = \'flex\'; let elem2 = document.getElementById(\'flight' . $flight_id  . '\'); elem2.style.display == \'none\' ? elem2.style.display = \'flex\' : elem2.style.display = \'none\'; } ';
            
            // $div = $div . ' document.addEventListener("DOMContentLoaded", function() { document.getElementById("flight' . $flight_id . '").addEventListener("click", function() {document.getElementById("flight' . $flight_id . '").style.display = "none"; document.getElementById("flight' . $flight_info_id . '").style.display = "flex";}); document.getElementById("flight' . $flight_info_id . '").addEventListener("click", function() {document.getElementById("flight' . $flight_id . '").style.display = "flex"; document.getElementById("flight' . $flight_info_id . '").style.display = "none";});});';
            $index++;
        }
        return $div;
    }
}

/*
    TODO:
        --> Error Handeling
*/
/** ###### Duffel Offer Request ######
 * Prerequisite: Slices, Passengers, Cabin Class
 * 
 * Offer request class used
 * to send the main POST request
 * to Duffel that returns the 
 * available offers that follow
 * the given slices/passengers/cabin class
 * constraints. 
 * 
 * const MAX_OFFERS is used to
 * limit the number of offers returned.
 */
class Offer_request
{
    private $slices;
    private $passengers;
    private $cabin_class;
    private $offer_request_id;
    private $offers;
    private const MAX_OFFERS = 5;

    public function __construct($slices, $passengers, $cabin_class)
    {
        $this->slices = $slices;
        $this->passengers = $passengers;
        $this->cabin_class = $cabin_class;
        $this->offers = array();
        $this->offer_request_id = "";
    }

    /**
     * Get payload being sent to Duffel.
     */
    public function get_post_data()
    {
        $post_data = array(
            'data' => array(
                'slices' => $this->slices,
                'passengers' => $this->passengers,
                'cabin_class' => $this->cabin_class
            )
        );
        return json_encode($post_data);
    }

    public function get_offer_request_id() {
        return $this->offer_request_id;
    }

    /**
     * Returns Offers (class array), sends
     * an offer request (POST) to Duffel,
     * the payload must include slices,
     * passengers, and cabin_class.
     */
    public function get_offer_request()
    {
        $url = "https://api.duffel.com/air/offer_requests?return_offers=true";
        $header = array(
            'Accept-Encoding: gzip',
            'Accept: application/json',
            'Content-Type: application/json',
            'Duffel-Version: beta',
            'Authorization: Bearer duffel_test__CCC-2IpAyTjsoCzktXw_9Aaf3BPq8O26Tff5rzc1F0'
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
            console_log('[*] Error getting Duffel offer request - ' . $err);
            curl_close($ch);
            error_msg();
        } else {
            console_log('[*] Offer request successfully created');
            $response = gzdecode($res);
            $resp_decoded = json_decode($response);
            foreach ($resp_decoded as $_ => $data) {
                return $this->walk_data($data);
            }
        }
        curl_close($ch);
    }

    /**
     * Get TTL based on input, Offers expire over time.
     */
    public function get_offer_ttl($offer_created_at, $offer_expires_at)
    {
        $created_at_date_string = substr($offer_created_at, 0, 10) . "  " . substr($offer_created_at, 11, 5);
        $expires_at_date_string = substr($offer_expires_at, 0, 10) . "  " . substr($offer_expires_at, 11, 5);
        $created_at_date = new DateTime($created_at_date_string);
        $expires_at_date = new DateTime($expires_at_date_string);

        return $created_at_date->diff($expires_at_date);
    }

    /*
        NOTES:
        One-way: "offers"[key] --> "slices" ---> "segments" (1)   
        Return: "offers"[key] ---> "slices" ---> "segments" (2)
        One offer may include more than 1 segment/trips (return trip, 2 segments).
             \
              --> 1 total_amount per offer
    */
    /**
     * Function used to transverse
     * the data received from Duffel.
     * Using this data, Offers are 
     * created and saved in a global array
     * ($offers).
     */
    private function walk_data($data)
    {
        // ### Offers ###
        $count = 0;
        // ---  ####  ---

        // $slices = $data->slices;
        // $passen = $data->passengers;
        $offers = $data->offers;
        $this->offer_request_id = $data->id;

        console_log('\t- Offers received: ' . count($offers));
        foreach($offers as $_ => $v1) {
            // ### Offer Data ###
            $offer_id = "";
            $offer_created_at = "";
            $offer_expires_at = "";
            $source_iata_code = array();
            $source_airport = array();
            $source_terminal = array();
            $destination_iata_code = array();
            $destination_airport = array();
            $destination_terminal = array();
            $departing_at = array();
            $arriving_at = array();
            $total_amount = "";
            $total_currency = "";
            $airline = array();
            // ---    ####    ---
            foreach ($v1 as $k2 => $v2) {
                if ($k2 === "slices") {
                    foreach ($v2 as $_ => $v3) {
                        foreach ($v3 as $k4 => $v4) {
                            if ($k4 === "segments") {
                                foreach ($v4 as $_ => $v5) {
                                    foreach ($v5 as $k6 => $v6) {
                                        if ($k6 === "origin_terminal") {
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
                                        } else if ($k6 === "arriving_at") { // Last entry scanned 
                                            array_push($arriving_at, $v6);
                                        } else if ($k6 === "operating_carrier") {
                                            foreach ($v6 as $k7 => $v7) {
                                                if ($k7 === "name") {
                                                    array_push($airline, $v7);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else if ($k2 === "total_currency") {
                    $total_currency = $v2;
                } else if ($k2 === "total_amount") {
                    $total_amount = $v2 . ' ' . $total_currency;
                } else if ($k2 === "id") {
                    $offer_id = $v2;
                } else if ($k2 === "expires_at") {
                    $offer_expires_at = $v2;
                } else if ($k2 === "created_at") {
                    $offer_created_at = $v2;
                }
            }

            $count++;
            $offer_ttl = get_offer_ttl($offer_created_at, $offer_expires_at);
            //console_log("Offer id: " . $offer_id);
            //console_log("Offer ttl: " . $offer_ttl->format("%H:%I:%S"));
            $offer = $this->create_offer(
                $offer_id,
                $offer_ttl,
                $source_iata_code,
                $source_airport,
                $source_terminal,
                $destination_iata_code,
                $destination_airport,
                $destination_terminal,
                $departing_at,
                $arriving_at,
                $airline,
                $total_amount
            );
            array_push($this->offers, $offer);
            if ($count === self::MAX_OFFERS) {
                console_log('\t- Reached max offers');
                break;
            }
        }

        return $this->offers;
    }

    /**
     * Offer information sanitization
     * and creation using the Offer class.
     */
    private function create_offer($offer_id, $offer_ttl, $source_iata_code, $source_airport, $source_terminal, $destination_iata_code, $destination_airport, $destination_terminal, $departing_at, $arriving_at, $airline, $total_amount)
    {
        $size_arr = count($source_iata_code);
        if (
            $size_arr !== count($destination_iata_code) || $size_arr !== count($departing_at)
            || $size_arr !== count($arriving_at) || $size_arr !== count($airline) || $total_amount === ""
        ) {
            alert('Error syncronizing offer data');
            debug_offer_data($source_iata_code, $destination_iata_code, $departing_at, $arriving_at, $airline, $total_amount);
            error_msg();
        }

        return new Offer(
            $offer_id,
            $offer_ttl,
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
            $airline
        );
    }

}

// ###### Duffel Offer request arguments ######
/** ###### Slices ######
 * Slices class, holds each
 * flight in an offer.
 * 
 * json_slice holds the info.
 * about a single flight in which
 * origin and destination are both 
 * represented using the IATA code 
 * of the neares airport based on the
 * input city (WordPress, flight-booking),
 * departure date is also needed.
 */
class Slices
{
    private $slices_list;
    private $json_slice;

    public function __construct()
    {
        $this->slices_list = array();
    }

    public function add_slice($iata_from, $iata_to, $dep_date)
    {
        $this->json_slice = new stdClass();
        $this->json_slice->origin = $iata_from;
        $this->json_slice->destination = $iata_to;
        $this->json_slice->departure_date = $dep_date;
        array_push($this->slices_list, $this->json_slice);
    }

    public function return_slices()
    {
        return $this->slices_list;
    }
}

/** ###### Passengers ######
 * Passenger class, used to represent
 * each passenger using json_age.
 * 
 * json_age holds each passengers age
 * based on input ($adults -> number of
 * adults, $children -> number of children,
 * $children_age -> array of integers).
 */
class Passengers
{
    private $age_arr;
    private $json_age;

    public function __construct()
    {
        $this->age_arr = array();
    }

    public function add_passenger($adults, $children, $children_age)
    {        
        if (!empty($adults)) {
            while ($adults--) {
                $this->json_age = new stdClass();
                $this->json_age->age = 18;
                array_push($this->age_arr, $this->json_age);
            }
        }
        if (!empty($children)) {
            $index = 0;
            while($children--) {
                $age_str = substr($children_age, $index, 2);
                $age = intval($age_str); // ex1: 12, 14 | ex2: 0, 4, 5
                if ($age > 18) {
                    alert('Children Age not valid, format ex: 12, 14, 17.');
                    exit(0);
                }
                $this->json_age = new stdClass();
                $this->json_age->age = $age;
                array_push($this->age_arr, $this->json_age);
                if (strpos($age_str, ',')) {
                    $index += 2;
                } else {
                    $index += 4;
                }
            }
        }
    }

    public function return_passengers()
    {
        return $this->age_arr;
    }
}

// ###### EOF ######
?>