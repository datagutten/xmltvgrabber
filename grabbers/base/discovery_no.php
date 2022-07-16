<?php


namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\tools\build\programme;
use DateTimeImmutable;

/**
 * Common grabber class for Discovery Networks
 */
abstract class discovery_no extends common
{
    /**
     * @var string Internal Discovery channel id
     */
    public static string $discovery_id;
    public static string $language = 'nb';

    /**
     * Get EPG URL
     * @param int $timestamp
     * @return string URL string
     */
    public static function get_url(int $timestamp): string
    {
        $date = new DateTimeImmutable();
        $date = $date->setTimestamp($timestamp);
        $day_start = $date->setTime(0, 0);
        $day_end = $date->setTime(23, 59, 59);
        $date_format = 'Y-m-d\TH:i:s.v\Z';

        $url_template = 'https://disco-api.discoveryplus.no/tvlistings/v2/channels/%s?startDate=%s&endDate=%s';
        return sprintf(
            $url_template,
            static::$discovery_id,
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
        if(empty(static::$discovery_id))
            throw new exceptions\GrabberException(sprintf('Channel id not defined in grabber %s', static::class));

        if (empty($timestamp))
            $timestamp = strtotime('midnight');
        $this->get_token();

        $url = self::get_url($timestamp);
        $data = $this->download_cache($url, $timestamp, 'json');

        $programs = json_decode($data, true);
        if (empty($programs['data']))
        {
            $cache_file = $this->local_file($timestamp, 'json');
            unlink($cache_file);
            throw new exceptions\GrabberException(sprintf('No programs found for date %s', date('Y-m-d', $timestamp)));
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
