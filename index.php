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

echo "\n\n";

foreach($eventList as $event) {
    $params = '&origins=' . urlencode($home);
    $params .= '&destinations=' . urlencode($event['address']);

    echo "Now we're looking up the distance between '$home' and '" . $event['address'] . "' \n";
    $response = $client->request('GET', $gmaps . $params);
    $body = json_decode($response->getBody());
    $distance = $body->rows[0]->elements[0]->distance->value;
    $distance = $distance/1000;

    // It's further than 100 km away, probably time to fly!
    if ($distance > 100) {
        echo "  Whoa.. that's over $distance km away. You should fly.\n";
        continue;
    }
    echo "  Alright, you're only traveling $distance km.\n";

    echo "Now we're finding what products are available where you are..\n";
    $params = '&latitude=' . $lat;
    $params .= '&longitude=' . $lon;
    $uber = 'https://api.uber.com/v1/products?server_token=' . $uber_token;
    $response = $client->request('GET', $uber . $params);
    $products = json_decode($response->getBody());

    // Check to make sure at least one product is available.. if not, move on. If so, choose the first product.
    if (!count($products->products)) {
        echo "  Sorry, we don't have any products available for you there.\n";
        continue;
    }
    $product = $products->products[0];
    echo "  Alright, we're defaulting to " . $product->display_name . " for this pickup.\n";

    // This finds the lat/lon of our destination so we can create an estimate.
    $geocode = 'https://maps.googleapis.com/maps/api/geocode/json?key=' . $google_key;
    $params = '&address=' . urlencode($event['address']);
    $response = $client->request('GET', $geocode . $params);
    $body = json_decode($response->getBody());
    $end_lat = $body->results[0]->geometry->location->lat;
    $end_lon = $body->results[0]->geometry->location->lng;

    echo "Now we're finding out how much this ride will cost.\n";
    // This will give us an estimate.
    $params = [];
    $params['product_id'] = $product->product_id;
    $params['start_latitude'] = $lat;
    $params['start_longitude'] = $lon;
    $params['end_latitude'] = $end_lat;
    $params['end_longitude'] = $end_lon;
    $options = [];
    $options['headers'] = ['Authorization' => 'Bearer ' . $uber_api_key, 'Content-Type' => 'application/json'];
    $options['body'] = json_encode($params);
    $uber = 'https://api.uber.com/v1/requests/estimate';
    $response = $client->request('POST', $uber, $options);
    $estimate = json_decode($response->getBody());
    $duration = ($estimate->trip->duration_estimate/60);
    echo "  This ride is going to cost " . $estimate->price->display . " and take about $duration minutes.\n";
    echo "  The ETA is about " . $estimate->pickup_estimate . " minutes.\n";

    // This will actually book the ride request.
    echo "Now we're booking the ride.\n";
    $uber_sandbox = 'https://sandbox-api.uber.com/v1/requests';
    $response = $client->request('POST', $uber_sandbox, $options);
    $ride_request = json_decode($response->getBody());
    echo "  Your request id is " . $ride_request->request_id . ".\n";
    echo "  Your ride will arive in " . $ride_request->eta . " minutes.\n";
    echo "\n";
}
echo "\n\n";

// done: connect to google calendar
// done: define home
// done: get a list of meetings
// done: figure out where the meetings are
// todo: sort the list of meetings
// done: figure out if it's close enough to drive (100 km)
// done: figure out the lat/lon for where we are
// done: figure out the available products
// done: estimate a cost to get there
// todo: schedule a reservation for that time (sandbox)