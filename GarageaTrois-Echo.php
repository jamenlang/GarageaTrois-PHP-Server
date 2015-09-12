<?php
require('GarageaTrois-Config.php');

/************************************************************************************
 Functions as an inbetween between armzilla's hue emulator and GarageaTrois-Server.
 Requests from the echo should be directed here, then posted to GarageaTrois-Server.
 Place in same directory as GarageaTrois-Server and GarageaTrois-Config.php.
*************************************************************************************/

$dir = dirname($_SERVER['PHP_SELF']);

if($_POST){
	$content = json_encode($_POST);

	$curl = curl_init($hue_devices_url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
		array("Content-type: application/json")
	);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

	$json_response = curl_exec($curl);

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 201 ) {
		die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
	}

	curl_close($curl);
	$response = json_decode($json_response, true);
	var_dump($response);
	exit;
}

if(isset($_GET['id']) && $_GET['id'] = 'action'){
	$switch = $_GET['switch'];

	$url = "http://$_SERVER[HTTP_HOST]$dir/GarageaTrois-Server.php";
	$fields = array(
		'UID' => urlencode($echo_uid),
		'DID' => urlencode($echo_did),
		'DeviceName' => urlencode($echo_name),
		'hasNFC' => urlencode('false'),
		'switch' => urlencode(ucfirst($_GET['switch'])),
		'Latitude' => urlencode(ucfirst($garage_latitude)),
		'Longitude' => urlencode(ucfirst($garage_longitude))
	);

	//url-ify the data for the POST
	foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	rtrim($fields_string, '&');

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_POST, count($fields));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);
	echo $result;
}

if($hue_configurator_url == 'http://myawesomedomain-or-an-ip-address:8080/configurator.html'){
	echo 'warning: $hue_configurator_url needs to be configured in GarageaTrois-Config.php';
}
else{
	echo '<a href="' . $hue_configurator_url . '">link to hue emulator configurator url</a>';
}
?>
- or add here -
<table>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
<tr><td><label>Name</label></td><td><input placeholder="garage light" type="text" id="name" name="name"/></tr>
<tr><td><label>Device Type</label></td><td><input placeholder="switch" type="text" id="deviceType" name="deviceType"/></tr>
<tr><td><label>onURL</label></td><td><input placeholder="<?php echo "http://$_SERVER[HTTP_HOST]$dir";?>/GarageaTrois-Echo.php?id=action&switch=light" size="190" type="text" id="onUrl" name="onUrl"/></tr>
<tr><td><label>offURL</label></td><td><input placeholder="<?php echo "http://$_SERVER[HTTP_HOST]$dir";?>/GarageaTrois-Echo.php?id=action&switch=light" size="190" type="text" id="offUrl" name="offUrl"/></tr>
<tr><td><input type="submit" value="add device"></td></tr>
</form>
</table>
