<?php

define('TOKEN_FILE', $_SERVER['PATH_TRANSLATED'] . '../api/token.json');
define('AUTH_FILE', $_SERVER['PATH_TRANSLATED'] . '../api/auth.json');
define('CACHE_FILE', $_SERVER['PATH_TRANSLATED'] . '../api/cache.json');

define('AUTH_API', 'https://customer.bmwgroup.com/gcdm/oauth/authenticate');
define('VEHICLE_API', 'https://www.bmw-connecteddrive.de/api/vehicle');
define('DATE_FORMAT', 'd.m.Y H:i');

class Battery_API {

    private $auth;
    private $token;
    private $json;

    function __construct () {
        $this->auth = $this->get_auth_data();
        $this->token = $this->get_token();
    }

    function get_auth_data() {
        return json_decode(
            file_get_contents(
                AUTH_FILE
            )
        );
    }

    function cache_remote_token( $token_data ) {
        file_put_contents(
            TOKEN_FILE,
            json_encode( $token_data )
        );
    }

    function cache_vehicle_data( $vehicle_data ) {
        file_put_contents(
            CACHE_FILE,
            json_encode( $vehicle_data )
        );
    }

    function get_cached_vehicle_data() {
        return json_decode(
            file_get_contents(
                CACHE_FILE
            )
        );
    }

    function get_cached_token() {
        return json_decode(
            file_get_contents(
                TOKEN_FILE
            )
        );
    }

    function get_token() {
        // Get cached token
        if ( $cached_token_data = $this->get_cached_token() ) {
            if ( $cached_token_data->expires > time() ) {
                $token = $cached_token_data->token;
            }
        }

        // Get remote token
        if ( empty( $token ) ) {
            $token_data = $this->get_remote_token();
            $token = $token_data->token;

            $this->cache_remote_token( $token_data );
        }

        return $token;
    }

    function get_remote_token() {
        // Init cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt( $ch, CURLOPT_URL, AUTH_API );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HEADER, true );
        curl_setopt( $ch, CURLOPT_NOBODY, true );
        curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-Type: application/x-www-form-urlencoded' ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, 'username=' . urlencode( $this->auth->username) . '&password=' . urlencode( $this->auth->password) . '&client_id=dbf0a542-ebd1-4ff0-a9a7-55172fbfce35&redirect_uri=https%3A%2F%2Fwww.bmw-connecteddrive.com%2Fapp%2Fdefault%2Fstatic%2Fexternal-dispatch.html&response_type=token&scope=authenticate_user%20fupo&state=eyJtYXJrZXQiOiJkZSIsImxhbmd1YWdlIjoiZGUiLCJkZXN0aW5hdGlvbiI6ImxhbmRpbmdQYWdlIn0&locale=DE-de' );

        // Exec curl request
        $response = curl_exec( $ch );

        // Close connection
        curl_close( $ch );

        // Extract token
        preg_match( '/access_token=([\w\d]+).*token_type=(\w+).*expires_in=(\d+)/', $response, $matches );

        // Check token type
        if ( empty( $matches[2] ) OR $matches[2] !== 'Bearer' ) {
            die('API: Token type check failed, exit');
        }

        return (object) array(
            'token' => $matches[1],
            'expires' => time() + $matches[3]
        );
    }

    function get_vehicle_data() {
        // Init cURL
        $ch_1 = curl_init();
        $ch_2 = curl_init();

        // Set cURL options
        curl_setopt( $ch_1, CURLOPT_URL, VEHICLE_API . '/dynamic/v1/' . $this->auth->vehicle . '?offset=-60' );
        curl_setopt( $ch_1, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' , 'Authorization: Bearer ' . $this->token ) );
        curl_setopt( $ch_1, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch_1, CURLOPT_FOLLOWLOCATION, true );

        curl_setopt( $ch_2, CURLOPT_URL, VEHICLE_API . '/navigation/v1/' . $this->auth->vehicle );
        curl_setopt( $ch_2, CURLOPT_HTTPHEADER, array( 'Content-Type: application/json' , 'Authorization: Bearer ' . $this->token ) );
        curl_setopt( $ch_2, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch_2, CURLOPT_FOLLOWLOCATION, true );

        // Build the multi-curl handle
        $mh = curl_multi_init();
        curl_multi_add_handle( $mh, $ch_1 );
        curl_multi_add_handle( $mh, $ch_2 );

        // Execute all queries simultaneously
        $running = null;
        do {
            curl_multi_exec( $mh, $running );
        } while ( $running );

        // Close the handles
        curl_multi_remove_handle( $mh, $ch_1 );
        curl_multi_remove_handle( $mh, $ch_2 );
        curl_multi_close( $mh );

        // all of our requests are done, we can now access the results
        $response_1 = curl_multi_getcontent( $ch_1 );
        $response_2 = curl_multi_getcontent( $ch_2 );

        $json1 = json_decode( $response_1, true );
        $json2 = json_decode( $response_2, true );

        // Exit if error
        if ( json_last_error() OR empty($json1) OR empty($json2) ) {
            die('API: JSON error, exit');
        }

        // Merge data
        $json = (object)array_merge( $json1['attributesMap'], $json2 );

        return $this->prepare_vehicle_data( $json );
    }

    function prepare_vehicle_data($attributes) {
        $dateTime = DateTime::createFromFormat(DATE_FORMAT, $attributes->updateTime_converted);

        $updateTime = $dateTime->format(DATE_FORMAT);
        $electricRange = intval( $attributes->beRemainingRangeElectricKm );
        $chargingLevel = intval( $attributes->chargingLevelHv );
        $chargingActive = intval( $attributes->chargingSystemStatus === 'CHARGINGACTIVE' );

        $chargingTimeRemaining = intval( $attributes->chargingTimeRemaining );
        $chargingTimeRemaining = ( $chargingTimeRemaining ? ( date( 'H:i', mktime( 0, $chargingTimeRemaining ) ) ) : '0:00' );

        $stateOfCharge = number_format( round( $attributes->soc, 2 ), 2, ',', '.');
        $stateOfChargeMax = number_format( round( $attributes->socmax, 2 ), 2, ',', '.');

        $vehicle_data = array(
            'updateTime' => $updateTime,
            'electricRange' => $electricRange,
            'chargingLevel' => $chargingLevel,
            'chargingActive' => $chargingActive,
            'chargingTimeRemaining' => $chargingTimeRemaining,
            'stateOfCharge' => $stateOfCharge,
            'stateOfChargeMax' => $stateOfChargeMax
        );

        // Cache data
        $this->cache_vehicle_data( $vehicle_data );

        // Return data
        return (object)$vehicle_data;
    }
}
