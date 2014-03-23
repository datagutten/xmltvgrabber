<?php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';
$grabber=new grabber;
define('channel','disneychannel.no');
if(isset($argv[1]))
{
	$timestamp=strtotime($argv[1]);
	if(!$data=$grabber->getlocalfile(channel,'xml',$timestamp))
		return;
}
else
{
	$timestamp=strtotime('today');
	$data=$grabber->download('http://www.disney.no/DisneyChannel/binary/tvguide/tdc.xml',channel,'xml',$timestamp);
}

$xml=simplexml_load_string($data);
foreach($xml->SCHEDULE as $week)
{
	$startdate=$week->attributes();
	$startdate=preg_replace('^([0-9]{2})/([0-9]{2})/([0-9]{4})^','$3-$2-$1',(string)$startdate); //Rewrite the date so strtotime understands it
	echo $startdate."\n";
	$timestamp_week=strtotime($startdate);
	foreach($week->DAY as $day)
	{
		$xmltv=new xmltvgenerator(channel,'nb');
		$dayinfo=$day->attributes();
		$timestamp_day=strtotime($dayinfo['name'],$timestamp_week);
		foreach($day->SLOT as $slot)
		{
			$programinfo=$slot->attributes();
			if($slot['prog']=='MOVIE: TBC')
				$title='Film: '.substr($slot->FILM->attributes()['title'],2);
			else
				$title=(string)$slot['prog'];
			$program=$xmltv->program($title,(string)$slot['synopsis'],strtotime($slot['time'],$timestamp_day));
		}
		$filename=$xmltv->savefile($timestamp_day);
		echo "$filename\n";
		unset($xmltv);
	}	
}
?>
