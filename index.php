<?php
// Copyright 2021 - PYNKIWI
/**
 * Plugin Name: Pynkiwi flights plugin
 * Plugin URI: -
 * Description: Pynkiwi flights plugin used to search/book flights
 * Version: 1.1.0
 * Author: William Pereira
 * Text Domain: wds-wwe
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/**
 * Add Stylesheet for flight search results
 */
function add_scripts()
{
    // wp_enqueue_style('plugin-stylesheet', plugins_url('style_results.css', __FILE__));
    wp_enqueue_style('plugin-flight-search-stylesheet', plugin_dir_url(__FILE__) . 'style/flight_search.css');
    wp_enqueue_style('plugin-flight-search-results-stylesheet', plugin_dir_url(__FILE__) . 'style/flight_search_results.css');
    wp_enqueue_script('plugin-flight-search-calender-scripts', plugin_dir_url(__FILE__) . 'scripts/flight_search_calender.js');
    wp_enqueue_script('plugin-flight-results-scripts', plugin_dir_url(__FILE__) . 'scripts/flight_results_pages.js');
    wp_enqueue_script('plugin-passenger-form-scripts', plugin_dir_url(__FILE__) . 'scripts/passenger_form.js');
}
add_action('wp_enqueue_scripts', 'add_scripts');

// Auxilary Functions 
include_once(plugin_dir_path(__FILE__) . 'comp/aux.php');
// Classes - Slices, Passengers, Offer Request, Offers
include_once(plugin_dir_path(__FILE__) . 'comp/classes.php');

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

    // Activate when there are more credits
    // $geo_arr_from = get_lat_lon($from_text);
    // $iata_code_from = get_iata_code($geo_arr_from[0], $geo_arr_from[1]);
    // $geo_arr_to = get_lat_lon($to_text);
    // $iata_code_to = get_iata_code($geo_arr_to[0], $geo_arr_to[1]);

    $iata_code_from = 'OPO';
    $iata_code_to = 'YYZ'; // MAD

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

// Offer ID --> $_POST['offer_submit']
/**
 * On Offer price click proc check_user.
 */
if (isset($_POST['flight-price'])) {
    add_action('init', 'check_user');
}

/**
 * Checks if user is logged in, if so,
 * the user is redirected to his account
 * page with a main offer dashboard
 * where he can further customize his offer
 * and pay. The redirect url is sent with the
 * offer_id saved in 'offer_submit'.
 */
function check_user()
{
    if (is_user_logged_in()) :
        console_log('user logged in');
        header('Location: https://pynkiwi.wpcomstaging.com/?' . http_build_query(array(
            'page_id' => 2475,
            'up_offer_id' => $_POST['offer_submit']
        )));
    else : // TODO: !
        header('Location: https://pynkiwi.wpcomstaging.com/?page_id=2478');
        console_log('user not logged in');
    endif;
}


// Trigger -> onclick of offer price button (redirect to account)
/**
 * Upon receiving a redirect with a up_offer_id as 
 * a query argument, print offer options information 
 * as well as payment info (single offer request).
 */
if (isset($_GET['up_offer_id'])) {
    $offer_id = $_GET['up_offer_id'];
    show_current_offer($offer_id);
    $single_offer = new Single_Offer($offer_id);
    $single_offer->get_single_offer();
    $single_offer->print_single_offer_html();
    $single_offer->print_single_offer_opts_html();
}

function show_current_offer($offer_id) { // TODO: Make current offer tab responsive
    $offer_id_html = ' document.getElementById("main_dash").innerHTML += "<div id=\'curr_offer_id\' style=\'display:none;\'>'.$offer_id.'</div>"; ';
    echo '<script> document.addEventListener("DOMContentLoaded", function(event) { document.getElementById("main_dash").style.display = "block"; '.$offer_id_html.' }); </script>';
}

// TODO: Set type of payment.
if (isset($_GET['pay_offer_id'])) { 
    $offer_id = $_GET['pay_offer_id'];
    $duffel_total_amount = $_GET['total_amount']; // Includes currency ; type --> balance
    
    $passengers = array();
    $services = array();
    $index = 0;
    while(isset($_GET['p_'.$index.'_id'])) {
        $query_format = 'p_'.$index.'_';
        $passenger = new stdClass();
        $full_name = explode(' ', $_GET[$query_format . 'name']);
        $gender = $_GET[$query_format . 'gender'];
        if ($gender === 'male') {
            $gender = 'm';
        } else {
            $gender = 'f';
        }
        
        $passenger->title = $_GET[$query_format . 'title'];
        $passenger->phone_number = $_GET[$query_format . 'phone'];
        if (isset($_GET[$query_format . 'infant_id'])) {
            $passenger->infant_passenger_id = $_GET[$query_format . 'infant_id'];
        }
        if (isset($_GET[$query_format . 'doc_id'])) {   // ATM Duffel only supports passport
            $passenger->unique_identifier = $_GET[$query_format . 'doc_id'];
            $passenger->type = "passport";                                  
            $passenger->issuing_country_code = country_to_code($_GET[$query_format . 'country']);
            $passenger->doc_exp_date = $_GET[$query_format . 'doc_exp_date'];
        }
        $passenger->id = $_GET[$query_format . 'id'];
        $passenger->given_name = $full_name[0];
        $passenger->gender = $gender;
        $passenger->family_name = $full_name[1];
        $passenger->email = $_GET[$query_format . 'email'];
        $passenger->born_on = $_GET[$query_format . 'birthday'];

        $ase_index = 0;
        while(isset($_GET[$query_format . 'ase_' . $ase_index . '_id'])) {
            $service = new stdClass();
            $service->id = $_GET[$query_format . 'ase_' . $ase_index . '_id'];
            $service->quantity = $_GET[$query_format . 'ase_' . $ase_index . '_quan'];
            array_push($services, $service);
            $ase_index++;
        }
        array_push($passengers, $passenger);
        $index++;
    }

    var_dump($passengers);
    var_dump($services);
}
