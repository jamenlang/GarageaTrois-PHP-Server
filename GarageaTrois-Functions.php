<?php

function toggle_relay($gpio_relay){
        exec("/usr/local/bin/gpio write $gpio_relay 0");
        sleep(2);
        exec("/usr/local/bin/gpio write $gpio_relay 1");
}

function mailer($subject, $message, $newuser = ''){
	global $admin_mobile, $admin_email, $carriers, $notification_email, $admin_send_to;
	$from = $notification_email;
	$headers = "From: $from\r\n" . "X-Mailer: php";
	$to_list = array();
	if(isset($newuser) && $newuser != ''){
		$to_list[] = $newuser;
	}
	else if($admin_send_to == "both"){
		$to_list[] = $admin_mobile;
		$to_list[] = $admin_email;
	}
	else{
		$to_list[] = (($admin_send_to == "email") ? $admin_email : $admin_mobile);
	}
	foreach ($to_list as $to){
		if(!strstr($to,"@")){
			foreach($carriers as $carrier){
				$send_to = $to . "@" . $carrier;
				mail($send_to, $subject, $message, $headers, '-r ' . $from);
			}
		}
		else{
			mail($to, $subject, $message, $headers, '-r ' . $from);
		}
	}
}
// Sanitize input
function sanitize($in) {
	if(!$in){
		return '';
	}
	return addslashes(htmlspecialchars(strip_tags(trim($in))));
}

function distance($lat1, $lon1, $lat2, $lon2, $unit) {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtolower($unit);
        if ($unit == "kilometers") {
                return ($miles * 1.609344);
        } else if ($unit == "meters") {
                return ($miles * 1609.34);
        } else {
                return $miles;
        }
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

function _isCurl(){
	return function_exists('curl_version');
}

function logger($data){
	include('GarageaTrois-Config.php');
	if($log_to_file != "1"){
		return;
	}
	if(is_array($data)){
		foreach($data as $line){
			file_put_contents($log, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
		}
	}
	else{
		file_put_contents($log, $data . PHP_EOL, FILE_APPEND | LOCK_EX);
	}
}

?>
