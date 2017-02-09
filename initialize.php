#! /usr/bin/php
<?php
# move to /var/www/
# run sudo crontab -e and add this as @reboot cd /var/www/; php initialize.php

if(php_sapi_name() != 'cli'){
	exit;
}

if(!exec('git --version')){
	echo 'installing git-core';
	exec('apt-get -y install git-core');
}

$counter=0;

if(!function_exists('curl_version')){
	echo 'installing php5-curl';
	exec('apt-get -y install php5-curl', $output);
	while(!function_exists('curl_version')){
		if($counter > 20){
			echo 'expired waiting for php5-curl';
			break;
		}
		echo 'wating for php5-curl';
		$counter += 1;
		sleep(15);
	}
}

$dir = '/var/www';

if(getcwd() != $dir){
	if(!file_exists($dir . '/GarageaTrois') && file_exists($dir . '/GarageaTrois-PHP-Server')){
	   echo('moving to ' . $dir . '/GarageaTrois');
		move($dir . '/GarageaTrois-PHP-Server', $dir . '/GarageaTrois');
	}
	copy( $dir . '/GarageaTrois/initialize.php', $dir . '/initialize.php');
	echo('this script must be placed in /var/www/, a copy has been made and this file will be destroyed.');
	unlink($dir . '/GarageaTrois/initialize.php');
	exit;
}

$armzilla = '';
$bws = '';
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

echo 'scanning for additional files and folders.';

$files = scandir($dir);
foreach($files as $filename){
	if(preg_match('/amazon-echo-bridge/i',$filename,$matches )){
		echo 'amazon-echo-bridge found.';
		$armzilla = $filename;
	}
	if(preg_match('/ha-bridge/i',$filename,$matches )){
		echo 'ha-bridge found.';
		$bws = $filename;
	}
	if(preg_match('/GarageaTrois/',$filename,$matches )){
		if(is_dir($filename)){
			echo 'GaT directory found.';
			$gat = $filename;
		}
		if(is_file($filename)){
			echo 'GaT backup config file found.';
			$gat_backup_config = $filename;
		}
	}
	if(preg_match('/phpqrcode/',$filename,$matches )){
		if(is_dir($filename)){
			echo 'phpqrcode found.';
			$phpqrcode = $filename;
		}
	}
}

if($gat == ''){
	echo 'GaT not found, downloading.';

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
	else
		unlink($log);
}

require_once("$dir/GarageaTrois/GarageaTrois-Config.php");
require_once("$dir/GarageaTrois/GarageaTrois-Functions.php");

if(sha1_file("$dir/$gat/GarageaTrois-Config.php") == getSslPage('https://raw.githubusercontent.com/jamenlang/GarageaTrois-PHP-Server/master/GarageaTrois-Config.php')){
	logger('Configuration options need to be set in GarageaTrois-Config.php, check index.php for other options that need to be configured.');
	die('Configuration options need to be set in GarageaTrois-Config.php, check index.php for other options that need to be configured.');
}

$counter = 0;

if($use_gpio == true){
	if(!exec("gpio -v")){
		echo 'wiringpi is not installed, downloading now.';
		logger('wiringpi is not installed, downloading now.');
		exec("git clone git://git.drogon.net/wiringPi");
		chdir("$dir/wiringPi");
		exec("./build");
		echo 'wiringpi is being being compiled...';
		logger('wiringpi is being being compiled...');
		while(!exec('gpio -v')){
			if($counter > 20){
				echo 'man, wiringpi takes a long time to install.';
				logger('man, wiringpi takes a long time to install.');
				break;
			}

			echo 'waiting for wiringpi to finish installing.';
			logger('waiting for wiringpi to finish installing.');
			$counter += 1;
			sleep(15);
		}
		chdir($dir);
	}
	//gpio will need to be initialized before use.
	exec("/usr/local/bin/gpio readall", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio write $relay_1 1", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio write $relay_2 1", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio write $relay_3 1", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio write $relay_4 1", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio mode $relay_1 OUT", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio mode $relay_2 OUT", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio mode $relay_3 OUT", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio mode $relay_4 OUT", $output);
	logger($output);
	$output = '';
	exec("/usr/local/bin/gpio readall", $output);
	logger($output);
	$output = '';
}
else{
	echo 'gpio is not enabled in GarageaTrois-Config.php -skipping initialization.';
	logger('gpio is not enabled in GarageaTrois-Config.php -skipping initialization.');
}

if($phpqrcode == '' && $qr_enabled == "1"){
	exec('git clone git://git.code.sf.net/p/phpqrcode/git phpqrcode', $output);
	print_r($output);
	logger($output);
}

if($motion_ip != '' && $use_motion == false){
	echo '$motion variables are not configured, skipping.';
	logger('$motion variables are not configured, skipping.');
	//exit;
}
else{
	if(!file_exists("/etc/motion")){
		exec('apt-get -y install motion',$output);
		logger('motion is not configured, config files for motion can be found in \'/etc/motion/motion.conf\'');
		logger('if running motion as daemon, run \'sudo systemctl enable motion\'');
	}
}

if($hue_emulator_ip == 'myawesomedomain-or-an-ip-address' || $use_hue_emulator == false){
	echo '$hue_emulator variables are not configured, exiting.';
	logger('$hue_emulator variables are not configured, exiting.');
	exit;
}

$counter = 0;
$result = exec('command -v java >/dev/null && echo "true" || echo "false"');
if(!$result || $result == 'false'){
	echo 'java not installed, downloading java. this may take a few minutes...';
	logger('java not installed, downloading java. this may take a few minutes...');
	exec('sh -c \'echo "deb http://ppa.launchpad.net/webupd8team/java/ubuntu precise main" >> /etc/apt/sources.list\'', $output);
	logger($output);
	$output = '';
	exec('sh -c \'echo "deb-src http://ppa.launchpad.net/webupd8team/java/ubuntu precise main" >> /etc/apt/sources.list\'', $output);
	logger($output);
	$output = '';
	exec('sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys EEA14886', $output);
	logger($output);
	$output = '';
	exec('sudo apt-get update', $output);
	logger($output);
	$output = '';
	exec('echo oracle-java8-installer shared/accepted-oracle-license-v1-1 select true | sudo /usr/bin/debconf-set-selections', $output);
	logger($output);
	$output = '';
	exec('sudo apt-get -y install oracle-java8-installer', $output);
	logger($output);
	$output = '';
	exec('sudo update-java-alternatives -s java-8-oracle', $output);
	logger($output);
	$output = '';
	exec('sudo apt-get -y install oracle-java8-set-default');
	$result = exec('command -v java >/dev/null && echo "true" || echo "false"');

	while(!$result){
		if($counter > 20){
			echo 'man, java takes a long time to install.';
			logger('man, java takes a long time to install.');
			break;
		}
		echo 'waiting for java to finish installing.';
		logger('waiting for java to finish installing.');
		$counter += 1;
		$result = exec('command -v java >/dev/null && echo "true" || echo "false"');
		sleep(15);
	}
}
else{
	echo 'java is installed.';
	logger('java is installed.');
}

$counter = 0;
while(true){
	echo 'attempting to parse ip address from ' . $configured_interface;

	if($counter > 100){
		echo 'could not parse ip address from ' . $configured_interface;
		logger('could not parse ip address from ' . $configured_interface);
		break;
	}
	$counter += 1;
	$command="/sbin/ifconfig $configured_interface | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
	$localIP = exec($command, $output);
	logger($output);
	echo $localIP;
	logger($localIP);
	if($localIP != '')
		break;
}

$hue_emulator_link = '';

if($use_bws){
	if($bws == ''){
		exec('wget --max-redirect=0 $( curl -s https://api.github.com/repos/bwssytems/ha-bridge/releases/latest | grep \'browser_\' | cut -d\" -f4) 2>&1', $output);
		foreach ($output as $line){
			if($hue_emulator_link != '')
				continue;
			preg_match('/\bhttp.*jar\b/',$line, $matches);
			if(isset($matches[0])){
				$hue_emulator_link = $matches[0];
				break;
			}
        	}
		exec("wget $hue_emulator_link", $output);
		logger($output);

		$files = scandir($dir);
		foreach($files as $filename){
			if(preg_match('/ha-bridge/i',$filename,$matches )){
				logger($filename);
				$bws = $filename;
			}
		}
	}

	if ($bws != ''){
		$output = '';
		//check if ha-bridge is in the running applications list
		exec("ps ax | grep ha-bridge", $output);
		if(is_array($output))
			$output = implode(',',$output);
		if(stristr($output, "java")){
			logger('bws hue emulator is already started.');
		}
		else{
			logger('starting bws hue emulator');
			logger("java -jar -Dserver.port=$start_port $dir/$bws");
			exec("java -jar -Dserver.port=$start_port $dir/$bws");
		}
	}
	else {
		logger("could not start $dir/$bws $localIP");
	}
}else{
	if($armzilla == ''){
		exec('wget --max-redirect=0 $( curl -s https://api.github.com/repos/armzilla/amazon-echo-ha-bridge/releases/latest | grep \'browser_\' | cut -d\" -f4) 2>&1', $output);
		foreach ($output as $line){
			if($hue_emulator_link != '')
				continue;
			preg_match('/\bhttp.*jar\b/',$line, $matches);
			if(isset($matches[0])){
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
		$output = '';
		//check if amazon-echo-bridge is in the running applications list
		exec("ps ax | grep amazon-echo-bridge", $output);
		if(is_array($output))
			$output = implode(',',$output);
		if(stristr($output, "java")){
			logger('armzilla hue emulator is already started.');
		}
		else{
			logger('starting armzilla hue emulator');
			logger("java -jar $dir/$armzilla --server.port=$start_port --upnp.config.address=$localIP");
			exec("java -jar $dir/$armzilla --server.port=$start_port --upnp.config.address=$localIP");
		}
	}
	else {
		logger("could not start $dir/$armzilla $localIP");
	}
}
?>
