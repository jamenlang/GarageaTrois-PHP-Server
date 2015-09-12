#! /bin/bash
sudo cp /var/www/GarageaTrois/GarageaTrois-Config.php /var/www/GarageaTrois-Config.php.backup
sudo rm /var/www/GarageaTrois -fr
sudo rm /var/www/phpqrcode -fr
sudo git clone https://github.com/jamenlang/GarageaTrois-PHP-Server.git GarageaTrois
sudo git clone git://git.code.sf.net/p/phpqrcode/git phpqrcode
sudo cp /var/www/GarageaTrois-Config.php.backup /var/www/GarageaTrois/GarageaTrois-Config.php
