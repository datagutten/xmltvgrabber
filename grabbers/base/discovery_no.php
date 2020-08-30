<?php


namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\tools\build\programme;
use Requests_Exception;

class discovery_no extends common
{
    public $channels=array(
        'tvnorge.no'=>'TVNorge',
        'fem.no'=>'FEM',
        'max.no'=>'MAX',
        'voxtv.no'=>'VOX',
        'eurosport.no'=>'Eurosport Norge',
        'animalplanet.discovery.no'=>'Animal Planet',
        'tlc.discovery.no'=>'TLC',
        'discovery.no'=>'Discovery Channel',
        'investigation.discovery.no'=>'Investigation Discovery',
        'science.discovery.no'=>'Discovery Science','world.discovery.no'=>'Discovery World','hd.discovery.no'=>'Discovery HD Showcase');
    function grab($timestamp=null)
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');

        $channel = $this->channels[$this->channel];
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
                $programme->description($program['shortDescription']);
                $programme->stop(strtotime($program['endTime']));

                if(!empty($program['episode']))
                    $programme->series($program['episode'], $program['season'], $program['numEpisodes']);
            }
        }
        return $this->save_file($timestamp);
    }
}