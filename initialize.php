#! /usr/bin/php
<?php

if(php_sapi_name() != 'cli'){
	exit;
}

include('GarageaTrois/GarageaTrois-Config.php');

$dir = '/var/www/';

if($use_gpio == true){
	//gpio will need to be initialized before use.
	exec("/usr/local/bin/gpio write $other_relay 1");
	exec("/usr/local/bin/gpio write $light_relay 1");
	exec("/usr/local/bin/gpio write $door_relay 1");
	exec("/usr/local/bin/gpio write $lock_relay 1");
	exec("/usr/local/bin/gpio mode $other_relay OUT");
	exec("/usr/local/bin/gpio mode $light_relay OUT");
	exec("/usr/local/bin/gpio mode $door_relay OUT");
	exec("/usr/local/bin/gpio mode $lock_relay OUT");
}

while(true){
	$command="/sbin/ifconfig eth0 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$localIP = exec ($command);
	echo $localIP;
	if($localIP != '')
		break;
}

$files = scandir($dir);
foreach($files as $filename){
	echo $filename;
	if(preg_match('/amazon-echo-bridge/i',$filename,$matches )){
		$armzilla = $filename;
	}
}

if(!$armzilla){
	exec('wget --max-redirect=0 $( curl -s https://api.github.com/repos/armzilla/amazon-echo-ha-bridge/releases/latest | grep \'browser_\' | cut -d\" -f4) 2>&1', $output);
	foreach ($output as $line){
		if($hue_emulator_link != '')
			continue;
		preg_match('/\bhttp.*jar\b/',$line, $matches);
		if($matches[0]){
			$hue_emulator_link = $matches[0];
			break;
		}
        }
	exec("wget $hue_emulator_link");
}

if($armzilla == ''){
	$files = scandir($dir);
	foreach($files as $filename){
		echo $filename;
		if(preg_match('/amazon-echo-bridge/i',$filename,$matches )){
			$armzilla = $filename;
		}
	}
}

if ($armzilla != ''){
	exec("java -jar $dir$armzilla --upnp.config.address=$localIP");
}
else {
	echo 'could not start ' . $armzilla . ' ' . $localIP;
}

?>
