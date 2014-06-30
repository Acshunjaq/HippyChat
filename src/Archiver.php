<?php

use GuzzleHttp\Client;
use Carbon\Carbon;

class Archiver
{
    protected $dateTimeZone = 'UTC';
    protected $apiEndpointUrl;
    protected $token;
    protected $maxDays;
    protected $now;
    protected $authString;
    protected $client;

    /**
     * @param $apiEndpointUrl
     * @param $token
     * @param $maxDays
     */
    public function __construct($apiEndpointUrl, $token, $maxDays)
    {
        $this->apiEndpointUrl = $apiEndpointUrl;
        $this->token          = $token;
        $this->maxDays        = $maxDays;
        $this->authString     = '?auth_token=' . $this->token;
        $this->now            = new Carbon($this->dateTimeZone);
        $this->client         = new Client();
    }

    /**
     * Archive rooms
     */
    public function run()
    {
        $rooms = $this->getRooms();

        if (count($rooms) <= 0) {
            $this->out('No rooms found, exiting.');
        }

        foreach ($rooms as $room) {
            $lastActiveDate = $this->getLastActiveDate($room);
            $diffInDays     = $this->now->diffInDays($lastActiveDate);

            if ($diffInDays >= $this->maxDays) {
                $this->out(
                    'Archiving: ' . $room['name'] . $this->humanDays($diffInDays)
                );
                $this->archive($room);
            } else {
                $this->out(
                    'Not Archiving: ' . $room['name'] . $this->humanDays($diffInDays)
                );
            }
        }
    }

    /**
     * @param int $diffInDays
     *
     * @return string
     */
    protected function humanDays($diffInDays)
    {
        $day = 'day';
        if (1 !== $diffInDays) {
            $day .= 's';
        }

        return ' (' . $diffInDays . ' ' . $day . ' old)';
    }

    /**
     * @param $dateTimeZone
     */
    public function setDateTimeZone($dateTimeZone)
    {
        $this->dateTimeZone = $dateTimeZone;
    }


    /**
     * @return mixed
     */
    protected function getRooms()
    {
        $url      = $this->apiEndpointUrl . 'room' . $this->authString;
        $response = $this->client->get($url);
        $rooms    = $response->json();

        return $rooms['items'];
    }

    /**
     * @param $room
     *
     * @return Carbon
     */
    protected function getLastActiveDate($room)
    {
        $roomStatistics = $this->getRoomStatistics($room);

        return new Carbon($roomStatistics['last_active'], $this->dateTimeZone);
    }

    /**
     * @param $room
     *
     * @return mixed
     */
    protected function getRoomStatistics($room)
    {
        $url = $this->apiEndpointUrl
            . 'room/'
            . $room['id']
            . '/statistics'
            . $this->authString;

        $response = $this->client->get($url);

        return $response->json();
    }

    /**
     * @param $room
     */
    protected function archive($room)
    {
        $roomInfo = $this->getRoomInfo($room);

        // Rebuild the room options, but change to archived and no guest access
        $payload = array(
            'json' => array(
                'name'                => $roomInfo['name'],
                'privacy'             => $roomInfo['privacy'],
                'is_archived'         => true,
                'is_guest_accessible' => false,
                'topic'               => $roomInfo['topic'],
                'owner'               => array('id' => $roomInfo['owner']['id']),
            ),
        );

        $url = $this->apiEndpointUrl . 'room/' . $room['id'] . $this->authString;

        // Punch it!
        $this->client->put($url, $payload);
    }

    /**
     * @param $room
     *
     * @return mixed
     */
    protected function getRoomInfo($room)
    {
        $url      = $this->apiEndpointUrl . 'room/' . $room['id'] . $this->authString;
        $response = $this->client->get($url);

        return $response->json();
    }

    /**
     * @param $msg
     */
    protected function out($msg)
    {
        echo $msg . PHP_EOL;
    }
}
