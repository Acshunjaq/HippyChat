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
    protected $dryRun;
    protected $debug;

    /**
     * @param      $apiEndpointUrl
     * @param      $token
     * @param int  $maxDays
     * @param bool $debug
     * @param bool $dryRun
     */
    public function __construct(
        $apiEndpointUrl,
        $token,
        $maxDays,
        $debug = false,
        $dryRun = false
    ) {
        $this->apiEndpointUrl = $apiEndpointUrl;
        $this->token          = $token;
        $this->maxDays        = $maxDays;
        $this->authString     = '?auth_token=' . $this->token;
        $this->now            = new Carbon($this->dateTimeZone);
        $this->client         = new Client();
        $this->debug          = $debug;
        $this->dryRun         = $dryRun;
    }

    /**
     * Archive rooms
     */
    public function run()
    {
        if ($this->dryRun) {
            $this->out('** Dry run - no archiving actions performed **');
        }
        $rooms = $this->getRooms();

        if (count($rooms) <= 0) {
            $this->out('No rooms found, exiting');
        }

        foreach ($rooms as $room) {
            $lastActiveDate  = $this->getLastActiveDate($room);
            $lastMessageDate = $this->getLastMessageDate($room);

            $lastActiveDateDiff  = $this->now->diffInDays($lastActiveDate);
            $lastMessageDateDiff = $this->now->diffInDays($lastMessageDate);

            $lesserDiffInDays = $lastActiveDateDiff < $lastMessageDateDiff ? $lastActiveDateDiff : $lastMessageDateDiff;

            if ($lesserDiffInDays >= $this->maxDays) {
                $this->out(
                    'Archiving: ' . $room['name'] . $this->humanDays($lesserDiffInDays)
                );
                $this->archive($room);
            } else {
                $this->out(
                    'Not Archiving: ' . $room['name'] . $this->humanDays($lesserDiffInDays)
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

        if ($this->debug) {
            $this->requestDebugInfo($url, $response);
        }

        return $rooms['items'];
    }

    /**
     * @param $room
     *
     * @return Carbon|null
     */
    protected function getLastMessageDate($room)
    {
        $roomMessage = $this->getLastRoomMessage($room);

        if ($roomMessage) {
            return new Carbon($roomMessage['date'], $this->dateTimeZone);
        }

        return null;
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

        if ($this->debug) {
            $this->requestDebugInfo($url, $response);
        }

        return $response->json();
    }

    /**
     * @param $room
     *
     * @return mixed
     */
    protected function getLastRoomMessage($room)
    {
        $url = $this->apiEndpointUrl
            . 'room/'
            . $room['id']
            . '/history'
            . $this->authString
            . '&max-results=1';

        $response = $this->client->get($url);

        if ($this->debug) {
            $this->requestDebugInfo($url, $response);
        }

        $messages = $response->json();

        return isset($messages['items'][0]) ? $messages['items'][0] : null;
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

        if (!$this->dryRun) {
            // Punch it!
            $response = $this->client->put($url, $payload);

            if ($this->debug) {
                $this->requestDebugInfo($url, $response, $payload);
            }
        } else {
            $this->requestDebugInfo($url);
        }
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

        if ($this->debug) {
            $this->requestDebugInfo($url, $response);
        }

        return $response->json();
    }

    /**
     * @param $msg
     */
    protected function out($msg)
    {
        echo $msg . PHP_EOL;
    }

    protected function requestDebugInfo($url, $response = null, $payload = null)
    {
        $pre  = "\x1B[90m";
        $post = "\x1B[39m";
        $this->out($pre . '### Begin debug info' . $post);
        if ($payload) {
            print_r($payload);
        }
        $this->out($pre . $url . ($response ? ' ' . $response->getStatusCode() : '') . $post);
        $this->out($pre . '### End debug info' . $post);
    }
}
