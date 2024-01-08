<?php
    /*
     * status.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */
 
    include 'cgi-bin/common.php';
    $TMO=240;

    if (count($_GET) == 0) {
        do_hostopts();
	    @system("leases " .g_lan()." ".g_domain().">/tmp/reslist &");
	    @system("alleases ".g_domain().">/tmp/dmlist &");
        @system("topspammers > /tmp/spamlist &");
    }
    $ppgw=$pubip=NULL;
    if ($vpnifs=trim(@exec("/sbin/ifconfig | grep ppp | wc -l")) >0)
        $ppgw=trim(@exec("ip addr show ppp0 | grep -v secondary | awk  '/inet /{ print $2 }'"));

    if (g_srvstat("ddclient"))
        $pubip=trim(@exec("cat /var/cache/ddclient/ddclient.cache | awk -Fip= '{print \$2}' | awk -F, '{print \$1}'"));
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="/css/bridge.css">
        <title><?php p_title(); ?></title>
        <script>

function onbody()			// Executed only once.
{
	window.onresize=resize;
	resize();
}

// Percent of width: Define the size of "1.0em"
function resize()	// Set font relative to window width.
{
    var f_Factor=1;
    var W = window.innerWidth || document.body.clientWidth;
//return;
    if (W <= 800) return;
//	P =  Math.floor (W/38);				// ca. 3 percent constant
	P =  Math.floor (f_Factor*(8+W/160));		// Linear function
	if (P<12)P=12;					// Smallest size.
	document.body.style.fontSize=P + 'px';
}

var refresh = 10;
var counter;
var secs;
var timeout;
function re_scan()
{
    secs=secs-1;
    document.getElementById("timer").innerHTML="Logout "+ secs + " s";  
   
    if (++timeout > <?php echo $TMO ?>) {
        clearInterval(counter);
        self.location.href="/logout.php";
    } else if (--refresh < 0)  {
        clearInterval(counter);
        var str="<?php echo $_SERVER['SCRIPT_NAME']; ?>?secs="+secs+"&timeout="+timeout;
	    self.location.href=str;
    }                 
}

function initPage()
{
    secs = document.getElementById("secs").value;
    timeout = document.getElementById("timeout").value;
    counter = setInterval(re_scan, 1000);
}
        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <input type="hidden" id="secs" name="secs" value="<?php if (isset($_GET['secs'])) {echo intval($_GET['secs']) >0? $_GET['secs']:$TMO;} else echo $TMO; ?>">
    <input type="hidden" id="timeout" name="timeout" value="0<?php if (isset($_GET['timeout'])) {echo intval($_GET['timeout']) >0? $_GET['timeout']:'0';} ?>">

        <table id="topContainer">
            <tr>
	            <td class="laCN">Project Page&nbsp;:&nbsp;<a href="<?php p_productHome(); ?>" target=_blank><?php p_serverName(); ?></a></td>
	            <td class="raCN">Version&nbsp;:&nbsp;<?php p_firmware("-ro");?>&nbsp;</td>
            </tr>
        </table>
        <table id="topTable">
            <tr>
	            <td id="topBarLeft"><a id="logo" href="<?php p_productHome(); ?>"></a></td>	            
	            <td id="topBarRight"></td>
            </tr>
        </table>
        <table id="topMenuTable">
            <tr>
	            <td class="ledPanel">
                    <img class="led" alt="fwstat"   title="Firewall" src="/img/<?php echo g_srvstat("shorewall")? "on":"off" ?>.png">
                    <img class="led" alt="dnsstat"  title="DNS"      src="/img/<?php echo g_srvstat("named")? "on":"off" ?>.png">
                    <img class="led" alt="dhcpstat" title="DHCP"     src="/img/<?php echo g_srvstat("dhcpd")? "on":"off" ?>.png">
                </td>
	            <td class="topMenuLink"><a href="/network.php">Setup</a></td>
	            <td class="topMenuLink"><a href="/advanced.php">Advanced</a></td>
	            <td class="topMenuLink"><a href="/admin.php">MAINTENANCE</a></td>
	            <td class="topMenuThis"><a href="/status.php">Status</a></td>
	            <td class="topMenuLink"><a href="/logout.php"><span id="timer"></span></a></td>		
            </tr>
        </table>
        <table id="mainContentTable">
            <tr style="vertical-align: top;">
                <td class="leftMenyContatiner">
                    <div class="leftNavLink">
                        <ul style="width: 100%">
                            <li><div id="left" class="navThis">Status</div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Device Information</h1>
                        Connection details for your <?php p_mode(); ?> are displayed on this page.
		            </div><div class="vbr"></div>
		            <div class="actionBox">
			            <h2 class="actionHeader">System Information</h2>
                        <table>
				            <tr>
					            <td class="raCB" style="width:40%"><b>Firmware Version&nbsp;:</b></td>
					            <td class="laCB"><b>&nbsp;<?php p_firmware("-ro"); ?></b></td>
				            </tr>
				            <tr>
					            <td class="raCB"><b>Uptime&nbsp;:</b></td>
					            <td class="laCB"><b>&nbsp;<?php p_uptime(); ?></b></td>
				            </tr>
                            <tr>
					            <td class="raCB"><b>System&nbsp;:</b></td>
					            <td class="laCB"><b>&nbsp;<?php p_osrel(); echo "@"; p_serverName(); ?></b></td>
				            </tr>
							<tr>
					            <td class="raCB"><b>Firewall&nbsp;:</b></td>
					            <td class="laCB"><b>&nbsp;<?php echo g_srvstat("shorewall")==true? "Enabled":"Disabled"; ?></b></td>
				            </tr>
			            </table>
                    </div><div class="vbr"></div>
                    <div class="actionBox">
			            <h2 class="actionHeader"><?php echo g_iftype(g_lan())==1? "Ethernet":"Wireless"; ?> LAN Connection (<?php echo g_lan(); ?>)</h2>
			            <table>
			                <tr>
				                <td class="raCB" style="width:40%">Connection Type&nbsp;:</td>
				                <td class="laCB">&nbsp;
				                <?php p_connectionType(g_lan()); ?> IP&nbsp;</td>
			                </tr>
			                <tr>
				                <td class="raCB">MAC Address&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_mac(g_lan()) ?></td>
			                </tr>
			                <tr>
				                <td class="raCB">IP Address&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_ip(g_lan()); ?></td>
			                </tr>
			                <tr>
				                <td class="raCB">Subnet Mask&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_mask(g_lan()); ?></td>
			                </tr>
						    <tr>
				                <td class="raCB">Connected Clients&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php echo g_nclients(g_lan()); ?></td>
			                </tr>
			            </table>
                    </div><div class="vbr"></div>

                    <?php if ($vpnifs > 0) { ?>

                    <div class="actionBox">
			            <h2 class="actionHeader"> VPN Connections</h2>
			            <table>
                            <tr>
                                <td class="raCB">LAN Gateway IP&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php echo $ppgw; ?></td>
                            </tr>
                            <?php if ($pubip !=NULL) { ?>

                            <tr>
                                <td class="raCB">Public DDNS IP&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php echo $pubip; ?></td>
                            </tr>

                            <?php }?>

                            <tr>
                                <td class="raCB" style="width:40%">Connected Clients&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php echo $vpnifs; ?></td>
                            </tr>       
                        </table>
                    </div><div class="vbr"></div>
                    <?php }?>

                    <div class="actionBox" style="display:<?php echo g_ifip(g_lan1())? "block":"none"; ?>">
			            <h2 class="actionHeader">Blacklist Filter and Redirector</h2>
			            <table>
                            <tr>
			                    <td class="raCB" style="width:40%">To Host IP Address&nbsp;:</td>
                                <td class="laCB">&nbsp;<?php  p_spfredir(); if(g_spfhere()) {echo " on this "; p_mode();} ?></td>
			                </tr>
                            <?php if(g_spfhere()) { ?><tr>
				                <td class="raCB">URLs rejected&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_denycount(); ?></td>
			                 </tr><?php }?>
			            </table>
                    </div><div class="vbr"></div>
                    <div class="actionBox">
			            <h2 class="actionHeader"><?php echo g_mode()==2? "Wireless WAN Connection":"Ethernet WAN Connection"; ?> (<?php echo g_wan(); ?>)</h2>
			            <table>
			                <tr>
				                <td class="raCB" style="width:40%">Connection Type&nbsp;:</td>
				                <td class="laCB">&nbsp;
				                <?php p_connectionType(g_wan()); ?> IP&nbsp;</td>
			                </tr>
			                <tr>
				                <td class="raCB">MAC Address&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_mac(g_wan()) ?></td>
			                </tr>
			                <tr>
				                <td class="raCB">IP Address&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_ip(g_wan()); ?></td>
			                </tr>
			                <tr>
				                <td class="raCB">Subnet Mask&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_mask(g_wan()); ?></td>
			                </tr>
			                <tr>
				                <td class="raCB">Default Gateway&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_gateway(); ?></td>
			                </tr>
                            <tr>
				                <td class="raCB">Traffic&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_lanStat(g_wan()); ?></td>
			                </tr>
			            </table>
                    </div>
                    <?php  $allwifi=g_allwifidev();  foreach ($allwifi as $wifidev) { ?><div class="vbr"></div>
                    <div class="actionBox">
			            <h2 class="actionHeader">Wireless Connection for <?php echo $wifidev."@".g_wifiband($wifidev); ?></h2>
			            <table>
			                <tr>
				                <td class="raCB" style="width:40%">Wireless Radio&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php echo g_wstatus($wifidev)? 'Enabled':'Disabled'; ?></td>
			                </tr>
                            <tr>
				                <td class="raCB" style="width:40%">Wireless Mode&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php echo g_wmode($wifidev) ?></td>
			                </tr>                     		
			                <tr>
				                <td class="raCB">Network Name(SSID)&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_wssid($wifidev); ?></td>
                            </tr>
			                <tr><?php if (g_mode()==1) {?>
				                <td class="raCB">Bit Rate&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_wbitrate($wifidev); ?></td>
			                </tr><?php }?>
                            <?php if (g_mode()==2) {?><tr>
				                <td class="raCB">Status&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_wstatus($wifidev); ?></td>
			                </tr>
                            <tr>
				                <td class="raCB">Link Quality&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_wquality($wifidev); ?></td>
			                </tr><?php }?>
			                <tr>
				                <td class="raCB">Security Type&nbsp;:</td>
				                <td class="laCB">&nbsp;<?php p_keyType($wifidev); ?></td>
			                </tr>
			            </table>
		            </div><?php }?>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    General status and network information is displayed here.
               </td>
            </tr>
            <tr>
	            <td colspan="3" id="footer">
                    Copyright &copy; <?php p_copyRight(); ?>
                </td>                   
            </tr>
        </table>
    </body>
</html>
