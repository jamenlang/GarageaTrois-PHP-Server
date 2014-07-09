<?php
/************ For MYSQL Database (Logging, User authentication and Device authentication)
Read the README and follow instructions before proceeding ************/

$hostname = 'localhost'; //replace with database hostname
$username = 'USERNAME'; //replace with database username
$password = 'PASSWORD'; //replace with database password
$db_name = 'garage'; //replace with database name
$SUPER_SECRET_ADMIN_RESULT = 'SUPER_SECRET_ADMIN_RESULT'; //replace with whatever is in the res/strings.xml file in the android app.
$SUPER_SECRET_USER_RESULT = 'SUPER_SECRET_USER_RESULT'; //replace with whatever is in the res/strings.xml file in the android app.

/************ Configuration for Logging ************/

$log_to_file = '1'; //after everything is installed and working you'll want to disable logging.
$log = 'logfile.txt'; //change to whatever you'd like

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
$apk_link = 'files.myawesomedomain.net/garageatrois.apk';

/************ Configuration for geofence
Geofencing is a pretty popular form of access restriction based on GPS data and distance between two points. 
If you have a use for it, by all means try it out.************/

$geofence_enabled = 'true'; //set to true to enable or false to disable the geofence.
$geofence_return_result = 'true'; //set to true for testing or to attract stalkers, set to false to disable.
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
?>