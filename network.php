<? 
    /*
     * network.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>";exit;}

    if (count($_POST) && isset($_POST["POST_ACTION"])) {

        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child
            if ($_POST["POST_ACTION"] == "STATIC") {
                do_setStatic($_POST["staticIP"],
                            $_POST["staticNetMask"],
                            $_POST["staticGateway"],
                            $_POST["stationName"],
                            $_POST["staticIPLan"],
                            $_POST["staticNetMaskLan"],
                            $_POST["stationMode"],
                            $_POST["WAN"],
                            $_POST["LAN"],
                            $_POST["LAN1"]);
                            
            } else if ($_POST["POST_ACTION"] == "DHCP") {
                  do_setDhcp($_POST["stationName"],
                            $_POST["staticIPLan"],
                            $_POST["staticNetMaskLan"],
                            $_POST["stationMode"],
                            $_POST["WAN"],
                            $_POST["LAN"],
                            $_POST["LAN1"]);
            }
            if (($fd = fopen($_SERVER["DOCUMENT_ROOT"]."/cgi-bin/netif.php", "w")) != NULL) {
                fwrite($fd, '<?php $THEWAN="'.$_POST["WAN"].'"; $THELAN="'.$_POST["LAN"].'"; $THESRV="'.$_POST["SRVLANassignement"].':srv"; $THESRVIP="'.$_POST["staticSRVIP"].'"; $THEMODE="'.$_POST["stationMode"].'"; ?>'."\n");
                fclose($fd);
                sleep(5);
                ifsrv($_POST["SRVLANassignement"].":srv", $_POST["staticSRVIP"]);
              
            }
            @system("echo 'DEST=127.0.0.1; INTERFACES=".g_lan().";' > /etc/default/watchdog.dest");
        } else {
            // We are the parent
            $dest="&ip=".g_destip($_SERVER["SERVER_ADDR"], $_POST["staticSRVIP"], $_POST["SRVLANassignement"].':srv');            
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds=40&loc='.$_SERVER['SCRIPT_NAME'].$dest);
            exit;
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="/css/bridge.css">
        <title><? p_title(); ?></title>
        <script>

<? setTimeout(); ?>

<? include 'inc/general.js.php' ?>

function initPage()
{
	var f = getObj("form");

    <?if (g_mode() == 1) { ?>

    getObj("gt").checked = true;
    <?} else {?>

    getObj("hb").checked = true;

    <?}
    if (g_connectionType(g_wan()) == "dhcp") {
        echo 'f.POST_ACTION.value = "DHCP";'."\n";
        echo '    getObj("IPassignement").selectedIndex=1;'."\n";
        
    } else {
        echo 'f.POST_ACTION.value = "STATIC";'."\n";
        echo '    getObj("IPassignement").selectedIndex=0;'."\n";
    }       
    ?>

    getObj("displayStaticIP").style.display="<?  echo g_connectionType(g_wan()) == "static"?  "block":"none"; ?>";

    return true;
}

function setMode()
{
    var f=getObj("form");

    if (getObj("gt").checked == true) {
        f.stationMode.value=1;
    } else {
        f.stationMode.value=2;
    }
}

function checkPage()
{
	var f=getObj("form");

    if(isFieldBlank(f.stationName.value))
    {
        alert("The Device Name field cannot be blank.");
	    f.stationName.select();
	    return false;
    }
    if(isFieldBlank(f.stationName.value))
    {
        alert("The Device Name field cannot be blank.");
	    f.stationName.select();
	    return false;
    }

    if (f.POST_ACTION.value == "STATIC")
    {
        if (isIPValid(f.staticIP) == false) {
            alert ("The IP Address field is invalid");
            f.staticIP.select();
            return false;
        }

        if (isNetmaskValid(f.staticNetMask) == false) {
            alert ("The Netmask Address field is invalid");
            f.staticNetMask.select();
            return false;
        }

        if (isIPValid(f.staticGateway) == false) {
            alert ("The IP Address field is invalid");
            f.staticGateway.select();
            return false;
        }

      
    }
    
    if (f.SRVLANassignement.selectedIndex !=0 ) {
        if(isFieldBlank(f.staticSRVIP.value)) {
            alert("The Station IP Address field cannot be blank.");
	        f.staticSRVIP.select();
	        return false;
        }
        if (isIPValid(f.staticSRVIP) == false) {
            alert ("The Station IP Address field is invalid");
            f.staticSRVIP.select();
            return false;
        }
    } else f.staticSRVIP.value = 0;

    if (f.WANassignement.value == f.LANassignement.value) {
        alert("The Network Interface Assignments are invalid")
        return false;
    }

    if (getObj("hb").checked == true) {
        var wifs = <? do_wifilist() ?>;
        var i;
        var res=false;
        for (i = 0; i < wifs.length; ++i) {
            if (f.WANassignement.value == wifs[i]) {
                res = true; break;
            }
        }
        if (res==false) {
            alert("The bridge's WAN interface is not a wireless one");
            return false;
        }
    }

    f.WAN.value=f.WANassignement.value;
    f.LAN.value=f.LANassignement.value;
    if (f.LAN1.value == ":1" || f.LAN1.value == "")
        f.LAN1.value = f.LANassignement.value +":1";
    else
        f.LAN1.value = f.LANassignement.value +":1";
   
    f.submit();

    setMode();

    return true;
}

function changeIPassignement(val)
{
    var f=getObj("form");

    if (val==1) {
        getObj("displayStaticIP").style.display = "block";
        f.POST_ACTION.value = "STATIC";
    } else {
        getObj("displayStaticIP").style.display = "none";
        f.POST_ACTION.value = "DHCP";
    }

    return true;
}
        </script>
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<? echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION" value="" type="hidden">
        <input type="hidden" name="staticIPLan" value="<? p_ip(g_lan()); ?>">
        <input type="hidden" name="staticNetMaskLan" value="<? p_mask(g_lan()); ?>">
        <input type="hidden" name="stationMode" value="0">
        <input type="hidden" name="LAN" value="<? echo g_lan() ?>">
        <input type="hidden" name="WAN" value="<? echo g_wan() ?>">
        <input type="hidden" name="LAN1" value="<? echo g_lan1() ?>">
        <table id="topContainer">
            <tr>
	            <td class="laCN">Project Page&nbsp;:&nbsp;<a href="<? p_productHome(); ?>" target=_blank><? p_serverName(); ?></a></td>
	            <td class="raCN">Version&nbsp;:&nbsp;<? p_firmware("-ro");?>&nbsp;</td>
            </tr>
        </table>
        <table id="topTable">
            <tr>
	            <td id="topBarLeft"><a id="logo" href="<? p_productHome(); ?>"></a></td>	            
	            <td id="topBarRight"></td>
            </tr>
        </table>
        <table id="topMenuTable">
            <tr>

	            <td class="ledPanel">
                    <img class="led" alt="fwstat"   title="Firewall" src="/img/<? echo g_srvstat("shorewall")? "on":"off" ?>.png">
                    <img class="led" alt="dnsstat"  title="DNS"      src="/img/<? echo g_srvstat("named")? "on":"off" ?>.png">
                    <img class="led" alt="dhcpstat" title="DHCP"     src="/img/<? echo g_srvstat("dhcpd")? "on":"off" ?>.png">
                </td>
	            <td class="topMenuThis"><a href="/network.php">Setup</a></td>
	            <td class="topMenuLink"><a href="/advanced.php">Advanced</a></td>
	            <td class="topMenuLink"><a href="/admin.php">MAINTENANCE</a></td>
	            <td class="topMenuLink"><a href="/status.php">Status</a></td>
	            <td class="topMenuLink"><a href="/logout.php"><span id="timer"></span></a></td>		
            </tr>
        </table>
        <table id="mainContentTable">
            <tr style="vertical-align: top;">
                <td class="leftMenyContatiner">
                    <div class="leftNavLink">
                        <ul style="width: 100%">
                            <li><div id="left" class="navThis">Network</div></li>
                            <? if (g_haswifi()) { ?><li><div class="leftnavLink"><a href="/wireless.php">Wireless</a></div></li><?}?>
                            <li><div class="leftnavLink"><a href="/dhcp.php">DHCP</a></div></li>
                            <li><div class="leftnavLink"><a href="/dns.php">DNS</a></div></li>
                            <li><div class="leftnavLink"><a href="/firewall.php">Security</a></div></li>
                            <li><div class="leftnavLink"><a href="/storage.php">Storage</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Network Settings</h1>
                        Use this section to configure the internal network settings of your <? p_mode(); ?>.
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
		            </div><div class="vbr"></div>

                    <div class="actionBox">
                        <h2 class="actionHeader">Device Mode</h2>
                        This section determines whether this device is configured for a typical Home Gateway Configuration
                          or as a Bridge Configuration with wireless connectivity to an external Internet modem.<br>
                          In a Home Gateway Configuration, this system can be used as a Wireless Access Point if the
                          Local Area Network (LAN) side is assigned with a Wireless device as defined in
                          the "Network Interface Assignments" section below.
                        <table>
			                    <tr>
				                    <td class="raCB" style="width:30%">Home Gateway :</td>
                                    <td class="laCB" style="width:8%">&nbsp;
                                        <input type="radio" id="gt" name="mode" onclick="setMode()">
                                    </td>
                                    <td style="text-align:center"><img alt="lanpic" style="width:70%; height:auto" src="/img/lan.png"></td>
                                </tr>
                                <tr>
				                    <td class="raCB">Wireless Home Bridge :</td>
                                    <td class="laCB">&nbsp;
                                        <input type="radio" <? echo g_haswifi()? "":'disabled="disbaled"' ?> id="hb" name="mode" onclick="setMode()">
                                    </td>
                                    <td style="text-align:center"><img alt="wifipic" style="width:70%; height:auto" src="/img/wifi.png"></td>
                                </tr>
                        </table>
                    </div><div class="vbr"></div>

                    <div class="actionBox">
			            <h2 class="actionHeader">Internet Settings</h2>
                            Use this section to configure the internal network settings of this device.<br>
                            The IP Address that is configured here is the IP Address that you use to transfer
                            data between the WAN (Internet) interface connected to a modem and your LAN (private network)
                            that is managed by this device.<br>
                            To configure the LAN side, go to the DHCP settings menu.<br><br>
			            <table>
			                <tr>
				                <td class="raCB" style="width: 40%">Internet Connection Type :</td>
				                <td class="laCB">&nbsp;
				                 <select id="IPassignement" onchange="changeIPassignement(this.value)">
                                        <?
                                            if (g_connectionType(g_wan()) === "static") {
                                                echo '<option value="1" selected="">Static IP</option>';
                                                echo '<option value="2">Dynamic IP (DHCP)</option>';
                                            } else {
                                                echo '<option value="1">Static IP</option>';
                                                echo '<option value="2" selected="">Dynamic IP (DHCP)</option>';
                                            } echo "\n";
                                        ?>
					                </select>
				                </td>
			                </tr>
			            </table>
                    </div>
			        <div id="displayStaticIP">
			            <table>
			                <tr>
				                <td class="raCB" style="width: 40%">IP Address :</td>
				                <td class="laCB">&nbsp;
				                    <input id="staticIP" name="staticIP" size="19" maxlength="15" value="<? p_ip(g_wan()); ?>" type="text">
				                </td>
			                </tr>
			                <tr>
				                <td class="raCB">Subnet Mask :</td>
				                <td class="laCB">&nbsp;
					                <input id="staticNetMask" name="staticNetMask" size="19" maxlength="15" value="<? p_mask(g_wan()); ?>" type="text">
				                </td>
			                </tr>
			                <tr>
				                <td class="raCB">Default Gateway :</td>
				                <td class="laCB">&nbsp;
				                    <input id="staticGateway" name="staticGateway" size="19" maxlength="15" value="<? p_gateway(); ?>" type="text">
				                </td>
			                </tr>
			            </table>
                    </div><div class="vbr"></div>
                    
                    <div class="actionBox">
			            <h2 class="actionHeader">Network Interface Assignments</h2>
                        Select which physical network interface that should act on ech side of your device.<br><br>
			            <table>
                            <tr>
                                <td class="raCB" style="width: 40%">Internet Side :</td>
                                <td class="laCB">&nbsp;
                                    <select id="WANassignement"><? p_ifopts(g_wan()) ?></select>
                                </td>
                            </tr>
                            <tr>
                                <td class="raCB" style="width: 40%">LAN Side :</td>
                                <td class="laCB">&nbsp;
                                    <select id="LANassignement"><? p_ifopts(g_lan()) ?></select>
                                </td>
                            </tr>
			            </table>
		            </div><div class="vbr"></div>

                     <div class="actionBox">
			            <h2 class="actionHeader">Fixed Station Address</h2>
                        Define a fixed IP Address that is always accessible for these pages during (initial/re) configuration.<br>
                        An Ethernet (not Wifi) connection is preferred.<br>Disable this feature
                        when the station's configuration has stabilized as it is a potential conflicting address.<br>
                        If you choose to have this service on the WAN (Internet) side, the firewall will be stopped and thus the security will be compromised.<br><br>
                        <table>
                            <tr>
                                <td class="raCB" style="width: 40%">Bind to Interface :</td>
                                <td class="laCB">&nbsp;
                                    <select id="SRVLANassignement" name="SRVLANassignement" ><option value="0">disabled</option><? p_ifopts(g_srvlan()) ?></select>
                                </td>
                            </tr>
                            <tr>
				                <td class="raCB">IP Address :</td>
				                <td class="laCB">&nbsp;
					                <input id="staticSRVIP" name="staticSRVIP" size="19" maxlength="15" value="<? echo g_srvip(); ?>" type="text">
				                </td>
			                </tr>
                        </table>
                    </div><div class="vbr"></div>

                    <div class="actionBox">
			            <h2 class="actionHeader">Station Name (DNS Name)</h2>
			            <table>
			                <tr>
				                <td class="raCB" style="width: 40%">Station Name :</td>
				                <td class="laCB">&nbsp;
				                    <input id="stationName" name="stationName" size="19" maxlength="32" type="text" value="<? p_nodeName(); ?>">
				                </td>
			                </tr>
			            </table>
		            </div>

                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    If you have an DHCP server on your Internet Modem, you can select DHCP to get the IP
                    address from that DHCP server.<br><br>
                    <strong>NOTE!</strong> If your Internet Modem has a built in firewall, you must either
                    disabled it completely and/or enable this device to be in a DMZ zone as seen from the Internet Modem.<br><br>
                    <strong>NOTE!</strong> You may have to re-enable the watchdog (advanced settings tab)
                    if changes has been made on the Internet connection side.<br><br>
                    <strong>NOTE!</strong> Some 3g/4g USB Modems are identified as Ethernet Devices (ethX) and they can be used as
                    an Internet Modem in a Home Gateway Configuration.<br><br>
                    <strong>NOTE!</strong> Altering the Network Interface Assignments will stop the dhcp, dns and possibly the
                    wireless Access Point features since they must be attended to in their respective setup page.

               </td>
            </tr>
            <tr>
	            <td colspan="3" id="footer">
                    Copyright &copy; <? p_copyRight(); ?>
                </td>                   
            </tr>
        </table>
    </form>
    </body>
</html>
