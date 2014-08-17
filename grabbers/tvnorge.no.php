<?Php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$grabber=new grabber;

$dom=new DOMDocument;

$urldate=date('dmY',$timestamp);
$ymd=date('Y-m-d',$timestamp);

$prevday=strtotime('-1 day',$timestamp);
$data=$grabber->download('http://www.tvnorge.no/tv-guide?d='.$urldate,'tvnorge.no','htm',$timestamp);
$prevdata=$grabber->download('http://www.tvnorge.no/tv-guide?d='.date('dmY',$prevday),'tvnorge.no','htm',$prevday);

foreach(array($prevdata,$data) as $day)
{
	@$dom->loadHTML($day);
	$sections=$dom->getElementsByTagName('section');
	
	$channels=array('tvnorge.no'=>$sections->item(4),'max.no'=>$sections->item(6),'fem.no'=>$sections->item(5));
	foreach($channels as $id=>$channel)
	{
		$div_shows=$channel->childNodes->item(1)->childNodes; //div
		if(!isset($xmltv[$id]))
			$xmltv[$id]=new xmltvgenerator($id,'nb',$grabber->outpath);
		foreach($div_shows as $article)
		{
			//$show=$article;
			if(!is_object($article->childNodes))
				continue;
		
			$header=$article->childNodes->item(0)->childNodes;
			if(!is_object($header))
				continue;
	
			$starttime=strtotime($header->item(0)->attributes->item(1)->textContent); //Time start
			$endtime=strtotime($header->item(1)->attributes->item(1)->textContent); //Time end
			

				
			if(date('d',$timestamp)!=date('d',$starttime))
				continue; //Only fetch data for the specified day

			$div_details=$article->childNodes->item(1);
		
			$desc=$div_details->getElementsByTagName('p')->item(0)->textContent;
	
			$episode=$div_details->getElementsByTagName('dd')->item(1)->textContent;
			
			if(strlen($episode)<10)
				$episode=$div_details->getElementsByTagName('dd')->item(2)->textContent;
			preg_match('^(([0-9]+)/([0-9]+)).*(\(.+\))*^s',$episode,$episodeinfo);
			if($id=='tvnorge.no')
			{
				echo date('d H:i',$starttime).' '.$header->item(2)->textContent."\n";
				//print_r($episodeinfo);
				//var_dump($episode);
			}
	
			
		
			preg_match('/[år|sesong]+ ([0-9]+)/',$title=$div_details->getElementsByTagName('dd')->item(0)->textContent,$season); //Get season from title
			if(empty($season)) //No season
			{
				$season[0]=0;
				$season[1]=false;
			}

			$programme=$xmltv[$id]->program($header->item(2)->textContent,$desc,$starttime,$endtime);
			//print_r($programme);

			if(isset($episodeinfo[1]))
				$episode=$xmltv[$id]->episodeinfo($programme,$season[1],$episodeinfo[2],$episodeinfo[3],$episodeinfo[1].' '.$season[0]);
		}
		
		//unset($xmltv[$id]);
	}
}

foreach($channels as $id=>$channel)
{
	$filename=$xmltv[$id]->savefile($timestamp);
	echo "$filename\n";
}