<?php

namespace datagutten\xmltv\grabbers;


use datagutten\xmltv\tools\build\programme;
use DOMDocument;
use DOMXPath;

class cbsreality extends base\common
{
    function __construct()
    {
        parent::__construct('cbsreality.com', 'en');
    }

    function grab($timestamp = 0)
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');
        $url = sprintf('https://www.cbsreality.tv/eu_2/tv_guide.php?date=%s&section=day', date('Y-m-d', $timestamp));
        $data = $this->download_cache($url, $timestamp);

        $dom = new DOMDocument();
        @$dom->loadHTML($data);
        $xpath = new DOMXPath($dom);
        $content = $dom->getElementById('content');
        $times = $xpath->query('./div[@class="tvguide-time"]', $content);
        $shows = $xpath->query('./div[@class="tvguide-show"]', $content);
        foreach ($shows as $key => $show)
        {
            $time = $times->item($key);
            $time = trim($time->textContent);
            $program_start = strtotime($time, $timestamp);

            $programme = new programme($program_start, $this->tv);
            $sub_title = $xpath->query('./span[@class="sub-title"]', $show);
            if ($sub_title->length == 1)
            {
                $sub_title = trim($sub_title->item(0)->textContent);
                $programme->sub_title($sub_title);
                if (preg_match('/Series ([0-9]+), Episode ([0-9]+)/', $sub_title, $matches))
                {
                    $programme->series($matches[2], $matches[1]);
                }
            }
            $title = $xpath->query('./a[@class="title"]', $show);
            $programme->title(trim($title->item(0)->textContent));
            $description = $xpath->query('./div[@class="tvguide-description"]', $show);
            $programme->description(trim($description->item(0)->textContent));
        }
        if (!empty($programme))
            return $this->save_file($timestamp);
        else
        {
            $cache_file = $this->local_file($timestamp);
            unlink($cache_file);
            throw new exceptions\GrabberException(sprintf('No programs found for date %s', date('Y-m-d', $timestamp)));
        }
    }
}