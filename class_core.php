<?Php
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
	public function foldername($channel,$subfolder,$timestamp=false)
	{
		if($timestamp===false)
			$timestamp=strtotime('midnight');
		$date=date('Y',$timestamp);
		$folder=$this->outpath."$channel/$subfolder/$date/";
		if(!file_exists($folder))
			mkdir($folder,0777,true);
		return $folder;
	}
	public function filename($channel,$timestamp,$extension)
	{
		return $channel.'_'.date('Y-m-d',$timestamp).'.'.$extension;
	}
	public function fullpath($channel,$subfolder,$timestamp,$extension)
	{
		return $this->foldername($channel,$subfolder,$timestamp).$this->filename($channel,$timestamp,$extension);	
	}
}
?>