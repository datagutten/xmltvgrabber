<?php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$channels=array('disneychannel.no'=>'/tv-oversikt', 'junior.disneychannel.no'=>'/tv-oversikt/disney-junior', 'xd.disneychannel.no'=>'/tv-oversikt/disney-xd');
$grabber=new grabber;
$url_template='https://tv.disney.no/_schedule/full/%s/2/%s';

foreach($channels as $channel_id=>$channel)
{
	$xmltv=new xmltvgenerator($channel_id,'nb',$grabber->outpath);
	$url=sprintf($url_template,date('Ymd',$timestamp),urlencode($channel));
	$data=$grabber->download($url,$channel_id,'json',$timestamp);

	$schedule=json_decode($data,true);
	//print_r($schedule);
	//die();
	foreach($schedule['schedule'] as $time_period)
	{
		if(empty($time_period['schedule_items']))
			continue;
		foreach($time_period['schedule_items'] as $schedule_item)
		{
			//print_r($schedule_item);
			//var_dump(date('H:i',));
			$program=$xmltv->program($schedule_item['show_title'],$schedule_item['description'],$start=strtotime($schedule_item['iso8601_utc_time']));
			if(!empty($schedule_item['episode_title']))
			{
				$sub_title=$program->addChild('sub-title',$schedule_item['episode_title']);
				$sub_title->addAttribute('lang',$xmltv->lang);
			}
			//break;
		}
	}
		$filename=$xmltv->savefile($timestamp);
		if($filename!==false)
			echo "$filename\n";
		unset($xmltv);
}
?>
