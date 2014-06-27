<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use GuzzleHttp\Client;
use Carbon\Carbon;

$dateTimeZone = 'America/Vancouver';

$config          = Yaml::parse(__DIR__ . '/../config/parameters.yml');
$parameters      = $config['parameters'];
$token           = $parameters['hipchat_auth_token'];
$maxInactiveDays = $parameters['inactivity_limit_in_days'];
$authString      = '?auth_token=' . $token;
$apiUrl          = 'https://api.hipchat.com/v2/';
$client          = new Client();
$url             = $apiUrl . 'room' . $authString;
$response        = $client->get($url);
$rooms           = $response->json(); // Outputs the JSON decoded data

$maxInactiveDate = new Carbon($dateTimeZone);
$maxInactiveDate->subDays($maxInactiveDays);

foreach ($rooms['items'] as $room) {
    $url            = $apiUrl . 'room/' . $room['id'] . '/statistics' . $authString;
    $response       = $client->get($url);
    $roomStatistics = $response->json();

    $lastActiveDate = new Carbon($roomStatistics['last_active'], $dateTimeZone);

    if ($maxInactiveDate->gte($lastActiveDate)) {
        echo "Archiving: " . $room['name'] . PHP_EOL;
        $url = $apiUrl . 'room/' . $room['id'] . $authString;
        $response = $client->get($url);
        $room     = $response->json();

        // Rebuild the room options, but change to archived and no guest access
        $payload = array(
            'json' => array(
                'name'                => $room['name'],
                'privacy'             => $room['privacy'],
                'is_archived'         => true,
                'is_guest_accessible' => false,
                'topic'               => $room['topic'],
                'owner'               => array('id' => $room['owner']['id']),
            ),
        );
        $url     = $apiUrl . 'room/' . $room['id'] . $authString;
        $client->put($url, $payload);
    } else {
        echo "Not Archiving: " . $room['name'] . PHP_EOL;
    }
}
