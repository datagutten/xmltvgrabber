<?php


namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\tools\build\programme;
use Requests_Exception;

/**
 * Common grabber class for Discovery Networks
 */
class discovery_no extends common
{
    /**
     * @var string[] Channel name and IDs
     */
    public static $channels = [
        'tvnorge.no'                 => 'TVNorge',
        'fem.no'                     => 'FEM',
        'max.no'                     => 'MAX',
        'voxtv.no'                   => 'VOX',
        'animalplanet.discovery.no'  => 'Animal Planet',
        'tlc.discovery.no'           => 'TLC',
        'discovery.no'               => 'Discovery Channel',
        'investigation.discovery.no' => 'Investigation Discovery',
        'science.discovery.no'       => 'Discovery Science',
    ];


    function grab($timestamp=null)
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');

        $channel = self::$channels[$this->channel];
        $url_template='http://epg.dnnservice.no/dplayepg/?channel=%s&date=%s';

        foreach(array(strtotime('-1 day',$timestamp),$timestamp) as $day)
        {
            $url=sprintf($url_template,urlencode($channel),date('Y-m-d',$day));
            try {
                $data = $this->download_cache($url, $day, 'json');
            } // @codeCoverageIgnoreStart
            catch (Requests_Exception $e) {
                echo 'Error loading data: '.$e->getMessage();
                return null;
            } // @codeCoverageIgnoreEnd

            if ($data === '[]')
            {
                $cache_file = $this->local_file($day, 'json');
                unlink($cache_file);
                return null;
            }

            list($day_start, $day_end) = self::day_start_end($timestamp);

            $programs=json_decode($data,true);
            foreach($programs as $program)
            {
                $program_start = strtotime($program['startTime']);

                if($program_start<$day_start)
                    continue;
                if($program_start>$day_end)
                    break 2;

                $programme = new programme($program_start, $this->tv);
                $programme->title($program['title']);
                if(!empty($program['shortDescription']))
                    $programme->description($program['shortDescription']);
                $programme->stop(strtotime($program['endTime']));

                if(!empty($program['episode']))
                    $programme->series($program['episode'], $program['season'], $program['numEpisodes']);
            }
        }
        return $this->save_file($timestamp);
    }
}
