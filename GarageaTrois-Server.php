<?php
/************ I don't believe anything here needs to be modified. ************/
//require config and function files
require 'GarageaTrois-Config.php';
require 'GarageaTrois-Functions.php';

// outputs image directly into browser, as PNG stream
// the code can be downloaded or this can be disabled, you can also use the google API line below.
// I cannot get QR to launch an intent, I would like to get this working so a QR code can be scanned and give the app the server information to be stored locally on the device and get rid of hardcoded server strings altogether.

if (!isset($_POST) || empty($_POST)){
	header('Location: index.php?showlink=1');
	exit;
}

logger(print_r($_POST,true));

//Get POST data and assign new variables.
$name = sanitize(isset($_POST['Name']) ? $_POST['Name'] : '');
$nfc = sanitize(isset($_POST['NFC']) ? $_POST['NFC'] : '');
$did = sanitize(isset($_POST['DID']) ? $_POST['DID'] : '');
$uid = sanitize(isset($_POST['UID']) ? $_POST['UID'] : '');
$switch = sanitize(isset($_POST['switch']) ? $_POST['switch'] : '');
$device_latitude = sanitize(isset($_POST['Latitude']) ? $_POST['Latitude'] : '');
$device_longitude = sanitize(isset($_POST['Longitude']) ? $_POST['Longitude'] : '');
$adminaction = sanitize(isset($_POST['AdminAction']) ? $_POST['AdminAction'] : '');
$number = sanitize(isset($_POST['TelNum']) ? $_POST['TelNum'] : '');
$devicealias = sanitize(isset($_POST['DeviceName']) ? $_POST['DeviceName'] : '');
$hasnfc = (sanitize(isset($_POST['hasNFC']) ? $_POST['hasNFC'] : '') == true ? '1' : '0');
$change = sanitize(isset($_POST['Change']) ? $_POST['Change'] : '');

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
			$sql = 'INSERT INTO log (uid, ip, action, did, number, latitude, longitude, date) VALUES ( "' . "NFC (" . $uid . ')","' . $_SERVER['REMOTE_ADDR'] . '","' . 'Denied' . '","' . $did . '","' . $number . '","' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';

			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}

			//insert some helpful stuff about the device here.
			if ($did_exists == '0'){
				$sql = 'INSERT INTO device (alias, nfc, has_nfc, force_nfc, did, allowed, number, date) ' . 'VALUES ( "' . $devicealias . '","' . '0' . '", "' . $hasnfc . '", "' . '0' . '", "' . $did . '", "' . '1' . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';
			}
			else{
				$sql = 'update device set alias = "' . $devicealias . '", has_nfc = "' . $hasnfc . '", force_nfc = "' . $forcenfc . '", number = "' . $number . '", date = "' . date('Y-m-d H:i:s') . '" where did = "' . $did . '";';
			}
			
			if(! $retval = mysql_query($sql))
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
				if($use_gpio){
					toggle_relay($door_relay);
				}
				else{
					shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit $door_relay write");
					sleep(2);
					shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit $door_relay write");
				}
				echo 'Door toggled';
			}

			$sql = 'INSERT INTO log (uid, ip, action, did, number, latitude, longitude, date) VALUES ( "' . $uid . '","' . $_SERVER['REMOTE_ADDR'] . '","' . 'Granted' . '","' . $did . '","' . $number . '","' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';

			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}
			//maybe update the device too.

			$sql = 'update device set alias="' . $devicealias . '", nfc="' . $nfc_allowed . '", has_nfc="' . $hasnfc . '", number="' . $number . '", date="' . date('Y-m-d H:i:s') . '" where did="' . $did . '"';

			if(! $retval = mysql_query($sql))
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

/************ ADMINISTRATIVE ACTION SENT BY APP ************/

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
				}
			}
			
			logger('uid ' . $old_uid);

			if ($uid_exists == '1')
			{
				logger('uid ' . $uid_exists);

				$sql = 'Update auth set uid="' . $uid . '", allowed="' . $allowed . '" where name="' . $name . '"';
				
				logger($sql);

				if(! $retval = mysql_query($sql))
				{
					die('Could not enter data: ' . mysql_error());
				}

				echo 'UID changed from ' . $old_uid . ' to ' . $uid . ' for ' . $name;
				exit;
			}
			else {
				//this is experimental 5/29/14
				logger('uid ' . $uid_exists);

				//$sql = 'insert into auth (uid, allowed, name) values ('{'$uid'}', '{'$allowed'}', '{'$name'}')';
				$sql = 'INSERT INTO auth (name, uid, allowed, date) ' . 'VALUES ( "' . $name . '","' . $uid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';

				logger($sql);

				if(! $retval = mysql_query($sql))
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

			logger($sql);
			
			$dbres = mysql_query($sql);

			while ($row = mysql_fetch_assoc($dbres))
			{
				//print_r($row);
				if ($row[uid] == $uid){
					$old_name = $row[name];
					$name_exists = '1';
					//echo 'uid exists';
					//the uid already exists; so we need to run an update query instead of insert.

				}
			}

			logger($old_name);
			
			if ($name_exists == '1')
			{
				logger('name ' . $name_exists);
				$sql = 'Update auth set name="' . $name . '", allowed="' . $allowed . '" where uid="' . $uid . '"';
				
				if(! $retval = mysql_query($sql))
				{
					die('Could not enter data: ' . mysql_error());
				}
				echo 'Name changed from ' . $old_name . ' to ' . $name . ' for ' . $uid;
				exit;
			}
			else {
				//this is experimental 5/29/14
				logger('name ' . $name_exists);
				//$sql = 'insert into auth (uid, allowed, name) values ('{'$uid'}', '{'$allowed'}', '{'$name'}')';
				$sql = 'INSERT INTO auth (name, uid, allowed, date) ' . 'VALUES ( "' . $name . '","' . $uid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';
				logger($sql);

				if(! $retval = mysql_query($sql))
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
		$dbres = mysql_query($sql);

		while ($row = mysql_fetch_assoc($dbres))
		{
			if ($row[uid] == $uid){
				$old_name = $row[name];
				$uid_exists = '1';
				//echo 'uid exists';
				//the user already exists; so we need to run an update query instead of insert.
			}
		}
	}

	if (isset($did) && $did !='')
	{
		//we need to run a select to find out if the device id already exists.
		$sql = 'Select * from device where did="' . $did . '"';
		$dbres = mysql_query($sql);


		while ($row = mysql_fetch_assoc($dbres))
		{
			//print_r($row);
			if ($row[did] == $did){
				$did_exists = '1';
				//echo 'did exists';
				//the did already exists; so we need to run an update query instead of insert.
			}
		}
	}

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
			//update .. where uid = $uid
			$sql = 'update auth set allowed="' . $allowed . '", name="' . $name . '", date="' . date('Y-m-d H:i:s') . '" where uid= "' . $uid . '"';

			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}
			//echo $uid . ' already exists. Auth updated.';
			echo "Privileges for " . $name . " (" . $uid . ") " . $adminaction . "ed";
			exit;
		}

		if ($did_exists == '1' && isset($did) && $did !=''){
			//update .. where did = $did
			$sql = 'update device set allowed="' . $allowed . '", nfc="' . $nfc . '", force_nfc="' . $forcenfc . '", date="' . date('Y-m-d H:i:s') . '" where did= "' . $did . '"';

			//file_put_contents("post.log", $sql);
			if(! $retval = mysql_query($sql))
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

			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}
			else{
				if (isset($did) && $did != '')
				{
					echo "Privileges for " . $did . " " . $adminaction . "ed";

					$stringData = $did . ' ' . $adminaction . "ed" . "\n";
					mailer("Device " . $granted, $stringData, $number);
					exit;
				}
				if (isset($name) && $name != '')
				{
					echo "Privileges for " . $name . " (" . $uid . ") " . $adminaction . "ed. " . (isset($number) && $number != '' ? "User has been notified @ " . $number : "");
					exit;
				}
			}
		}
	}
}

/************ ADMINISTRATIVE LOG REQUESTED BY APP ************/

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
		logger($table . ' log requsted by app.');
		$con=mysql_connect($hostname, $username, $password)or die("cannot connect");
		mysql_select_db($db_name)or die("cannot select DB");
		$sql = "select * from " . $table . " order by date desc";
		logger($sql);
		$result = mysql_query($sql);
		$json = array();

		if(mysql_num_rows($result)){
			while($row=mysql_fetch_assoc($result)){
				$json[ $table . '_info'][]=$row;
			}
		}
		mysql_close($con);
		echo json_encode($json);
		logger(print_r($json));
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
if($dummy_admin)
	$admin_users[$dummy_admin] = 'dummy admin';
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

/************ ACTION REQUESTED BY APP ************/

if (isset($switch) && $switch != ''){
	//we'll put this here since the geofence doesn't apply to NFC or the admin sections.
	//also prevents user trickery by logging in inside the fence then leaving the app open while they cross the boundry.
	if($geofence_enabled == 'true')
	{
		$distance_away = distance($garage_latitude, $garage_longitude, $device_latitude, $device_longitude, $geofence_unit_of_measurement);
		if($device_latitude == '' || $device_longitude == '' || $device_latitude == '0.0' || $device_longitude == '0.0'){
			$switch = $switch . ' Denied (Geofence Empty)';
			$sql = 'INSERT INTO log (name, ip, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' . $_SERVER['REMOTE_ADDR'] . '","' . $uid . '","' . $did . '", "' . $switch . '", "' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';
			
			if(!$retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}
			echo 'Geofence Enabled: GPS Empty.';
			exit;
		}

		if($distance_away >= $geofence_maximum_allowed_distance)
		{
			$switch = $switch . ' Denied (Geofence ' . $distance_away . ' ' . $geofence_unit_of_measurement . ')';
			$sql = 'INSERT INTO log (name, ip, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' . $_SERVER['REMOTE_ADDR'] . '","' . $uid . '", "' . $did . '", "' . $switch . '", "' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';

			if(!$retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}

			echo 'Geofence Enabled: Out of bounds.' . (($geofence_return_result == 'true') ? ' (' . $distance_away . ' ' . $geofence_unit_of_measurement . ')' : '');
			exit;
			//this will effectively disable the buttons in the app. The only thing available is administration of users and devices.
		}
	}

	if ($switch == "Light"){
		if($use_gpio){
			toggle_relay($light_relay);
		}
		else{
			shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit $light_relay write");
			sleep(2);
			shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit $light_relay write");
		}
		echo 'Light toggled';
	}

	if ($switch == "Door"){
		if($use_gpio){
			toggle_relay($door_relay);
		}
		else{
			shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit $door_relay write");
			sleep(2);
			shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit $door_relay write");
		}
		echo 'Door toggled';
	}

	if ($switch == "Lock"){
		if($use_gpio){
			toggle_relay($lock_relay);
		}
		else{
			shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit $lock_relay write");
			sleep(2);
			shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit $lock_relay write");
		}
		echo 'Lock toggled';
	}

	$sql = 'INSERT INTO log (name, ip, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' . $_SERVER['REMOTE_ADDR'] . '","' . $uid . '", "' . $did . '", "' . $switch . '", "' . $device_latitude . '","' . $device_longitude . '","'. date('Y-m-d H:i:s') . '" )';

	if(! $retval = mysql_query($sql))
	{
		die('Could not enter data: ' . mysql_error());
	}

	$stringData = $switch . " toggled\n";
	$txt = $users[$uid] . " toggled " . $switch . ' @ ' . $stringData;
	mailer($stringData, $txt);
	exit;
}
else{
	if($log_attempts == 'true' && $max_attempts > 0){
		$sql = "SELECT COUNT(*) AS `attempts` FROM `log` WHERE `ip` = '{$_SERVER[REMOTE_ADDR]}' AND `action` = 'Denied' AND `date` > DATE_SUB(NOW(),INTERVAL '{$attempt_interval}' MINUTE)";
		$result = mysql_query($sql);
		$attempts = mysql_result($result, 0);
		if($attempts >= $max_attempts){
			echo 'Maximum login attempts reached';
			if($block_after_max_attempts == 'true')
			{
				if (array_key_exists($did, $devices))
				{
					//maybe update device here.
					$sql = 'update device set allowed="0", alias="' . $devicealias . '", has_nfc="' . $hasnfc . '", number="' . $number . '", date="' . date('Y-m-d H:i:s') . '" where did="' . $did . '"';

					if(! $retval = mysql_query($sql))
					{
						die('Could not enter data: ' . mysql_error());
					}
				}
				else {
					//insert some helpful stuff about the device here.
					$sql = 'INSERT INTO device (alias, allowed, has_nfc, did, number, date) ' . 'VALUES ( "' . $devicealias . '","' . '0' . '", "' . $hasnfc . '", "' . $did . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';

					if(! $retval = mysql_query($sql))
					{
						die('Could not enter data: ' . mysql_error());
					}
				}
			mailer("Blocked Device " . $devicealias . '(' . $did . ')', 'Maximum login attempts (' . $max_attempts . ') has been reached by ' . $devicealias . '(' . $did . ')');
			}
			exit;
		}
	}
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
		echo $SUPER_SECRET_ADMIN_RESULT . ',' . $geofence_enabled;
	}
	else if (array_key_exists($uid, $allowed_users)){
		$granted = 'Granted';
		echo $SUPER_SECRET_USER_RESULT . ',' . $geofence_enabled;
	}
	else{
		$granted = 'Denied';
		echo 'Access denied';
	}

	$sql = 'INSERT INTO log (name, ip, uid, did, number, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' . $_SERVER['REMOTE_ADDR'] . '","' . $uid . '","' . $did . '","' . $number . '","' . $granted . '","' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';

	if(! $retval = mysql_query($sql))
	{
		die('Could not enter data: ' . mysql_error());
	}

	if (array_key_exists($did, $devices))
	{
		//maybe update device here.
		$sql = 'update device set allowed="1", alias="' . $devicealias . '", has_nfc="' . $hasnfc . '", number="' . $number . '", date="' . date('Y-m-d H:i:s') . '" where did="' . $did . '"';

		if(! $retval = mysql_query($sql))
		{
			die('Could not enter data: ' . mysql_error());
		}
	}
	else {
		//insert some helpful stuff about the device here.
		$sql = 'INSERT INTO device (alias, allowed, has_nfc, did, number, date) ' . 'VALUES ( "' . $devicealias . '","' . '1' . '", "' . $hasnfc . '", "' . $did . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';

		if(! $retval = mysql_query($sql))
		{
			die('Could not enter data: ' . mysql_error());
		}
	}

	$stringData = $users[$uid] . ' from ' . $did . "\n";
	mailer("User " . $granted, $stringData);
}

?>
