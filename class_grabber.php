<?Php
require_once 'class_core.php';
require_once __DIR__.'/class_core.php';
class grabber extends core
{
	public function download($url,$channel,$extension,$timestamp=false) //Download and save the original data
	{
		$data=file_get_contents($url);
		if($timestamp===false)
			$timestamp=strtotime('midnight');

		$file=$this->fullpath($channel,'rawdata',$timestamp,$extension);
		if(!file_exists($dirname=dirname($file)))
			mkdir($dirname,0777,true);

		file_put_contents($file,$data);
		return $data;
	}
	public function getlocalfile($channel,$extension,$timestamp)
	{
		$path=$this->foldername($channel,'rawdata',$timestamp);
		//$path=$this->outpath."rawdata/$channel/".date('Y',$timestamp).'/';
		$file=$path.$channel.'_'.$timestamp.'.'.$extension;
		if(file_exists($file))
			return file_get_contents($file);
		else
		{
			echo "File not found $file\n";
			return false;
		}
	}
	
}
?>