<?Php

namespace datagutten\xmltv\grabbers\base;

use datagutten\xmltv\grabbers\exceptions;
use datagutten\xmltv\tools\build\tv;
use datagutten\xmltv\tools\common\files;
use datagutten\xmltv\tools\exceptions\ChannelNotFoundException;
use FileNotFoundException;
use WpOrg\Requests;

/**
 * Base class for all grabbers
 */
class common
{
    /**
     * @var string Current channel id
     */
    public $channel;
    /**
     * @var files Files class
     */
    public $files;
    /**
     * @var tv tv class
     */
    public $tv;
    /**
     * @var Requests\Session
     */
    protected $session;

    /**
     * common constructor.
     * @param $channel
     * @param $language
     * @throws FileNotFoundException
     */
    public function __construct($channel, $language)
    {
        $config = require 'config.php';
        $this->channel = $channel;
        $this->files = new files($config['xmltv_path'], $config['xmltv_sub_folders']);
        $this->tv = new tv($channel, $language);
        $this->session = new Requests\Session();
    }

    /**
     * HTTP GET request
     * @param string $url URL to GET
     * @return string Response body
     * @throws exceptions\ConnectionError
     */
    protected function get(string $url)
    {
        try
        {
            $response = $this->session->get($url);
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
     * @return string
     * @throws exceptions\ConnectionError
     * @throws exceptions\XMLTVError
     */
    public function download(string $url, int $timestamp=0, string $extension='html')
    {
        $body = $this->get($url);
        $file = $this->local_file($timestamp, $extension);
        file_put_contents($file, $body);
        return $body;
    }

    /**
     * @param $url
     * @param null $timestamp
     * @param string $extension
     * @return string
     * @throws exceptions\ConnectionError|exceptions\XMLTVError
     */
    public function download_cache($url, $timestamp=null, $extension='html')
    {
        try {
            return $this->load_local_file($timestamp, $extension);
        } catch (FileNotFoundException $e) {
            return $this->download($url, $timestamp, $extension);
        }
    }

    /**
     * Get XMLTV file
     * Wrapper for datagutten\xmltv\tools\common\files with project specific exception
     * @param string $channel XMLTV channel id
     * @param int $timestamp Timestamp for the date to get
     * @param string $sub_folder Sub folder of channel folder
     * @param string $extension File extension
     * @param bool $create Create folder
     * @return string File name
     * @throws exceptions\XMLTVError
     */
    protected function file(string $channel, $timestamp = 0, $sub_folder = '', $extension = 'xml', $create = false)
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
    public function local_file(int $timestamp, $extension = 'html')
    {
        return $this->file($this->channel, $timestamp, 'raw_data', $extension, true);
    }

    /**
     * @param string $extension File extension
     * @param int $timestamp Time stamp
     * @return string
     * @throws FileNotFoundException|exceptions\XMLTVError
     */
    public function load_local_file($timestamp, $extension = 'html')
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
    public static function day_start_end($timestamp)
    {
        $day_start = strtotime('midnight', $timestamp);
        $day_end = strtotime('23:59', $timestamp);
        return [$day_start, $day_end];
    }

    /**
     * @param int $timestamp Time stamp for the day to grab
     * @return string File name
     * @codeCoverageIgnore
     * @throws exceptions\GrabberException
     */
    public function grab($timestamp=0)
    {
        return $timestamp; //Dummy return to avoid warnings
    }

    /**
     * @param $timestamp
     * @return string
     * @throws exceptions\XMLTVError
     */
    public function save_file($timestamp)
    {
        $file = $this->file($this->channel, $timestamp, '', 'xml', true);
        $count = $this->tv->xml->{'programme'}->count();
        if ($count == 0)
            return null;
        $xml_string = $this->tv->format_output();
        $this->files->filesystem->dumpFile($file, $xml_string);
        return $file;
    }
}
