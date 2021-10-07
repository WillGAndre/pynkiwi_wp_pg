<?php
// Copyright 2021 - PYNKIWI

// ###### Auxilary ######
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
