<?Php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$grabber=new grabber;
$xmltv=new xmltvgenerator('natgeo.no','nb');
$dom=new DOMDocument;

$urldate=date('Ymd',$timestamp);
$ymd=date('Y-m-d',$timestamp);

$data=$grabber->download($url='http://www.natgeotv.com/no/tvguide/natgeo/'.$urldate,'natgeo.no','htm',$timestamp);
@$dom->loadHTML($data);
$days=$dom->getElementById('scheduleDays');
//$startday=substr($days->textContent,0,5);

//print_r($dom->getElementById('scheduleDays')->getelementsbytagname('a')->item(0)->getAttribute('href'));
//die();
preg_match('/[0-9]+/',$dom->getElementById('scheduleDays')->textContent,$startday);
//print_r($startday);
$startday=$startday[0];

if($startday>date('j',$timestamp)) //First day is in previous month
	$startdate=sprintf('%d-%02d-%02d',date('Y',$timestamp),date('m')-1,$startday);
else
	$startdate=sprintf('%s-%02d',date('Y-m'),$startday);

$ul=$dom->getelementsbytagname('ul');
foreach ($ul as $day)
{
	if($day->childNodes->length<5)
		continue;
	if(substr($day->childNodes->item(0)->textContent,0,2)!='N'.chr(0xc3))
		continue;

    /**
     * @var $program DOMElement
     */
	foreach($day->childNodes as $program)
	{
		//$starttime=$program->childNodes->item(0)->childNodes->item(1)->textContent;

		$date=$program->getattribute('data-datetime-date'); //Program date
		
		if(isset($prevdate) && $date!=$prevdate) //New day
		{
			$filename=$xmltv->savefile($starttimestamp); //Save previous day
			echo $filename."\n";
			//Restart xmltv
			unset($xmltv);
			$xmltv=new xmltvgenerator('natgeo.no','nb');

		}

		$starttime=$program->getelementsbytagname('h5')->item(0)->textContent; //Program start time
		$starttimestamp=strtotime($date.' '.$starttime);

		//print_r($program);
		$title=$program->getelementsbytagname('h3')->item(0)->textContent;
		//die();
		//$title=$program->childNodes->item(0)->childNodes->item(2)->textContent;
		//$epname=$program->childNodes->item(1)->childNodes->item(1)->childNodes->item(0)->textContent;			
		//$description=$program->childNodes->item(1)->childNodes->item(1)->childNodes->item(1)->textContent;
		$description=$program->getelementsbytagname('p')->item(0)->textContent;

		//print_r($ep);
		$programme=$xmltv->program($title,$description,$starttimestamp);
		if($program->getelementsbytagname('h4')->length==1)
		{
			$epname=$program->getelementsbytagname('h4')->item(0)->textContent;
			if(preg_match('/(.*),\s+Sesong ([0-9]+).+Episode ([0-9]+)/',$epname,$ep))
			    $xmltv->episodeinfo($programme,$ep[2],$ep[3]);
			else
            {
                printf("Unable to find episode information from \"%s\"\n", $epname);
            }
		}
		$prevdate=$date; //Save current date for next iteration
		//print_r($programme);
		//break 2;
	}
}