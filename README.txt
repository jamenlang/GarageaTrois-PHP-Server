############# ReadMe #############
Project Overview:

The garage door is the gateway to the home. There are tons of DIY projects to make it easier to control, and tutorials for controlling it remotely.

But I'm lazy, and I don't have money for kickstarter projects for this type of thing. I also like to make things harder than they need to be.
Sometimes I need to let someone into my house for them to pick things up or drop things off, I don't want to be there all the time and if they don't have a key I'd like to be able to let them in remotely. 
I've been able to do this with my garage door and a web page for a while but I needed something less cumbersome and a little more secure.

Necessary Hardware:

A desktop computer or server with a printer port (possibly a serial port with an adapter or a natively serial controlled relay)

A Parallel/Serial port controlled relay with 3-4 modules (my garage door button had 3 buttons, light, lock and door).

- OR -

A Raspberry Pi using GPIO, hell the RPi should be able to run the server stuff too... I tested out wiring pi's gpio control and it works without needing root or sudo, exec works great, install http://wiringpi.com/the-gpio-utility/ to use gpio

- OR - 

Anything else you can think of, a server using a z-wave or zigbee switch... but if you're getting that fancy you probably have your own project in mind :P
 
############# Starting Off #############

My server didn't have an onboard printer port. I bought a parallel PCI card with a moschip controller (for compatibility reasons)
Note: there are a lot of fake unsupported parellel adapters out there, By fake I mean they're really just a pair of serial controllers, and by unsupported I mean linux doesn't like them.


Relay controller board (USB or Parallel) - $40
Android device
LAMP Server - I'm using MYSQL as the back end for user authentication, device authentication and logging, the structure is contained in the MySQL Structure for documentation. I mail out notifications from my personal FQDN on the server.
I'm also using Ubuntu for the OS, although I'm not certain there's any linux specific stuff going on here. Might work on Windows as well.
The GarageaTrois-Config.php file is where settings are located.

############# Design #############

The server controls the relay via parallel port (in my case)
I managed to get everything physical up and running using http://www.faqs.org/docs/Linux-mini/IO-Port-Programming.html as a guide, thank you Tomi Engdahl!

Android app controls relay by sending POST to Garageatrois-Server.php script running on the server.

############# Echo Support #############

To get Amazon Echo working, I used the Hue Emulator from armzilla's github projects and installed it on a raspberry pi.

The steps I took are below. 

I flashed the latest available version of raspbian.
Connected to Wifi.
Found the IP address of the WLAN interface.

Installed Java8.

sudo sh -c 'echo "deb http://ppa.launchpad.net/webupd8team/java/ubuntu precise main" >> /etc/apt/sources.list'
sudo sh -c 'echo "deb-src http://ppa.launchpad.net/webupd8team/java/ubuntu precise main" >> /etc/apt/sources.list'
sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys EEA14886
sudo apt-get update
echo oracle-java8-installer shared/accepted-oracle-license-v1-1 select true | sudo /usr/bin/debconf-set-selections
sudo apt-get install oracle-java8-installer
sudo update-java-alternatives -s java-8-oracle
sudo apt-get install oracle-java8-set-default

Downloaded the 2.1.jar release file from https://github.com/armzilla/amazon-echo-ha-bridge/releases

Ran it with the IP address of the raspberry pi.

java -jar amazon-echo-bridge-0.2.1.jar --upnp.config.address=192.168.1.xxx

Pointed my browser to http://myawesomedomain.net/GarageaTrois-Echo.php

Configured all 3 of my switches

Asked Alexa to scan for available devices.

I watched the terminal window while the echo was scanning and saw that it the pi was being queried. 

And viola, the switches are available for the echo to control

As far as I know, there is no way to remove switches that have been added. If you need to start over delete the .data directory that is generated from running the commands above.
