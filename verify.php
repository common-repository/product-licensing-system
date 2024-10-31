<?php
$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
require_once( $parse_uri[0] . 'wp-load.php' );

function plicense_api_json($domain, $license_key) {
    global $wpdb;
    $valid = 'false';
    
    $sql = "SELECT * FROM {$wpdb->prefix}plicenses WHERE license='{$license_key}'";
    $row = $wpdb->get_row($sql);
    
    if ($row) {
        if ($row->status == 0) $valid = 'pending';
        else {
            $expired = ($row->expire < time() && $row->expire != 0);
            if ($expired)
                $valid = 'expired';
            else {
                if ($row->allowed == 0) // means unlimited domain
                    $valid = 'true';
                else if (strpos($row->domain, $domain) !== false)
                    $valid = 'true';
            }
        }
    }
    $json['valid'] = $valid;
    return json_encode($json);
}

$domain         = $_POST['domain'];
$license_key    = $_POST['licensekey'];

if (substr($domain, 0, 4) == "www.")
    $domain = substr($domain, 4);

if(!empty($license_key))
{
    header("Content-Type: application/json");
    echo plicense_api_json($domain, $license_key);
}