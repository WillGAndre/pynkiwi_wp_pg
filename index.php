<?php

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
    wp_enqueue_style('plugin-stylesheet', plugin_dir_url(__FILE__) . 'style/results.css');
}
add_action('wp_enqueue_scripts', 'add_scripts');

// Auxilary Functions 
include_once(plugin_dir_path(__FILE__) . 'comp/aux.php');
// Classes - Slices, Passengers, Offer Request, Offers
include_once(plugin_dir_path(__FILE__) . 'comp/classes.php');


// (isset($_POST['hidden_submit']))
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
    $iata_code_to = 'MAD';

    // Define constants
    define("IATA_FROM", $iata_code_from);
    define("IATA_TO", $iata_code_to);
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
            $offer->print_html();
        }
    }
} else if ($_POST['flight-price'] === "PAYMENT") {
    alert('PRICE div3');
} 

if (isset($_POST['flight-price'])) {
    alert('PRICE div4');
}