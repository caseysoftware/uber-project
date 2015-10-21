<?php

include 'creds.php';
include 'vendor/autoload.php';

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




// done: connect to google calendar
// done: define home
// done: get a list of meetings
// done: figure out where the meetings are
// todo: sort the list of meetings
// todo: figure out the timing based on traffic
// todo: map a route between there and there
// todo: map a route home at the end of the day

