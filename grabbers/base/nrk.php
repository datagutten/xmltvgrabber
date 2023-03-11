<?php


namespace datagutten\xmltv\grabbers\base;


use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\tools\build\programme;
use DateInterval;
use DateTimeImmutable;
use Exception;

/**
 * Grabber for NRK channels
 */
abstract class nrk extends common
{
    /**
     * @var string[] Channel name and IDs
     */
    public static $channels = [
        'nrk1.nrk.no'     => 'nrk1_oslo_viken',
        'nrk2.nrk.no'     => 'nrk2',
        'nrk3.nrk.no'     => 'nrk3',
        'nrksuper.nrk.no' => 'nrksuper',
    ];

    function grab(int $timestamp = null): ?string
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');
        $channel = self::$channels[$this->channel];
        list($day_start, $day_end) = self::day_start_end($timestamp);

        foreach (array(strtotime('-1 day', $timestamp), $timestamp) as $day)
        {
            $url = sprintf('https://psapi.nrk.no/epg/%s?date=%s', $channel, date('Y-m-d',$day));
            $data = $this->download_cache($url, $day, 'json');

            $info = json_decode($data, true);
            if (empty($info[0]['entries']))
            {
                unlink($this->local_file($day, 'json'));
                continue;
            }

            foreach($info[0]['entries'] as $entry)
            {
                $program_start = preg_replace('#/Date\(([0-9]+)[0-9]{3}\+[0-9]+\)/#','$1', $entry['actualStart']);

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
                if(!empty($entry['description']))
                    $programme->description($entry['description']);
                $programme->stop($end);
                if (!empty($entry['description']) && preg_match('#Sesong ([0-9]+) \(([0-9]+):([0-9]+)\)#', $entry['description'], $matches))
                    $programme->series($matches[2], $matches[1], $matches[3], $matches[0]);
            }
        }
        if(!empty($programme))
            return $this->save_file($timestamp);
        else
            throw new exceptions\GrabberException(sprintf('No programs found for date %s', date('Y-m-d', $day)));
    }
}
