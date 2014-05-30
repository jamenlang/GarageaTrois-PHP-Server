############# ReadMe #############

Necessary Hardware:

A desktop computer or server with a printer port (possibly a serial port with an adapter or a natively serial controlled relay)

A Parallel/Serial port controlled relay with 3-4 modules (my garage door button had 3 buttons, light, lock and door).

- OR -

A Raspberry Pi using GPIO, hell the RPi should be able to run the server stuff too... hmmm.

- OR - 

Anything else you can think of, a server using a z-wave or zigbee switch... but if you're getting that fancy you probably have your own project in mind :P
 
############# Starting Off #############

My server didn't have an onboard printer port. I bought a parallel PCI card with a moschip controller (for compatibility reasons)
Note: there are a lot of fake unsupported parellel adapters out there, By fake I mean they're really just a pair of serial controllers, and by unsupported I mean linux doesn't like them.


Relay controller board (USB or Parallel) - $40
Android device
LAMP Server - I'm using MYSQL as the back end for user authentication, device authentication and logging, the structure is contained in the MySQL Structure for documentation. I mail out notifications from my personal FQDN on the server.


############# Design #############

The server controls the relay via parallel port (in my case)
I managed to get everything physical up and running using http://www.faqs.org/docs/Linux-mini/IO-Port-Programming.html as a guide, thank you Tomi Engdahl!

Android app controls relay by sending POST to Garageatrois-Server.php script running on the server.
