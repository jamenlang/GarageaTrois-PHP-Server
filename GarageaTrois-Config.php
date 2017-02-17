<?php
/************ Relay Configuration
Set values of the relay to control per variable ************/
$use_gpio = true; //set to true to use the default gpio on a raspberry pi
$use_wiringpi = true; //set to true to use wiringpi gpio packages
$use_parallel = false; //set to true to use PC parallel port

$relay_1 = 0; //set to relay or WiringPI gpio pin
$relay_2 = 7; //set to relay or WiringPI gpio pin
$relay_3 = 8; //set to relay or WiringPI gpio pin
$relay_4 = 9; //set to relay or WiringPI gpio pin
//continue?

$switch_array = array(
	$relay_1 => array(
		//example for hold trigger
		'name' => 'Light 1',
		'app_will_request' => urlencode('Light1'),
		'trigger' => 'hold', //set trigger {hold,timeout,gpio_callback,toggle}
		'state' => 'off', //set state to hold {on,off}
		'invert' => false //force 0 to be 'on' and 1 to be 'off' for trigger, status and callback pins {true,false}
	),
	$relay_2 => array(
		//example for timeout trigger
		'name' => 'Door',
		'app_will_request' => urlencode('Door1'),
		'trigger' => 'timeout', //set trigger {hold,timeout,gpio_callback,toggle}
		'timeout' => 12, //in seconds, this is the timeout for the timeout		
		'display_progress' => 'during', //display progress in app {during,after}
		'support_requested_state' => true, //supports on/off/open/close commands and compares with gpio callback if set.
		'motion_thread' => '0', //if motion is enabled for this relay/device, replace with thread number
		'invert' => false //force 0 to be 'on' and 1 to be 'off' for trigger, status and callback pins {true,false}
	),
	$relay_3 => array(
		//example for gpio_callback trigger
		'name' => 'Lock',
		'app_will_request' => urlencode('Lock1'),
		'trigger' => 'gpio_callback', //set trigger {hold,timeout,gpio_callback,toggle}
		'timeout' => 10, //optional. this is the timeout for the gpio_callback
		'motion_thread' => '1', //if motion is enabled for this relay/device, replace with thread number
		'support_requested_state' => true, //only allow an action if the requested state does not match the trigger 
		'display_progress' => 'during', //display progress in app {during,after}
		'gpio_status_pin' => 1, //pin to check for status.
		'invert' => false //force 0 to be 'on' and 1 to be 'off' for trigger, status and callback pins {true,false}
	),
	$relay_4 => array(
		//example for toggle trigger
		'name' => 'Light 2',
		'app_will_request' => urlencode('Light2'),
		'trigger' => 'toggle', //set trigger {hold,timeout,gpio_callback,toggle}
		'timeout' => 2, //in seconds, this is the timeout for the toggle
		'invert' => false //set to true to force 0 to be 'on' and 1 to be 'off' for trigger, status and callback pins {true,false}
	)
	//continue?
);

/************ For MYSQL Database (Logging, User authentication and Device authentication)
Read the README and follow instructions before proceeding ************/

$hostname = 'localhost'; //replace with database hostname
$username = 'USERNAME'; //replace with database username
$password = 'PASSWORD'; //replace with database password
$db_name = 'garage'; //replace with database name
$super_admin = '0009';//first login requires a PIN, Users created by super admin will be administrators.
$SUPER_SECRET_ADMIN_RESULT = 'SUPER_SECRET_ADMIN_RESULT'; //make sure the android app setting matches.
$SUPER_SECRET_USER_RESULT = 'SUPER_SECRET_USER_RESULT'; ////make sure the android app setting matches.

/************ Configuration for Interfaces************/

$configured_interface = 'wlan0'; //e.g. eth0, set to interface that server will be reachable on.

/************ Configuration for Logging ************/

$log_to_file = true; //after everything is installed and working you'll want to disable logging. {true,false}
$log = '/var/www/GarageaTrois/logfile.txt'; //change to whatever you'd like

/************ Configuration for IP Logging ************/

$log_attempts = true; // enable or disable logging {true,false}
$max_attempts = '3'; //per $attempt_interval in minutes for blocking crackers
$attempt_interval = '15';
$block_after_max_attempts = false; //set to true to add the device id to disallowed devices. {true,false}

/************ Configuration for NFC
If you want to be able to open the door with an NFC tag, just write a tag to start the NFC activity of this app.
If you don't want to allow NFC or don't have a use for it, you can disable it here.************/

$nfc_enabled = '1'; //set to 1 to enable or 0 to disable NFC.

/************ Configuration for QR
when this page is visited in a browser, a QR code will be generated for you to download the APK based on the link.
An easy way to get the app onto other devices would be to use a QR code, set it and forget it.
this requires qrlib.php which can be downloaded from http://sourceforge.net/projects/phpqrcode/************/

$qr_enabled = '1'; // set to 1 to enable or 0 to disable.
$qr_size = '250'; // default is 250 pixels
$apk_link = 'http://files.myawesomedomain.net/garageatrois.apk';

/************ Configuration for geofence
Geofencing is a pretty popular form of access restriction based on GPS data and distance between two points.
If you have a use for it, by all means try it out.************/

$geofence_super_admin_override = false; //set to true to allow super_admin to override gps restrictions. {true,false}
$geofence_autologin_enabled = false; //set to true to skip pin entry at your specified location. {true,false}
$geofence_autologin_user_type = 'user'; //set to either user or admin {user,admin}
$geofence_enabled = true; //set to true to enable or false to disable the geofence. {true,false}
$geofence_return_result = true; //set to true for testing or to attract stalkers, set to false to disable. {true,false}
$garage_latitude = '32.9697'; //set to garage latitude
$garage_longitude = '-96.80322'; //set to garage longitude
$geofence_unit_of_measurement = 'meters'; // use meters, kilometers or miles;
$geofence_maximum_allowed_distance = '30'; //distance in units from the garage door latitude/longitude.

/************ Configuration for notifications ************/

$admin_mobile = '4033029392'; //replace with mobile number for recieving text messages
$admin_email = 'admin@whereveryouwantthem.com'; //replace with an admin email address
$notification_email = 'notifications@fromtheserver.com'; //replace with a notification email address

/************ Location Specific
This is a list of carriers in the US. Change to your country or add your carrier information to the array.************/

$carriers = array (
	0 => 'tomomail.net',
	1 => 'messaging.sprintpcs.com',
	2 => 'vtext.com',
	3 => 'txt.att.net',
	4 => 'vmobl.com',
	//5 => 'mycarrier.com',
);

/************ Motion Support
This is for Motion Support ************/

$use_motion = true; //set to true to use Motion webcam {true,false}
$motion_ip = 'localhost'; //set to motion url
$motion_http_version = '1.0' //must match http version in motion.conf or camera connection will time out
$motion_control_port = 8079; //set to motion control port for snapshots and restarting motion
$motion_view_port = 8078; //set to motion view port for live streaming
$motion_image_height = 240; //fits nicely on phones
$motion_image_width = 320; // also fits nicely on phones
$motion_http_username = 'username'; // if motion http auth is used
$motion_http_password = 'password'; // if motion http auth is used


/************ Amazon Echo Support
This is for Echo Support, currently working through http://github.com/armzilla's Hue Emulator.************/

$use_hue_emulator = false; //set to true to enable hue emulator {true,false}
$start_port = 8080; // be careful when using with motion or anything else that uses port 8080 and the next 3 consecutive ports
$use_bws = false; //faster startup and less memory/cpu intensive {true,false}
$hue_emulator_ip = 'localhost'; //set to localhost if using a raspberry pi for both garageatrois and hue emulator

$echo_name = 'Amazon Echo'; //Name for the device
$echo_did = ''; //15 characters, maybe the serial number -or- watch the terminal window for the hue emulator and use the id that shows up during a device scan.
$echo_uid = 'echo'; // set the UID for echo to an unused 4 digit pin, or leave it default

?>
