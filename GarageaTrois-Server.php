<?php

//########### For MYSQL Database (Logging, User authentication and Device authentication) ##########//
//########### Read the README and follow instructions before proceeding ##########//

$hostname="localhost"; //replace with database hostname
$username="USERNAME"; //replace with database username
$password="PASSWORD"; //replace with database password
$db_name="garage"; //replace with database name
$SUPER_SECRET_ADMIN_RESULT="SUPER_SECRET_ADMIN_RESULT"; //replace with whatever is in the res/strings.xml file in the android app.
$SUPER_SECRET_USER_RESULT="SUPER_SECRET_USER_RESULT"; //replace with whatever is in the res/strings.xml file in the android app.

//########### Configuration for notifications ###################//

$admin_mobile="4033029392"; //replace with mobile number for recieving text messages
$admin_email="admin@whereveryouwantthem.com"; //replace with an admin email address
$notification_email="notifications@fromtheserver.com"; //replace with a notification email address


/************ Location Specific ***********/
//This is a list of carriers in the US. Change to your country if necessary. 
$carriers = array (
	0 => 'tomomail.net',
	1 => 'messaging.sprintpcs.com',
	2 => 'vtext.com',
	3 => 'txt.att.net',
	4 => 'vmobl.com',
);

include('phpqrcode/qrlib.php');
// this can be downloaded from http://sourceforge.net/projects/phpqrcode/


//########### I don't believe anything else needs to be modified. ##############//


// outputs image directly into browser, as PNG stream
// the code can be downloaded or this can be disabled, you can also use the google API line below.
// I cannot get QR to launch an intent, I would like to get this working so a QR code can be scanned and give the app the server information to be stored locally on the device and get rid of hardcoded server strings altogether.

file_put_contents("post.log",print_r($_POST,true));
if (!isset($_POST) || empty($_POST)){
	//might as well generate a qr code for the server address since no post data was received
	$link = "my.special.scheme://server=".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	//OR echo "http://chart.apis.google.com/chart?chf=a,s,000000&chs=200x200&chld=%7C2&cht=qr&chl=" . $link;
	define('IMAGE_WIDTH',250);
	define('IMAGE_HEIGHT',250);
	QRcode::png($link);
	exit;
}

if(isset($_POST['initialtest']) && $_POST['initialtest'] == 'true')
{
	echo 'hi';
	exit;
}

//Get POST data and assign new variables.
$name = sanitize($_POST['Name']);
$nfc = sanitize($_POST['NFC']);
$did = sanitize($_POST['DID']);
$uid = sanitize($_POST['UID']);
$switch = sanitize($_POST['switch']);
$latitude = sanitize($_POST['Latitude']);
$longitude = sanitize($_POST['Longitude']);
$adminaction = sanitize($_POST['AdminAction']);
$number = sanitize($_POST['TelNum']);
$devicealias = sanitize($_POST['DeviceName']);
$hasnfc = sanitize($_POST['hasNFC']);
$change = sanitize($_POST['Change']);

$dbhandle = mysql_connect($hostname, $username, $password)
	or die("Unable to connect to MySQL");

$selected = mysql_select_db($db_name,$dbhandle)
	or die("Could not select $db_name");

if (isset($uid) && $uid == 'nfc0' && isset($did) && $did !=''){
	//user is trying to open the door with nfc.
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
		$sql = 'INSERT INTO log (uid, action, did, number, latitude, longitude, date) VALUES ( "' . "NFC (" . $uid . ')","' . 'Denied' . '","' . $did . '","' . $number . '","' . $latitude . '","' . $longitude . '","' . date('Y-m-d H:i:s') . '" )';

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
		mailer2($admin_mobile, "NFC User " . "Denied",$stringData);

		exit;
	}
	//otherwise we can go ahead and open the door.
	else if ($did_exists == '1' && $did_allowed == '1' && $nfc_allowed == '1'){
		if ($switch == "door"){
			//garage openers put a short on each pair for a breif period, that's what we'll do with the relay
			shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit 1 write");
			sleep(2);
			shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit 1 write");
			echo 'Door toggled';
		}

		$sql = 'INSERT INTO log (uid, action, did, number, latitude, longitude, date) VALUES ( "' . $uid . '","' . 'Granted' . '","' . $did . '","' . $number . '","' . $latitude . '","' . $longitude . '","' . date('Y-m-d H:i:s') . '" )';

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
                mailer2($admin_mobile, "NFC User " . "Granted",$stringData);
		exit;
	}
	else {
		//the user has scanned the tag, maybe mutliple times, we've logged it every time and started a device profile. the default profile doesn't have rights so we don't do anything indefinitely.
		echo 'I have no idea what is happening. did exists: ' . $did_exists . ' did allowed: ' . $did_allowed;
		exit;
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

			file_put_contents("post.log", 'uid ' . $old_uid);
			if ($uid_exists == '1')
			{
				file_put_contents("post.log", 'uid ' . $uid_exists);

				$sql = 'Update auth set uid="' . $uid . '", allowed="' . $allowed . '" where name="' . $name . '"';
				file_put_contents("post.log", $sql);

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
				file_put_contents("post.log", 'uid ' . $uid_exists);
				//$sql = 'insert into auth (uid, allowed, name) values ('{'$uid'}', '{'$allowed'}', '{'$name'}')';
				$sql = 'INSERT INTO auth (name, uid, allowed, date) ' . 'VALUES ( "' . $name . '","' . $uid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';

				file_put_contents("post.log", $sql);

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
			file_put_contents("post.log", $sql);

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
			file_put_contents("post.log", $old_name);
			if ($name_exists == '1')
			{
				file_put_contents("post.log", 'name ' . $name_exists);

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
				file_put_contents("post.log", 'name ' . $name_exists);
				//$sql = 'insert into auth (uid, allowed, name) values ('{'$uid'}', '{'$allowed'}', '{'$name'}')';
				$sql = 'INSERT INTO auth (name, uid, allowed, date) ' . 'VALUES ( "' . $name . '","' . $uid . '", "' . $allowed . '", "' . date('Y-m-d H:i:s') . '" )';

				file_put_contents("post.log", $sql);

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
				$forcenfc = (($adminaction == "Revok") ? '1' : '0');
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
				        mailer2("$number", "Device " . $granted,$stringData);
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

if (isset($_POST['Log']) && $_POST['Log'] != '')
{
	if (isset($_POST['Log']) && $_POST['Log'] == 'viewlog')
	{
		$con=mysql_connect($hostname, $username, $password)or die("cannot connect");
		mysql_select_db("$db_name")or die("cannot select DB");
		$sql = "select * from log order by date desc";
		$result = mysql_query($sql);
		$json = array();

		if(mysql_num_rows($result)){
		//      echo mysql_num_rows($result);
	        	while($row=mysql_fetch_assoc($result)){
	                	$json['log_info'][]=$row;
	       		}
		}
		mysql_close($con);
		echo json_encode($json);
		file_put_contents("post.log",print_r($json));
		exit;
	}
}

if (isset($_POST['Admin']) && $_POST['Admin'] != '')
{
	if (isset($_POST['Admin']) && $_POST['Admin'] == 'viewusers')
	{
		$con=mysql_connect($hostname, $username, $password)or die("cannot connect");
		mysql_select_db($db_name)or die("cannot select $db_name");
		$sql = "select * from auth";

		$result = mysql_query($sql);
		$json = array();

		if(mysql_num_rows($result)){
		//      echo mysql_num_rows($result);
	        	while($row=mysql_fetch_assoc($result)){
	                	$json['user_info'][]=$row;
	       		}
		}
		mysql_close($con);
		echo json_encode($json);
		file_put_contents("post.log",print_r($json));
		exit;
	}

	if (isset($_POST['Admin']) && $_POST['Admin'] == 'viewdevices')
	{
		$con=mysql_connect($hostname, $username, $password)or die("cannot connect");
		mysql_select_db($db_name)or die("cannot select $db_name");
		$sql = "select * from device";
		$result = mysql_query($sql);
		$json = array();

		if(mysql_num_rows($result)){
		//      echo mysql_num_rows($result);
	        	while($row=mysql_fetch_assoc($result)){
	                	$json['device_info'][]=$row;
	       		}
		}
		mysql_close($con);
		echo json_encode($json);
		file_put_contents("post.log",print_r($json));
		exit;
	}
}

if ($testing_mode == true)
{
	//testing should be done by now.
	exit;
}

if (!isset($uid))
{
	echo "No user";
	exit;
}


$dbhandle = mysql_connect($hostname, $username, $password)
  or die("Unable to connect to MySQL");

$selected = mysql_select_db($db_name,$dbhandle)
  or die("Could not select $db_name");
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

	if ($switch == "light"){
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit 0 write");
		sleep(2);
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit 0 write");
		echo 'Light toggled';
	}

	if ($switch == "door"){
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit 1 write");
		sleep(2);
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit 1 write");
		echo 'Door toggled';
	}

	if ($switch == "lock"){
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read setbit 2 write");
		sleep(2);
		shell_exec("/usr/local/sbin/portcontrol LPT1DATA read resetbit 2 write");
		echo 'Lock toggled';
	}

	$sql = 'INSERT INTO log (name, uid, did, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' . $uid . '", "' . $did . '", "' . $switch . '", "' . $latitude . '","' . $longitude . '","'. date('Y-m-d H:i:s') . '" )';

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
	mailer($admin_email,$stringData,$txt);
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
	$sql = 'INSERT INTO log (name, uid, did, number, action, latitude, longitude, date) ' . 'VALUES ( "' . $users[$uid] . '","' .  $uid . '","' . $did . '","' . $number . '","' . $granted . '","' $latitude . '","' . $longitude . '","' . date('Y-m-d H:i:s') . '" )';

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
	mailer2($admin_mobile, "User " . $granted,$stringData);
	//fwrite($fh, $stringData);
	//fclose($fh);

}

function mailer($to, $subject, $message){

	$from = $notification_email;
	$headers = "From: $from\r\n" . "X-Mailer: php";
	mail($to, $subject, $message, $headers, '-r ' . $from);
}

function mailer2($to, $subject, $message){

	foreach($carriers as $carrier){
		$to = $to . "@" . $carrier;
		$from = $notification_email;
		$headers = "From: $from\r\n" . "X-Mailer: php";
		mail($to, $subject, $message, $headers, '-r ' . $from);
	}
}
// Sanitize input
function sanitize($in) {
 return addslashes(htmlspecialchars(strip_tags(trim($in))));
}
?>
