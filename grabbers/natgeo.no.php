<?Php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$grabber=new grabber;
$xmltv=new xmltvgenerator('natgeo.no','nb');

$urldate=date('dmy',$timestamp);
$ymd=date('Y-m-d',$timestamp);

$data=$grabber->download('http://natgeotv.com/no/listings/ngc/'.$urldate,'natgeo.no','htm',$timestamp);

$outfile='/mnt/web/tv/natgeo.no/natgeo.no_'.date('Y-m-d',$timestamp).'.xml';

preg_match_all('^ScheduleDayRow.+ScheduleDayHour"\>([0-9]+:[0-9]+)\<.+/ul^Us',$data,$result);
	
foreach ($result[0] as $key=>$program)
{
	if(preg_match('^\<span class=.Bold.+/span\>^',$program,$title))
	{
		$title=strip_tags($title[0]);
		$title=str_replace('&','&amp;',$title);
		echo $result[1][$key].' '.$title."\n";
		$starttime=str_replace(':','',$result[1][$key]);
		$starttimestamp_temp=strtotime($ymd.' '.$result[1][$key]);
		if(isset($starttimestamp) && $starttimestamp_temp<$starttimestamp)
			$starttimestamp=$starttimestamp_temp+86400; //If the current time is earlier than the previous, increase the date
		else
			$starttimestamp=$starttimestamp_temp;
	
		if(isset($result[1][$key+1]))
			$stoptime=str_replace(':','',$result[1][$key+1]);
		else
			$stoptime='';

		$programme=$xmltv->program($title,'',$starttimestamp);
	}
}

$filename=$xmltv->savefile($timestamp);
echo "\n$filename\n";
?>
