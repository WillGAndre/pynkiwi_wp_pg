<?php

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
function check_user() {
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
    // ---
    $single_offer->print_single_offer_html();
    $single_offer->print_single_offer_opts_html();
    // ---
    $single_offer->print_user();
}

/**
 * Display current offer in main dashboard,
 * this dashboard may be found in Account page.
 */
function show_current_offer($offer_id) { // TODO: Make current offer tab responsive
    $offer_id_html = ' document.getElementById("main_dash").innerHTML += "<div id=\'curr_offer_id\' style=\'display:none;\'>'.$offer_id.'</div>"; ';
    echo '<script> document.addEventListener("DOMContentLoaded", function(event) { document.getElementById("main_dash").style.display = "block"; '.$offer_id_html.' }); </script>';
}

/**
 * When selectiong current offer for payment, user
 * is redirected. Depending on payment selected
 * (if option is available) user will prompted
 * for payment or "hold" order is created.
 */
if (isset($_GET['pay_offer_id']) && $_GET['page_id'] === '3294') { 
    $user_id = $_GET['user_id'];
    $offer_id = $_GET['pay_offer_id'];
    $duffel_total_amount = explode(' ', $_GET['duffel_total_amount']); // Includes currency
    $url_info = get_url_info();
    $passengers = $url_info[0];
    $services = $url_info[1];
    $pay_type = $_GET['type'];

    if ($pay_type === "instant") {
        insert_off_info($offer_id, $passengers, $services);
        add_action('init', function () use ($user_id, $offer_id, $duffel_total_amount) {
            $orders = new Orders($user_id);
            $orders->add_pending_order($offer_id, $duffel_total_amount);
        });
        header('Location: https://pynkiwi.wpcomstaging.com/?' . http_build_query(array(
            'page_id' => 3721,
            'offer_id' => $offer_id,
            'stripe_total_amount' => $_GET['stripe_total_amount']
        )));
    } else if ($pay_type === "hold") {
        $selected_offers = array();
        array_push($selected_offers, $offer_id);
        $order_req = new Order_request($pay_type, $services, $selected_offers, array(), $passengers);
        $order = $order_req->create_order();
        $orders = new Orders($user_id);
        $orders->add_order($order);     // Prints orders 
        $orders->show_orders();
    }
}

/**
 * If user selects payment type == "instant",
 * after creating an Order in Duffel, user is redirected
 * to Stripe payment page and on successful payment
 * redirected again to checkout page.
 */
if (isset($_GET['page_id']) && $_GET['page_id'] === '3721') {
    $offer_id = $_GET['offer_id'];
    $stripe_total_amount = $_GET['stripe_total_amount'];
    echo '<script>
    let intervalID_price_check;
    var monitor = setInterval(function(){
        let elem = document.activeElement;
        if(elem && elem.tagName == \'IFRAME\'){
            clearInterval(monitor);
            let html_elem = elem.contentDocument.children[0];
            let body_elem = html_elem.children[1];
            let aligner_elem = body_elem.children[0].children[2];

            let aligner_head = aligner_elem.children[1];
            let order_title = aligner_head.children[1];
            let order_descr = aligner_head.children[2];
            order_title.innerHTML += \' \' + \''.$offer_id.'\'
            order_descr.innerHTML += \' Total: \' + \''.$stripe_total_amount.'\'

            let aligner_body = aligner_elem.children[2].children[0].children[1];
            let submit_bt = aligner_body.children[3].children[0].children[0].children[0];
            let amount_cont = aligner_body.children[0];
            let amount_input = amount_cont.children[1];
            amount_input.addEventListener("change", (event) => {
                let input_val = event.target.value;
                let order_split = order_descr.innerHTML.split(\' \');
                
                let price_split = order_split[3].split(\'.\');
                let price_lhs = price_split[0];
                let price_rhs = price_split[1].slice(0, 2);

                let full_price = price_lhs + \'.\' + price_rhs;
                if (input_val != full_price) {
                    submit_bt.disabled = true;
                } else {
                    submit_bt.disabled = false;
                }
            });
        }
    }, 100);
    </script>';
}

/**
 * On checkout page, get offer_info from db
 * (check /db) and create order using duffels API.
 */
if (isset($_GET['page_id']) && $_GET['page_id'] === '3640') { 
    add_action(
        'init',
        function() {
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;
            $orders = new Orders($user_id);
            $offer_payment_info = $orders->get_pending_order();
            $offer_id = $offer_payment_info[0];
            $offer_duffel_amount = $offer_payment_info[1];

            if ($offer_id !== -1) {
                $offer_info = get_off_info($offer_id); //stdClass
                if ($offer_info === null) {
                    console_log('Failed to get offer passenger information');
                } else {
                    $payments = array();
                    $payment = new stdClass();
                    $payment->type = "balance";
                    $payment->currency = $offer_duffel_amount[1];
                    $payment->amount = $offer_duffel_amount[0];
                    array_push($payments, $payment);

                    $selected_offers = array();
                    array_push($selected_offers, $offer_id);
                    $order_req_info = read_off_info($offer_info);
                    $passengers = $order_req_info[0];
                    $services = $order_req_info[1];

                    $order_req = new Order_request("instant", $services, $selected_offers, $payments, $passengers);
                    $order = $order_req->create_order();
                    $orders = new Orders($user_id);
                    $orders->add_pending_order_meta($order);       
                    $orders->debug_get_orders();   
                }
            } else {
                console_log('Failed to get pending order');
            }
        }
    );
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

?>