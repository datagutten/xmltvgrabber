<?Php
//Make a text file with id of all channels
require 'config.php';
$channels=array_diff(scandir($outpath),array('.','..'));
$output='';
foreach($channels as $channel)
{
	if(is_dir($outpath.$channel))
	{
		$output.=$channel."\n";
	}
}
echo $output;
file_put_contents($outpath.'channels.txt',$output);
?>