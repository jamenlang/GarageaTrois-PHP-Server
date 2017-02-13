<?php
/************ I don't believe anything here needs to be modified. ************/
//require config and function files

require('GarageaTrois-Common.php');

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
$cname = sanitize(isset($_POST['CName']) ? $_POST['CName'] : '');
$cnfc = sanitize(isset($_POST['CNFC']) ? $_POST['CNFC'] : '');
$cdid = sanitize(isset($_POST['CDID']) ? $_POST['CDID'] : '');
$cuid = sanitize(isset($_POST['CUID']) ? $_POST['CUID'] : '');
$req_state = sanitize(isset($_POST['req_state']) ? $_POST['req_state'] : '');
$req_percent = sanitize(isset($_POST['req_percent']) ? $_POST['req_percent'] : '');
$switch = sanitize(isset($_POST['switch']) ? $_POST['switch'] : '');
$motion_thread = (isset($_POST['motion_thread']) ? $_POST['motion_thread'] : '');
$motion_action = sanitize(isset($_POST['motion_action']) ? $_POST['motion_action'] : '');
$device_latitude = sanitize(isset($_POST['Latitude']) ? $_POST['Latitude'] : '');
$device_longitude = sanitize(isset($_POST['Longitude']) ? $_POST['Longitude'] : '');
$adminaction = sanitize(isset($_POST['AdminAction']) ? $_POST['AdminAction'] : '');
$number = sanitize(isset($_POST['TelNum']) ? $_POST['TelNum'] : '');
$devicealias = sanitize(isset($_POST['DeviceName']) ? $_POST['DeviceName'] : '');
$hasnfc = (sanitize(isset($_POST['hasNFC']) ? $_POST['hasNFC'] : '') == true ? '1' : '0');
$change = sanitize(isset($_POST['Change']) ? $_POST['Change'] : '');

//logger(print_r($door_relay));

foreach($switch_array as $switch_id => $switch_parameters){
	if(stristr($switch_parameters['app_will_request'], 'door')){
		$door_relay = $switch_id;
	}
}

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

			logger($sql);

			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}

			//insert some helpful stuff about the device here.
			if ($did_exists == '0'){
				$sql = 'INSERT INTO device (alias, nfc, has_nfc, force_nfc, did, allowed, number, date) ' . 'VALUES ( "' . $devicealias . '","' . '0' . '", "' . $hasnfc . '", "' . '0' . '", "' . $did . '", "' . '1' . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';
				logger($sql);
			}
			else{
				$sql = 'update device set alias = "' . $devicealias . '", has_nfc = "' . $hasnfc . '", force_nfc = "' . $forcenfc . '", number = "' . $number . '", date = "' . date('Y-m-d H:i:s') . '" where did = "' . $did . '";';
				logger($sql);
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
			logger($sql);

			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}
			//maybe update the device too.

			$sql = 'update device set alias="' . $devicealias . '", nfc="' . $nfc_allowed . '", has_nfc="' . $hasnfc . '", number="' . $number . '", date="' . date('Y-m-d H:i:s') . '" where did="' . $did . '"';
			logger($sql);

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

if (!isset($uid))
{
	echo "No user";
	exit;
}

if (!isset($did))
{
	echo "No device";
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
	//set an array for all users
	$all_users[$row{'uid'}] = $row{'name'};
}
if($super_admin){
	$admin_users[$super_admin] = 'Super Admin';
	$all_users[$super_admin] = 'Super Admin';
	$allowed_users[$super_admin] = 'Super Admin';
}
if($geofence_autologin_enabled && $geofence_enabled && $uid == 'gps0'){
	//set gps0 to the correct admin/user assignment from the config file.
	if($geofence_autologin_user_type == 'admin'){
		$impromtu_title = 'GPS Admin';
		$admin_users['gps0'] = 'GPS Admin';
	}
	if($geofence_autologin_user_type == 'user'){
		$impromtu_title = 'GPS User';
		$all_users['gps0'] = 'GPS User';
	}
	$allowed_users['gps0'] = $impromtu_title;
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
	if ($row[did] == $did){
		$did_exists = '1';
		$nfc_allowed = $row['nfc'];
		$did_allowed = $row['allowed'];
		//echo 'did exists';
	}
	$devices[$row{'did'}] = $row{'did'};
}

if(($did == $echo_did && $echo_did != '') && ($uid == $echo_uid && $echo_uid != '')){
	$did_exists = '1';
	$did_allowed = '1';
	$allowed_users[$uid] = $echo_name;
	$allowed_devices[$did] = $did;
	$devices[$did] = $did;
	$all_users[$uid] = $echo_name;
}

/************ ADMINISTRATIVE ACTION SENT BY APP ************/

if (isset($adminaction) && $adminaction !='' && isset($allowed_users[$uid]) && $did_exists != '0' && $did_allowed != '0')
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
			$sql = 'Select * from auth where name="' . $cname . '"';
			$dbres = mysql_query($sql);

			while ($row = mysql_fetch_assoc($dbres))
			{
				//print_r($row);
				if ($row[name] == $cname){
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

				$sql = 'Update auth set uid="' . $cuid . '", allowed="' . $allowed . '"';
				if ($uid == $super_admin){
					$sql .= ', admin="' . $allowed . '"';
					$additional_info = 'Admin ';
				}

				$sql .= ' where name="' . $cname . '"';
				logger($sql);

				if(! $retval = mysql_query($sql))
				{
					die('Could not enter data: ' . mysql_error());
				}

				echo $additional_info . 'UID changed from ' . $old_uid . ' to ' . $cuid . ' for ' . $cname;
				exit;
			}
			else {
				//this is experimental 5/29/14
				logger('uid ' . $uid_exists);

				//$sql = 'insert into auth (uid, allowed, name) values ('{'$uid'}', '{'$allowed'}', '{'$name'}')';
				$sql = 'INSERT INTO auth (name, ' . (($uid == $super_admin) ? 'admin, ' : '') . 'uid, allowed, date) ' . 'VALUES ( "' . $cname . '"' . (($uid == $super_admin) ? ',"1"' : '') . ',"' . $cuid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';

				logger($sql);

				if ($uid == $super_admin){
					$additional_info = ' Admin ';
				}

				if(! $retval = mysql_query($sql))
				{
					die('Could not enter data: ' . mysql_error());
				}

				//echo 'User: ' . $name . ' UID: ' . $uid . ' ' . $allowed;
				echo 'New' . $additional_info . 'User: ' . $cname . ' UID: ' . $cuid . ' ' . (($allowed == '1') ? 'allowed' : 'disallowed');
				exit;
			}
		}
		if ($change == 'change_name'){
		//update the name where name = $uid

			$sql = 'Select * from auth where uid="' . $cuid . '"';

			logger($sql);

			$dbres = mysql_query($sql);

			while ($row = mysql_fetch_assoc($dbres))
			{
				//print_r($row);
				if ($row[uid] == $cuid){
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
				$sql = 'Update auth set name="' . $cname . '", allowed="' . $allowed . '"';

				if ($uid == $super_admin){
					$sql .= ', admin="' . $allowed . '"';
					$additional_info = 'Admin ';
				}
				$sql .= 'where uid="' . $cuid . '"';

				if(! $retval = mysql_query($sql))
				{
					die('Could not enter data: ' . mysql_error());
				}
				echo $additional_info . 'Name changed from ' . $old_name . ' to ' . $cname . ' for UID: ' . $cuid;
				exit;
			}
			else {
				//this is experimental 5/29/14
				logger('name ' . $name_exists);
				//$sql = 'insert into auth (uid, allowed, name) values ('{'$uid'}', '{'$allowed'}', '{'$name'}')';
				$sql = 'INSERT INTO auth (name, ' . (($uid == $super_admin) ? 'admin, ' : '') . 'uid, allowed, date) ' . 'VALUES ( "' . $cname . '"' . (($uid == $super_admin) ? ',"1"' : '') . ',"' . $cuid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';
				logger($sql);

				if ($uid == $super_admin){

					$additional_info = ' Admin ';
				}

				if(! $retval = mysql_query($sql))
				{
					die('Could not enter data: ' . mysql_error());
				}

				echo 'New' . $additional_info . 'User: ' . $cname . ' UID: ' . $cuid . ' ' . (($allowed == '1') ? 'allowed' : 'disallowed');
				exit;
			}
		}
	}

	if (isset($cuid) && $cuid !='')
	{
		//we need to run a select to find out if the user id already exists.
		$sql = 'Select * from auth where uid="' . $cuid . '"';
		$dbres = mysql_query($sql);

		while ($row = mysql_fetch_assoc($dbres))
		{
			if ($row[uid] == $cuid){
				$old_name = $row[name];
				$uid_exists = '1';
				//echo 'uid exists';
				//the user already exists; so we need to run an update query instead of insert.
			}
		}
	}

	if (isset($cdid) && $cdid !='')
	{
		//we need to run a select to find out if the device id already exists.
		$sql = 'Select * from device where did="' . $cdid . '"';
		$dbres = mysql_query($sql);

		while ($row = mysql_fetch_assoc($dbres))
		{
			//print_r($row);
			if ($row[did] == $cdid){
				$did_exists = '1';
				//echo 'did exists';
				//the did already exists; so we need to run an update query instead of insert.
			}
		}
	}

	if($adminaction == "Grant" || $adminaction == "Revok")
	{
		if (isset($cnfc) && $cnfc != ''){
			if ($cnfc == 'nonexclusive'){
				//set nfc = allowed (unless we're revoking privileges)
				$cnfc = (($adminaction == "Revok") ? '0' : '1');
				$forcenfc = '0';
			}
			if ($cnfc == 'exclusive'){
				//set nfc = allowed because it has to be for exclusive access... if the privileges are revoked then nfc is disabled entirely.
				$cnfc = (($adminaction == "Revok") ? '0' : '1');
				$forcenfc = (($adminaction == "Revok") ? '0' : '1');
			}
		}

		if ($uid_exists == '1' && isset($cuid) && $cuid !=''){
			//update .. where uid = $uid
			$sql = 'update auth set allowed="' . $allowed . '", name="' . $cname . '", date="' . date('Y-m-d H:i:s') . '"';

			if ($uid == $super_admin){
				$sql .= ', admin="' . $allowed . '"';
				$additional_info = 'Admin ';
			}
			$sql .= ' where uid= "' . $cuid . '"';

			logger($sql);

			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}
			//echo $uid . ' already exists. Auth updated.';
			echo $additional_info . "Privileges for " . $cname . " (" . $cuid . ") " . $adminaction . "ed";
			exit;
		}

		if ($did_exists == '1' && isset($cdid) && $cdid !=''){
			//update .. where did = $did
			$sql = 'update device set allowed="' . $allowed . '", nfc="' . $cnfc . '", force_nfc="' . $forcenfc . '", date="' . date('Y-m-d H:i:s') . '" where did= "' . $cdid . '"';

			logger($sql);

			//file_put_contents("post.log", $sql);
			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}
			echo "Privileges for " . $cdid . " " . $adminaction . "ed";

			exit;
		}

		if ($did_exists != '1' || $uid_exists != '1'){
			//this is a new user. insert.
			if ($cuid != ''){
				$sql = 'INSERT INTO auth (name, ' . (($uid == $super_admin) ? 'admin, ' : '') . 'uid, allowed, date) ' . 'VALUES ( "' . $cname . '"' . (($uid == $super_admin) ? ',"1"' : '') . ',"' . $cuid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';
				logger($sql);
				if ($uid == $super_admin){
					$additional_info = 'Admin ';
				}
			}
			if ($cdid != ''){
				$sql = 'INSERT INTO device (nfc, has_nfc, force_nfc, did, allowed, number, date) ' . 'VALUES ( "' . $cnfc . '","' . $hasnfc . '", "' . $forcenfc . '", "' . $cdid . '", "' . $allowed . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';
				logger($sql);
			}

			if(! $retval = mysql_query($sql))
			{
				die('Could not enter data: ' . mysql_error());
			}
			else{
				if (isset($cdid) && $cdid != '')
				{
					echo "Privileges for " . $cdid . " " . $adminaction . "ed";

					$stringData = $cdid . ' ' . $adminaction . "ed" . "\n";
					mailer("Device " . $granted, $stringData, $number);
					exit;
				}
				if (isset($cname) && $cname != '')
				{
					echo $additional_info . "Privileges for " . $cname . " (" . $cuid . ") " . $adminaction . "ed. " . (isset($number) && $number != '' ? "User has been notified @ " . $number : "");
					exit;
				}
			}
		}
	}
}

/************ ADMINISTRATIVE LOG REQUESTED BY APP ************/

if (isset($_POST['Admin']) && $_POST['Admin'] != '' && isset($allowed_users[$uid]) && $did_exists != '0' && $did_allowed != '0')
{
	if ($_POST['Admin'] == 'viewlog')
		$table = 'log';
	if ($_POST['Admin'] == 'viewdevices')
		$table = 'device';
	if ($_POST['Admin'] == 'viewusers'){
		$table = 'auth';
		$new_columns = Array('uid' => '0000','allowed' => '1','date' => 'never','name' => 'new user','admin' => '0');
	}
	if (isset($table) && $table != '')
	{
		logger($table . ' log requsted by app.');
		$con=mysql_connect($hostname, $username, $password)or die("cannot connect");
		mysql_select_db($db_name)or die("cannot select DB");
		$sql = "select * from " . $table . " order by date desc";
		logger($sql);
		$result = mysql_query($sql);
		$json = array();

		if($new_columns)
			$json[ $table . '_info'][]= $new_columns;

		if(mysql_num_rows($result) > 0){
			while($row=mysql_fetch_assoc($result)){
				$x = $x + 1;
				$json[ $table . '_info'][]=$row;
			}
		}
		mysql_close($con);
		echo json_encode($json);
		//logger(print_r($json));
		exit;
	}
}

/************ ACTION REQUESTED BY APP ************/

if (isset($switch) && $switch != '' && isset($allowed_users[$uid]) && $did_exists != '0' && $did_allowed != '0'){
	//we'll put this here since the geofence doesn't apply to NFC or the admin sections.
	//also prevents user trickery by logging in inside the fence then leaving the app open while they cross the boundry.
	if($switch == 'motion'){
		if($use_motion == true){
			if($motion_action == 'retrieve_image'){
				if($snapshots_only == true){
					$boundary="\n--";

					if(fopen("http://$motion_ip:$motion_view_port/$motion_thread","r")){
						while (substr_count($r,"Content-Length") != 2) $r.=fread($f,512);
						$start = strpos($r,'\xff');
						$end = strpos($r,$boundary,$start)-1;
						$frame = substr("$r",$start,$end - $start);
						header("Content-type: image/jpeg");
						echo $frame;
					}
					fclose($f);
				}
				 else{
                                        set_time_limit(0);
                                        $fp = fsockopen ($motion_ip, $motion_view_port, $errno, $errstr, 30);

                                        if (!$fp) {
                                                echo "$errstr ($errno)<br>\n";
                                        }
                                        else{
                                                if($motion_http_username != ''){
                                                        $auth=base64_encode($motion_http_username.":".$motion_http_password);
                                                        $header="GET / HTTP/1.0\r\n\r\n";
                                                        $header.="Accept: text/html\r\n";
                                                        $header.="Authorization: Basic $auth\r\n\r\n";
                                                        fputs ($fp, $header);
                                                }
                                                else {
                                                        fputs ($fp, "GET / HTTP/1.0\r\n\r\n");
                                                }
                                                while ($str = trim(fgets($fp, 4096)))
                                                        header($str);
                                                fpassthru($fp);
                                                fclose($fp);
                                        }
                                }
			}
			if($motion_action == 'restart'){
				$url = "http://" . (($motion_http_username != '') ? $motion_http_username . ':' . $motion_http_password . '@' : '') . "$motion_ip:$motion_control_port/$motion_thread/action/restart";
				$output = file_get_contents($url);
				//logger('restarting motion daemon');
				//logger($url);
				//logger($motion_thread);
				//logger($output);
				echo $output;
			}
			if($motion_action == 'snapshot'){
				file_get_contents("http://" . (($motion_http_username != '') ? $motion_http_username . ':' . $motion_http_password . '@' : '') . "$motion_ip:$motion_control_port/$motion_thread/action/snapshot");
				//logger('snapshot');
				echo 'snapshot thread ' . $motion_thread;
			}
		}
		exit;
		/*
		if($motion_action = 'snapshot'){
			file_get_contents("http://$motion_ip:$motion_control_port/$motion_thread/action/snapshot");
			exit;
		}
		if($motion_action = 'restart'){
			exec('sudo service motion restart');
			exit;
		}
		*/
	}
	ob_end_clean();
	header("Connection: close");
	ignore_user_abort(); // optional
	ob_start();

	if($geofence_super_admin_override == false || $uid != $super_admin){
		if($geofence_enabled == true)
		{
			$distance_away = distance($garage_latitude, $garage_longitude, $device_latitude, $device_longitude, $geofence_unit_of_measurement);
			logger($garage_latitude.'_'. $garage_longitude.'_'. $device_latitude.'_'. $device_longitude.'_'. $geofence_unit_of_measurement);
			logger('dist' . $distance_away);
			if($device_latitude == '' || $device_longitude == '' || $device_latitude == '0.0' || $device_longitude == '0.0'){
				$switch = $switch . ' Denied (Geofence Empty)';
				$sql = 'INSERT INTO log (name, ip, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $all_users[$uid] . '","' . $_SERVER['REMOTE_ADDR'] . '","' . $uid . '","' . $did . '", "' . $switch . '", "' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';
				logger($sql);

				if(!$retval = mysql_query($sql))
				{
					die('Could not enter data: ' . mysql_error());
				}
				echo 'Geofence Enabled: GPS Empty.';
				logger('Geofence Enabled: GPS Empty.');

				exit;
			}

			if($distance_away >= $geofence_maximum_allowed_distance)
			{
				$switch = $switch . ' Denied (Geofence ' . $distance_away . ' ' . $geofence_unit_of_measurement . ')';
				$sql = 'INSERT INTO log (name, ip, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $all_users[$uid] . '","' . $_SERVER['REMOTE_ADDR'] . '","' . $uid . '", "' . $did . '", "' . $switch . '", "' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';
				logger($sql);

				if(!$retval = mysql_query($sql))
				{
					die('Could not enter data: ' . mysql_error());
				}

				echo 'Geofence Enabled: Out of bounds.' . (($geofence_return_result == true) ? ' (' . $distance_away . ' ' . $geofence_unit_of_measurement . ')' : '');
				logger('Geofence Enabled: Out of bounds.' . (($geofence_return_result == true) ? ' (' . $distance_away . ' ' . $geofence_unit_of_measurement . ')' : ''));
				exit;
				//this will effectively disable the buttons in the app. The only thing available is administration of users and devices.
			}
		}
	}

	if($switch == 'retrieve_switches'){
		foreach($switch_array as $switch_id => $switch_params){
			if(isset($switch_params['gpio_status_pin'])){
				$switch_array[$switch_id]['current_status'] = (bool) get_pin_status($switch_params['gpio_status_pin']);
				$switch_array[$switch_id]['reports_current_status'] = (bool) true;
				logger("pin " . $switch_params['gpio_status_pin'] . get_pin_status($switch_params['gpio_status_pin']));
			}
			else{
				$switch_array[$switch_id]['reports_current_status'] = (bool) false;
			}
			$json['switch_info'][]=$switch_array[$switch_id];
		}
		echo json_encode($json);
		exit;
	}

	$count = 0;

	foreach($switch_array as $switch_id => $switch_parameters){
		if($switch == $switch_parameters['app_will_request']){
			logger($switch . ' found.');
			logger($count);
			logger($switch_parameters['trigger']);
			$return_count = $count;
			$return_name = $switch_parameters['name'];
			switch ($switch_parameters['trigger']){
				case 'hold' :
					if($switch_parameters['trigger'] == 'hold' && $switch_parameters['state'] != 'off'){
						$toggled_on = true;
						toggle_relay($switch_id,1);
					}
					if($switch_parameters['trigger'] == 'hold' && $switch_parameters['state'] != 'on'){
						$toggled_off = true;
						toggle_relay($switch_id,0);
					}
					if($toggled_on != true && $toggled_off == true){
						$return_state = 0;
						$return_result = ' holding off.';
					}
					if($toggled_on == true && $toggled_off != true){
						$return_state = 1;
						$return_result = ' holding on.';
					}
					break;
				case 'toggle' :
					//blind toggle with optional timeout.
					toggle_relay($switch_id,1);
					if($switch_parameters['timeout'] != '')
						sleep($switch_parameters['timeout']);
					toggle_relay($switch_id,0);
					$return_state = 2;
					$return_result = ' toggled.';
					break;

				case 'gpio_callback' :
					if($switch_parameters['gpio_status_pin'] != '' && $use_gpio != false){
						//get current state.
						$start_pin_status = get_pin_status($switch_parameters['gpio_status_pin']);
						$end_pin_status = $start_pin_status;
						//the app could possibly send an 'open/close/on/off request'
						if($switch_parameters['support_requested_state']){
							if((in_array($req_state,$con_array) && $start_pin_status == '0') || (in_array($req_state,$pro_array) && $start_pin_status == '1')){
								logger('requested state status is the same as the current status: ' . $start_pin_status);
								//bws will send 3 consecutive commands unless it gets a response right away.
								if($uid == $echo_uid)
									echo 'ok';
								exit;
							}
						}
						//dimming
						if($uid == $echo_uid){
							echo 'ok';
							//ob to reply back to ha bridge so it doesn't issue any more commands.
							$size = ob_get_length();
							header("Content-Length: $size");
							ob_end_flush(); // Strange behaviour, will not work
							flush();// Unless both are called !
							// Do processing here
						}
						if(isset($req_percent) && $req_percent == '100'){
							unset($req_percent);
							$req_state = (($start_pin_status) ? 'off' : 'on');
						}
						if(isset($req_percent) && $req_percent == '0'){
							unset($req_percent);
							$req_state = (($start_pin_status) ? 'on' : 'off');
						}
						if(isset($req_percent) && $req_percent != ''){
							//check if door is open
							if($start_pin_status == 1){
								$req_percent = 100 - $req_percent;
								logger('inverting percentage');
							}
							logger($req_percent . ' percent');

							if($switch_parameters['timeout'] != ''){
								logger('timeout of ' . $switch_parameters['timeout']);
								logger('initial toggle of relay ' . $switch_id);
								toggle_relay($switch_id);

								$dimseconds = $switch_parameters['timeout'] * $req_percent / 100;
								//if($dimseconds < 1)
								//	exit;
								logger('seconds to reach before second toggle ' . $dimseconds);
								usleep(1000000 * $dimseconds);
								toggle_relay($switch_id);
								logger('secondary toggle of relay ' . $switch_id);

                                                                $return_state = $start_pin_status;
								exit;
                                                        }
							else{
								logger('switch timeout is not specified, dim cannot be performed.');
							}
						}

						toggle_relay($switch_id);
						if($uid == $echo_uid){
							echo 'ok';
							exit;
						}
						$time_start = microtime(true);
						//logger('start mt ' . microtime());
						while($end_pin_status == $start_pin_status){
							logger('start pin status: ' . $start_pin_status);
							logger('end pin status: ' . $end_pin_status);
							$end_pin_status = get_pin_status($switch_parameters['gpio_status_pin']);
							if($switch_parameters['timeout'] != ''){
								if($loop_counter >= $switch_parameters['timeout']){
									$return_result = ' failure - Timeout reached.';
									logger($return_result);
									$return_state = $start_pin_status;
									break;
								}
								logger($loop_counter . ' of ' . (($switch_parameters['timeout']) ? $switch_parameters['timeout'] : 'infinity') . ' seconds reached');
								$loop_counter++;
							}
							//usleep(1000000);
							//echo '.';
							sleep(1);
						}
						$time_end = microtime(true);
						$time = $time_end - $time_start;

						logger("loop execution took $time seconds");

						if(!$return_result){
							$return_state = $end_pin_status;
							$return_result = ' toggled.';
						}
					}
					else{
						logger('gpio callback was used but it is not configured.');
					}
					break;

				case 'timeout' :
					//let the app handle the timeout from here.
					toggle_relay($switch_id);
					$return_result = ' toggled.';
					$return_state = 2;
					break;

				default :
					break;
			}
		}
		$count++;
	}

	$return_final = $return_count . ',' . $return_state . ',' . $return_name . $return_result;
	logger($return_final);
	echo $return_final;
	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush(); // Strange behaviour, will not work
	flush();// Unless both are called !
	// Do processing here 

	$sql = 'INSERT INTO log (name, ip, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $all_users[$uid] . '","' . $_SERVER['REMOTE_ADDR'] . '","' . $uid . '", "' . $did . '", "' . $switch . '", "' . $device_latitude . '","' . $device_longitude . '","'. date('Y-m-d H:i:s') . '" )';
	logger($sql);

	if(! $retval = mysql_query($sql))
	{
		logger('died');
		die('Could not enter data: ' . mysql_error());
	}

	$stringData = $switch . " toggled\n";
	logger($stringData);

	$txt = $all_users[$uid] . " toggled " . $switch . ' @ ' . $stringData;
	logger($txt);

	mailer($stringData, $txt);
	exit;
}
else{
	ob_end_clean();
	header("Connection: close");
	ignore_user_abort(); // optional
	ob_start();

	if($log_attempts == true && $max_attempts > 0){
		$sql = "SELECT COUNT(*) AS `attempts` FROM `log` WHERE `ip` = '{$_SERVER[REMOTE_ADDR]}' AND `action` = 'Denied' AND `date` > DATE_SUB(NOW(),INTERVAL '{$attempt_interval}' MINUTE)";
		logger($sql);
		$result = mysql_query($sql);
		$attempts = mysql_result($result, 0);
		logger('attempt ' . $attempts . ' of ' . $max_attempts . (($block_after_max_attempts == true) ? ' until blocking DID' . $did : ''));

		if($attempts > $max_attempts){
			echo 'Maximum login attempts reached';
			$size = ob_get_length();
			header("Content-Length: $size");
			ob_end_flush(); // Strange behaviour, will not work
			flush();// Unless both are called !
			// Do processing here

		}
		if($attempts == $max_attempts){
			if($block_after_max_attempts == true)
			{
				logger('$block_after_max_attempts = ' . $block_after_max_attempts);
				if (array_key_exists($did, $devices))
				{
					logger('$block_after_max_attempts = ' . $block_after_max_attempts . ', array_key_exists($did, $devices)');
					//maybe update device here.
					$sql = 'update device set allowed="0", alias="' . $devicealias . '", has_nfc="' . $hasnfc . '", number="' . $number . '", date="' . date('Y-m-d H:i:s') . '" where did="' . $did . '"';
					logger($sql);

					if(! $retval = mysql_query($sql))
					{
						die('Could not enter data: ' . mysql_error());
					}
				}
				else {
					logger('$block_after_max_attempts = ' . $block_after_max_attempts . ', !array_key_exists($did, $devices)');
					//insert some helpful stuff about the device here.
					$sql = 'INSERT INTO device (alias, allowed, has_nfc, did, number, date) ' . 'VALUES ( "' . $devicealias . '","' . '0' . '", "' . $hasnfc . '", "' . $did . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';
					logger($sql);

					if(! $retval = mysql_query($sql))
					{
						die('Could not enter data: ' . mysql_error());
					}
				}
				mailer("Blocked Device " . $devicealias . '(' . $did . ')', 'Maximum login attempts (' . $max_attempts . ') has been reached by ' . $devicealias . '(' . $did . ')');
			}
		}
	}
	if (!isset($uid))
	{
		echo 'Log in';
	}
	else if (!isset($did))
	{
		echo 'Log in';
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
		echo md5($SUPER_SECRET_ADMIN_RESULT) . ',' . ($geofence_enabled ? 'true' : 'false');
	}
	else if (array_key_exists($uid, $allowed_users)){
		$granted = 'Granted';
		echo md5($SUPER_SECRET_USER_RESULT) . ',' . ($geofence_enabled ? 'true' : 'false');
	}
	else{
		if($uid == "gps0" && !$geofence_autologin_enabled){
			echo 'Log in by entering your PIN';
			exit;
		}
		else{
			$granted = 'Denied';
			echo 'Access denied';
		}
	}

	$size = ob_get_length();
	header("Content-Length: $size");
	ob_end_flush(); // Strange behaviour, will not work
	flush();// Unless both are called !
	// Do processing here 

	$sql = 'INSERT INTO log (name, ip, uid, did, number, action, latitude, longitude, date) ' . 'VALUES ( "' . $all_users[$uid] . '","' . $_SERVER['REMOTE_ADDR'] . '","' . $uid . '","' . $did . '","' . $number . '","' . $granted . '","' . $device_latitude . '","' . $device_longitude . '","' . date('Y-m-d H:i:s') . '" )';
	logger($sql);

	if(! $retval = mysql_query($sql))
	{
		die('Could not enter data: ' . mysql_error());
	}

	if (array_key_exists($did, $devices))
	{
		//maybe update device here.
		$sql = 'update device set allowed="1", alias="' . $devicealias . '", has_nfc="' . $hasnfc . '", number="' . $number . '", date="' . date('Y-m-d H:i:s') . '" where did="' . $did . '"';
		logger($sql);

		if(! $retval = mysql_query($sql))
		{
			die('Could not enter data: ' . mysql_error());
		}
	}
	else {
		//insert some helpful stuff about the device here.
		$sql = 'INSERT INTO device (alias, allowed, has_nfc, did, number, date) ' . 'VALUES ( "' . $devicealias . '","' . '1' . '", "' . $hasnfc . '", "' . $did . '", "' . $number . '", "' . date('Y-m-d H:i:s') . '" )';
		logger($sql);

		if(! $retval = mysql_query($sql))
		{
			die('Could not enter data: ' . mysql_error());
		}
	}

	$stringData = $all_users[$uid] . ' from ' . $did . "\n";
	mailer("User " . $granted, $stringData);
}

?>
