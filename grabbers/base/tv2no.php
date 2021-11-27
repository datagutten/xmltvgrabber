<?php

namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\tools\build\programme;
use datagutten\xmltv\grabbers\exceptions;


abstract class tv2no extends common
{
    /**
     * @var string[] Channel name and IDs
     */
    public static $channels = [
        'zebra.tv2.no' => 'tv2zebra',
        'tv2.no' => 'tv2norge',
        'livsstilhd.tv2.no' => 'tv2livsstil',
        'nyhet.tv2.no' => 'tv2nyhet',
        'sport1.tv2.no' => 'S01',
        'sport2.tv2.no' => 'S02',
        'discovery.no' => 'dischd',
    ];

    public function local_file(int $timestamp, $extension = 'html')
    {
        return $this->file('tv2.no', $timestamp, 'raw_data', $extension, true);
    }

    function grab($timestamp = null)
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');

        if (!isset(self::$channels[$this->channel]))
            throw new exceptions\GrabberException(sprintf('Unknown channel id: %s', $this->channel));

        $channel_id = self::$channels[$this->channel];
        list($day_start, $day_end) = self::day_start_end($timestamp);

        foreach (array(strtotime('-1 day', $timestamp), $timestamp) as $day)
        {
            $url = sprintf('https://rest.tv2.no/epg-dw-rest/epg/program/%s/', date('Y/m/d', $day));
            $json_raw = $this->download_cache($url, $day, 'json');
            $data = json_decode($json_raw, true);
            $channel_ids = array_column($data['channel'], 'shortName');
            if (array_search($channel_id, $channel_ids) === false)
                throw new exceptions\GrabberException(sprintf('Unknown channel short name: %s', $channel_id));

            if (empty($data['channel']))
            {
                echo sprintf("No data for %s\n", date('Y-m-d', $timestamp));
                continue;
            }

            foreach ($data['channel'] as $channel)
            {
                if ($channel['shortName'] != $channel_id)
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
                        $programme->series(intval($program['epnr']), intval($program['season']), intval($program['eptot']));

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
        return $this->save_file($timestamp);
    }
}