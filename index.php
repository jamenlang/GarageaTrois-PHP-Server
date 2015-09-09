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
	echo '<li>Curl needs to be enabled for some functionality, mostly for echo support and index.php.</li>';
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

if($qr_enabled == '1' && file_exists('phpqrcode/qrlib.php')){
	include('phpqrcode/qrlib.php');
	define('IMAGE_WIDTH',$qr_size);
	define('IMAGE_HEIGHT',$qr_size);
	QRcode::png($apk_link);
}
else
	echo '<li>QR Code is disabled or qrlib is not installed.</li>';

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
