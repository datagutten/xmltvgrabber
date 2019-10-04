<?Php
use datagutten\xmltv\tools\common\file;
class core
{
	public $outpath;
	public $subfolder;
	public function __construct()
	{
		require 'config.php';
		if(!isset($outpath))
			throw new Exception("Missing outpath in config file");
		if(!isset($subfolder))
            throw new Exception("Missing subfolder in config file");

		if(substr($outpath,-1,1)!='/')
			$outpath.='/';
		$this->outpath=$outpath;
		$this->subfolder=$subfolder;
	}

    /**
     * @param $channel
     * @param $subfolder
     * @param int $timestamp
     * @return string
     */
	public function foldername($channel,$subfolder,$timestamp=null)
	{
	    $folder = file::folder($channel, $subfolder, $timestamp);
		$folder=$this->outpath.'/'.$folder;
		if(!file_exists($folder))
			mkdir($folder,0777,true);
		return $folder;
	}

    /**
     * @deprecated Use file::filename
     * @param $channel
     * @param $timestamp
     * @param $extension
     * @return string
     */
	public function filename($channel,$timestamp,$extension)
	{
		return file::filename($channel, $timestamp, $extension);
	}

    /**
     * @deprecated Use file::file_path
     * @param $channel
     * @param $subfolder
     * @param $timestamp
     * @param $extension
     * @return string
     */
	public function fullpath($channel,$subfolder,$timestamp,$extension)
	{
		return file::file_path($channel, $subfolder, $timestamp, $extension);
	}
}
?>