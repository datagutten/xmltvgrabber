<?Php
//require_once 'class_core.php';
require_once __DIR__.'/class_core.php';
class xmltvgenerator extends core
{
	public $channel;
	public $lang;
	public $outpath;

	function __construct($channel,$lang,$outpath)
	{
		$this->xml=new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE tv SYSTEM "xmltv.dtd"><tv generator-info-name="quadepg"/>');
		$this->channel=$channel;
		$this->lang=$lang;
		$this->outpath=$outpath;
	}
	function program($programtitle,$description,$start,$stop=false)
	{
		$programme=$this->xml->addChild('programme');
		$programme->addAttribute('start',date('YmdHis O',$start));
		if($stop!==false)
			$programme->addAttribute('stop',date('YmdHis O',$stop));
		$programme->addAttribute('channel',$this->channel);
		$programtitle=str_replace('&','&amp;',$programtitle);
		$title=$programme->addChild('title',$programtitle);
		$title->addAttribute('lang',$this->lang);
		if(!empty($description))
		{
			$desc=$programme->addChild('desc',$description);
			$desc->addAttribute('lang',$this->lang);
		}
		return $programme;
	}
	function episodeinfo($programme,$season,$current,$total=false,$onscreen=false)
	{
		$xmltv_ns=$season-1;
		$xmltv_ns.='.';
		$xmltv_ns.=$current-1;
		if($total!==false)
			$xmltv_ns.='/'.$total;
		$xmltv_ns.='.';
		
		$episode_num=$programme->addChild('episode-num',$xmltv_ns);
		$episode_num->addAttribute('system','xmltv_ns');
		if($onscreen!==false)
		{
			$episode_num=$programme->addChild('episode-num',$onscreen);
			$episode_num->addAttribute('system','onscreen');
		}

	}
	function output()
	{
		$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($this->xml->asXML());
		return $dom->saveXML();
	}
	function savefile($timestamp)
	{
		$folder=$this->foldername($this->channel,'xmltv',$timestamp);
		$ymd=date('Y-m-d',$timestamp);
		file_put_contents($filename=$folder."{$this->channel}_$ymd.xml",$this->output());
		return $filename;
	}
}
?>
