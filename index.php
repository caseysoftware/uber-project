<?php

include 'creds.php';
include 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Google_Client();
$client->setApplicationName('Test App');
$client->setAccessType('offline');
$client->setAccessToken($google_access);
$service = new Google_Service_Calendar($client);

$eventList = array();

foreach($calendars as $calendarId) {
    // Print the next 10 events on the user's calendar.
    $optParams = array(
        'maxResults' => 10,
        'orderBy' => 'startTime',
        'singleEvents' => TRUE,
        'timeMin' => date('c'),
    );

    $results = $service->events->listEvents($calendarId, $optParams);
    foreach ($results->getItems() as $entries) {

        // Skip if there's no location or a hangout listed
        if (strpos($entries->location, 'http') !== false || strlen($entries->location) == 0) {
            continue;
        }

        $id = $entries->id;
        $event = array();
        $event['name'] = $entries->getSummary();
        $event['address'] = $entries->location;
        $event['startTime'] = $entries->getStart()->dateTime;
        $event['endTime'] = $entries->getEnd()->dateTime;

        $eventList[$id] = $event;
    }
}

// This finds the lat/lon of home so we know what products are available to us later.
$client = new Client();
$geocode = 'https://maps.googleapis.com/maps/api/geocode/json?key=' . $google_key;
$params = '&address=' . urlencode($home);
$response = $client->request('GET', $geocode . $params);
$body = json_decode($response->getBody());
$lat = $body->results[0]->geometry->location->lat;
$lon = $body->results[0]->geometry->location->lng;

// Alright, now let's find out where we're going.
$gmaps = 'https://maps.googleapis.com/maps/api/distancematrix/json?key=' . $google_key;

foreach($eventList as $event) {
    $params = '&origins=' . urlencode($home);
    $params .= '&destinations=' . urlencode($event['address']);

    $response = $client->request('GET', $gmaps . $params);
    $body = json_decode($response->getBody());
    $distance = $body->rows[0]->elements[0]->distance->value;
    // It's further than 100 km away, probably time to fly!
    if ($distance > 100000) {
        continue;
    }

    $params = '&latitude=' . $lat;
    $params .= '&longitude=' . $lon;
    $uber = 'https://api.uber.com/v1/products?server_token=' . $uber_token;
    $response = $client->request('GET', $uber . $params);
    $products = json_decode($response->getBody());

    // Check to make sure at least one product is available.. if not, move on. If so, choose the first product.
    if (!count($products->products)) {
        continue;
    }
    $product = $products->products[0];

    echo $event['address'] . "\n";
    echo ($distance/1000) . " km\n";

die();
}


// done: connect to google calendar
// done: define home
// done: get a list of meetings
// done: figure out where the meetings are
// todo: sort the list of meetings
// done: figure out if it's close enough to drive (100 km)
// done: figure out the lat/lon for where we are
// done: figure out the available products
// todo: estimate a cost to get there
// todo: schedule a reservation for that time (sandbox)