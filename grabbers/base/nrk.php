<?php


namespace datagutten\xmltv\grabbers\base;


use datagutten\xmltv\tools\build\programme;
use DateInterval;
use DateTimeImmutable;
use Exception;
use Requests_Exception;

abstract class nrk extends common
{
    public $channels = ['nrk1.nrk.no'=>'nrk1_oslo_viken', 'nrk2.nrk.no'=>'nrk2', 'nrk3.nrk.no'=>'nrk3','nrksuper.nrk.no'=>'nrksuper'];
    function grab($timestamp = null)
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');
        $channel = $this->channels[$this->channel];
        list($day_start, $day_end) = self::day_start_end($timestamp);

        foreach (array(strtotime('-1 day', $timestamp), $timestamp) as $day)
        {
            $url = sprintf('https://psapi.nrk.no/epg/%s?date=%s', $channel, date('Y-m-d',$day));
            try {
                $data = $this->download_cache($url, $day, 'json');

            } // @codeCoverageIgnoreStart
            catch (Requests_Exception $e) {
                echo $e->getMessage();
                return null;
            } // @codeCoverageIgnoreEnd

            $info = json_decode($data, true);
            foreach($info[0]['entries'] as $entry)
            {
                $program_start = preg_replace('#/Date\(([0-9]+)000\+[0-9]+\)/#','$1', $entry['actualStart']);

                if($program_start<$day_start)
                    continue;
                if($program_start>$day_end)
                    break 2;
                $duration = preg_replace('#(.+)\.[0-9]+S#', '$1S', $entry['duration']);

                try {
                    $duration = new DateInterval($duration);
                    $start = new DateTimeImmutable(sprintf('@%d', $program_start));
                } // @codeCoverageIgnoreStart
                catch (Exception $e) {
                    echo $e->getMessage();
                    return null;
                } // @codeCoverageIgnoreEnd

                $end = $start->add($duration);

                $end = $end->getTimestamp();


                $programme = new programme($program_start, $this->tv);
                $programme->title($entry['title']);
                $programme->description($entry['description']);
                $programme->stop($end);
                if(preg_match('#Sesong ([0-9]+) \(([0-9]+):([0-9]+)\)#', $entry['description'], $matches)) {
                    $programme->series($matches[2], $matches[1], $matches[3], $matches[0]);
                }
            }
        }
        if(!empty($programme))
            return $this->save_file($timestamp);
        else
            unlink($this->local_file($timestamp, 'json'));
    }
}