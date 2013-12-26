<?Php
require '../xmltvgenerator.php';
require '../class.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');
$grabber=new grabber;

$urldate=date('dmy',$timestamp);

$data=$grabber->download('http://natgeotv.com/no/listings/ngc/'.$urldate,'natgeo.no','htm',$timestamp);

$outfile='/mnt/web/tv/natgeo.no/natgeo.no_'.date('Y-m-d',$timestamp).'.xml';

if(file_exists($outfile))
	echo "Data for angitt dag er allerede lastet ned\n";
else
{
	
	preg_match_all('^ScheduleDayRow.+ScheduleDayHour"\>([0-9]+:[0-9]+)\<.+/ul^Us',$data,$result);
	
	//print_r($result);
	$output= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	$output.= "<!DOCTYPE tv SYSTEM \"xmltv.dtd\">\n\n";
	$output.= "<tv generator-info-name=\"quadtv\">\n";
	foreach ($result[0] as $key=>$program)
	{
		preg_match('^\<span class=.Bold.+/span\>^',$program,$title);
		//if($title[0]=='')
		{
		//echo $program."\n";
		$title=strip_tags($title[0]);
		$title=str_replace('&','&amp;',$title);
		echo $result[1][$key].' '.$title."\n";
		$starttime=str_replace(':','',$result[1][$key]);
		if(isset($result[1][$key+1]))
			$stoptime=str_replace(':','',$result[1][$key+1]);
		else
			$stoptime='';
		$date=date('Ymd',$timestamp);
		$output.="  <programme start=\"$date{$starttime}00 +0100\" stop=\"$date{$stoptime}00 +0100\" channel=\"natgeo.no\">\n";
		$output.="    <title lang=\"nb\">$title</title>\n";
		$output.= "  </programme>\n";
		
			
		}
	}
	$output.='</tv>';
	//echo $output;
	file_put_contents($outfile,$output);
	echo "\n<br>$outfile<br>\n";
}