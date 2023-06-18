<?php

namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\grabbers\exceptions\ConnectionError;
use datagutten\xmltv\grabbers\exceptions\GrabberException;
use datagutten\xmltv\tools\build\programme;
use Exception;

abstract class DiscoveryBase extends common
{
    protected static array $channel_id = [];
    protected static string $channel_slug;

    /**
     * @inheritDoc
     * @throws GrabberException Unable to get token
     */
    public function get(string $url, array $headers = [], array $options = [])
    {
        try
        {
            return parent::get($url, $headers, $options);
        }
        catch (ConnectionError $e)
        {
            if ($e->getCode() != 400)
                throw $e;
            else
            {
                $this->get_token();
                return parent::get($url, $headers, $options);
            }
        }
    }

    /**
     * Get token
     * @return void
     * @throws GrabberException HTTP error when fetching token
     */
    protected function get_token()
    {
        try
        {
            $deviceId = bin2hex(random_bytes(16));
        }
        catch (Exception $e)
        {
            throw new GrabberException('Unable to generate deviceId', $e->getCode(), $e);
        }
        $response = $this->session->get(sprintf('https://eu1-prod-direct.discoveryplus.com/token?deviceId=%s&realm=dplay&shortlived=true', $deviceId));
        if (!$response->success)
            throw new GrabberException('Failed to get token', $response->status_code);
    }

    /**
     * Find channel id
     * @param bool $list List valid channels if current channel is not found
     * @return string Channel id
     * @throws ConnectionError
     * @throws GrabberException
     */
    protected function get_channel_id(bool $list = false): string
    {
        if (isset(self::$channel_id[static::$xmltv_id]))
            return self::$channel_id[static::$xmltv_id];
        $data = $this->get('https://eu1-prod-direct.discoveryplus.com/cms/routes/epg?include=default&decorators=viewingHistory,isFavorite,playbackAllowed,badges');
        $data = json_decode($data, true);
        $channels = [];
        foreach ($data['included'] as $channel)
        {
            if ($channel['type'] != 'collection')
                continue;

            if (!str_starts_with($channel['attributes']['alias'], 'epg'))
                continue;
            $channels[$channel['attributes']['name']] = $channel['id'];
            if ($channel['attributes']['name'] == sprintf('epg-listing-%s', static::$channel_slug))
            {
                self::$channel_id[static::$xmltv_id] = $channel['id'];
                return $channel['id'];
            }
        }
        if ($list)
            print_r($channels);

        throw new GrabberException('No channel found with slug ' . static::$channel_slug);
    }

    public function grab(int $timestamp = 0): ?string
    {
        $channel_id = $this->get_channel_id();
        if (empty($timestamp))
            $timestamp = strtotime('midnight');
        $url = sprintf('https://eu1-prod-direct.discoveryplus.com/cms/collections/%s?include=default&decorators=playbackAllowed&pf[day]=%s', $channel_id, date('Y-m-d', $timestamp));
        $data = $this->download($url, $timestamp, 'json', 10,
            [
                'Referer' => 'https://www.discoveryplus.com/',
                'X-Disco-client' => 'WEB:UNKNOWN:dplus_us:2.21.0',
                'X-disco-params' => sprintf('realm=dplay,bid=dplus,hn=www.discoveryplus.com,hth=%s,features=ar', static::$language)
            ]);
        $data = json_decode($data, true);
        $programs_sorted = [];
        if (empty($data['included']))
            throw new GrabberException('No programs found');
        foreach ($data['included'] as $program)
        {
            if ($program['type'] != 'video')
                continue;
            $programs_sorted[strtotime($program['attributes']['scheduleStart'])] = $program;
        }
        ksort($programs_sorted);

        foreach ($programs_sorted as $program)
        {
            $programme = new programme(strtotime($program['attributes']['scheduleStart']), $this->tv);
            $programme->stop(strtotime($program['attributes']['scheduleEnd']));
            if (!empty($program['attributes']['customAttributes']['listingEpisodeNumber']))
            {
                $programme->series(
                    intval($program['attributes']['customAttributes']['listingEpisodeNumber']),
                    intval($program['attributes']['customAttributes']['listingSeasonNumber'])
                );
            }
            if (!empty($program['attributes']['customAttributes']['listingShowName']))
            {
                $programme->title($program['attributes']['customAttributes']['listingShowName']);
                $programme->sub_title($program['attributes']['name']);
            }
            else
                $programme->title($program['attributes']['name']);
        }
        return $this->save_file($timestamp);
    }
}