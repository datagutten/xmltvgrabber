<?Php

use datagutten\xmltv\tools\build\programme;
use datagutten\xmltv\tools\build\tv;

require __DIR__.'/../vendor/autoload.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$grabber=new grabber;
//$xmltv=new xmltvgenerator('natgeo.no','nb');

$config = require 'config.php';
$tv = new tv($config['xmltv_path']);
$tv->language = 'nb';
$tv->channel = 'natgeo.no';

$dom=new DOMDocument;

$urldate=date('Ymd',$timestamp);
$ymd=date('Y-m-d',$timestamp);

$data=$grabber->download($url='http://www.natgeotv.com/no/tvguide/natgeo/'.$urldate,'natgeo.no','htm',$timestamp);
@$dom->loadHTML($data);
$days=$dom->getElementById('scheduleDays');

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
		$date=$program->getattribute('data-datetime-date'); //Program date
		if(isset($prev_date) && isset($start_time_stamp) && $date!=$prev_date) //New day
		{
			$filename=$tv->save_file($start_time_stamp); //Save previous day
			echo $filename."\n";
			//Restart xmltv
            $tv->init_xml();
			/*unset($tv);
			$xmltv=new xmltvgenerator('natgeo.no','nb');*/
		}

		$start_time=$program->getelementsbytagname('h5')->item(0)->textContent; //Program start time
		$start_time_stamp=strtotime($date.' '.$start_time);

		$programme = new programme($start_time_stamp, $tv);

		$title=$program->getelementsbytagname('h3')->item(0)->textContent;
		$programme->title($title);

		$description=$program->getelementsbytagname('p')->item(0)->textContent;


		if($program->getelementsbytagname('h4')->length==1)
		{
			$epname=$program->getelementsbytagname('h4')->item(0)->textContent;

			if(preg_match('/(.*),\s+(Sesong ([0-9]+).+Episode ([0-9]+))/',$epname,$ep)) {
                $programme->sub_title($ep[1]);
                $programme->description($description);
                $programme->series($ep[4], $ep[3]);
                $programme->onscreen($ep[2]);
            }
			else
            {
                $programme->sub_title($epname);
                $programme->description($description);
            }
		}
		else
            $programme->description($description);

		$prev_date=$date; //Save current date for next iteration
		//print_r($programme->xml);
		//break 2;
	}
}