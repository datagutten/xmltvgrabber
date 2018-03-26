<?php
require_once '../class_xmltvgenerator.php';
require_once '../class_grabber.php';

if(isset($argv[1]))
	$timestamp=strtotime($argv[1]);
else
	$timestamp=strtotime('today');

$grabber=new grabber;
$xmltv=new xmltvgenerator('template.no','nb',$grabber->outpath);


//Grabber code here

$filename=$xmltv->savefile($timestamp);
echo "$filename\n";
?>
