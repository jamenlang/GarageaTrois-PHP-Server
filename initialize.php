#! /usr/bin/php
<?php
# run sudo crontab -e and add this as
@reboot cd /var/www/; php initialize.php

if(php_sapi_name() != 'cli'){
	exit;
}

include('GarageaTrois/GarageaTrois-Config.php');

if($log_to_file == '1'){
	//create temp file if it doesn't exist
	if (!file_exists($log)) {
		fopen("$log", "w");
		fwrite($log, 'creating log.');
		fclose($log);
	}
	if (!is_writable($log)) {
		chmod($log,0777);
	}
}

$dir = '/var/www';

$files = scandir($dir);
foreach($files as $filename){
	echo $filename;
	if(preg_match('/amazon-echo-bridge/i',$filename,$matches )){
		$armzilla = $filename;
	}
	if(preg_match('/GarageaTrois/',$filename,$matches )){
		$gat = $filename;
	}
	if(preg_match('/phpqrcode/',$filename,$matches )){
		$phpqrcode = $filename;
	}
}

if($use_gpio == true){
	//gpio will need to be initialized before use.
	exec("/usr/local/bin/gpio readall", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio write $other_relay 1", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio write $light_relay 1", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio write $door_relay 1", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio write $lock_relay 1", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio mode $other_relay OUT", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio mode $light_relay OUT", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio mode $door_relay OUT", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio mode $lock_relay OUT", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	exec("/usr/local/bin/gpio readall", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
}	

while(true){
	$command="/sbin/ifconfig $configured_interface | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$localIP = exec ($command,$output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
	echo $localIP;
	if($localIP != '')
		break;
}

if(!$gat){
	exec('git clone https://github.com/jamenlang/GarageaTrois-PHP-Server.git GarageaTrois',$output);
}
	
if(sha1_file("$dir/$gat/GarageaTrois-Config.php") == getSslPage('https://raw.githubusercontent.com/jamenlang/GarageaTrois-PHP-Server/master/GarageaTrois-Config.php')){
	echo 'Configuration options need to be set in GarageaTrois-Config.php, check index.php for other options that need to be configured.';
	exit;
}

if(!$phpqrcode && $qr_enabled == "1"){
	exec('git clone git://git.code.sf.net/p/phpqrcode/git phpqrcode',$output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
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
	exec("wget $hue_emulator_link", $output);
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
}

if($armzilla == ''){
	$files = scandir($dir);
	foreach($files as $filename){
		file_put_contents($log, $filename, FILE_APPEND | LOCK_EX);
		if(preg_match('/amazon-echo-bridge/i',$filename,$matches )){
			$armzilla = $filename;
		}
	}
}

if ($armzilla != ''){
	exec("java -jar $dir/$armzilla --upnp.config.address=$localIP");
	(($log_to_file == "1") ? file_put_contents($log, 'started armzilla hue emulator', FILE_APPEND | LOCK_EX) : '');
	
}
else {
	$output = "could not start $dir/$armzilla $localIP";
	(($log_to_file == "1") ? file_put_contents($log, $output, FILE_APPEND | LOCK_EX) : '');
}

?>
