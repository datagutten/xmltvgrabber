<?Php
class grabber
{
	public $outpath;
	public function __construct()
	{
		require 'config.php';
		if(substr($outpath,-1,1)!='/')
			$outpath.='/';
		$this->outpath=$outpath;
	}
	public function download($url,$channel,$extension,$timestamp=false) //Download and save the original data
	{
		$data=file_get_contents($url);
		if($timestamp===false)
			$timestamp=strtotime('midnight');

		if(!file_exists($path=$this->outpath."rawdata/$channel/".date('Y',$timestamp).'/'))
			mkdir($path,0777,true);
	
		file_put_contents($file="$path/".$channel.'_'.$timestamp.'.'.$extension,$data);
		echo $file."\n";
		return $data;
	}
	
}