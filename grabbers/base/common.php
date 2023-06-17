<?Php

namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\grabbers\exceptions\XMLTVError;
use datagutten\xmltv\tools\build\tv;
use datagutten\xmltv\tools\common\files;
use datagutten\xmltv\tools\exceptions\ChannelNotFoundException;
use FileNotFoundException;
use WpOrg\Requests;

/**
 * Base class for all grabbers
 */
abstract class common
{
    /**
     * @var files Files class
     */
    public files $files;
    /**
     * @var tv tv class
     */
    public tv $tv;
    /**
     * @var Requests\Session Requests session
     */
    protected Requests\Session $session;

    /**
     * @var string Channel language code according to RFC 1766
     * @link https://en.wikipedia.org/wiki/IETF_language_tag
     */
    public static string $language;
    /**
     * @var string XMLTV DNS-like channel id
     */
    public static string $xmltv_id;

    /**
     * @var string
     */
    public static string $sub_folder;

    /**
     * common constructor.
     * @param ?string $channel XMLTV DNS-like id
     * @param ?string $language
     * @throws FileNotFoundException
     */
    public function __construct()
    {
        $config = require 'config.php';
        $this->channel = static::$xmltv_id;
        $this->files = new files($config['xmltv_path'], [static::$sub_folder ?? $config['xmltv_sub_folder']]);
        $this->tv = new tv($channel ?? static::$xmltv_id, $language ?? static::$language);
        $this->session = new Requests\Session();
    }

    /**
     * HTTP GET request
     * @param string $url URL to GET
     * @param array $headers Headers to pass to Requests::get
     * @param array $options Options to pass to Requests::get
     * @return string Response body
     * @throws exceptions\ConnectionError
     */
    protected function get(string $url, array $headers = [], array $options = [])
    {
        try
        {
            $response = $this->session->get($url, $headers, $options);
            $response->throw_for_status();
        }
        catch (Requests\Exception $e)
        {
            throw new exceptions\ConnectionError($e->getMessage(), 0, $e);
        }

        return $response->body;
    }

    /**
     * Download and save the original data
     * @param string $url URL
     * @param int $timestamp Timestamp for the saved file
     * @param string $extension Extension for saved file
     * @param int $timeout HTTP requests timeout in seconds
     * @param array $headers HTTP headers to add to the request
     * @return string
     * @throws exceptions\ConnectionError
     * @throws exceptions\XMLTVError
     */
    public function download(string $url, int $timestamp = 0, string $extension = 'html', int $timeout = 10, array $headers = [])
    {
        try
        {
            $body = $this->get($url, $headers, ['timeout' => 2]);
        }
        catch (exceptions\ConnectionError $e)
        {
            $body = $this->get($url, $headers, ['timeout' => $timeout]);
        }
        $file = $this->local_file($timestamp, $extension);
        file_put_contents($file, $body);
        return $body;
    }

    /**
     * Download and save the original data if not cached
     * @param string $url URL
     * @param int|null $timestamp Timestamp for the saved file
     * @param string $extension Extension for saved file
     * @param int $timeout HTTP requests timeout in seconds
     * @param array $headers HTTP headers to add to the request
     * @return string
     * @throws exceptions\ConnectionError|exceptions\XMLTVError
     */
    public function download_cache(string $url, int $timestamp = null, string $extension = 'html', int $timeout = 10, array $headers = [])
    {
        try
        {
            return $this->load_local_file($timestamp, $extension);
        }
        catch (FileNotFoundException $e)
        {
            return $this->download($url, $timestamp, $extension, $timeout, $headers);
        }
    }

    /**
     * Get XMLTV file
     * Wrapper for datagutten\xmltv\tools\common\files with project specific exception
     * @param string $channel XMLTV channel id
     * @param ?int $timestamp Timestamp for the date to get
     * @param ?string $sub_folder Sub folder of channel folder
     * @param string $extension File extension
     * @param bool $create Create folder
     * @return string File name
     * @throws exceptions\XMLTVError
     */
    protected function file(string $channel, int $timestamp = null, string $sub_folder = null, string $extension = 'xml', bool $create = false)
    {
        try
        {
            return $this->files->file($channel, $timestamp, $sub_folder, $extension, $create);
        }
        catch (ChannelNotFoundException $e)
        {
            throw new exceptions\XMLTVError($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $extension File extension
     * @param int $timestamp Time stamp
     * @return string File name
     * @throws exceptions\XMLTVError
     */
    public function local_file(int $timestamp, string $extension = 'html')
    {
        return $this->file($this->channel, $timestamp, 'raw_data', $extension, true);
    }

    /**
     * @param string $extension File extension
     * @param int $timestamp Time stamp
     * @return string
     * @throws FileNotFoundException|exceptions\XMLTVError
     */
    public function load_local_file(int $timestamp, string $extension = 'html')
    {
        $file = $this->local_file($timestamp, $extension);
        if(file_exists($file))
            return file_get_contents($file);
        else
        {
            throw new FileNotFoundException($file);
        }
    }

    /**
     * Get start and end of day
     * @param int $timestamp
     * @return array
     */
    public static function day_start_end(int $timestamp)
    {
        $day_start = strtotime('midnight', $timestamp);
        $day_end = strtotime('23:59', $timestamp);
        return [$day_start, $day_end];
    }

    /**
     * @param int $timestamp Time stamp for the day to grab
     * @return ?string File name
     * @codeCoverageIgnore
     * @throws exceptions\GrabberException
     */
    abstract public function grab(int $timestamp = 0): ?string;

    /**
     * Save XML file
     * @param int $timestamp
     * @return ?string File name
     * @throws XMLTVError
     * @throws exceptions\GrabberException No programs found
     */
    public function save_file(int $timestamp): ?string
    {
        $file = $this->file(static::$xmltv_id, $timestamp, null, 'xml', true);
        $count = $this->tv->xml->{'programme'}->count();
        if ($count == 0)
            throw new exceptions\GrabberException(sprintf('No programs found for date %s', date('Y-m-d', $timestamp)));
        $xml_string = $this->tv->format_output();
        $this->files->filesystem->dumpFile($file, $xml_string);
        return $file;
    }
}
