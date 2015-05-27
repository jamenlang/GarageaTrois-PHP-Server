<?php require('GarageaTrois-Config.php');?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>GaT Home</title>
</head>

<body>
<?php
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
?>
</body>

</html>
