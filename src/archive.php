<?php

require __DIR__ . '/../vendor/autoload.php';

$file = __DIR__ . '/../config/parameters.ini';

if (!file_exists($file)) {
    die("Parameter file missing. Perhaps you haven't copied the dist file over");
}

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

$dryRun = false;
if (in_array('dry-run', $argv)) {
    $dryRun = true;
    $debug = true;
}

$parameters = parse_ini_file($file);

if ($debug) {
    print_r(array("Parsed parameters", $parameters));
}

$archiver = new Archiver(
    $parameters['api_endpoint_url'],
    $parameters['hipchat_auth_token'],
    $parameters['inactivity_limit_in_days'],
    $debug,
    $dryRun
);

$archiver->run();
