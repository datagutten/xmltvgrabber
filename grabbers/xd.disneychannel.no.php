<?Php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$grabber=new grabber;

$data=$grabber->download('http://disneyxd.disney.no/timeplan/tdxd.xml','xd.disneychannel.no','xml');
$xml=simplexml_load_string($data);

foreach($xml->SCHEDULE as $day)
{
	$xmltv=new xmltvgenerator('xd.disneychannel.no','nb',$grabber->outpath);
	$date=$day->attributes();
	$date=preg_replace('^([0-9]{2})/([0-9]{2})/([0-9]{4})^','$3-$2-$1',$date['startdate']); //Rewrite the date so strtotime understands it
	$timestamp=strtotime($date);
	//print_r($day->DAY);

	foreach($day->DAY->SLOT as $slot)
	{
		$slot=$slot->attributes();
		$program=$xmltv->program((string)$slot['prog'],(string)$slot['synopsis'],strtotime($slot['time'],$timestamp));
		//print_r($slot);
		//var_dump($slot['prog']);
	}
	$xmltv->writefile('xd.disneychannel.no',$timestamp);
	echo date('Y-m-d',$timestamp)."\n";
	unset($xmltv);
	//print_r($week->DAY);
	//break;
}