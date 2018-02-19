<?php 
    /*
     * advanced.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';
    $TR_PORT=trim(exec("grep rpc-port ".g_trcfg()." | awk -F: '{gsub(/,/,\"\"); print \$2}'"));
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="/css/bridge.css">
        <title><?php p_title(); ?></title>
        <style>
            #trFrame
            {
                width: 100%;
                height: 100%;
                margin: 0;
	            padding: 0;
                border: none;
                overflow: hidden;
            }
        </style>
        <script>

<?php include 'inc/general.js.php' ?>

<?php setTimeout(); ?>

function initPage()
{

    document.getElementById('trFrame').style.height = document.getElementById('contentHeading').offsetHeight +'px';

    return true;
}
        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form id="form"> 
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
	            <td class="topMenuThis"><a href="/advanced.php">Advanced</a></td>
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
                            <li><div class="leftnavLink"><a href="/storage.php">Storage</a></div></li>                          
                            <li><div id="left" class="navThis">Transmission</div></li>
                            <li><div class="leftnavLink"><a href="/tr_settings.php">Settings</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
		            <div class="actionBox">
	                    <h2 class="actionHeader">Transmission Web Interface</h2>
                        <?php if (g_srvstat("transmission-daemon")) { ?>
                        <iframe id="trFrame" src="http://<?php echo $_SERVER["SERVER_ADDR"].":".$TR_PORT; ?>">
                            <p>Your browser does not support iframes.</p>
                        </iframe>
                        <?php } else { ?>
                        You must start this service first.<br>Check items in "Avaiable Network Shares" in the STORAGE menu and
                        then the SETTINGS menu to the left.
                        <?php }?>
		            </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    Transmission is a BitTorrent client that allows users to download files from the Internet and upload their own files or torrents.<br>
                    Use the Open button to open a torrent file or a URL to a magnetic link.<br><br>
                    Direkt LAN link: <a href="http://<?php echo $_SERVER["SERVER_ADDR"].":".$TR_PORT; ?>" target=_blank>Transmission</a>
               </td>
            </tr>
            <tr>
	            <td colspan="3" id="footer">
                    Copyright &copy; <?php p_copyRight(); ?>
                </td>                   
            </tr>
        </table>
    </form>
    </body>
</html>
