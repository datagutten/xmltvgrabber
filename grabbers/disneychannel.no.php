<?php
require '../class.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');
	
require_once '../class.php';
$grabber=new grabber;

$data=$grabber->download('http://www.disney.no/DisneyChannel/binary/tvguide/tdc.xml','disneychannel.no','xml',$timestamp);
$xml=simplexml_load_string($data);
//$xml=simplexml_load_file('rawdata/disneychannel.no/disneychannel.no_1362524400.xml');
$xml=json_decode(json_encode($xml),true);
//print_r($xml);
//echo date('H:i',$time);
//die();
foreach ($xml['SCHEDULE'] as $week)
{
	$date=$week['@attributes']['startdate'];
	
	$year=substr($date,6,4);
	$month=substr($date,3,2);
	$day=substr($date,0,2);
	$startday=strtotime("$year/$month/$day");
	
	//$daykey=5; //Min 0 I dag 5 Max 6
	for ($daykey=0; $daykey<=6; $daykey++)
	{
			$output= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
			$output.= "<!DOCTYPE tv SYSTEM \"xmltv.dtd\">\n\n";
		
			$output.= "<tv generator-info-name=\"quadtv\">\n";
		
		
			
		$currentday=strtotime('+'.$daykey.' days',$startday);
		//print_r($xml);
		foreach ($xml['SCHEDULE'][0]['DAY'][$daykey]['SLOT'] as $key=>$program)
		{
			if(!isset($xml['SCHEDULE'][0]['DAY'][$daykey]['SLOT'][$key+1]))
			$stoptime='0123';
			else
			$stoptime=str_replace(':',NULL,$xml['SCHEDULE'][0]['DAY'][$daykey]['SLOT'][$key+1]['@attributes']['time']);
			
			$program=$program['@attributes'];
			
			$starttime=str_replace(':',NULL,$program['time']);
			$day=date('Ymd',$currentday);
			//$program['prog']=htmlentities($program['prog']);
			$program['prog']=str_replace('&',NULL,$program['prog']);
			$output.="  <programme start=\"$day{$starttime}00 +0100\" stop=\"$day{$stoptime}00 +0100\" channel=\"disneychannel.no\">\n";
			$output.="    <title lang=\"nb\">{$program['prog']}</title>\n";
			$output.= "    <category lang=\"en\">Children's</category>\n";
			$output.= "  </programme>\n";
		
		}
		$output.='</tv>';
		$outfile=$grabber->outpath.'disneychannel.no/disneychannel.no_'.date('Y-m-d',$currentday).'.xml';

		if(!file_exists($outfile))
			file_put_contents($outfile,$output);
		echo $outfile."<br>\n";
		unset($outfile,$output);
	}
}
//echo $output;
?>
	