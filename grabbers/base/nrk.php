<?php


namespace datagutten\xmltv\grabbers\base;


use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\grabbers\exceptions\GrabberException;
use datagutten\xmltv\tools\build\programme;
use DateInterval;
use DateTimeImmutable;
use Exception;

/**
 * Grabber for NRK channels
 */
abstract class nrk extends common
{
    public static string $language = 'nb';
    public static string $time_zone_str = 'Europe/Oslo';
    /**
     * @var string[] Channel name and IDs
     */
    public static $channels = [
        'nrk1.nrk.no'     => 'nrk1',
        'nrk2.nrk.no'     => 'nrk2',
        'nrk3.nrk.no'     => 'nrk3',
        'nrksuper.nrk.no' => 'nrksuper',
    ];

    function grab(?int $timestamp = null): ?string
    {
        $channel = self::$channels[static::$xmltv_id];
        list($day_start, $day_end) = $this->day_arg($timestamp);
        $yesterday = $day_start->sub(new DateInterval('P1D'));

        /** @var DateTimeImmutable $day */
        foreach ([$yesterday, $day_start] as $day)
        {
            $timestamp = $day->getTimestamp() + 3600;
            $url = sprintf('https://psapi.nrk.no/epg/%s?date=%s', $channel, $day->format('Y-m-d'));
            $data = $this->download_cache($url, $timestamp, 'json');

            $info = json_decode($data, true);
            if (empty($info[0]['entries']))
            {
                unlink($this->local_file($timestamp, 'json'));
                continue;
            }

            foreach($info[0]['entries'] as $entry)
            {
                $program_start = preg_replace('#/Date\(([0-9]+)[0-9]{3}\+[0-9]+\)/#','$1', $entry['actualStart']);
                $duration = preg_replace('#(.+)\.[0-9]+S#', '$1S', $entry['duration']);
                try {
                    $duration = new DateInterval($duration);
                    $program_start= $this->parse_timestamp_tz($program_start);
                } // @codeCoverageIgnoreStart
                catch (Exception $e) {
                    throw new GrabberException($e->getMessage(), $e->getCode(), $e);
                } // @codeCoverageIgnoreEnd

                if($program_start<$day_start)
                    continue;
                if($program_start>$day_end)
                    break 2;

                $end = $program_start->add($duration);

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
            throw new exceptions\GrabberException(sprintf('No programs found for date %s', $day->format('Y-m-d')));
    }
}
