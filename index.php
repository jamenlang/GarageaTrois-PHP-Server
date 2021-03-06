<?php
require('GarageaTrois-Config.php');
require('GarageaTrois-Functions.php');

if($apk_link == 'http://files.myawesomedomain.net/garageatrois.apk'){
	$ip = gethostbyname('github.com');
	if(!$ip){
		$dns_result = '<li style="bgcolor: red">A link to the APK file could not be retrieved due to DNS resolution issues. Run resolvconf -a 8.8.8.8 or edit /etc/network/interfaces and add dns-nameservers 8.8.8.8 8.8.4.4</li>';
	}
	exec('wget --max-redirect=0 $( curl -s https://api.github.com/repos/jamenlang/GarageaTrois/releases/latest | grep \'browser_\' | cut -d\" -f4) 2>&1', $output);
	foreach ($output as $line){
		if($apk_link != 'http://files.myawesomedomain.net/garageatrois.apk')
			continue;
		preg_match('/\bhttp.*apk\b/',$line, $matches);
		if($matches[0])
			$apk_link = $matches[0];
	}
}
$output = '';
$dir = '/var/www';
$files = scandir($dir);
foreach($files as $filename){
	//echo $filename;
	if(preg_match('/amazon-echo-bridge/i',$filename,$matches )){
		$armzilla = $filename;
	}
}
if((!$armzilla || !$use_bws) && $hue_emulator_ip != 'myawesomedomain-or-an-ip-address' && $use_hue_emulator != false){
	if(!$use_bws)
		$repo = 'armzilla/amazon-echo-ha-bridge/';
	else
		$repo = 'bwssystems/ha-bridge/';

	exec('wget --max-redirect=0 $( curl -s https://api.github.com/repos/' . $repo . 'releases/latest | grep \'browser_\' | cut -d\" -f4) 2>&1', $output);
	foreach ($output as $line){
		if($hue_emulator_link != '')
			continue;
		preg_match('/\bhttp.*jar\b/',$line, $matches);
			if($matches[0])
				$hue_emulator_link = $matches[0];
	}
	$hue_emulator_result = '<li><a href="' . $hue_emulator_link . '">Link to ' . (($use_bws) ? 'BWS\'s' : 'Armzilla\'s') . ' Hue Emulator</a></li>';
}
else
	$hue_emulator_result = '<li><a href="GarageaTrois-Echo.php">Link to Hue Emulator Configurator</a></li>';

if($qr_enabled == '1' && file_exists('/var/www/phpqrcode/lib/full/qrlib.php')){
	if(isset($_GET['showlink'])){
		include('../phpqrcode/lib/full/qrlib.php');
		define('IMAGE_WIDTH',$qr_size);
		define('IMAGE_HEIGHT',$qr_size);
		QRcode::png($apk_link);
		exit;
	}
	else{
		$link_result = '<li><a href="?showlink=1">Show QR Code</a></li>';
	}
}
else
	$link_result = '<li>QR Code is disabled or qrlib is not installed. A link to the APK file could not be generated. Download phpqrcode from \'http://sourceforge.net/p/phpqrcode/git/ci/863ffffac4c9d22e522464e325cbcbadfbb26470/tree/lib/full/\' or visit ' . $apk_link . ' on your android device.</li>';
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>GaT Home</title>
</head>
<body>
<h2>Configuration Checklist</h2>
<?php

$test = '0';

if($dns_result){
	echo $dns_result;
	$test++;
}

if(!_isCurl()){
	echo '<li>Curl needs to be enabled for some functionality, mostly for echo support and index.php. Run \'sudo apt-get install php5-curl\' to fix this message.</li>';
	$test++;
}

if(!exec('gpio -v')){
	echo '<li>WiringPI needs to be installed for raspberry pi relay control.</li>';
	$test++;
}

if(!$dbhandle = mysql_connect($hostname, $username, $password)){
	echo '<li>MYSQL database needs to be configured for users/devices/nfc/logging.</li>';
	$test++;
}

if(!$selected = mysql_select_db($db_name,$dbhandle)){
	echo '<li>MYSQL database `' . $db_name . '` is not accessible.</li>';
	$test++;
}

if(sha1_file('GarageaTrois-Config.php') == getSslPage('https://raw.githubusercontent.com/jamenlang/GarageaTrois-PHP-Server/master/GarageaTrois-Config.php')){
	echo '<li>Configuration options need to be set in GarageaTrois-Config.php</li>';
	$test++;
}

if($super_admin == '0009'){
	echo '<li>$super_admin needs to be updated or removed in GarageaTrois-Config.php</li>';
	$test++;
}

if($SUPER_SECRET_ADMIN_RESULT == 'SUPER_SECRET_ADMIN_RESULT'){
	echo '<li>Super Secret Admin Result needs to be set to a secure string.</li>';
	$test++;
}

if($SUPER_SECRET_USER_RESULT == 'SUPER_SECRET_USER_RESULT'){
	echo '<li>Super Secret User Result needs to be set to a secure string.</li>';
	$test++;
}

if($use_gpio == false){
	echo '<li>GPIO is disabled in the config file, an alternative method of control will be required.</li>';
	$test++;
}

if($hue_url == 'http://myawesomedomain-or-an-ip-address:' . $start_port .'/api/devices'){
	echo '<li>Hue Emulator URL is not set, you won\'t be able to set up devices for the Amazon Echo without this.</li>';
	$test++;
}

echo $link_result;

echo $hue_emulator_result;
echo '<br />';
echo '<br />';
echo (($test != 0) ? $test . ' items need your attention.<br /><br />' : 'Nothing else to do here, index.php needs to be removed or renamed.');

?>
</body>

</html>
