<?php
/*
 * Created on Sun Oct 10 2021
 *
 * Copyright (c) 2021 PYNKIWI
 */
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
 * Init DB
 */
include_once(plugin_dir_path(__FILE__) . 'db/index_db.php');

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

//                  --- *** ---

include_once(plugin_dir_path(__FILE__) . 'flight_search.php');
include_once(plugin_dir_path(__FILE__) . 'current_offer.php');
include_once(plugin_dir_path(__FILE__) . 'orders_dashboard.php');


