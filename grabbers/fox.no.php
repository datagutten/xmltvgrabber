<?Php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$grabber=new grabber;
$xmltv=new xmltvgenerator('fox.no','nb');
$dom=new DOMDocument;

$urldate=date('dmy',$timestamp);
$ymd=date('Y-m-d',$timestamp);
$data='';
for($page=0; $page<=3; $page++)
	$data.=$grabber->download($url="http://www.foxtv.no/schedule/$urldate/".$page,'fox.no','_'.$page.'.htm',$timestamp);

preg_match_all('^\<li class="" xmlns.+/li\>^sU',$data,$matches);

foreach($matches[0] as $program)
{
	@$dom->loadHTML($program);

	$title=$dom->getElementsByTagName('h1')->item(0)->textContent;
	preg_match('/[0-9]{2}:[0-9]{2}/',$program,$starttime);

	$spans=$dom->getElementsByTagName('span');
	$starttime=$spans->item(0)->getElementsByTagName('div')->item(0)->textContent;

	$episodetext=trim($spans->item(1)->textContent);
	$episodetitle=$spans->item(2)->textContent;

	$starttimestamp_temp=strtotime($starttime,$timestamp);
	if(isset($starttimestamp) && $starttimestamp_temp<$starttimestamp)
		$starttimestamp=$starttimestamp_temp+86400; //If the current time is earlier than the previous, increase the date
	else
		$starttimestamp=$starttimestamp_temp;

	$programme=$xmltv->program($title,$episodetitle,$starttimestamp);
	if(preg_match('/Sesong ([0-9]+) episode ([0-9]+)/',$episodetext,$seasonepisode))
		$xmltv->episodeinfo($programme,$seasonepisode[1],$seasonepisode[2],false,$episodetext);
}

$filename=$xmltv->savefile($timestamp);
echo "$filename\n";
?>
