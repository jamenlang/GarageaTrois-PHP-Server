<?php
require('GarageaTrois-Config.php');
require('GarageaTrois-Functions.php');

$mdate = md5(time());

$tempfile = '/tmp/' . $mdate . '.apk';

if($apk_link == 'http://files.myawesomedomain.net/garageatrois.apk'){
  exec('wget --max-redirect=0' $(curl -s https://api.github.com/repos/jamenlang/GarageaTrois/releases/latest | grep \'browser_\' | cut -d\" -f4) 2>&1', $output);
  //print_r($output);
  foreach ($output as $line){
      if($apk_link != 'http://files.myawesomedomain.net/garageatrois.apk')
        continue;
      preg_match('/\bhttp.*apk\b/',$line, $matches);
      if($matches[0])
        $apk_link = $matches[0];
  }
}
//echo $apk_link;

file_put_contents(
  $tempfile,
  file_get_contents($apk_link)
);

//header()

if (file_exists('/tmp/' . $mdate. '.apk')) {
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="'.basename('/tmp/GaT.apk').'"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($tempfile));
  readfile($tempfile);
  unlink($tempfile);
  exit;
}

?>
