<?php
// Copyright 2021 - PYNKIWI

// ###### Auxilary ######
/*
    Airports data -> https://raw.githubusercontent.com/jpatokal/openflights/master/data/airports.dat
*/

class Baggages {
    public $sli_id;    // set all: private
    public $seg_ids;   
    public $pass_ids;  
    public $baggages; 

    public function __construct($sli_id, $seg_ids, $pass_ids, $baggages) {
        $this->sli_id = $sli_id;
        $this->seg_ids = $seg_ids;
        $this->pass_ids = array_unique($pass_ids);
        $this->baggages = $baggages;
    }

    /**
     * Print baggage html after Offer
     * request. Upon printing an Offer,
     * (normal offer only) this function 
     * will print the text bellow 
     * the price button.
     * 
     * Args:
     *  $single_offer -> Same principle as
     *  Offer's print_html() if $single_offer = 1,
     *  then print in current offer tab otherwise
     *  print in flight results.
     */
    public function print_baggage_html($single_offer) {
        $total_seg = count($this->seg_ids);
        $total_pas = count($this->pass_ids);
        $total_bag = count($this->baggages);

        $total_bag_types = array();
        $total_bag_quans = array();

        if ($total_bag / $total_pas === $total_seg) {
            foreach ($this->baggages as $_ => $baggage) {
                $baggage_types = $baggage->get_types();
                $baggage_quans = $baggage->get_quans();
                array_push($total_bag_types, $baggage_types);
                array_push($total_bag_quans, $baggage_quans);
            }
        } else {
            console_log(' [*] Debug: $total_bag->{'.$total_bag.'} $total_pas->{'.$total_pas.'} $total_seg->{'.$total_seg. '} $total_bag / $total_pas === $total_seg');
            throw new Exception('\t- Number of bags per passenger should be equal to the number of segments');
        }

        if ($single_offer) {
            $code = 'document.getElementById("curr-bags_text").innerHTML = "'; 
            $msg = '';
        } else {
            $code = '<div id=\'baggage_text\'>';
            $msg = 'Includes ';
        }

        if (count(array_unique($total_bag_quans)) === 1 && 1 === count(array_unique($total_bag_types))) {
            $index = 0;
            while ($index < count($total_bag_quans[0])) {
                if ($index > 0 && $msg !== '' && $msg !== 'Includes ') {
                    $msg = $msg . ' and ';
                }
                $type = str_replace('_', ' ', $total_bag_types[0][$index]);
                $quan = $total_bag_quans[0][$index];
                $msg = $msg . $quan . ' ' . $type . ' bag';
                if (intval($quan) > 1) {
                    $msg = $msg . 's';
                }
                $index++;
            }
        } else if (count(array_unique($total_bag_quans)) > 1) {
            throw new Exception('\t- Passengers have different baggage allocations');
        }

        if ($single_offer && $msg === '') {
            console_log('\t- Bags per passenger: 0');
            return 'document.getElementById("entry-bag").style.display = "none"; ';
        } else if ($msg === 'Includes ') {
            return;
        }

        if ($single_offer) {
            console_log('\t- Bags per passenger: {'.$msg.'}');
            return $code . $msg . ' per passenger."; ';
        } else {
            return $code . $msg . '</div>';
        }
        return;
    }
}

class Baggage {
    public $pass_id;
    public $types;
    public $quans;

    public function __construct($pass_id, $types, $quans){
        $this->pass_id = $pass_id;
        $this->types = $types;
        $this->quans = $quans;
    }

    public function get_pass_id() 
    {
        return $this->pass_id;
    }

    public function get_types()
    {
        return $this->types;
    }

    public function get_quans()
    {
        return $this->quans;
    }
}

function country_to_code($country) {
    $country = ucfirst($country);
    $countryList = array(
        'AF' => 'Afghanistan',
        'AX' => 'Aland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas the',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island (Bouvetoya)',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory (Chagos Archipelago)',
        'VG' => 'British Virgin Islands',
        'BN' => 'Brunei Darussalam',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros the',
        'CD' => 'Congo',
        'CG' => 'Congo the',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Cote d\'Ivoire',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FO' => 'Faroe Islands',
        'FK' => 'Falkland Islands (Malvinas)',
        'FJ' => 'Fiji the Fiji Islands',
        'FI' => 'Finland',
        'FR' => 'France, French Republic',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia the',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard Island and McDonald Islands',
        'VA' => 'Holy See (Vatican City State)',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KP' => 'Korea',
        'KR' => 'Korea',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyz Republic',
        'LA' => 'Lao',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libyan Arab Jamahiriya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'AN' => 'Netherlands Antilles',
        'NL' => 'Netherlands the',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestinian Territory',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn Islands',
        'PL' => 'Poland',
        'PT' => 'Portugal, Portuguese Republic',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russian Federation',
        'RW' => 'Rwanda',
        'BL' => 'Saint Barthelemy',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts and Nevis',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin',
        'PM' => 'Saint Pierre and Miquelon',
        'VC' => 'Saint Vincent and the Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome and Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia (Slovak Republic)',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia, Somali Republic',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia and the South Sandwich Islands',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard & Jan Mayen Islands',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland, Swiss Confederation',
        'SY' => 'Syrian Arab Republic',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks and Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States of America',
        'UM' => 'United States Minor Outlying Islands',
        'VI' => 'United States Virgin Islands',
        'UY' => 'Uruguay, Eastern Republic of',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'WF' => 'Wallis and Futuna',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe'
    );
    return array_search($country, $countryList);
}

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
    --> TESTING!!

    --> add user friendly 500 error message
        --> button to redirect to /flights-booking
*/

/**
 * Get distance (in Km)
 * based on coord.
 */
function get_dist_km($lat1, $lon1, $lat2, $lon2) {
	$theta = $lon1 - $lon2;
  	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  	$dist = acos($dist);
  	$dist = rad2deg($dist);
  	$miles = $dist * 60 * 1.1515;
	return ($miles * 1.609344); // km
}

/**
 * Get IATA code based on city name
 * and country ($city_name), if 
 * latitude and longitude is 
 * provided then cities betwen
 * 10 km of the given one will
 * be used.
 * 
 * NOTE:
 *  IATA code is the offical airport/
 *  city code.
 */
function get_iata_code($city_name, $lat1, $lon1) {
    $iata_code = "";
    $file = plugin_dir_path(__FILE__) . '/airports.txt';
    $fp = fopen($file, "r");
    if ($fp) {
        while (($buff = fgets($fp)) !== false) {
            $buff_arr = explode(',', $buff);
            $city = strval(trim($buff_arr[2], '"'));
            $country = strval(trim($buff_arr[3], '"'));
            $iata = strval(trim($buff_arr[4], '"'));
            $lat2 = floatval($buff_arr[6]);
            $lon2 = floatval($buff_arr[7]);

            $city_name_arr = explode('&', $city_name);
            if ($city_name_arr[0] === $city && $city_name_arr[1] === $country && $iata !== "\N") {
                $iata_code = $iata;
                break;
            } else if (get_dist_km($lat1, $lon1, $lat2, $lon2) <= 10 && $iata !== "\N") {
                $iata_code = $iata;
                break;
            }
        }
        fclose($fp);
    } else {
        echo 'File handler not set';
    }
    return $iata_code;
}

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

// function get_iata_code($lat, $lon)
// {
//     $url = 'https://airlabs.co/api/v9/nearby?lat=' . $lat . '&lng=' . $lon . '&distance=20&api_key=abafe3aa-bb01-444a-98b1-4d27266669cc';
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

//     $iata_code = "";

//     $res = curl_exec($ch);
//     if ($err = curl_error($ch)) {
//         console_log('Error getting IATA code - ' . $err);
//         curl_close($ch);
//         error_msg();
//     } else {
//         $json = json_decode($res);
//         $json_arr = get_object_vars($json);

//         /* Debug */
//         // var_dump($json);

//         foreach ($json_arr as $key => $entry) {
//             if ($key === "response") {
//                 $res_arr = get_object_vars($entry);

//                 foreach ($res_arr as $_ => $airports) {
//                     foreach ($airports as $_ => $airport_info) {
//                         $airport_info_arr = get_object_vars($airport_info);
//                         foreach ($airport_info_arr as $tag => $value) {
//                             if ($tag === "iata_code") {
//                                 $iata_code = $value;
//                             }
//                             if ($iata_code !== "") {
//                                 break;
//                             }
//                         }
//                     }
//                 }
//             }
//         }
//     }
//     curl_close($ch);
//     return $iata_code;
// }

function format_date($date) {
    $real_date = substr($date, 0, 10);
    $real_time = substr($date, 11, 8);
    return $real_date . ' ' . $real_time;
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
