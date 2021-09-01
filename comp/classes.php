<?php
// ###### Offer ######
/*
    TODO:
        --> Add payment

            -> Before payment passenger info should be double checked
               with information received from walk_data().
*/

class Offer
{
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

    public function print_html()
    { 

        $trips = count($this->source_iata_code);
        if ($this->flight_class == "Premium_Economy") {
            $this->flight_class = "P.Economy";
        }
        if ($trips === 1) {
            $departing_time = substr($this->departing_at[0], 11, 5);
            $flight_duration = $this->get_flight_duration($this->departing_at[0], $this->arriving_at[0]);

            console_log('Printing flight from: ' . $this->source_iata_code[0] . '  to  ' . $this->destination_iata_code[0]);

            echo
            '<link rel="stylesheet" href="./style_results.css">
            <script>
                    document.addEventListener("DOMContentLoaded", function(event) {
                        document.getElementById("flightResults").innerHTML += "<div class=\'flightResult vcenter\'><div class=\'flightNo infoDiv\'><div class=\'value\'>' . $this->airline[0] . '</div></div><div class=\'flightDisplay vcenter\'><div class=\'location infoDiv\'><div class=\'label\'>SOURCE</div><div class=\'value\'>' . $this->source_iata_code[0] . '</div></div><div class=\'timeline\'><div class=\'symbol center\'><img src=\'https://i.imgrpost.com/imgr/2018/09/08/airplane.png\' alt=\'airplane.png\' border=\'0\' /></div><div class=\'duration center\'>' . $flight_duration . '</div></div><div class=\'location infoDiv\'><div class=\'label\'>DESTINATION</div><div class=\'value\'>' . $this->destination_iata_code[0] . '</div></div></div><div class=\'flightInfo infoDiv\'><div class=\'label\'>FLIGHT TIME</div><div class=\'value\'>' . $departing_time . '</div><div class=\'label\'>SEAT CLASS</div><div class=\'value\'>' . $this->flight_class . '</div></div><form method=\'post\'><div class=\'flightInfo infoDiv\'><input type=\'submit\' class=\'flight-price\' name=\'flight-price\' value=\'PRICE ' . $this->flight_price . '\' style=\'background: #5B2A4C;\' /></div></form></div>";
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

            console_log('Printing flight from: ' . IATA_FROM . '  to  ' . IATA_TO);

            echo
            '<link rel="stylesheet" href="./style_results.css">
            <script>
                    function showDiv' . $div_id . '() {
                        let elem = document.getElementById("subflight' . $div_id . '");
                        elem.style.display == "block" ? elem.style.display = "none" : elem.style.display = "block"; 
                    }

                    document.addEventListener("DOMContentLoaded", function(event) {
                        document.getElementById("flightResults").innerHTML += "<div class=\'flightResult vcenter\'><div id=\'airlines\' class=\'flightNo infoDiv\'>' . $airlines . '</div><div class=\'flightDisplay vcenter\'><div class=\'location infoDiv\'><div class=\'label\'>SOURCE</div><div class=\'value\'>' . IATA_FROM . '</div></div><div class=\'timeline\'><div class=\'symbol center\'><img src=\'https://i.imgrpost.com/imgr/2018/09/08/airplane.png\' alt=\'airplane.png\' border=\'0\' /></div><div class=\'center\'><input class=\'flightsBt\' type=\'button\' name=\'answer\' value=\'Show Flights\' onclick=\'showDiv' . $div_id . '()\' /></div></div><div class=\'location infoDiv\'><div class=\'label\'>DESTINATION</div><div class=\'value\'>' . IATA_TO . '</div></div></div><div class=\'flightInfo infoDiv\'><div class=\'label\'>FLIGHT TIME</div><div class=\'value\'>' . $departing_time . '</div><div class=\'label\'>SEAT CLASS</div><div class=\'value\'>' . $this->flight_class . '</div></div><form method=\'post\'><div class=\'flightInfo infoDiv\'><input type=\'submit\' class=\'flight-price\' name=\'flight-price\' value=\'PRICE ' . $this->flight_price . '\' style=\'background: #5B2A4C;\' /></div></form></div><div id=\'subflight' . $div_id . '\' class=\'flightResults\' style=\'display:none; margin-left: 2.6em; width: -moz-fit-content; width: fit-content;\'>' . $middle_flights . '</div>";
                        console.log(document.getElementById("flightResults"));
                        console.log(document.getElementById("subflight' . $div_id . '"));
                    });

                    ' . $middle_flights_scripts . '
            </script>';
        }
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
            $div = $div . '<div class=\'value\'>' . array_pop($airlines) . '</div>';
        }
        return $div;
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
            $div = $div . '<div id=\'flight' . $flight_id . '\' class=\'flightResult vcenter\' style=\'cursor: pointer;\'><div class=\'flightNo infoDiv\'><div class=\'value\'>' . $this->airline[$index] . '</div></div><div class=\'flightDisplay vcenter\'><div class=\'location infoDiv\'><div class=\'label\'>SOURCE</div><div class=\'value\'>' . $this->source_iata_code[$index] . '</div></div><div class=\'timeline\'><div class=\'symbol center\'><img src=\'https://i.imgrpost.com/imgr/2018/09/08/airplane.png\' alt=\'airplane.png\' border=\'0\' /></div><div class=\'duration center\'>' . $flight_duration . '</div></div><div class=\'location infoDiv\'><div class=\'label\'>DESTINATION</div><div class=\'value\'>' . $this->destination_iata_code[$index] . '</div></div></div><div class=\'flightInfo infoDiv\'><div class=\'label\'>FLIGHT TIME</div><div class=\'value\'>' . $departing_time . '</div><div class=\'label\'>SEAT CLASS</div><div class=\'value\'>' . $this->flight_class . '</div></div></div><div id=\'flight' . $flight_info_id . '\' class=\'flightResultInfo vcenter\' style=\'display: none; cursor: pointer;\'><div id=\'set-left\' class=\'flightInfo infoDiv\'><div class=\'labelc\'>DEP. DATE</div><div class=\'valuec\'>' . $departing_date . '</div><div class=\'labelc\'>DEP. TIME</div><div class=\'valuec\'>' . $departing_time . '</div></div><div class=\'location infoDiv\'><div class=\'labelc\'>TERMINAL</div><div class=\'valuec\'>' . $this->source_terminal[$index] . '</div><div class=\'valuec\'>' . $this->source_airport[$index] . '</div></div><div class=\'location infoDiv\'><div class=\'labelc\'>TERMINAL</div><div class=\'valuec\'>' . $this->destination_terminal[$index] . '</div><div class=\'valuec\'>' . $this->destination_airport[$index] . '</div></div><div id=\'set-right\' class=\'flightInfo infoDiv\'><div class=\'labelc\'>ARR. DATE</div><div class=\'valuec\'>' . $arriving_date . '</div><div class=\'labelc\'>ARR. TIME</div><div class=\'valuec\'>' . $arriving_time . '</div></div></div>';
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
            $div = $div . 'document.addEventListener("DOMContentLoaded", function() { document.getElementById("flight' . $flight_id . '").addEventListener("click", function() {document.getElementById("flight' . $flight_id . '").style.display = "none"; document.getElementById("flight' . $flight_info_id . '").style.display = "flex";}); document.getElementById("flight' . $flight_info_id . '").addEventListener("click", function() {document.getElementById("flight' . $flight_id . '").style.display = "flex"; document.getElementById("flight' . $flight_info_id . '").style.display = "none";});});';
            $index++;
        }
        return $div;
    }
}

// ###### Duffel Offer Request ######
/*
    TODO:
        --> Error Handeling
        --> Before payment passenger info should be double checked
            with information received from walk_data().
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

        foreach ($data as $tag => $content) {
            if ($tag === "slices") {                         
                // General airport info
                continue;
            } else if ($tag === "offers") { // Refactor when done
                console_log('Offers received: ' . count($content));
                foreach ($content as $_ => $v1) {
                    // ### Offer Data ###
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
                            $total_amount = $v2 . $total_currency;
                        }
                    }
                    $count++;
                    $offer = $this->create_offer($source_iata_code, $source_airport, $source_terminal, 
                        $destination_iata_code, $destination_airport, $destination_terminal, 
                        $departing_at, $arriving_at, $airline, $total_amount
                    );
                    array_push($this->offers, $offer);
                    if ($count === self::MAX_OFFERS) {
                        console_log('Reached max offers');
                        break;
                    }
                }
            } else if ($tag === "passengers") {
                // Passengers info
                // ### Debug:
                //var_dump($content);
            } else if ($tag === "id") {
                $this->offer_request_id = $content;
            }
        }

        return $this->offers;
    }

    private function create_offer($source_iata_code, $source_airport, $source_terminal, $destination_iata_code, $destination_airport, $destination_terminal, $departing_at, $arriving_at, $airline, $total_amount)
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