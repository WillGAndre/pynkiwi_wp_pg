<?php

/*
    TODO:
        --> Add payment

            -> On payment button click open up html page.

                Use offer ID to get updated offer (on button clicked),
                then double check passenger info (input and returned by
                duffel), show legal notices (*1), get total_amount/
                total_currency and open a form for passenger info input 
                (email, phone num, etc, ..), this also inculdes passenger_id
                (returned by duffel in specific Offer).

                *1 - https://help.duffel.com/hc/en-gb/articles/360021056640

                Create class for offer payment, having:
                    - total amount (+ currency)
                    - total tax (+ currency)
                    - offer options (if available, etc)
                    - payment flag (if offer was purchased)
                    - Passenger Info (class)

            -> Before payment passenger info should be double checked
               with information received from walk_data().
*/

// ###### Single Offer ######
// Prerequisite: offer id

class Single_Offer
{
    private $offer_id;
    private $offer;
    private $offer_payment_info;
    private $passenger_info;

    public function __construct($offer_id)
    {
        $this->offer_id = $offer_id;
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
            // console_log('Error getting single offer - ' + $err);
        } else {
            // console_log('Got single offer successfully');
            $response = gzdecode($res);
            $resp_decoded = json_decode($response);

            $data = $resp_decoded->data;
            // var_dump($data);

            $this->walk_data($data);
        }
    }

    private function walk_data($data)
    {
        $this->passenger_info = $data->passengers;

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
            $total_amount, $tax_amount, $payment_req, 
            $pass_id_doc_req, $conds, $base_amount,
            $available_services, $allowed_pass_id_doc_types
        );
    }

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

    public function get_offer() {
        return $this->offer;
    }

    public function print_single_offer_html() {
        return $this->offer->print_html(1);
    }

    public function get_offer_payment_info() {
        return $this->offer_payment_info;
    }

    public function get_passengers_info() {
        return $this->passenger_info;
    }
}

// ###### Offer payment info ######
// TODO: Finish class
class Offer_Payment_Info
{
    private $total_amount;
    private $tax_amount; // plus currency
    private $payment_requirements;
    private $passenger_identity_documents_required;
    private $conditions;
    private $base_amount; // plus currency
    private $available_services;
    private $allowed_passenger_identity_document_types;

    public function __construct($total_amount, $tax_amount, $payment_req, $passenger_id_doc_req, $conds, $base_amount, $services, $allowed_pass_id_docs)
    {
        $this->total_amount = $total_amount;
        $this->tax_amount = $tax_amount;
        $this->payment_requirements = $payment_req;
        $this->passenger_identity_documents_required = $passenger_id_doc_req;
        $this->conditions = $conds;
        $this->base_amount = $base_amount;
        $this->available_services = $services;
        $this->allowed_passenger_identity_document_types = $allowed_pass_id_docs;
    }
}

// ###### Offer ######
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

    public function print_html($single_offer)
    {
        $trips = count($this->source_iata_code);
        if ($this->flight_class == "Premium_Economy") {
            $this->flight_class = "P.Economy";
        }

        if ($single_offer):
            console_log('Printing Single Offer');
            $depart_date_string = substr($this->departing_at[0], 0, 10) . "  " . substr($this->departing_at[0], 11, 5);
            $arrive_date_string = substr($this->arriving_at[0], 0, 10) . "  " . substr($this->arriving_at[0], 11, 5);
            $flight_duration = $this->get_flight_duration($depart_date_string, $arrive_date_string);
            // if $trips == 1 --> Remove subflights button
            // else --> print the rest of the trips
            echo
            '<script>
                document.addEventListener("DOMContentLoaded", function(event) { 
                    document.getElementById("entry-source").innerHTML += "' . $this->source_iata_code[0] . '";
                    document.getElementById("entry-dest").innerHTML += "' . $this->destination_iata_code[0] . '";
                    document.getElementById("entry-dep_date").innerHTML += "' . $depart_date_string . '";
                    document.getElementById("entry-arr_date").innerHTML += "' . $arrive_date_string . '";
                    document.getElementById("entry-flight_time").innerHTML += "' . $flight_duration . '";
                });
            </script>';
        else:
            if ($trips === 1) {
                $airline = $this->get_airlines_div($this->airline);
                $departing_time = substr($this->departing_at[0], 11, 5);
                $flight_duration = $this->get_flight_duration($this->departing_at[0], $this->arriving_at[0]);

                console_log('Printing flight from: ' . $this->source_iata_code[0] . '  to  ' . $this->destination_iata_code[0]);

                echo
                '<link rel="stylesheet" href="./style_results.css">
                <script>
                        document.addEventListener("DOMContentLoaded", function(event) {
                            document.getElementById("flightResults").innerHTML += "<div class=\'flightResult vcenter\'><div class=\'flightNo infoDiv\'>' . $airline . '</div><div class=\'flightDisplay vcenter\'><div class=\'location infoDiv\'><div class=\'label\'>SOURCE</div><div class=\'value\'>' . $this->source_iata_code[0] . '</div></div><div class=\'timeline\'><div class=\'symbol center\'><img src=\'https://i.imgrpost.com/imgr/2018/09/08/airplane.png\' alt=\'airplane.png\' border=\'0\' /></div><div class=\'duration center\'>' . $flight_duration . '</div></div><div class=\'location infoDiv\'><div class=\'label\'>DESTINATION</div><div class=\'value\'>' . $this->destination_iata_code[0] . '</div></div></div><div class=\'flightInfo infoDiv\'><div class=\'label\'>FLIGHT TIME</div><div class=\'value\'>' . $departing_time . '</div><div class=\'label\'>SEAT CLASS</div><div class=\'value\'>' . $this->flight_class . '</div></div><form method=\'post\'><div class=\'flightInfo infoDiv\'><input type=\'submit\' class=\'flight-price\' name=\'flight-price\' value=\'' . $this->flight_price . '\' style=\'background: #5B2A4C;\' /><input type=\'hidden\' name=\'offer_submit\' value=\'' . $this->offer_id  . '\'></div></form></div>";
                            console.log(document.getElementById("flightResults"));
                        });
                </script>';
            } else {
                $div_id = rand();
                $flight_tag = rand();
                $departing_time = substr($this->departing_at[0], 11, 5);
                $airlines = $this->get_airlines_div(array_unique($this->airline));
                $middle_flights = $this->get_intermediate_flights($flight_tag);
                $middle_flights_scripts = $this->get_intermediate_flights_scripts($flight_tag);

                console_log('Printing flight from: ' . IATA_FROM . '  to  ' . IATA_TO . ' + subflights ');

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

    private function get_flight_duration($departing_at, $arriving_at)
    {
        $depart_date_string = substr($departing_at, 0, 10) . "  " . substr($departing_at, 11, 5);
        $arrive_date_string = substr($arriving_at, 0, 10) . "  " . substr($arriving_at, 11, 5);
        $departing_date = new DateTime($depart_date_string);
        $arriving_date = new DateTime($arrive_date_string);

        $interval = $departing_date->diff($arriving_date);
        return $interval->format("%H:%I:%S");
    }

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

// ###### Duffel Offer Request ######
/*
    TODO:
        --> Error Handeling
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
     * Returns Offers (class array)
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
            console_log('Error getting Duffel offer request - ' . $err);
            curl_close($ch);
            error_msg();
        } else {
            console_log('Offer request successfully created');
            $response = gzdecode($res);
            $resp_decoded = json_decode($response);
            foreach ($resp_decoded as $_ => $data) {
                return $this->walk_data($data);
            }
        }
        curl_close($ch);
    }

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
    private function walk_data($data)
    {
        // ### Offers ###
        $count = 0;
        // ---  ####  ---

        // $slices = $data->slices;
        // $passen = $data->passengers;
        $offers = $data->offers;
        $this->offer_request_id = $data->id;

        console_log('Offers received: ' . count($offers));
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
                console_log('Reached max offers');
                break;
            }
        }



        return $this->offers;
    }

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

// ###### Slices ######

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

// ###### Passengers ######

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
                $age = intval(substr($children_age, $index, 2)); // ex: 12, 14
                if ($age < 1 || $age > 18) {
                    alert('Children Age not valid, format ex: 12, 14, 17.');
                    exit(0);
                }
                $this->json_age = new stdClass();
                $this->json_age->age = $age;
                array_push($this->age_arr, $this->json_age);
                $index += 4;
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