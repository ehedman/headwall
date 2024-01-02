<?php
    /*
     * admin.php
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

    if (count($_POST) && strlen($_POST["admin_pw1"]) > 0) {
        system("echo ". md5(trim($_POST["admin_pw1"])) ." > ".$_SERVER["DOCUMENT_ROOT"]."/inc/gui.secrets");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="/css/bridge.css">
        <title><?php p_title(); ?></title> 
<script>
function do_timeout()
{
	self.location.href="/logout.php";
}
<?php setTimeout(); ?>

<?php include 'inc/general.js.php' ?>

function initPage()
{
	var f = getObj("form");
	f.admin_pw1.value = "XaXbXcXdXeXf";
    f.admin_pw2.value = "YfYeYdYcYbYa";
}

function checkPage()
{
	var f=getObj("form");
	
	if(f.admin_pw1.value != f.admin_pw2.value)
	{
		alert("The New Password and the Confirmation Password are not matched.");
		f.admin_pw1.select();
		return false;
	}

	return true;
}
</script>
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" onsubmit="return checkPage();">
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
	            <td class="topMenuThis"><a href="/admin.php">MAINTENANCE</a></td>
	            <td class="topMenuLink"><a href="/status.php">Status</a></td>
	            <td class="topMenuLink"><a href="/logout.php"><span id="timer"></span></a></td>		
            </tr>
        </table>
        <table id="mainContentTable">
            <tr style="vertical-align: top;">
                <td class="leftMenyContatiner">
                    <div class="leftNavLink">
                        <ul style="width: 100%">
                            <li><div id="left" class="navThis">Admin</div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Administrator Access Settings</h1>
                        The 'admin' account can access this <?php p_mode(); ?> and can change system properties. 
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
		            </div><br>
		            <div class="actionBox">
			            <h2 class="actionHeader">ADMIN PASSWORD</h2>
                            It is recommended that you change the password the first time you log in to the system
                            to keep your <?php p_mode(); ?> and firewall secure.
                            <br><br>
			            <b style="color: #002288">Please confirm the same password into both boxes.</b>
                        <br><br>
			            <table>
			                <tr>
				                <td class="raCN" style="width:40%"><b>Password :</b></td>
				                <td>&nbsp;<input type="password" name="admin_pw1" maxlength="40" onFocus="this.select();"></td>
			                </tr>
			                <tr>
				                <td class="raCN"><b>Verify Password :</b></td>
				                <td>&nbsp;<input type="password" name="admin_pw2" maxlength="40" onFocus="this.select();"></td>
			                </tr>
			            </table>
		            </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                    <br>
                    It is desirable to use a password to the <?php p_mode(); ?> to prevent unauthorized access to general
                    settings and security settings for the firewall.<br>
                    Write down this password because a lost password requires super user access to the <?php p_mode(); ?>.
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
