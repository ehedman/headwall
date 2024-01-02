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

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>";exit;}

    if (count($_POST) && $_POST["POST_ACTION"] == "OK") {

    if (!function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child

            if (isset($_POST['reboot'])) {
                @system("reboot");
                exit;
            }

            if (isset($_POST['watchdog']) && isset($_POST['w_probe'])) {
                $if=$_POST['f_if'] == "lan"? g_lan():g_wan();
                @system("echo 'DEST=".$_POST['w_probe']."; INTERFACES=".$if.";' > /etc/default/watchdog.dest");
                @system("watchdogd start bg > /dev/null 2>&1 &");           
            } else {
                // Cannot stop once started! do something harmless.
                system("echo 'DEST=127.0.0.1; INTERFACES=".g_lan().";' > /etc/default/watchdog.dest");
            }
        } else {
            $sec = isset($_POST['reboot'])? 64:30;
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds='.$sec.'&loc='.$_SERVER['SCRIPT_NAME']);
            exit;
        }
    }  
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

function checkPage()
{
	var f=getObj("form");

    if (isFieldBlank(f.w_probe.value) == true) {
		f.w_probe.select();
		alert("Blank IP address");
		return false;
    }

    if (isIPValid(f.w_probe) == false) {
        f.w_probe.select();
        alert("Invalid IP address");
        return false;
    }

    f.f_if.value = isSubnetSame(f.w_probe.value, "<?php p_ip(g_lan());  ?>", "<?php p_mask(g_lan()) ?>") == false? "lan":"wan";

    f.POST_ACTION.value = "OK";
	f.submit();

	return true;
}
        </script> 
    </head>
    <body><script>onbody();</script>
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION" value="" type="hidden">
        <input name="f_if" id="f_if"  value="0"   type="hidden">
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
                            <li><div id="left" class="navThis">Advanced</div></li>
                            <li><div class="leftnavLink"><a href="/wd_log.php">Logs</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Settings</h1>
                        If you are not familiar with these Advanced settings, please leave them unmodified.
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
		            </div><div class="vbr"></div>

		            <div class="actionBox">
	                    <h2 class="actionHeader">SYSTEM SETTINGS</h2>
			            <table>      
			                <tr>
				                <td class="raCB" style="width: 30%"><b>Enable the watchdog timer :</b></td>
				                <td>&nbsp;<input type="checkbox" name="watchdog" <?php echo exec("watchdogd status")=="0"? 'checked="checked"':''; ?>></td>                                 
                                <td style="width: 45%">Forced reboot to prevent permanent lockups after prolonged network errors.</td>
			                </tr> 
                            <tr><td colspan="3">&nbsp;</td></tr>
                            <tr>
                                <td class="raCB"><b>Watchdog ping :</b></td>
                                <td><input name="w_probe" id="w_probe" type="text" maxlength="40" value="<?php echo exec("watchdogd probe"); ?>">&nbsp;</td>
                                <td>This host must answer to a ping within resonable time to avoid a system reboot.</td>
                            </tr>
                            <tr><td colspan="3">&nbsp;</td></tr>
			                <tr>
				                <td class="raCB"><b>Manual Reboot :</b></td>
				                <td  colspan="2">&nbsp;<input type="submit" name="reboot" value="Reboot The Device"></td>
			                </tr>
			            </table>
		            </div>

                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    It is recommended that you leave these parameters at their default values.<br>
                    Adjusting them could limit the performance of your system.<br><br>
                    If you see that the address "Watchdog ping" is set to 127.0.0.1, then this
                    system has decided to use an always safe target address, usually
                    because of a system reconfiguration or an attempt to disable the service here.<br>
                    A watchdog timer can not be stopped once it has started.
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
