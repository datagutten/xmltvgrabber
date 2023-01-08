<?php

namespace datagutten\xmltv\grabbers\base;

use datagutten\tools\files\files as file_tools;
use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\tools\build\programme;
use DateTime;
use Symfony\Component\Process\Process;

abstract class vg_no extends common
{
    public static string $slug;
    public static string $language = 'nb';

    public static function get_url(string $slug, int $timestamp)
    {
        return sprintf('https://tvguide.vg.no/kanal/%s/%s', $slug, date('Y-m-d', $timestamp));
    }

    protected static function get_data($html): array
    {
        preg_match('#<script id="__NEXT_DATA__" type="application/json">(.+?)</script>#', $html, $matches);
        return json_decode($matches[1], true);
    }

    /**
     * @param string $topic
     * @param string $slug
     * @return mixed
     * @throws exceptions\ConnectionError
     */
    public function get_element(string $topic, string $slug)
    {
        $folder = file_tools::path_join($this->files->xmltv_path, 'vg.no.metadata', $topic);
        $cache_file = file_tools::path_join($folder, $slug . '.json');
        if (file_exists($cache_file))
            $data = json_decode(file_get_contents($cache_file), true);
        else
        {
            $topic_mapping = ['movie' => 'film', 'series' => 'program'];
            $this->files->filesystem->mkdir($folder);
            $html = $this->get(sprintf('https://tvguide.vg.no/%s/%s', $topic_mapping[$topic], $slug));
            $data = static::get_data($html);
            file_put_contents($cache_file, json_encode($data));
        }
        return $data['props']['pageProps']['title'];
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
            preg_match('#<script id="__NEXT_DATA__" type="application/json">(.+?)</script>#', $html, $matches);
            $data = json_decode($matches[1], true);
            foreach ($data['props']['pageProps']['initialTvSchedule']['listings'] as $program)
            {
                if (isset($program_start) && strtotime($program['startsAt']) <= $program_start)
                    continue;
                $program_start = strtotime($program['startsAt']);
                $program_end = strtotime($program['endsAt']);

                if ($program_start < $day_start)
                    continue;
                if ($program_start > $day_end)
                    break 2;

                $programme = new programme($program_start, $this->tv);
                $programme->stop($program_end);
                $programme->title($program['title']['title']);

                if (in_array($program['title']['type'], ['movie', 'series']))
                {
                    try
                    {
                        $info = $this->get_element($program['title']['type'], $program['title']['slug']);
                        if(!empty($info['releasedAt']))
                            $programme->date(new DateTime($info['releasedAt']));
                        if (!empty($info['imdbId']))
                            $programme->url_imdb($info['imdbId']);
                        foreach ($info['genres'] as $genre)
                        {
                            $programme->category($genre['name']);
                        }
                        if ($program['title']['type'] == 'movie' && !empty($info['overview']))
                            $programme->description($info['overview']);
                    }
                    catch (exceptions\ConnectionError $e)
                    {
                        trigger_error(sprintf('Unable to get information for %s', $program['title']['title']));
                    }

                    if ($program['title']['type'] == 'series' && !empty($program['episode']))
                    {
                        $programme->series($program['episode']['episodeNumber'] ?? 0, $program['episode']['seasonNumber']);
                        if (!empty($program['episode']['name']))
                            $programme->sub_title($program['episode']['name']);
                        if(!empty($program['episode']['overview']))
                            $programme->description($program['episode']['overview']);
                    }
                }
                else
                {
                    if(!empty($program['episode']))
                        $programme->description($program['episode']['overview']);
                    if(!empty($program['releasedAt']))
                        $programme->date(new DateTime($program['releasedAt']));
                }

                if (!empty($program['imdb']))
                {
                    $rating = $programme->xml->addChild('rating');
                    $rating->addAttribute('system', 'IMDb');
                    $rating->addChild('value', $program['imdb']['rating']);
                }
            }
            if (empty($programme))
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