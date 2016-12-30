<?php
require_once('GarageaTrois-Config.php');
require_once('GarageaTrois-Functions.php');

if($hue_emulator_ip == 'localhost'){
        $hue_emulator_ip = $_SERVER[HTTP_HOST];
}

$armzilla_configurator_url = "http://$hue_emulator_ip:$start_port/configurator.html"; //url for the hue emulator device manager
$armzilla_devices_url = "http://$hue_emulator_ip:$start_port/api/devices";

$bws_configurator_url = "http://$hue_emulator_ip:$start_port/#"; //url for the hue emulator device manager
$bws_devices_url = "http://$hue_emulator_ip:$start_port/#";

$hue_configurator_url = (($use_bws) ? $bws_configurator_url : $armzilla_configurator_url);
$hue_devices_url = (($use_bws) ? $bws_devices_url : $armzilla_devices_url);

$con_array = array('0','down','off','closed','close','shut','stop');
$pro_array = array('1','up','on','opened','open','start');


?>
