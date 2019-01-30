<?php

// Load libraries
require_once $_SERVER['DOCUMENT_ROOT'] . '_inc/api.class.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '_inc/notify.class.php';

// Init Battery API
$api = new Battery_API();

// Get data
$data_from_cache = $api->get_cached_vehicle_data();
$data = $api->get_vehicle_data();

// Exit if empty
if ( ! isset($data->chargingActive) || ! isset($data_from_cache->chargingActive) ) {
    die('Notify: No values, exit');
}

// Exit if no changes
if ( $data->chargingActive === $data_from_cache->chargingActive ) {
    die('Notify: No changes, exit');
}

// Send notification
new Webhook_Notification(
    sprintf(
        'Charging %s @ %s %% (%s kWh)',
        ( $data->chargingActive == 1 ? 'started' : 'finished' ),
        $data->chargingLevel,
        $data->stateOfCharge
    )
);

exit;
