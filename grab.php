<?php

use datagutten\xmltv\grabbers;

require __DIR__.'/vendor/autoload.php';
set_include_path(__DIR__);

if(isset($argv[1]) && $argv[1]!='all')
    $grabbers = [grabbers\grabbers::grabber($argv[1])];
else
    $grabbers = grabbers\grabbers::getGrabbers();

foreach ($grabbers as $grabber_class) {

    if (!isset($argv[2]))
        $start_timestamp = time();
    else
        $start_timestamp = strtotime($argv[2]);

    if (!isset($argv[3])) //If no end timestamp is specified, end today
        $end_timestamp = $start_timestamp;
    else
        $end_timestamp = strtotime($argv[3]);

    printf("Grabbing from %s to %s\n", date('c', $start_timestamp), date('c', $end_timestamp));

    for ($timestamp = $start_timestamp; $timestamp <= $end_timestamp; $timestamp = $timestamp + 86400) {
        /**
         * @var $grabber grabbers\base\common
         */
        $grabber = new $grabber_class;
        $file = $grabber->grab($timestamp);
        echo $file . "\n";
        unset($grabber);
    }
}