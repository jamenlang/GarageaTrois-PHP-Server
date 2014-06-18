<?php

/************ I don't believe anything else needs to be modified. ************/
require 'GarageaTrois-Config.php';

// outputs image directly into browser, as PNG stream
// the code can be downloaded or this can be disabled, you can also use the google API line below.
// I cannot get QR to launch an intent, I would like to get this working so a QR code can be scanned and give the app the server information to be stored locally on the device and get rid of hardcoded server strings altogether.

if (!isset($_POST) || empty($_POST)){

        if($qr_enabled == '1'){
                include('phpqrcode/qrlib.php');

                //might as well generate a qr code for the server address since no post data was received
                //$link = "my.special.scheme://server=".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                //OR echo "http://chart.apis.google.com/chart?chf=a,s,000000&chs=200x200&chld=%7C2&cht=qr&chl=" . $link;
                define('IMAGE_WIDTH',$qr_size);
                define('IMAGE_HEIGHT',$qr_size);
                QRcode::png($apk_link);
                exit;
        }
	else{
		exit;
	}
}

if($log_to_file == '1')
	file_put_contents($log,print_r($_POST,true));

//Get POST data and assign new variables.
$name = sanitize($_POST['Name']);
$nfc = sanitize($_POST['NFC']);
$did = sanitize($_POST['DID']);
$uid = sanitize($_POST['UID']);
$switch = sanitize($_POST['switch']);
$device_latitude = sanitize($_POST['Latitude']);
$device_longitude = sanitize($_POST['Longitude']);
$adminaction = sanitize($_POST['AdminAction']);
$number = sanitize($_POST['TelNum']);
$devicealias = sanitize($_POST['DeviceName']);
$hasnfc = sanitize($_POST['hasNFC']);
$change = sanitize($_POST['Change']);

$dbhandle = mysql_connect($hostname, $username, $password)
	or die("Unable to connect to MySQL");

$selected = mysql_select_db($db_name,$dbhandle)
	or die("Could not select " . $db_name);

if (isset($uid) && $uid == 'nfc0' && isset($did) && $did !=''){
	//user is trying to open the door with nfc.

	if(isset($nfc_enabled) && $nfc_enabled == '1'){

		//all we need from them is a uid of 'nfc0' and the did to make sure it's an allowed device and nfc is allowed.
		//first we'll see if the DID is in the allowed array since we have no user to tie it to.
		//if it is then we'll want to check to see if it's also in the NFC allowed array.

		$did_exists = '0';//we'll check this later
		$nfc_allowed = '0';//we'll check this later
		$did_allowed = '0';//we'll check this later
		$hasnfc = '1';//we know this is true.

		if (isset($did) && $did !='')
		{
			//we need to run a select to find out if the device id already exists in the devices database.
			$sql = 'Select * from device where did="' . $did . '"';
			//$reset = mysql_query("RESET QUERY CACHE");
			$dbres = mysql_query($sql);
			//$rows = mysql_fetch_row($dbres);

			while ($row = mysql_fetch_assoc($dbres))
			{
				//print_r($row);
				if ($row[did] == $did){
					$did_exists = '1';
					$nfc_allowed = $row['nfc'];
					$did_allowed = $row['allowed'];
					//echo 'did exists';
				}
			}
		}
		//if it's not then we'll notify the user that when an administrator allows their device

		//they will be notified at the following number
		//this will also need to be logged for the administrator to allow the device.

		if (($did_exists == '0' && $did_allowed == '0') || ($did_exists == '1' && $nfc_allowed != '1')){
			$sql = 'INSERT INTO log (uid, action, did, number, latitude, longitude, date) VALUES ( "' . "NFC (" . $uid . ')","' . 'Denied' . '","' . $did . '","' . $number . '","' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';

			$retval = mysql_query( $sql );
			if(! $retval )
			{
				die('Could not enter data: ' . mysql_error());
			}

			//insert some helpful shit about the device here.
			$sql = 'INSERT INTO device (alias, nfc, has_nfc, force_nfc, did, allowed, number, date) ' . 'VALUES ( "' . $devicealias . '","' . '0' . '", "' . $hasnfc . '", "' . '0' . '", "' . $did . '", "' . '1' . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';
			$retval = mysql_query( $sql );
			if(! $retval )
			{
				die('Could not enter data: ' . mysql_error());
			}

			echo 'Your device has not been allowed yet. ' . (isset($number) && $number !='' ? ' You will receive a text to ' . $number . ' when your device has been allowed. ' : '');

			$stringData = 'New NFC request from ' . $did . "\n";
			mailer("NFC User " . "Denied", $stringData);

			exit;
		}
		//otherwise we can go ahead and open the door.
		else if ($did_exists == '1' && $did_allowed == '1' && $nfc_allowed == '1'){
			if ($switch == "Door"){
				//garage openers put a short on each pair for a breif period, that's what we'll do with the relay
				shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit 1 write");
				sleep(2);
				shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit 1 write");
				echo 'Door toggled';
			}

			$sql = 'INSERT INTO log (uid, action, did, number, latitude, longitude, date) VALUES ( "' . $uid . '","' . 'Granted' . '","' . $did . '","' . $number . '","' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';

			$retval = mysql_query( $sql );
			if(! $retval )
			{
				die('Could not enter data: ' . mysql_error());
			}
			//maybe update the device shit too.

			$sql = 'update device set alias="' . $devicealias . '", nfc="' . $nfc . '", has_nfc="' . $hasnfc . '", number="' . $number . '", date="' . date('Y-m-d H:i:s') . '" where did="' . $did . '"';

			$retval = mysql_query( $sql );
			if(! $retval )
			{
				die('Could not enter data: ' . mysql_error());
			}

			$stringData = 'NFC request from ' . $_POST['DID'] . "\n";
			mailer("NFC User " . "Granted", $stringData);
			exit;
		}
		else {
			//the user has scanned the tag, maybe mutliple times, we've logged it every time and started a device profile. the default profile doesn't have rights so we don't do anything indefinitely.
			echo 'I have no idea what is happening. did exists: ' . $did_exists . ' did allowed: ' . $did_allowed;
			exit;
		}
	}
	else{
		echo 'NFC access has been disabled by an administrator.';
	}
}
//######################## ADMINISTRATIVE ACTION SENT BY APP ###########################//


if (isset($adminaction) && $adminaction !='')
{
	if($adminaction != "Revok" && $adminaction != "Grant")
	{
		exit;
	}
	if($adminaction == "Grant"){
		$allowed = '1';
	}
	else{
		$allowed = '0';
	}
	if (isset($change) && $change !='')
	{
		if ($change == 'change_uid'){
			//update the uid where name = $name
			$sql = 'Select * from auth where name="' . $name . '"';
			$dbres = mysql_query($sql);

			while ($row = mysql_fetch_assoc($dbres))
			{
				//print_r($row);
				if ($row[name] == $name){
					$old_uid = $row[uid];
					$uid_exists = '1';
					//echo 'uid exists';
					//the did already exists; so we need to fail because duh.
					//exit;
				}
			}
			if($log_to_file == '1')
				file_put_contents($log, 'uid ' . $old_uid);
			if ($uid_exists == '1')
			{
				if($log_to_file == '1')
					file_put_contents($log, 'uid ' . $uid_exists);

				$sql = 'Update auth set uid="' . $uid . '", allowed="' . $allowed . '" where name="' . $name . '"';
				if($log_to_file == '1')
					file_put_contents($log, $sql);

				$retval = mysql_query( $sql);
				if(! $retval )
				{
					die('Could not enter data: ' . mysql_error());
				}

				echo 'UID changed from ' . $old_uid . ' to ' . $uid . ' for ' . $name;
				exit;
			}
			else {
				//this is experimental 5/29/14
				if($log_to_file == '1')
					file_put_contents($log, 'uid ' . $uid_exists);
				//$sql = 'insert into auth (uid, allowed, name) values ('{'$uid'}', '{'$allowed'}', '{'$name'}')';
				$sql = 'INSERT INTO auth (name, uid, allowed, date) ' . 'VALUES ( "' . $name . '","' . $uid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';
				if($log_to_file == '1')
					file_put_contents($log, $sql);

				$retval = mysql_query( $sql);
                                if(! $retval )
                                {
                                       die('Could not enter data: ' . mysql_error());
                                }

				//echo 'User: ' . $name . ' UID: ' . $uid . ' ' . $allowed;
				echo 'New User: ' . $name . ' UID: ' . $uid . ' ' . (($allowed == '1') ? 'allowed' : 'disallowed');

				exit;
			}
		}
		if ($change == 'change_name'){
		//update the name where name = $uid

			$sql = 'Select * from auth where uid="' . $uid . '"';
			if($log_to_file == '1')
				file_put_contents($log, $sql);

			$dbres = mysql_query($sql);

			while ($row = mysql_fetch_assoc($dbres))
			{
				//print_r($row);
				if ($row[uid] == $uid){
					$old_name = $row[name];
					$name_exists = '1';
					//echo 'uid exists';
					//the uid already exists; so we need to run an update query instead of insert.
					//exit;
				}

			}
			if($log_to_file == '1')
				file_put_contents($log, $old_name);
			if ($name_exists == '1')
			{
				if($log_to_file == '1')
					file_put_contents($log, 'name ' . $name_exists);

				$sql = 'Update auth set name="' . $name . '", allowed="' . $allowed . '" where uid="' . $uid . '"';
				$retval = mysql_query( $sql);
				if(! $retval )
				{
					die('Could not enter data: ' . mysql_error());
				}
				echo 'Name changed from ' . $old_name . ' to ' . $name . ' for ' . $uid;
				exit;
			}
			else {
				//this is experimental 5/29/14
				if($log_to_file == '1')
					file_put_contents($log, 'name ' . $name_exists);
				//$sql = 'insert into auth (uid, allowed, name) values ('{'$uid'}', '{'$allowed'}', '{'$name'}')';
				$sql = 'INSERT INTO auth (name, uid, allowed, date) ' . 'VALUES ( "' . $name . '","' . $uid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';
				if($log_to_file == '1')
					file_put_contents($log, $sql);

				$retval = mysql_query( $sql);
                                if(! $retval )
                                {
                                        die('Could not enter data: ' . mysql_error());
                                }

				echo 'New User: ' . $name . ' UID: ' . $uid . ' ' . (($allowed == '1') ? 'allowed' : 'disallowed');

				exit;
			}
		}
	}

	if (isset($uid) && $uid !='')
	{
		//we need to run a select to find out if the user id already exists.
		$sql = 'Select * from auth where uid="' . $uid . '"';
		//$reset = mysql_query("RESET QUERY CACHE");
		$dbres = mysql_query($sql);
		//$rows = mysql_fetch_row($dbres);

		while ($row = mysql_fetch_assoc($dbres))
		{
			//print_r($row);
			if ($row[uid] == $uid){
				$old_name = $row[name];
				$uid_exists = '1';
				//echo 'uid exists';
				//the user already exists; so we need to run an update query instead of insert.
				//exit;
			}
		}
	}

	if (isset($did) && $did !='')
	{
		//we need to run a select to find out if the user id already exists.
		$sql = 'Select * from device where did="' . $did . '"';
		//$reset = mysql_query("RESET QUERY CACHE");
		$dbres = mysql_query($sql);
		//$rows = mysql_fetch_row($dbres);

		while ($row = mysql_fetch_assoc($dbres))
		{
			//print_r($row);
			if ($row[did] == $did){
				$did_exists = '1';
				//echo 'did exists';
				//the did already exists; so we need to run an update query instead of insert.
				//exit;
			}
		}
	}
	//file_put_contents("post.log", 'did exists: ' . $did_exists . ' uid exists ' . $uid_exists);

	if($adminaction == "Grant" || $adminaction == "Revok")
	{
		if (isset($nfc) && $nfc != ''){
			if ($nfc == 'nonexclusive'){
				//set nfc = allowed (unless we're revoking priviledges)
				$nfc = (($adminaction == "Revok") ? '0' : '1');
				$forcenfc = '0';
			}
			if ($nfc == 'exclusive'){
				//set nfc = allowed because it has to be for exclusive access... if the priviledge is revoked then nfc is disabled entirely.
				$nfc = (($adminaction == "Revok") ? '0' : '1');
				$forcenfc = (($adminaction == "Revok") ? '0' : '1');
			}
		}

		if ($uid_exists == '1' && isset($uid) && $uid !=''){
			//file_put_contents("post.log", 'uid exists: ' . $uid_exists . ' posted uid exists ' . $uid);
			//update .. where uid = $uid
			$sql = 'update auth set allowed="' . $allowed . '", name="' . $name . '", date="' . date('Y-m-d H:i:s') . '" where uid= "' . $uid . '"';

			$retval = mysql_query( $sql);

			if(! $retval )
			{
				die('Could not enter data: ' . mysql_error());
			}
			//echo $uid . ' already exists. Auth updated.';
			echo "Privileges for " . $name . " (" . $uid . ") " . $adminaction . "ed";
			exit;
		}

		if ($did_exists == '1' && isset($did) && $did !=''){
			//update .. where did = $did
			$sql = 'update device set allowed="' . $allowed . '", nfc="' . $nfc . '", force_nfc="' . $forcenfc  . '", date="' . date('Y-m-d H:i:s') . '" where did= "' . $did . '"';

			//file_put_contents("post.log", $sql);
			$retval = mysql_query( $sql);

			if(! $retval )
			{
				die('Could not enter data: ' . mysql_error());
			}
			echo "Privileges for " . $did . " " . $adminaction . "ed";

			exit;
		}

		if ($did_exists != '1' && $uid_exists != '1'){
			//this is a new user. insert.
			if ($uid != ''){
				$sql = 'INSERT INTO auth (name, uid, allowed, date) ' . 'VALUES ( "' . $name . '","' . $uid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';
			}
			if ($did != ''){
				$sql = 'INSERT INTO device (nfc, has_nfc, force_nfc, did, allowed, number, date) ' . 'VALUES ( "' . $nfc . '","' . $hasnfc . '", "' . $forcenfc . '", "' . $did . '", "' . $allowed . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';
			}

			$retval = mysql_query( $sql);

			if(! $retval )
			{
				die('Could not enter data: ' . mysql_error());
			}
			else{
				if (isset($did) && $did != '')
				{
					echo "Privileges for " . $did . "  " . $adminaction . "ed";

					$stringData = $did . ' ' . $adminaction . "ed" . "\n";
				        mailer("Device " . $granted, $stringData, $number);
					exit;
				}
				if (isset($name) && $name != '')
				{
					//return "Privileges for " . $_POST['Name'] . " (" . $_POST['UID'] . ") " . $_POST['AdminAction'] . "ed"; 

					echo "Privileges for " . $name . " (" . $uid . ") " . $adminaction . "ed. " . (isset($number) && $number != '' ? "User has been notified @ " . $number : "");
					exit;
				}
			}
		}
	}
}

if (isset($_POST['Admin']) && $_POST['Admin'] != '')
{
	if ($_POST['Admin'] == 'viewlog')
		$table = 'log';
	if ($_POST['Admin'] == 'viewdevices')
		$table = 'device';
	if ($_POST['Admin'] == 'viewusers')
		$table = 'auth';
	if (isset($table) && $table != '')
	{
		 if($log_to_file == '1')
                        file_put_contents($log,$table);
		$con=mysql_connect($hostname, $username, $password)or die("cannot connect");
		mysql_select_db($db_name)or die("cannot select DB");
		$sql = "select * from " . $table . " order by date desc";
		 if($log_to_file == '1')
                        file_put_contents($log,$sql);
		$result = mysql_query($sql);
		$json = array();

		if(mysql_num_rows($result)){
		//      echo mysql_num_rows($result);
	        	while($row=mysql_fetch_assoc($result)){
	        		if($log_to_file == '1')
					file_put_contents($log,$row);
	                	$json[ $table . '_info'][]=$row;
	       		}
		}
		mysql_close($con);
		echo json_encode($json);
		if($log_to_file == '1')
			file_put_contents($log,print_r($json));
		exit;
	}
}

if (!isset($uid))
{
	echo "No user";
	exit;
}


$dbhandle = mysql_connect($hostname, $username, $password)
  or die("Unable to connect to MySQL");

$selected = mysql_select_db($db_name,$dbhandle)
  or die("Could not select " . $db_name);
/*
if (isset($_POST['Log']) && $_POST['Log'] == "true")
{
	$result = mysql_query("SELECT * FROM log");
	//fetch tha log from the database
	while ($row = mysql_fetch_row($result)) {
        	echo $row[0] . ' ' . $row[1] . ' ' . $row[2] . ' ' . $row[3] . ' ' . $row[4] . '\r\n';
	}
	exit;
}
*/
$result = mysql_query("SELECT * FROM auth");
//fetch tha data from the database
while ($row = mysql_fetch_array($result)) {

   	if ($row{'allowed'} == "1"){
		$allowed_users[$row{'uid'}] = $row{'name'};

		if ($row{'admin'} == "1"){
			$admin_users[$row{'uid'}] = $row{'name'};
		}
	}
	else {
		$disallowed_users[$row{'uid'}] = $row{'name'};
	}
	$users[$row{'uid'}] = $row{'name'};
}
$result = mysql_query("SELECT * FROM device");
//fetch tha data from the database
while ($row = mysql_fetch_array($result)) {

   	if ($row{'allowed'} == "1"){
		$allowed_devices[$row{'did'}] = $row{'did'};

		if ($row{'force_nfc'} == "1"){
			$nfc_only_devices[$row{'did'}] = $row{'did'};
		}
	}
	else {
		$disallowed_devices[$row{'did'}] = $row{'did'};
	}
	$devices[$row{'did'}] = $row{'did'};
}

if (isset($switch) && $switch != ''){
	//we'll put this here since the geofence doesn't apply to NFC or the admin sections.
	//also prevents user trickery by logging in inside the fence then leaving the app open while they cross the boundry.
	if($geofence_enabled == 'true')
        {
        	if($device_latitude == '' || $device_longitude == '' || $device_latitude = '0.0' || $device_longitude = '0.0'){
                        echo 'Geofence Enabled: GPS Empty.';
                        exit;
                }
                
		if(distance($garage_latitude, $garage_longitude, $device_latitude, $device_longitude, $geofence_unit_of_measurement) >= $geofence_maximum_allowed_distance)
                {
                        $switch = $switch . ' Denied (Geofence)';
                        $sql = 'INSERT INTO log (name, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' . $uid . '", "' . $did . '", "' . $switch . '", "' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';

                        $retval = mysql_query( $sql);

                        if(! $retval )
                        {
                                die('Could not enter data: ' . mysql_error());
                        }

                        echo 'Geofence Enabled: Out of bounds.';
                        exit;
                        //this will effectively disable the button in the app. The only thing available is administration of users and devices.
                }
        }

	if ($switch == "Light"){
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit 0 write");
		sleep(2);
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit 0 write");
		echo 'Light toggled';
	}

	if ($switch == "Door"){
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit 1 write");
		sleep(2);
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit 1 write");
		echo 'Door toggled';
	}

	if ($switch == "Lock"){
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit 2 write");
		sleep(2);
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit 2 write");
		echo 'Lock toggled';
	}

	$sql = 'INSERT INTO log (name, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' . $uid . '", "' . $did . '", "' . $switch . '", "' . $device_latitude . '","' . $device_longitude . '","'. date('Y-m-d H:i:s') . '" )';

	$retval = mysql_query( $sql);

	if(! $retval )
	{
  		die('Could not enter data: ' . mysql_error());
	}

	//$myFile = "auth.log";
	//$fh = fopen($myFile, 'a');
	//$stringData = date("Y-m-d H:i:s") . " ";
	//fwrite($fh, $stringData);
	$stringData = $switch . " toggled\n";
	$txt = $users[$uid] . " toggled " . $switch . ' @ ' . $stringData;
	// fix post uid so we can identify the user here, this is working in the latest build.
	// Send email
	mailer($stringData, $txt);
	//fwrite($fh, $stringData);
	//fclose($fh);
	exit;
}
else{

	if (!isset($uid))
	{
	       	echo 'Log in';
        	exit;
	}
	else if (array_key_exists($did, $disallowed_devices)){
		$granted = 'Denied (Device)';
		echo 'Access denied';
	}
	else if (array_key_exists($did, $nfc_only_devices)){
		$granted = 'Denied (Device listed as NFC Only)';
		echo 'Access denied';
	}
	else if (array_key_exists($uid, $admin_users)){
		$granted = 'Admin Granted';
        	echo $SUPER_SECRET_ADMIN_RESULT;
	}
	else if (array_key_exists($uid, $allowed_users)){
        	$granted = 'Granted';
		echo $SUPER_SECRET_USER_RESULT;
		//
	}
	else{
        	$granted = 'Denied';
        	echo 'Access denied';
	}
	//$myFile = "auth.log";
	//$fh = fopen($myFile, 'a');
	//$stringData = date("Y-m-d H:i:s") . " ";
	//fwrite($fh, $stringData);
	$sql = 'INSERT INTO log (name, uid, did, number, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' .  $uid . '","' . $did . '","' . $number . '","' . $granted . '","' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';

	$retval = mysql_query( $sql );

	if(! $retval )
	{
		die('Could not enter data: ' . mysql_error());
	}

	if (array_key_exists($did, $devices))
	{
		//maybe update device shit here.
		$sql = 'update device set allowed="1", alias="' . $devicealias . '", has_nfc="' . $hasnfc . '", number="' . $number . '", date="' . date('Y-m-d H:i:s') . '" where did="' . $did . '"';

		$retval = mysql_query( $sql );

		if(! $retval )
		{
			die('Could not enter data: ' . mysql_error());
		}
	}
	else {
		//insert some helpful shit about the device here.
		$sql = 'INSERT INTO device (alias, allowed, has_nfc, did, number, date) ' . 'VALUES ( "' . $devicealias . '","' . '1' . '", "' . $hasnfc . '", "' . $did . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';

		$retval = mysql_query( $sql );

		if(! $retval )
		{
			die('Could not enter data: ' . mysql_error());
		}
	}

	$stringData = $users[$uid] . ' from ' . $did . "\n";
	mailer("User " . $granted, $stringData);
	//fwrite($fh, $stringData);
	//fclose($fh);

}

function mailer($subject, $message, $newuser){
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

?>
