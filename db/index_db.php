<?php
global $pass_info_db_version;
$pass_info_db_version = '1.0';

/**
 * Steps to generate new DB:
 * 1 - First install plugin
 * 2 - Delete DB in myphpadmin
 * 3 - Wait and new DB should be up
 * 
 * Reason for using TEXT instead of varchar:
 * (https://stackoverflow.com/a/29970710)
 */
function pass_info_db_install() {
    global $wpdb;
    global $pass_info_db_version;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS "."off_pass_info"." (
        Off_ID TEXT NOT NULL,
        Title TEXT NOT NULL,
        Name TEXT NOT NULL,
        Gender TEXT NOT NULL,
        Mail TEXT NOT NULL,
        Phone TEXT NOT NULL,
        BDay DATE NOT NULL,
        DocID TEXT DEFAULT '',
        DocType TEXT DEFAULT '',
        DocCountryCode TEXT DEFAULT '',
        DocExpDate DATE DEFAULT '0000-00-00',
        InfantID TEXT DEFAULT '',
        ServiceID TEXT DEFAULT '',
        ServiceQuan TEXT DEFAULT ''
    ) $charset_collate;";
    require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('pass_info_db_version', $pass_info_db_version);
}
add_action('init', 'pass_info_db_install');


function insert_off_info($offer_id, $passengers, $services) {
    $services_index = 0;
    foreach ($passengers as $passenger) {
        $data = array(
            'Off_ID' => $offer_id,
            'Title' => $passenger->title,
            'Name' => $passenger->given_name . ' ' . $passenger->family_name,
            'Gender' => $passenger->gender,
            'Mail' => $passenger->email,
            'Phone' => $passenger->phone_number,
            'BDay' => $passenger->born_on,
        );
        if (count($passenger->identity_documents) !== 0) {
            $doc_info = $passenger->identity_documents[0];
            $data['DocID'] = $doc_info->unique_identifier;
            $data['DocType'] = $doc_info->type;
            $data['DocCountryCode'] = $doc_info->issuing_country_code;
            $data['DocExpDate'] = $doc_info->doc_exp_date;
        }
        if (isset($passenger->infant_passenger_id)) {
            $data['InfantID'] = $passenger->infant_passenger_id;
        }
        if ($services_index < count($services)) {
            $service = $services[$services_index];
            $data['ServiceID'] = $service->id;
            $data['ServiceQuan'] = $service->quantity;
        }
        add_action('init', function () use ($data) {
            //var_dump($data);
            global $wpdb;
            $status = $wpdb->insert(
                'off_pass_info',
                $data
            );
            console_log('Database update status: {'.$status.'}');
        });
        $services_index++;
    }
}

function get_off_info($offer_id) { 
    global $wpdb;
    $sql_results = '
        SELECT * FROM `off_pass_info` WHERE `Off_ID`=\''.$offer_id.'\';';
    $results = $wpdb->get_row($sql_results , OBJECT);
    if ($results !== null) {
        return $results;
    } else {
        console_log('Failed to get offer passenger info - Please retry your search');
    }
    return null;
}

?>