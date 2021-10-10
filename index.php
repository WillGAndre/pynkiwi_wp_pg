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
    wp_enqueue_script('plugin-account-order-script', plugin_dir_url(__FILE__) . 'scripts/account_orders.js');
}
add_action('wp_enqueue_scripts', 'add_scripts');

// Auxilary Functions 
include_once(plugin_dir_path(__FILE__) . 'comp/aux/flight_search_aux.php');
include_once(plugin_dir_path(__FILE__) . 'comp/aux/current_offer_aux.php');
include_once(plugin_dir_path(__FILE__) . 'comp/aux/aux.php');
// Classes - Slices, Passengers, Offer Request, Offers
include_once(plugin_dir_path(__FILE__) . 'comp/payment_classes.php');
include_once(plugin_dir_path(__FILE__) . 'comp/current_offer_classes.php');
include_once(plugin_dir_path(__FILE__) . 'comp/flight_search_classes.php');

$hashmap_offers = array();  // Global offers hasmap (index -> offer id, value -> offer)

//                  --- *** ---

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

//                  --- *** ---

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
 * offer_id and the current user id.
 */
function check_user()
{
    if (is_user_logged_in()) :
        console_log('user logged in');
        $current_user = wp_get_current_user();
        $current_user_id = $current_user->ID;
        header('Location: https://pynkiwi.wpcomstaging.com/?' . http_build_query(array(
            'page_id' => 2475,
            'up_offer_id' => $_POST['offer_submit'],
            'user_id' => $current_user_id
        )));
    else : // TODO: !
        header('Location: https://pynkiwi.wpcomstaging.com/?page_id=2478');
        console_log('user not logged in');
    endif;
}

//                  --- *** ---

// Trigger -> onclick of offer price button (redirect to account)
/**
 * Upon receiving a redirect with a up_offer_id as 
 * a query argument, print offer options info
 * as well as payment info (single offer request).
 */
if (isset($_GET['up_offer_id'])) {
    $offer_id = $_GET['up_offer_id'];
    $user_id = $_GET['user_id'];
    show_current_offer($offer_id);
    $single_offer = new Single_Offer($offer_id, $user_id);
    $single_offer->get_single_offer();
    $single_offer->print_single_offer_html();
    $single_offer->print_single_offer_opts_html();
    $single_offer->print_user();
}

function show_current_offer($offer_id) { // TODO: Make current offer tab responsive
    $offer_id_html = ' document.getElementById("main_dash").innerHTML += "<div id=\'curr_offer_id\' style=\'display:none;\'>'.$offer_id.'</div>"; ';
    echo '<script> document.addEventListener("DOMContentLoaded", function(event) { document.getElementById("main_dash").style.display = "block"; '.$offer_id_html.' }); </script>';
}


//                  --- *** ---


/* TODO: 
 / Send payment via stripe                                              (ISSUE --> #17)
 / Integrate support for later payment (via payment endpoint)           (ISSUE --> #20)
 / Integrate support for canceling order upon creation                  (ISSUE --> #22) - Partially Done
 / Refactor get_iata_code / get_lat_lon                                 (ISSUE --> #23) - Done
 /
 / FILE: payment_classes.php
 /  \
 /   TODO:
 /    --> Order payment ("instant" --> Integrate Stripe / "hold" --> Integrate Duffel & Stripe)
 /    --> Order cancelation (add Stripe refund to user)
*/
/**
 * On current offer payment click,
 * the frontend (js), redirects
 * the user back to the account
 * dashboard (to be changes).
 * This redirect includes query
 * parameters in the url, later
 * used to send relevant passenger
 * info to Duffel
 */
if (isset($_GET['pay_offer_id'])) { 
    $user_id = $_GET['user_id'];
    $offer_id = $_GET['pay_offer_id'];
    $duffel_total_amount = explode(' ', $_GET['total_amount']); // Includes currency
    $pay_type = $_GET['type'];

    $url_info = get_url_info();
    $passengers = $url_info[0];
    $services = $url_info[1];

    $payments = array();
    $payment = new stdClass();
    $payment->type = "balance";
    $payment->currency = $duffel_total_amount[1];
    $payment->amount = $duffel_total_amount[0];
    array_push($payments, $payment);

    $selected_offers = array();
    array_push($selected_offers, $offer_id);
    
    $order_req = new Order_request($pay_type, $services, $selected_offers, $payments, $passengers);
    $order = $order_req->create_order();
    $orders = new Orders($user_id);
    $orders->add_order($order);
    
    // debug
    // imp
    $orders->debug_get_orders();
    //$orders->delete_orders();
}

/**
 * Extracts passenger and
 * services info from the url.
 */
function get_url_info() {
    $index = 0;
    $passengers = array();
    $services = array();
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
            $identity_documents = array();
            $doc_info = new stdClass();
            $doc_info->unique_identifier = $_GET[$query_format . 'doc_id'];
            $doc_info->type = "passport";
            $doc_info->issuing_country_code = country_to_code($_GET[$query_format . 'country']);
            $doc_info->doc_exp_date = $_GET[$query_format . 'doc_exp_date'];
            array_push($identity_documents, $doc_info);
            $passenger->identity_documents = $identity_documents;
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
    return [$passengers, $services];
}

/**
 * Triggers init script for
 * showing orders.
 */
if (isset($_GET['init_show_orders'])) {
    add_action('init', 'init_show_orders');
}

function init_show_orders() {
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;
    header('Location: https://pynkiwi.wpcomstaging.com/?' . http_build_query(array(
        'page_id' => 3294,
        'show_orders' => 1,
        'user_id' => $current_user_id
    )));
}

if (isset($_GET['show_orders'])) {
    $user_id = $_GET['user_id'];
    $orders = new Orders($user_id);
    $orders->show_orders();
    
    // debug
    // imp
    $orders->delete_orders();
}

/**
 * Action type for available orders,
 * (1) cancel the selected order,
 * (2) pay the selected order (TODO).
 */
if (isset($_GET['action_type'])) {
    $action_type = $_GET['action_type'];
    $order_id = $_GET['order_id'];
    
    if ($action_type === "1") {
        $flag = cancel_order($order_id);
        if ($flag) {
            add_action(
                'init',
                function() use ($order_id) {
                    $current_user = wp_get_current_user();
                    $user_id = $current_user->ID;
                    $orders = new Orders($user_id);
                    $orders->delete_order_meta($order_id);
                }
            );
        }
    }
}

