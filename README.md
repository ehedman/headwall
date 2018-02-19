# headwall
README Jan-2018

Headwall is a home gateway, firewall and network bridge that is designed to
run on hardware that is widely available to anyone with an interest in
embedded systems and Linux.

Exampled hardware could be an Rasperry Pi, a PandaBoard, a BeagleBoardord or similar
systems that can be purchased over the counter.

The goal of the design of this system is that it should be easy to use and
configure with the technical terms and properties as far as possible, hidden
to the user.
Internally, a goal that as far as possible, verify user inputs and give
guidance during configuration.

### FUNCTIONAL FEATURES:
* Linux based Home Gateway and Wireless Bridge
* DHCP Server in stand alone mode
* DNS Server in stand alone mode
* DDNS (DNS and DHCP integrated)
* Firewall based on shorewall
* URL outgoing filtering with tracker jump over
* Watchdog (hard) surveillance of network connectivity
* VPN Server for the LAN
* Mass storage CIFS server for the LAN

### GUI FEATURES
* lighthttpd web server for device configuration
* PHP and JavaScript for device management
* HTML5 verified code
* Auto scaling of fonts - looks beter on tablets
* Session management, password protection & auto logout
* Wireless setup pages
* Wireless Site Survey dialog
* DNS and DHCP Management pages
* Firewall management pages
* Firewall logs (drop, reject)
* Virtual Hosts management (nat and port orchestration)
* URL blacklist management
* Blacklist rejects statistics
* VPN Management
* CIFS management of attached disks
* Bittorrent client

### Screenshots
<img src="http://www.hedmanshome.se/components/com_ponygallery/img_pictures/status.png" width=100%></br>
<img src="http://www.hedmanshome.se/components/com_ponygallery/img_pictures/wifi.png" width=100%></br>
<img src="http://www.hedmanshome.se/components/com_ponygallery/img_pictures/virt.png" width=100%></br>
