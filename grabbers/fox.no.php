<?Php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$date=strtotime($argv[1]);
else
	$date=strtotime('today');

$urldate=date('dmy',$date);
echo $ymd=date('Y-m-d',$date);
$data='';
for($page=0; $page<=3; $page++)
	$data.=file_get_contents('http://www.foxtv.no/schedule/100913/'.$page);

preg_match_all('^\<li class="" xmlns.+/li\>^sU',$data,$matches);

$grabber=new grabber;
$xmltv=new xmltvgenerator('fox.no','nb',$grabber->outpath);

$dom=new DOMDocument;

foreach($matches[0] as $program)
{
	@$dom->loadHTML($program);

	$title=$dom->getElementsByTagName('h1')->item(0)->textContent;
	preg_match('/[0-9]{2}:[0-9]{2}/',$program,$starttime);

	$spans=$dom->getElementsByTagName('span');
	$starttime=$spans->item(0)->textContent;
	$episodetext=trim($spans->item(1)->textContent);
	$episodetitle=$spans->item(2)->textContent;

	preg_match('/Sesong ([0-9]+) episode ([0-9]+)/',$episodetext,$seasonepisode);

	$programme=$xmltv->program($title,$episodetitle,strtotime($ymd.' '.$starttime));
	$xmltv->episodeinfo($programme,$seasonepisode[1],$seasonepisode[2],false,$episodetext);

}

$xmltv->writefile('fox.no',$date);
?>