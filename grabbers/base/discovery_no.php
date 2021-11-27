<?php


namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\tools\build\programme;
use DateTimeImmutable;

/**
 * Common grabber class for Discovery Networks
 */
class discovery_no extends common
{
    /**
     * @var string[] Channel name and IDs
     */
    public static $channels = [
        'tvnorge.no' => 'no.sbsdiscovery.channel.tvn',
        'fem.no' => 'no.sbsdiscovery.channel.fem',
        'max.no' => 'no.sbsdiscovery.channel.max',
        'voxtv.no' => 'no.sbsdiscovery.channel.vox',
        //'animalplanet.discovery.no'  => 'Animal Planet',
        'tlc.discovery.no' => 'TLCN',
        //'discovery.no'               => 'Discovery Channel',
        'investigation.discovery.no' => 'IDXE',
        //'science.discovery.no'       => 'Discovery Science',
    ];

    /**
     * Get EPG URL
     * @param string $xmltv_channel XMLTV channel string
     * @param int $timestamp
     * @return string URL string
     * @throws exceptions\GrabberException Unknown channel id
     */
    public static function get_url(string $xmltv_channel, int $timestamp): string
    {
        $date = new DateTimeImmutable();
        $date = $date->setTimestamp($timestamp);
        $day_start = $date->setTime(0, 0);
        $day_end = $date->setTime(23, 59, 59);
        $date_format = 'Y-m-d\TH:i:s.v\Z';
        if (!isset(self::$channels[$xmltv_channel]))
            throw new exceptions\GrabberException(sprintf('Unknown channel id: %s', $xmltv_channel));

        $channel = self::$channels[$xmltv_channel];
        $url_template = 'https://disco-api.discoveryplus.no/tvlistings/v2/channels/%s?startDate=%s&endDate=%s';
        return sprintf(
            $url_template,
            $channel,
            $day_start->format($date_format),
            $day_end->format($date_format)
        );
    }

    /**
     * Get token (set cookie)
     * @return string Token
     * @throws exceptions\ConnectionError
     */
    public function get_token(): string
    {
        $url = 'https://disco-api.discoveryplus.no/token?realm=dplayno&deviceId=6d100d808ee332f46b18d609f6aac8791aae38e50abc261ff08ba55d335f169b&shortlived=true';
        $response = $this->get($url);
        $data = json_decode($response, true);
        return $data['data']['attributes']['token'];
    }

    function grab($timestamp = null)
    {
        if (empty($timestamp))
            $timestamp = strtotime('midnight');
        $this->get_token();

        $url = self::get_url($this->channel, $timestamp);
        $data = $this->download_cache($url, $timestamp, 'json');

        $programs = json_decode($data, true);
        if (empty($programs['data']))
        {
            $cache_file = $this->local_file($timestamp, 'json');
            unlink($cache_file);
            return null;
        }

        foreach ($programs['data'] as $program)
        {
            $attributes = $program['attributes'];
            $program_start = strtotime($attributes['utcStart']);
            $programme = new programme($program_start, $this->tv);
            $programme->stop($program_start + $attributes['duration']);

            $programme->title($attributes['showName']);
            if (!empty($attributes['eventName']))
                $programme->sub_title($attributes['eventName']);

            if (!empty($attributes['description']))
                $programme->description($attributes['description']);

            if (!empty($attributes['episode']))
                $programme->series($attributes['episode'], $attributes['season'] ?? null);
        }

        return $this->save_file($timestamp);
    }
}
