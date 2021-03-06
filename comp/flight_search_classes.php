<?php
// Copyright 2021 - PYNKIWI

// TODO: Some airlines don't have associated pics
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

    private $baggage_per_sli; // slice -> set of segments

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
        $airline,
        $baggage
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
        $this->baggage_per_sli = $baggage;
    }

    public function get_offer_id() 
    {
        return $this->offer_id;
    }

    /**
     * Prints relevant html code that represents
     * the offer in different areas of the WP
     * website.
     * Args:
     *  $single_offer (boolean): 
     *      0 --> normal offer (Offers in /flight-booking)
     *      1 --> single offer (Offers in /account, after pay div) 
     *  
     *  $hide_offer (boolean):
     *      NOTE: This arg is only triggered if $single_offer = 0.
     *      0 --> print offer 
     *      1 --> print offer but hide the contents (display: none).
     */
    public function print_html($hide_offer)
    {
        $trips = count($this->source_iata_code);
        if ($this->flight_class == "premium_economy") {
            $this->flight_class = "P.Economy";
        } else if ($this->flight_class == "economy") {
            $this->flight_class = "Economy";
        } else if ($this->flight_class == "first") {
            $this->flight_class = "First";
        } else if ($this->flight_class == "business") {
            $this->flight_class = "Business";
        }

        $opt = '';
        if ($hide_offer) {
            $opt = " style=\'display:none;\' ";
            console_log('[*] Offer from: ' . IATA_FROM . '  to  ' . IATA_TO . ' | Offer ttl: ' . $this->ttl);
        } else {
            console_log('[*] Printing offer from: ' . IATA_FROM . '  to  ' . IATA_TO . ' | Offer ttl: ' . $this->ttl);
        }
            
        $baggage_html = $this->check_baggage_per_slice(0);
        if ($trips === 1) { // One-way
            $airline = $this->get_airlines_div($this->airline);
            $departing_time = substr($this->departing_at[0], 11, 5);
            $flight_duration = $this->get_flight_duration($this->departing_at[0], $this->arriving_at[0]);
            $html_parser = new HTML_PARSER(
                'flight_with_no_subflights.html', 
                array(
                    '[OPT]', '[AIRLINE]', '[SOURCE_IATA_CODE]', '[FLIGHT_DURATION]',
                    '[DESTINATION_IATA_CODE]', '[DEPARTING_TIME]', '[SEAT_CLASS]',
                    '[FLIGHT_PRICE]', '[OFFER_ID]', '[BAGGAGE_HTML]'
                ), 
                array(
                    $opt, $airline, $this->source_iata_code[0],
                    $flight_duration, $this->destination_iata_code[0], $departing_time,
                    $this->flight_class, $this->flight_price, $this->offer_id, $baggage_html
                )
            );

            echo
            '<link rel="stylesheet" href="./style/style_results.css">
            <script>
                    document.addEventListener("DOMContentLoaded", function(event) {
                        document.getElementById("flightResults").innerHTML += "'.$html_parser->parse().'"
                    });
            </script>';
        } else {    // Multiple trips and return flights (TODO: Integrate Multi trip flights).
            $div_id = rand();
            $flight_tag = rand();
            $departing_time = substr($this->departing_at[0], 11, 5);
            $airlines = $this->get_airlines_div(array_unique($this->airline));
            $middle_flights = $this->get_intermediate_flights($flight_tag);
            $middle_flights_scripts = $this->get_intermediate_flights_scripts($flight_tag);
            $html_parser = new HTML_PARSER(
                'flight_with_subflights.html',
                array(
                    '[OPT]', '[AIRLINES]', '[IATA_FROM]', '[DIV_ID]',
                    '[IATA_TO]', '[DEPARTING_TIME]', '[SEAT_CLASS]',
                    '[FLIGHT_PRICE]', '[OFFER_ID]', '[BAGGAGE_HTML]',
                    '[MIDDLE_FLIGHTS]'
                ),
                array(
                    $opt, $airlines, IATA_FROM, $div_id, 
                    IATA_TO, $departing_time, $this->flight_class, 
                    $this->flight_price, $this->offer_id, $baggage_html,
                    $middle_flights
                )
            );

            echo
            '<link rel="stylesheet" href="./style/style_results.css">
            <script>
                function showDiv' . $div_id . '() {
                    let elem = document.getElementById("subflight' . $div_id . '");
                    elem.style.display == "block" ? elem.style.display = "none" : elem.style.display = "block"; 
                }

                ' . $middle_flights_scripts . '

                document.addEventListener("DOMContentLoaded", function(event) {
                    document.getElementById("flightResults").innerHTML += "'.$html_parser->parse().'";
                });
            </script>';
            /*
                console.log(document.getElementById("flightResults"));
                console.log(document.getElementById("subflight' . $div_id . '"));
            */
        }
    }

    public function print_current_offer_html()
    {
        $trips = count($this->source_iata_code);
        $script = '<script> document.addEventListener("DOMContentLoaded", function(event) { ';
        if ($trips === 1) {
            // one way
        } else {
            
        }
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
            $airline = array_pop($airlines);    // https://pynkiwi.com/wp-content/uploads/2021/09/' . $airline_logo_name . '.png
            $airline_logo_name = $this->get_airline_logo_name($airline);
            
            if ($airline_logo_name !== '') {
                $img_tag = '<img title=\'' . $airline . '\' height=\'24px\' width=\'24px\' src=\'https://pynkiwi.com/wp-content/uploads/2021/09/' . $airline_logo_name . '.png\'>';
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
                //console_log('Airline Logo name not found');
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
            $html_parser = new HTML_PARSER(
                'sub_flight_info.html',
                array(
                    '[FLIGHT_ID]', '[AIRLINE]', '[SOURCE_IATA_CODE]',
                    '[FLIGHT_DURATION]', '[DESTINATION_IATA_CODE]', '[DEPARTING_TIME]',
                    '[SEAT_CLASS]', '[FLIGHT_INFO_ID]', '[DEPARTING_DATE]', 
                    '[SOURCE_TERMINAL]', '[SOURCE_AIRPORT]', '[DESTINATION_TERMINAL]', 
                    '[DESTINATION_AIRPORT]', '[ARRIVAL_DATE]', '[ARRIVAL_TIME]'
                ),
                array(
                    $flight_id, $this->airline[$index], $this->source_iata_code[$index],
                    $flight_duration, $this->destination_iata_code[$index], $departing_time,
                    $this->flight_class, $flight_info_id, $departing_date,
                    $this->source_terminal[$index], $this->source_airport[$index], $this->destination_terminal[$index],
                    $this->destination_airport[$index], $arriving_date, $arriving_time
                )
            );
            $div = $div . $html_parser->parse();
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

    /**
     * Check if $this->baggage_per_sli is fully
     * syncronized (same baggage allocations for
     * all passengers), if so print html code based
     * on argument (Auxilatry -> class Baggages -> print_baggage_html).
     */
    public function check_baggage_per_slice($single_offer) {
        $total_baggages_per_sli = count($this->baggage_per_sli);

        if ($total_baggages_per_sli > 1) {
            $fst_sli_baggage = $this->baggage_per_sli[0];
            $index = 1;
            while ($index < $total_baggages_per_sli) {
                if (count($fst_sli_baggage) !== count($this->baggage_per_sli[$index])) {
                    console_log('\t- Exception, baggage allocation not syncronized between slices');
                    return '';
                }
                $index++;
            }
        }
        return $this->baggage_per_sli[0]->print_baggage_html($single_offer);
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
    private const MAX_OFFERS = 50;
    private const MIN_OFFERS = 5;

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
        // $post_data = array(
        //     'data' => array(
        //         'slices' => $this->slices,
        //         'passengers' => $this->passengers,
        //         'cabin_class' => $this->cabin_class
        //     )
        // );
        // return json_encode($post_data);
        return array(
            'slices' => $this->slices,
            'passengers' => $this->passengers,
            'cabin_class' => $this->cabin_class
        );
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
        $req = new CURL_REQUEST('POST', 'https://api.duffel.com/air/offer_requests?return_offers=true', $this->get_post_data());
        $resp_decoded = $req->send_duffel_request();
        foreach ($resp_decoded as $_ => $data) {
            return $this->walk_data($data);
        }
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

        $this->print_num_offers(count($offers));
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
            $baggage_per_sli = array(); // per sli_id
            // ---    ####    ---
            foreach ($v1 as $k2 => $v2) {
                if ($k2 === "slices") {
                    foreach ($v2 as $_ => $v3) {
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
                                            foreach($v6 as $_ => $passenger) {
                                                // ### Baggage Data ###
                                                $pas_id = "";
                                                $types = array();
                                                $quans = array();
                                                // ---    ####    ---
                                                foreach($passenger as $pass_key => $pass_val) {
                                                    if ($pass_key === "passenger_id") {
                                                        $pas_id = $pass_val;
                                                        array_push($pas_ids, $pas_id);
                                                    } else if ($pass_key === "baggages") {
                                                        foreach($pass_val as $_ => $baggage) {
                                                            foreach($baggage as $bag_key => $bag_val) {
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
                $total_amount,
                $baggage_per_sli
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
    private function create_offer($offer_id, $offer_ttl, $source_iata_code, $source_airport, $source_terminal, $destination_iata_code, $destination_airport, $destination_terminal, $departing_at, $arriving_at, $airline, $total_amount, $baggage)
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
            $airline,
            $baggage
        );
    }

    /**
     * Print page count (container --> page_count_index).
     */
    private function print_num_offers($count_offers) {  // TODO: Fix pagination bug (not showing correctly)
        if ($count_offers >= self::MAX_OFFERS) {
            $received_offers = $count_offers;
            $count_offers = self::MAX_OFFERS;
            console_log('\t- Offers received: ' . $received_offers . ' | Actual: ' . $count_offers);
        } else {
            console_log('\t- Offers received: ' . $count_offers);
            if ($count_offers === 1) {
                echo '<script> document.addEventListener("DOMContentLoaded", function(event) { document.getElementById("page_count").innerHTML = "1/'.$count_offers.'"; document.getElementById("page_count_index").style.display = "inline-flex"; }); </script>';
                return;
            } else if ($count_offers < self::MIN_OFFERS) {
                echo '<script> document.addEventListener("DOMContentLoaded", function(event) { document.getElementById("page_count").innerHTML = "'.$count_offers.'/'.$count_offers.'"; document.getElementById("page_count_index").style.display = "inline-flex"; }); </script>';
                return;
            }
        }
        echo '<script> document.addEventListener("DOMContentLoaded", function(event) { document.getElementById("page_count").innerHTML = "5/'.$count_offers.'"; document.getElementById("page_count_index").style.display = "inline-flex"; }); </script>';
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
