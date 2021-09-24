<?php
// Copyright 2021 - PYNKIWI

// ###### Auxilary ######
function get_offer_ttl($offer_created_at, $offer_expires_at)
{
        $created_at_date_string = substr($offer_created_at, 0, 10) . "  " . substr($offer_created_at, 11, 5);
        $expires_at_date_string = substr($offer_expires_at, 0, 10) . "  " . substr($offer_expires_at, 11, 5);
        $created_at_date = new DateTime($created_at_date_string);
        $expires_at_date = new DateTime($expires_at_date_string);

        return $created_at_date->diff($expires_at_date)->format("%H:%I:%S") . "";
}

/*
    TODO:
    --> add user friendly 500 error message
        --> button to redirect to /flights-booking
*/

function get_lat_lon($city_name)
{
    $url = 'https://eu1.locationiq.com/v1/search.php?key=pk.4f79e46d2f387c7244a1738aa714179d&q=' . $city_name . '&format=json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $lat = "";
    $lon = "";

    $res = curl_exec($ch);
    if ($err = curl_error($ch)) {
        console_log('Error getting latitude/longitude - ' . $err);
        curl_close($ch);
        error_msg();
    } else {
        $json = json_decode($res);

        /* Debug */
        // var_dump($json);

        foreach ($json as $_ => $entry) {
            foreach ($entry as $tag => $value) {
                if ($tag === 'lat') {
                    $lat = $value;
                } else if ($tag === 'lon') {
                    $lon = $value;
                }
                if ($lat !== "" && $lon !== "") {
                    break;
                }
            }
        }
    }
    curl_close($ch);
    return [$lat, $lon];
}

function get_iata_code($lat, $lon)
{
    $url = 'https://airlabs.co/api/v9/nearby?lat=' . $lat . '&lng=' . $lon . '&distance=20&api_key=abafe3aa-bb01-444a-98b1-4d27266669cc';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $iata_code = "";

    $res = curl_exec($ch);
    if ($err = curl_error($ch)) {
        console_log('Error getting IATA code - ' . $err);
        curl_close($ch);
        error_msg();
    } else {
        $json = json_decode($res);
        $json_arr = get_object_vars($json);

        /* Debug */
        // var_dump($json);

        foreach ($json_arr as $key => $entry) {
            if ($key === "response") {
                $res_arr = get_object_vars($entry);

                foreach ($res_arr as $_ => $airports) {
                    foreach ($airports as $_ => $airport_info) {
                        $airport_info_arr = get_object_vars($airport_info);
                        foreach ($airport_info_arr as $tag => $value) {
                            if ($tag === "iata_code") {
                                $iata_code = $value;
                            }
                            if ($iata_code !== "") {
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    curl_close($ch);
    return $iata_code;
}

// first and second date must be > than current date (TODO!)
function check_input_dates($first_date, $second_date)
{
    // yyyy-mm-dd
    $first_year = intval(substr($first_date, 0, 4));
    $first_month = intval(substr($first_date, 5, 2));
    $first_day = intval(substr($first_date, 8, 2));
    $second_year = intval(substr($second_date, 0, 4));
    $second_month = intval(substr($second_date, 5, 2));
    $second_day = intval(substr($second_date, 8, 2));

    if ($first_year > $second_year) {
        alert('Input year not valid');
    } else if ($first_year === $second_year && $first_month > $second_month) {
        alert('Input month not valid');
    } else if ($first_year === $second_year && $first_month === $second_month && $first_day > $second_day) {
        alert('Input day not valid');
    } else {
        return 1;
    }
    return 0;
}

function debug_log($iata_code_from, $iata_code_to, $first_date, $second_date, $slices_list, $passengers_list)
{
    console_log($iata_code_from . ' , ' . $iata_code_to);
    console_log($first_date . ' , ' . $second_date);
    var_dump($slices_list);
    var_dump($passengers_list);
}

function debug_offer_data($source_iata_code, $destination_iata_code, $departing_at, $arriving_at, $airline, $total_amount)
{
    var_dump($source_iata_code);
    var_dump($destination_iata_code);
    var_dump($departing_at);
    var_dump($arriving_at);
    var_dump($airline);
    echo $total_amount;
}

function error_msg()
{
    alert('Please refresh your page and try again');
    exit(0);
}

function console_log($msg)
{
    echo '<script type="text/javascript">';
    echo 'console.log(\'' . $msg . '\')';
    echo '</script>';
}

function alert($msg)
{
    echo '<script type="text/javascript">';
    echo 'alert(\'' . $msg . '\')';
    echo '</script>';
}
// ###### EOF ######
?>