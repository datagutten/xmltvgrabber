<?Php
namespace datagutten\xmltv\grabbers;
use datagutten\xmltv\tools\build\tv;
use datagutten\xmltv\tools\common\files;
use FileNotFoundException;
use Requests;
use Requests_Exception;
use Requests_Exception_HTTP;

class common
{
    public $channel;
    public $files;
    /**
     * @var tv
     */
    public $tv;

    /**
     * common constructor.
     * @param $channel
     * @param $language
     * @throws FileNotFoundException
     */
    function __construct($channel, $language)
    {
        $config = require 'config.php';
        $this->channel = $channel;
        $this->files = new files($config['xmltv_path'], $config['xmltv_sub_folders']);
        $this->tv = new tv($channel, $language);
    }

    /**
     * @param $url
     * @param $extension
     * @param null $timestamp
     * @return string
     * @throws Requests_Exception
     * @throws Requests_Exception_HTTP
     */
    public function download($url, $timestamp=null, $extension = 'html') //Download and save the original data
	{
		$response = Requests::get($url);
		$response->throw_for_status();
		$file=$this->local_file($timestamp,$extension);
		file_put_contents($file, $response->body);
		return $response->body;
	}

    /**
     * @param $url
     * @param null $timestamp
     * @param string $extension
     * @return string
     * @throws Requests_Exception
     * @throws Requests_Exception_HTTP
     */
	public function download_cache($url, $timestamp = null, $extension = 'html')
    {
        try {
            return $this->load_local_file($timestamp, $extension);
        }
        catch (FileNotFoundException $e)
        {
            return $this->download($url, $timestamp, $extension);
        }
    }

    /**
     * @param string $extension File extension
     * @param int $timestamp Time stamp
     * @return string File name
     */
	public function local_file($timestamp, $extension = 'html')
	{
	    return $this->files->file($this->channel, $timestamp, 'raw_data', $extension, true);
	}

    /**
     * @param string $extension File extension
     * @param int $timestamp Time stamp
     * @return string
     * @throws FileNotFoundException
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
     */
    public function grab($timestamp=null)
    {
        return $timestamp; //Dummy return to avoid warnings
    }

    function save_file($timestamp)
    {
        $file = $this->files->file($this->channel, $timestamp);
        $xml_string = $this->tv->format_output();
        $this->files->filesystem->dumpFile($file, $xml_string);
        return $file;
    }

}