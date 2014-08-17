<?Php
$startdate=strtotime($argv[1]);
for($i=0; $i<=30; $i++)
{
	$date=strtotime("+$i days",$startdate);
	$cmddate=date('Y-m-d',$date);
	echo "php {$argv[2]}.php $cmddate\n";
}