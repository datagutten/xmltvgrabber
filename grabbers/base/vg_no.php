<?php

namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\tools\build\programme;
use Symfony\Component\Process\Process;

abstract class vg_no extends common
{
    public static $slug;
    public static $xmltv_id;
    public static $language = 'nb';

    function __construct()
    {
        parent::__construct(static::$xmltv_id, static::$language);
    }

    public static function get_url(string $slug, int $timestamp)
    {
        return sprintf('https://tvguide.vg.no/kanal/%s/%s', $slug, date('Y-m-d', $timestamp));
    }

    function grab($timestamp = null)
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');

        list($day_start, $day_end) = self::day_start_end($timestamp);
        $this->tv->xml->addAttribute('source-info-url', self::get_url(static::$slug, $timestamp));

        foreach (array(strtotime('-1 day', $timestamp), $timestamp) as $day)
        {
            $url = self::get_url(static::$slug, $day);
            $html = $this->download_cache($url, $day, 'html', 30);
            preg_match('#<script>(__INITIAL_STATE__.+)</script>#', $html, $matches);
            $data = self::parse_js_array($matches[1], '__INITIAL_STATE__');
            foreach ($data['schedule']['broadcasts'] as $program)
            {
                $program_start = $program['broadcast']['startTime'] / 1000;
                $program_end = $program['broadcast']['endTime'] / 1000;

                if ($program_start < $day_start)
                    continue;
                if ($program_start > $day_end)
                    break 2;

                $programme = new programme($program_start, $this->tv);
                $programme->stop($program_end);
                $programme->title($program['title']);
                $programme->description($program['description']);

                if (!empty($program['year']))
                    $programme->xml->addChild('date', $program['year']);

                if (!empty($program['genres']))
                {
                    foreach ($program['genres'] as $genre)
                        $programme->xml->addChild('category', $genre);
                }

                if (!empty($program['imdb']['link']))
                    $programme->xml->addChild('url', $program['imdb']['link']);

                if (!empty($program['seasonNumber']))
                    $programme->series($program['episodeNumber'] ?? 0, $program['seasonNumber']);

                if (!empty($program['imdb']))
                {
                    $rating = $programme->xml->addChild('rating');
                    $rating->addAttribute('system', 'IMDb');
                    $rating->addChild('value', $program['imdb']['rating']);
                }
            }
            if(empty($programme))
                unlink($this->local_file($day));
        }
        return $this->save_file($timestamp);
    }

    public static function parse_js_array($array, $name)
    {
        $js = sprintf("%s;\nconsole.log(JSON.stringify(%s))", $array, $name);
        $process = new Process(['node']);
        $process->setInput($js);
        $process->run();
        $json_string = $process->getOutput();
        return json_decode(json_decode($json_string, true), true);
    }
}