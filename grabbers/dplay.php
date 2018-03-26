<?php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$channels=array('tvnorge.no'=>'TVNorge','fem.no'=>'FEM','max.no'=>'MAX','voxtv.no'=>'VOX','eurosport.no'=>'Eurosport Norge','animalplanet.discovery.no'=>'Animal Planet','tlc.discovery.no'=>'TLC','discovery.no'=>'Discovery Channel','investigation.discovery.no'=>'Investigation Discovery','science.discovery.no'=>'Discovery Science','world.discovery.no'=>'Discovery World','hd.discovery.no'=>'Discovery HD Showcase');

$grabber=new grabber;

//$data=$grabber->download('http://www.tvnorge.no/tv-guide?d='.$urldate,'tvnorge.no','htm',$timestamp);
//$prevdata=$grabber->download('http://www.tvnorge.no/tv-guide?d='.date('dmY',$prevday),'tvnorge.no','htm',$prevday);
$url_template='http://epg.dnnservice.no/dplayepg/?channel=%s&date=%s';

foreach($channels as $channel_id=>$channel)
{
	$xmltv=new xmltvgenerator($channel_id,'nb',$grabber->outpath);

	foreach(array(strtotime('-1 day',$timestamp),$timestamp) as $day)
	{	
		$url=sprintf($url_template,urlencode($channel),date('Y-m-d',$day));
		$data=$grabber->download($url,$channel_id,'json',$timestamp);
		$programs=json_decode($data,true);
		foreach($programs as $program)
		{
			if(date('d',$timestamp)!=date('d',strtotime($program['startTime'])))
				continue; //Only fetch data for the specified day
			$xmltv_program=$xmltv->program($program['title'],$program['shortDescription'],strtotime($program['startTime']),strtotime($program['endTime']));
			if(!empty($program['episode']))
				$xmltv->episodeinfo($xmltv_program,$program['season'],$program['episode'],$program['numEpisodes']);
		}
	}
	$filename=$xmltv->savefile($timestamp);
	echo "$filename\n";

	unset($xmltv);
}
