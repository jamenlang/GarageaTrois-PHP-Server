#! /usr/bin/php
<?php
# move to /var/www/
# run sudo crontab -e and add this as @reboot cd /var/www/; php initialize.php

if(php_sapi_name() != 'cli'){
	exit;
}

$dir = '/var/www';

if(getcwd != $dir){
	copy('/var/www/GarageaTrois/initialize.php', '/var/www/initialize.php');
	echo('this script must be placed in /var/www/, a copy has been made and this file will be destroyed.');
	unlink('/var/www/GarageaTrois/initialize.php');
	exit;
}

$armzilla = '';
$gat = '';
$phpqrcode = '';

if(is_file("$dir/GarageaTrois/GarageaTrois-Config.php")){
	include("$dir/GarageaTrois/GarageaTrois-Config.php");

	if(isset($log_to_file) && $log_to_file == '1'){
		//create temp file if it doesn't exist
		if (!file_exists($log)) {
			$fp = fopen($log, "w");
			fwrite($fp, 'creating log.');
			fclose($fp);
		}
		if (!chmod($log,0777)) {
			echo 'failed to make log writable.';
		}
	}
}
else{
	echo 'GarageaTrois-Config.php not found. Downloading it now.';
}

$files = scandir($dir);
foreach($files as $filename){
	if(preg_match('/amazon-echo-bridge/i',$filename,$matches )){
		$armzilla = $filename;
	}
	if(preg_match('/GarageaTrois/',$filename,$matches )){
		if(is_dir($filename)){
			$gat = $filename;
		}
		if(is_file($filename)){
			$gat_backup_config = $filename;
		}
	}
	if(preg_match('/phpqrcode/',$filename,$matches )){
		if(is_dir($filename)){
			$phpqrcode = $filename;
		}
	}
}

if($gat == ''){
	exec('git clone https://github.com/jamenlang/GarageaTrois-PHP-Server.git GarageaTrois', $output);
	if(isset($gat_backup_config)){
		echo 'deleting default config file.';
		unlink('GarageaTrois/GarageaTrois-Config.php');
		echo 'restoring backup config file.';
		copy($gat_backup_config,'GarageaTrois/GarageaTrois-Config.php');
	}
	else{
		die('Configuration options need to be set in GarageaTrois-Config.php, check index.php for other options that need to be configured.');
	}
}

require_once("$dir/GarageaTrois/GarageaTrois-Config.php");
require_once("$dir/GarageaTrois/GarageaTrois-Functions.php");

if(sha1_file("$dir/$gat/GarageaTrois-Config.php") == getSslPage('https://raw.githubusercontent.com/jamenlang/GarageaTrois-PHP-Server/master/GarageaTrois-Config.php')){
	logger('Configuration options need to be set in GarageaTrois-Config.php, check index.php for other options that need to be configured.');
	die('Configuration options need to be set in GarageaTrois-Config.php, check index.php for other options that need to be configured.');
}

if($use_gpio == true){
	//gpio will need to be initialized before use.
	exec("/usr/local/bin/gpio readall", $output);
	logger($output);
	exec("/usr/local/bin/gpio write $other_relay 1", $output);
	logger($output);
	exec("/usr/local/bin/gpio write $light_relay 1", $output);
	logger($output);
	exec("/usr/local/bin/gpio write $door_relay 1", $output);
	logger($output);
	exec("/usr/local/bin/gpio write $lock_relay 1", $output);
	logger($output);
	exec("/usr/local/bin/gpio mode $other_relay OUT", $output);
	logger($output);
	exec("/usr/local/bin/gpio mode $light_relay OUT", $output);
	logger($output);
	exec("/usr/local/bin/gpio mode $door_relay OUT", $output);
	logger($output);
	exec("/usr/local/bin/gpio mode $lock_relay OUT", $output);
	logger($output);
	exec("/usr/local/bin/gpio readall", $output);
	logger($output);
}
else
	logger('gpio is not enabled in GarageaTrois-Config.php -skipping initialization.');

while(true){
	$command="/sbin/ifconfig $configured_interface | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$localIP = exec($command, $output);
	logger($output);
	echo $localIP;
	logger($localIP);
	if($localIP != '')
		break;
}

if($phpqrcode == '' && $qr_enabled == "1"){
	exec('git clone git://git.code.sf.net/p/phpqrcode/git phpqrcode',$output);
	logger($output);
}

if($hue_emulator_ip == 'myawesomedomain-or-an-ip-address'){
	logger('$hue_emulator_ip is not configured, exiting.');
	exit;
}

if($armzilla == ''){
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
	logger($output);

	$files = scandir($dir);
	foreach($files as $filename){
		if(preg_match('/amazon-echo-bridge/i',$filename,$matches )){
			logger($filename);
			$armzilla = $filename;
		}
	}
}

if ($armzilla != ''){
	logger('starting armzilla hue emulator');
	logger("java -jar $dir/$armzilla --upnp.config.address=$localIP");
	exec("java -jar $dir/$armzilla --upnp.config.address=$localIP");
}
else {
	logger("could not start $dir/$armzilla $localIP");
}

?>
