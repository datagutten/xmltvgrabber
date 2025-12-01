<?php

namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\tools\build\programme;


abstract class tv2no extends common
{
    /**
     * @var string TV2 channel slug
     */
    public static string $slug;
    public static string $language = 'nb';
    public static bool $refetch = true;

    public function local_file(int $timestamp, $extension = 'html')
    {
        return $this->file('tv2.no', $timestamp, 'raw_data', $extension, true);
    }

    function grab(int $timestamp = null): ?string
    {
        if (empty(static::$slug))
            throw new exceptions\GrabberException(sprintf('Channel slug not defined in grabber %s', static::class));

        if (empty($timestamp))
            $timestamp = strtotime('midnight');

        list($day_start, $day_end) = self::day_start_end($timestamp);

        foreach (array(strtotime('-1 day', $timestamp), $timestamp) as $day)
        {
            $url = sprintf('https://tv2no-epg-api.public.tv2.no/epg/days/%s', date('Y/m/d', $day));
            $json_raw = $this->download_cache($url, $day, 'json');
            $data = json_decode($json_raw, true);
            $local_file = $this->local_file($day, 'json');
            $cache_age = time() - filemtime($local_file);

            if (empty($data))
            {
                //echo sprintf("No data for %s\n", date('Y-m-d', $day));
                unlink($this->local_file($day, 'json'));
                continue;
            }

            $channel_ids = array_column($data, 'channelId');
            //Clear the cache if data is missing
            //Make sure the cache file is at least 30 minutes old to avoid infinite loops
            if (!in_array(static::$slug, $channel_ids) && $cache_age > 1800)
            {
                unlink($local_file);
                return $this->grab($timestamp);
            }

            if (!in_array(static::$slug, $channel_ids))
                throw new exceptions\GrabberException(sprintf('Unknown channel slug "%s" in grabber %s', static::$slug, static::class));

            foreach ($data as $channel)
            {
                if ($channel['channelId'] != static::$slug)
                    continue;

                foreach ($channel['programs'] as $program)
                {
                    $program_start = strtotime($program['startTime']);

                    if ($program_start < $day_start)
                        continue;
                    if ($program_start > $day_end)
                        break 2;

                    $programme = new programme($program_start, $this->tv);
                    $programme->stop(strtotime($program['endTime']));

                    if (!empty($program['title']))
                        $programme->title($program['title'], 'nb');

                    /*                    if (!empty($program['epttl']))
                                            $programme->sub_title($program['epttl']);*/

                    if (!empty($program['synopsis'])) //Episode description
                        $programme->description(trim($program['synopsis']), 'nb');
                    elseif (!empty($program['seasonSynopsis'])) //Series description
                        $programme->description(trim($program['seasonSynopsis']), 'nb');

                    if (!empty($program['genre']))
                        $programme->xml->addChild('category', $program['genre']);

                    if ($program['episodeNumber'] > 0) //Add episode information
                        $programme->series($program['episodeNumber'], $program['seasonNumber'], $program['episodeCount']);

                    if ($program['replay'])
                        $programme->xml->addChild('previously-shown');
                    if (!empty($program['ageRating']))
                    {
                        $rating = $programme->xml->addChild('rating');
                        $rating->addChild('value', $program['ageRating']);
                    }
                }
            }
        }

        if (!empty($programme))
            return $this->save_file($timestamp);
        else
            throw new exceptions\GrabberException(sprintf('No programs found for date %s', date('Y-m-d', $timestamp)));
    }
}