<?php

require __DIR__ . '/../vendor/autoload.php';

$file = __DIR__ . '/../config/parameters.ini';

if (!file_exists($file)) {
    die("Parameter file missing. Perhaps you haven't copied the dist file over");
}
$parameters = parse_ini_file($file);

$archiver = new Archiver(
    $parameters['api_endpoint_url'],
    $parameters['hipchat_auth_token'],
    $parameters['inactivity_limit_in_days']
);

$archiver->run();
