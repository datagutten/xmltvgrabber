<?php


namespace datagutten\xmltv\grabbers\base;


use datagutten\xmltv\tools\build\programme;
use Requests_Exception;

/**
 * Common grabber class for Disney
 */
abstract class disney_no extends common
{
    /**
     * @var string[] Channel name and IDs
     */
    public static $channels = [
        'disneychannel.no'    => '/tv-oversikt',
        'xd.disneychannel.no' => '/tv-oversikt/disney-xd',
        //'junior.disneychannel.no' => '/tv-oversikt/disney-junior',
    ];

    function grab($timestamp=null)
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');
        $channel = self::$channels[$this->channel];
        $url = sprintf('https://tv.disney.no/_schedule/full/%s/2/%s', date('Ymd',$timestamp), urlencode($channel));
        try {
            $data = $this->download_cache($url, $timestamp, 'json');
        } // @codeCoverageIgnoreStart
        catch (Requests_Exception $e) {
            echo 'Error loading data: '.$e->getMessage();
            return null;
        } // @codeCoverageIgnoreEnd

        list($day_start, $day_end) = self::day_start_end($timestamp);

        $schedule=json_decode($data,true);
        foreach($schedule['schedule'] as $time_period)
        {
            if(empty($time_period['schedule_items']))
                continue;
            foreach($time_period['schedule_items'] as $schedule_item)
            {
                $program_start = strtotime($schedule_item['iso8601_utc_time']);

                if($program_start<$day_start)
                    continue;
                if($program_start>$day_end)
                    break 2;

                if(isset($programme)) //The stop time of the previous program is the start of the current
                    $programme->stop($program_start);

                $programme = new programme($program_start, $this->tv);
                $programme->title($schedule_item['show_title']);
                $programme->description($schedule_item['description']);

                if(!empty($schedule_item['episode_title']))
                {
                    $programme->sub_title($schedule_item['episode_title']);
                }
                //break;

            }
        }
        if(!empty($programme))
            return $this->save_file($timestamp);
    }
}
