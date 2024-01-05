<?php 
    /*
     * firewall.php
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

    if (count($_POST) && $_POST["POST_ACTION"] == "OK") {

         if ($_POST["ACTION_DO_FWS"] == 1 || $_POST["activate"] == "Stop") {
            if (do_firewall_pw($_POST["SUPER_PASSWD"]) == false) {
				header('Location: http://'. $_SERVER["SERVER_ADDR"]. $_SERVER['SCRIPT_NAME'].'?msg=noauth');
                die();
            }
		}
        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child
            if ($_POST["activate"] == "Activate") {
                do_firewall_masqif(g_wan()." ".g_lan()." ".g_srvlan()." ".g_srvip());
                if ($_POST["ACTION_DO_TS"] == 1)
                    do_firewall_ts($_POST['ts_enabled']." ".$_POST['dl_speed']." ".$_POST['ul_speed']." ".g_wan());
                if ($_POST["ACTION_DO_FWS"] == 1)
                    do_firewall_fws($_POST['ssh_enabled']." ".$_POST['nfs_enabled']." ".$_POST['vpn_enabled']);
                do_firewall_mgm("restart");
            } else if ($_POST["activate"] == "Stop") {
                do_firewall_mgm("stop");
            }
        } else { 
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds=14&loc='.$_SERVER['SCRIPT_NAME']);
            exit;
        }
    }
    $nfssts="";
    $vpnsts="";
    $sshsts="";
    $ret=exec("grep -m1 'SSH(ACCEPT)' /etc/shorewall/rules | awk '{printf \"%s%s\",\$2,\$3}'");
    if ($ret == 'loc$FW') $sshsts="checked ";
    $ret=exec("grep -m1 portmap /etc/shorewall/rules | awk '{printf \"%s%s%s\", \$1,\$2,\$3}'");
    if ($ret == 'ACCEPTloc$FW') $nfssts="checked ";
    if (stat("/etc/shorewall/tunnels") != false)
        $vpnsts="checked ";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="/css/bridge.css">
        <title><?php p_title(); ?></title>
        <script>

<?php setTimeout(); ?>

<?php include 'inc/general.js.php' ?>

function initPage()
{
	var f=getObj("form");
    f.ts_enable.checked = <?php echo @stat("/etc/shorewall/tcinterfaces")==false? "false":"true" ?>;
    <?php if (file_exists("/etc/shorewall/tcinterfaces"))  {?>

    f.dl_speed.value="<?php echo exec ("cat /etc/shorewall/tcinterfaces | awk 'NR>5{gsub(".'"mbit",""); printf "%s"'.", $3}'"); ?>";
    f.ul_speed.value="<?php echo exec ("cat /etc/shorewall/tcinterfaces | awk 'NR>5{gsub(".'"mbit",""); printf "%s"'.", $4}'"); ?>";

    <?php } if (g_srvstat("shorewall") == true) { echo 'f.activate.value = "Activate";'; } ?>

    f.PASSWD.value == "00000000";

    return true;
}

function checkPage()
{
    var f=getObj("form");

	if (getObj("ts_enable").checked == true) {
		if (isFieldBlank(f.dl_speed.value) == true) {
		    f.dl_speed.select();
		    alert("Blank Download speed field");
		    return false;
		}
		if (isFieldBlank(f.ul_speed.value) == true) {
		    f.ul_speed.select();
		    alert("Blank Upload speed field");
		    return false;
		}
		if (isDecimal(f.dl_speed.value) == false) {
		    f.dl_speed.select();
		    alert("Not a valid number");
		    return false;
		}
		if (isDecimal(f.ul_speed.value) == false) {
		    f.ul_speed.select();
		    alert("Not a valid number");
		    return false;
		}
		if (isInRange(f.dl_speed.value, 1, 1000) == false) {
		    f.dl_speed.select();
		    alert("Not in range (1-1000");
		    return false;
		}
		if (isInRange(f.ul_speed.value, 1, 1000) == false) {
		    f.ul_speed.select();
		    alert("Not in range (1-1000");
		    return false;
		}
	}

    var pw=getObj("PASSWD");
    if (f.ACTION_DO_FWS.value==1) {
        if (pw.value == "00000000" || isFieldBlank(pw.value) == true ) {
            pw.select();
            alert("A Super Admin Password is required to set <?php p_mode(); ?> Accessibility");
            return false;
        }
    }

    f.ts_enabled.value=(getObj("ts_enable").checked? "1":"0");
    f.SUPER_PASSWD.value = pw.value;
    
    var f=getObj("form");
    f.POST_ACTION.value = "OK";
	f.submit();

}

function do_stop()
{
    var f=getObj("form");

	var pw=getObj("PASSWD");

    if (pw.value == "00000000" || isFieldBlank(pw.value) == true ) {
        pw.select();
        alert("A Super Admin Password is required");
        return false;
    }
	f.activate.value = "Stop";
    f.SUPER_PASSWD.value = pw.value;
    f.POST_ACTION.value = "OK";
    f.submit();
}

function do_activate()
{
    var f=getObj("form");
    f.activate.value = "Activate";
    f.POST_ACTION.value = "OK";
	f.submit();
}

function onenable_ts(objs)
{
    var f=getObj("form");

    f.ts_enabled.value=(objs.checked? "1":"0");
    return true;
}

function onenable_fws()
{
    var f=getObj("form");

    f.ssh_enabled.value=(getObj("ssh_enable").checked? "1":"0");
    f.nfs_enabled.value=(getObj("nfs_enable").checked? "1":"0");
	f.vpn_enabled.value=(getObj("vpn_enable").checked? "1":"0");
    f.ACTION_DO_FWS.value = "1";
    return true;
}

function do_ts()
{
	var f=getObj("form");
    f.ACTION_DO_TS.value = "1";
}
       </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION"   value=""    type="hidden">
        <input name="ts_enabled"    value="0"   id="ts_enabled" type="hidden">
        <input name="ACTION_DO_FWS" value="0"   type="hidden">
        <input name="ACTION_DO_TS"  value="0"   type="hidden" > 
        <input name="ssh_enabled"   value="0"   id="ssh_enabled" type="hidden">
        <input name="nfs_enabled"   value="0"   id="nfs_enabled" type="hidden">
	<input name="vpn_enabled"   value="0"   id="vpn_enabled" type="hidden">
        <input name="activate"      value=""    id="activate" type="hidden">
        <input name="SUPER_PASSWD"  value="00000000" id="SUPER_PASSWD"  type="hidden">
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
                            <li><div id="left" class="navThis">Security</div></li>
                            <li><div class="leftnavLink"><a href="/virtual.php">Virtual Server</a></div></li>
                            <li><div class="leftnavLink"><a href="/vpn.php">Inbound Access</a></div></li> 
                            <li><div class="leftnavLink"><a href="/blacklist.php">URL Blacklist</a></div></li>
                            <li><div class="leftnavLink"><a href="/logfw.php">Firewall Log</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Firewall</h1>
                        <?php if (g_srvstat("shorewall") || stat("/tmp/shwlog") !=false) { ?>
                        <br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
                        <?php }?>
                        <table><tr><td style="text-align:right">
                        <?php
                            if (g_srvstat("shorewall") == false) {
                                echo '<b style="color:#f00;">Firewall: stopped</b>&nbsp;&nbsp;';
                                if (($_SERVER["SERVER_ADDR"] == g_srvip()&& g_srvlan() == g_wan()) || $_SERVER["SERVER_ADDR"] == g_ip(g_wan())) {
                                    echo '&nbsp;&nbsp;<br><b>The firewall cannot be started since you are administer this pages from the WAN side.</b>&nbsp;&nbsp;';
                                    echo '<br>Start using the public LAN (address='.g_ip(g_lan()).'/interface='.g_lan().') instead.&nbsp;&nbsp;';
                                    if (g_srvip() != "0")
                                        echo '<br>Also disable the Fixed Static Address in the <b>SETUP=>Network</b> menu.&nbsp;&nbsp;';
                                    
                                    $dis=' disabled';
                                }
                                if (stat("/tmp/shwlog") !=false)
                                    $dis=' disabled';
                                echo '<input type="button"'.$dis.' onclick="return do_activate();" value="Activate">';
                            } else {
                                echo '<b style="color:#090;">Firewall: active</b>&nbsp;&nbsp;';
                                echo '<input type="button" onclick="do_stop()" value="Stop">';
                            }
							if (@stat("/tmp/shwlog") != false) {
								echo "<pre>\n";
								@system("grep -i error /tmp/shwlog;");
								echo "</pre>\n";
							}
                        ?>                  
                        </td></tr></table>
                                              
                        <?php if (count($_GET)>0 && $_GET["msg"] == "noauth") { ?>
	                    <b>Super Admin Authorization failed</b>
                        <?php }?>
		            </div><div class="vbr"></div>

                     <div class="actionBox" id="showTs" onclick="do_ts();" style="display:<?php echo  g_srvstat("shorewall")? "block":"none"; ?>">                 
			                <h2 class="actionHeader">WAN Traffic Shaping</h2>
		                    <table> 
		                    <tbody>
				                <tr>
				                    <td colspan="2" class="raCB" style="width:40%; height:25px">Enable Traffic Shaping&nbsp;:</td>
				                    <td colspan="3" class="laCB">&nbsp;
				                        <input type="checkbox" id="ts_enable" onChange="onenable_ts(this)">
				                    </td>
				                </tr>
				                <tr>
				                    <td class="raCB" style="height:25px">Download speed&nbsp;:</td>
				                    <td class="laCB">&nbsp;
				                        <input size="6" maxlength="4" type="text" name="dl_speed" id="dl_speed" value="0">&nbsp;mbit
				                    </td><td></td> 
				                    <td class="raCB" style="height:25px">Upload speed&nbsp;:</td>
				                    <td class="laCB" style="width:40%">&nbsp;
				                        <input size="6" maxlength="4" type="text" name="ul_speed" id="ul_speed" value="0">&nbsp;mbit
				                    </td> 
				                </tr>
		                    </tbody>
		                    </table>                     
				        </div><div class="vbr"></div>

						<div class="actionBox" id="showAcc" style="display:<?php echo  g_srvstat("shorewall")? "block":"none"; ?>">              
			                <h2 class="actionHeader">Accessibility for the <?php p_mode(); ?></h2>
		                    <table> 
		                    <tbody>
								<tr>
									<td class="raCB" style="vertical-align:bottom;width:40%" >Super Admin Password&nbsp;:</td>
									<td colspan="2" class="laCB">&nbsp;&nbsp;<input type="password" autocomplete="off" id="PASSWD" value="00000000"></td>
								<tr>
				                    <td class="raCB" style="height:25px">Enable VPN&nbsp;:</td>
				                    <td class="laCB">&nbsp;
				                        <input type="checkbox" <?php echo $vpnsts; ?>id="vpn_enable" onChange="onenable_fws();">
				                    </td>
									<td>Allow this <?php p_mode(); ?> to be the VPN Server for the LAN</td>
				                </tr>									
				                <tr>
				                    <td class="raCB" style="height:25px">Enable SSH&nbsp;:</td>
				                    <td class="laCB">&nbsp;
				                        <input type="checkbox" <?php echo $sshsts; ?>id="ssh_enable" onChange="onenable_fws();">
				                    </td>
									<td>Login permission to the <?php p_mode(); ?> from the LAN</td>
				                </tr>
								<tr>
				                    <td class="raCB" style="height:25px">LAN NFS access of these pages&nbsp;:</td>
				                    <td class="laCB">&nbsp;
				                        <input type="checkbox" <?php echo $nfssts; ?>id="nfs_enable" onChange="onenable_fws();">
				                    </td>
									<td>Mainly for system development</td>
				                </tr>				                
		                    </tbody>
		                    </table>                     
				        </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    Here you can start and stop the firewall for your LAN.<br>To stop the firewall, a Super Admin password is required.
                    <br><br>
                    You can shape incoming and outgoing network traffic.
                    Simple Traffic Shaping allows you to set a limit on the total bandwidth allowed in or out of the <?php p_mode(); ?>.
                    <br><br>
					To complete the VPN service running on this <?php p_mode(); ?> you have to go to the "Inbound Access" page.<br><br>
					If you have a VPN service running on a server inside your LAN, go to the "Virtual Server" page to allow a VPN service for that server.<br>
               </td>
            </tr>
            <tr>
	            <td colspan="3" id="footer">
                    Copyright &copy; <?php p_copyRight(); ?>
                </td>                   
            </tr>
        </table>
    </form>
    <?php
    if (@stat("/tmp/shwlog") != false) {
        echo '<script>getObj("showTs").style.display = "block"; getObj("showAcc").style.display = "block";</script>';
        unlink("/tmp/shwlog");							}
    ?>
    </body>
</html>
