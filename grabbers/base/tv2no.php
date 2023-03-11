<?php

namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\tools\build\programme;
use datagutten\xmltv\grabbers\exceptions;


abstract class tv2no extends common
{
    /**
     * @var string TV2 channel slug
     */
    public static string $slug;
    public static string $language = 'nb';

    public function local_file(int $timestamp, $extension = 'html')
    {
        return $this->file('tv2.no', $timestamp, 'raw_data', $extension, true);
    }

    function grab(int $timestamp = null): ?string
    {
        if(empty(static::$slug))
            throw new exceptions\GrabberException(sprintf('Channel slug not defined in grabber %s', static::class));

        if (empty($timestamp))
            $timestamp = strtotime('midnight');

        list($day_start, $day_end) = self::day_start_end($timestamp);

        foreach (array(strtotime('-1 day', $timestamp), $timestamp) as $day)
        {
            $url = sprintf('https://rest.tv2.no/epg-dw-rest/epg/program/%s/', date('Y/m/d', $day));
            $json_raw = $this->download_cache($url, $day, 'json');
            $data = json_decode($json_raw, true);

            if (empty($data['channel']))
            {
                //echo sprintf("No data for %s\n", date('Y-m-d', $day));
                unlink($this->local_file($day, 'json'));
                continue;
            }

            $channel_ids = array_column($data['channel'], 'shortName');
            if (array_search(static::$slug, $channel_ids) === false)
                throw new exceptions\GrabberException(sprintf('Unknown channel slug "%s" in grabber %s', static::$slug, static::class));

            foreach ($data['channel'] as $channel)
            {
                if ($channel['shortName'] != static::$slug)
                    continue;

                foreach ($channel['program'] as $program)
                {
                    $program_start = strtotime($program['start']);

                    if ($program_start < $day_start)
                        continue;
                    if ($program_start > $day_end)
                        break 2;

                    $programme = new programme($program_start, $this->tv);
                    $programme->stop(strtotime($program['stop']));

                    if (!empty($program['title']))
                        $programme->title($program['title'], 'nb');

                    if (!empty($program['epttl']))
                        $programme->sub_title($program['epttl']);

                    if (!empty($program['epsyn'])) //Episode description
                        $programme->description(trim($program['epsyn']), 'nb');
                    elseif (!empty($program['srsyn'])) //Series description
                        $programme->description(trim($program['srsyn']), 'nb');

                    if (!empty($program['genre']))
                        $programme->xml->addChild('category', $program['genre']);

                    if (!empty($program['epnr'])) //Add episode information
                        $programme->series(intval($program['epnr']), intval($program['season']), intval($program['eptot'] ?? 0));

                    if ($program['isrepl'])
                        $programme->xml->addChild('previously-shown');
                    if (!empty($program['age']))
                    {
                        $rating = $programme->xml->addChild('rating');
                        $rating->addChild('value', $program['age']);
                    }
                }
            }
        }

        if(!empty($programme))
            return $this->save_file($timestamp);
        else
            throw new exceptions\GrabberException(sprintf('No programs found for date %s', date('Y-m-d', $timestamp)));
    }
}