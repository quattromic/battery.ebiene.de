<?php

// Simple security
if ( empty( $_SERVER['HTTP_REFERER'] ) OR strcmp( parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST ), $_SERVER['SERVER_NAME'] ) !== 0 ) {
    http_response_code( 403 ) && exit;
}

// Load library
require_once $_SERVER['PATH_TRANSLATED'] . '../_inc/api.class.php';

// Init Battery API
$api = new Battery_API();

// Get data
$data = $api->get_vehicle_data();

// Send Header
header('Access-Control-Allow-Origin: https://' . $_SERVER['SERVER_NAME'] );
header('Content-Type: application/json; charset=utf-8');

// Output
die( json_encode( $data ) );
