<?php
$hashmap_offers = array();  // Global offers hasmap (index -> offer id, value -> offer)
if ($_POST['submit-search'] === "SEARCH FLIGHTS") {
    $first_date = $_POST['input-date-first'];
    $second_date = $_POST['input-date-second'];

    $from_text = $_POST['flight-depart-text'];
    $to_text = $_POST['flight-arrival-text'];

    $adults = $_POST['adults'];
    $children = $_POST['children'];
    $children_age = $_POST['children-age'];
    $cabin_class = $_POST['class-type'];

    $airline_name = $_POST['airline-name'];

    if (empty($from_text) || empty($to_text) || empty($first_date)) {
        alert('Information missing');
        exit(0);
    } else {
        if (
            !preg_match("#[a-zA-Z]+#", $from_text) || !preg_match("#[a-zA-Z]+#", $to_text) ||
            preg_match("#[0-9]#", $from_text) || preg_match("#[0-9]#", $to_text)
        ) {
            alert('Input data not valid');
            exit(0);
        }
        $from_text = str_replace(' ', '&', $from_text);
        $to_text = str_replace(' ', '&', $to_text);
    }

    $geo_arr_from = get_lat_lon($from_text);
    $geo_arr_to = get_lat_lon($to_text);
    $iata_code_from = get_iata_code($from_text, $geo_arr_from[0], $geo_arr_from[1]);
    $iata_code_to = get_iata_code($to_text, $geo_arr_to[0], $geo_arr_to[1]);

    //$iata_code_from = 'OPO';
    //$iata_code_to = 'YYZ'; // MAD

    // Define constants
    define("IATA_FROM", $iata_code_from);
    define("IATA_TO", $iata_code_to);
    define("MAX_OFFERS_PER_PAGE", 5);
    // ***

    $passengers = new Passengers();
    $passengers->add_passenger($adults, $children, $children_age);
    $passengers_list = $passengers->return_passengers();

    $slices = new Slices();

    if (empty($second_date)) {
        $slices->add_slice($iata_code_from, $iata_code_to, $first_date);
    } else if (check_input_dates($first_date, $second_date)) {
        $slices->add_slice($iata_code_from, $iata_code_to, $first_date);
        $slices->add_slice($iata_code_to, $iata_code_from, $second_date);
    }

    $slices_list = $slices->return_slices();
    // debug_log($iata_code_from, $iata_code_to, $first_date, $second_date, $slices_list, $passengers_list);

    $offer_request = new Offer_request($slices_list, $passengers_list, $cabin_class);
    $offers = $offer_request->get_offer_request();
    foreach ($offers as $index => $offer) {
        if ($airline_name === "None" || $offer->compare_airline($airline_name)) {
            // console_log("Input airline name: " . $airline_name);
            if (count($hashmap_offers) >= MAX_OFFERS_PER_PAGE) {
                $offer->print_html(0, 1);
            } else {
                $offer->print_html(0, 0);
            }
            
            $hashmap_offers[$offer->get_offer_id()] = $offer;
        }
    }
    //$offers[0]->debug_baggage();
}

?>