<?php 
    /*
     * advanced.php
     *
     *  Copyright (C) 2013-2024 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>";}

    if (count($_POST) && $_POST["POST_ACTION"] == "OK") {


        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child
            @system("/usr/bin/systemctl stop transmission-daemon.service");
            do_transmission(implode(" ", $_POST));
            if ($_POST['f_tr_enabled'] == 0)
                @system("/usr/bin/systemctl disable transmission-daemon.service");
            else {
                @system("/usr/bin/systemctl enable transmission-daemon.service");
                @system("/usr/bin/systemctl restart transmission-daemon.service");
            }
            if (g_srvstat("shorewall") == true)
                do_firewall_mgm("restart");

        } else {
            // We are the parent
            $tmo=10;
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds='.$tmo.'&loc='.$_SERVER['SCRIPT_NAME']);
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

<?php include 'inc/general.js.php' ?>

<?php setTimeout(); ?>

function initPage()
{
    var f=getObj("form");
    f.tr_enabled.checked=<?php echo g_srvstat("transmission-daemon")? "true":"false" ?>;
    f.tr_port.value=<?php echo trim(exec("grep rpc-port ".g_trcfg()." | awk -F: '{gsub(/,/,\"\"); print \$2}'")); ?>;
    f.tr_user_name.value="<?php echo trim(exec("grep rpc-username ".g_trcfg()." | awk -F\\\" '{gsub(/,/,\"\"); print \$4}'")); ?>";
    
    if (<?php echo trim(exec("grep rpc-authentication-required ".g_trcfg()." | awk -F: '{gsub(/,/,\"\"); print \$2}'")); ?>)
    {
        f.tr_auth.checked=true;
        getObj("show_auth").style.display = "block";
    } else getObj("show_auth").style.display = "none";

    return true;
}

function checkPage()
{
	var f=getObj("form");

    if (isFieldBlank(getObj("tr_port").value) == true) {
        getObj("tr_port").select();
        alert("Server Port field is blank");
        return false;
	} 

    if (isDecimal(getObj("tr_port").value) == false) {
        getObj("tr_port").select();
        alert("Server Port field is not a number");
        return false;
	}

    if (getObj("tr_folder").value == "none") {
        alert("No download folder selected");
        return false;
    }

    if (f.tr_auth.checked == true) {
        if (isFieldBlank(getObj("tr_user_name").value) == true) {
            getObj("tr_user_name").select();
            alert("User Name field is blank");
            return false;
	    }
        if (isFieldAscii(getObj("tr_user_name").value) == false) {
            getObj("tr_user_name").select();
            alert("User Name field is garbled");
            return false;
	    }
        if (isFieldBlank(getObj("tr_user_pw").value) == true) {
            getObj("tr_user_pw").select();
            alert("User Password field is blank");
            return false;
	    }
        if (isFieldAscii(getObj("tr_user_pw").value) == false) {
            getObj("tr_user_pw").select();
            alert("User Password field is garbled");
            return false;
	    }
        f.f_tr_auth.value=1;
    }


    f.POST_ACTION.value = "OK";
	f.submit();

	return true;
}

function setEnable(obj)
{
    var f=getObj("form");

    f.f_tr_enabled.value = obj.checked == true? 1:0;

    return true; 
}

function setAuth(obj)
{
    var f=getObj("form");
    if (obj.checked == true) {
       getObj("show_auth").style.display = "block";
        f.f_tr_auth.value = 1;
    } else {
        getObj("show_auth").style.display = "none";
        f.f_tr_auth.value = 0;
    }
}

function setDlf()
{
    var f=getObj("form");
    var sf="<?php echo trim(exec("grep download-dir ".g_trcfg()." | awk -F/ '{gsub(/\",/,\"\"); print \$(NF-1)}'")); ?>";

    var fld=f.tr_folder;

    for (var i = 0; i < fld.length; i++)
    {
        if (fld.options[i].text == sf) {
            fld.selectedIndex=i;
        }
    }
}

        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION"   value="0"   type="hidden">
        <input name="f_tr_enabled"  value="<?php echo g_srvstat("transmission-daemon")? "1":"0"; ?>"   type="hidden">
        <input name="f_tr_auth"     value="0"   type="hidden">

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
                            <li><div class="leftnavLink"><a href="/transmission.php">Transmission</a></div></li>                         
                            <li><div id="left" class="navThis">Settings</div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Settings</h1>
                        Here you can setup your <?php p_mode(); ?> to act as a BitTorrent Server for for most computers on your LAN.
                        <br><br>
                        <?php if (g_srvstat("smbd")) {?><input value="Save Settings" type="submit">&nbsp;<?php }?>
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php echo g_srvstat("transmission-daemon")? '<b style="color:#090;">Transmission Service is active</b>':'<b style="color:#f00;">Transmission Service is disabled.</b>'; ?>
		            </div><div class="vbr"></div>

                    <div class="actionBox">
	                    <h2 class="actionHeader">Transmission Settings</h2>
                        This section manages the settings for Transmission.<br><br>
                        <table>
                            <tr>
				                <td class="raCN" style="width:30%"><b>Service enabled :</b></td>
				                <td>&nbsp;
                                    <input type="checkbox" id="tr_enabled" onchange="setEnable(this);" <?php echo g_srvstat("smbd")? "":'disabled="disabled"'; ?>>
                                </td>
                            </tr>
                            <tr>
				                <td class="raCN"><b>Server Port :</b></td>
				                <td>&nbsp;<input style="width:8%" type="text" id="tr_port" name="tr_port" maxlength="4" value=""></td>
			                </tr>
                            <tr>
				                <td class="raCN"><b>Download folder :&nbsp;</b></td>
                                <td>
                                    <select id="tr_folder" name="tr_folder">
                                        <option value="none">none</option><?php system('cifsshares | awk -F, \'{ printf "<option value=\"%s\">%s</option>", $3, $1}\''); ?>
                                    </select>&nbsp;/transmision
                                    <script>setDlf();</script>
                                </td>
                            </tr>
                            <tr>
				                <td class="raCN"><b>Authentication-required :</b></td>
				                <td>&nbsp;
                                    <input type="checkbox" id="tr_auth" onchange="setAuth(this);">
                                </td>
                            </tr>
                        </table>
                        <div id="show_auth">
                            <table>
                                <tr>
                                    <td class="raCN" style="width:30%"><b>User Name :</b></td>
                                    <td>&nbsp;<input type="text" id="tr_user_name" name="tr_user_name" maxlength="60" value=""></td>
                                </tr>
                                <tr>
                                    <td class="raCN"><b>Password :</b></td>
                                    <td>&nbsp;<input type="text" id="tr_user_pw" name="tr_user_pw" maxlength="60" value=""></td>
			                    </tr> 
                            </table>
                        </div>
                    </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    To enable this service, you must have a disk partition and shares defined in the STORAGE menu.
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
