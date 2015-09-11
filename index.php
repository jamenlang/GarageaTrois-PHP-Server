<!DOCTYPE html>
<?php require('GarageaTrois-Config.php');?>
<html>
<head>
<meta charset="UTF-8">
<title>GaT Home</title>
</head>
<body>
<h2>Configuration Checklist</h2>
<?php

$test = '0';

if(!_isCurl()){
	echo '<li>Curl needs to be enabled for some functionality, mostly for echo support and index.php. Run \'sudo apt-get install php5-curl\' to fix this message.</li>';
	$test++;
}
if($apk_link == 'http://files.myawesomedomain.net/garageatrois.apk'){
	exec('wget --max-redirect=0 $( curl -s https://api.github.com/repos/jamenlang/GarageaTrois/releases/latest | grep \'browser_\' | cut -d\" -f4) 2>&1', $output);
	foreach ($output as $line){
		if($apk_link != 'http://files.myawesomedomain.net/garageatrois.apk')
			continue;
		preg_match('/\bhttp.*apk\b/',$line, $matches);
		if($matches[0])
			$apk_link = $matches[0];
	}
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

if($dummy_admin == '0009'){
        echo '<li>Dummy administrator needs to be updated or removed in GarageaTrois-Config.php</li>';
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

if($qr_enabled == '1' && file_exists('phpqrcode/qrlib.php')){
	include('phpqrcode/qrlib.php');
	define('IMAGE_WIDTH',$qr_size);
	define('IMAGE_HEIGHT',$qr_size);
	QRcode::png($apk_link);
}
else
	echo '<li>QR Code is disabled or qrlib is not installed. A link to the APK file could not be generated. Download phpqrcode from \'http://sourceforge.net/p/phpqrcode/git/ci/863ffffac4c9d22e522464e325cbcbadfbb26470/tree/lib/full/\' or visit ' . $apk_link . ' on your android device.</li>';

echo (($test != 0) ? $test . ' items need your attention.<br /><br />' : 'Nothing else to do here, index.php needs to be removed or renamed.');

?>
</body>

</html>
<?php
function _isCurl(){
	return function_exists('curl_version');
}

function getSslPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    return sha1($result);
}
