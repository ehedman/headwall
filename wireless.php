<?php 
    /*
     * wireless.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';
    include 'cgi-bin/country.php';

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>"; exit;}
    //echo "<pre>"; print_r($_GET); echo "</pre>";

    $connect = 0;

    isset($_GET['connect']) ?  $connect = $_GET['connect'] : $connect = 0;

    if ( (count($_POST) && $_POST["POST_ACTION"] == "OK") || $connect == "1" ) {

        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child
            sleep(6);
            if ($_GET[connect] == "1") {
                $a=array();
                $fd = fopen("/tmp/scanlist", "r");
                $indx=1;
                while (!feof($fd)) {
                    $a=explode("," ,fgets($fd),7);
                    if (!strlen($a[1])) continue;
                    if ($indx++ == $_GET[ckssidnum]) {
                        $indx = 0; 
                        break;
                    }   
                }
                @fclose($fd);
                @unlink("/tmp/scanlist");
                if (!$indx && $a[1]) {
                    switch ($a[5]) {
                        case 'WPA-PSK': // TKIP(2) only
                            do_setwpapsk("WPA",urlencode($a[1]),$_GET[ck_passp],$_GET[ck_cipher], g_wlanif());
                        break;
                        case 'WPA2-PSK': // TKIP(2) and AES(3)
                            do_setwpapsk("WPA2", urlencode($a[1]),$_GET[ck_passp],$_GET[ck_cipher], g_wlanif());
                        break;
                        default:
                            if (count($a) == 5)
                                do_setwep(urlencode($a[1]),$_GET[ck_passp], g_wlanif());
                        break;           
                    }
                }
            }
            if (count($_POST)) {
                if (g_mode() == 1) {
                    do_sethostapd($_POST['f_auth_type'], g_wlanif(), urlencode($_POST[f_ssid]),
                                $_POST[f_wpa_psk],$_POST[f_cipher], $_POST['WChannel'],
                                $_POST['WMode'], $_POST['WCountry'], $_POST['WVisibility'], $_POST['WDriver']);

                } else if (g_mode() == 2) {
                    switch ($_POST['f_auth_type']) {
                         case '1':
                                do_setwep(urlencode($_POST[f_ssid]), $_POST[f_wep_pw], g_wlanif());
                            break;
                        case '2':
                                do_setwpapsk("WPA",urlencode($_POST[f_ssid]),$_POST[f_wpa_psk],$_POST[f_cipher], g_wlanif());
                            break;   
                        case '3':
                                do_setwpapsk("WPA2",urlencode($_POST[f_ssid]),$_POST[f_wpa_psk],$_POST[f_cipher], g_wlanif());
                            break;
                        default:
                        break;
                    }
                } 
            }
            @system("echo 'DEST=127.0.0.1; INTERFACES=".g_lan().";' > /etc/default/watchdog.dest");                 
        } else {
            // We are the parent
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds=40&loc='.$_SERVER['SCRIPT_NAME']);
            exit;
        }
    }
?>
<!DOCTYPE html>
<html>
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
   
	f.enable.checked = <?php echo empty(g_wlanif())==true? 'false':'true'; ?>;
	f.ssid.value = "<?php p_wssid(); ?>";
		
     // WPA or WEP
    f.securityType.selectedIndex = <?php echo g_security()==1? "0":"1"; ?>;	
    f.wepKeyLenght.selectedIndex = 0;
    f.wepPPHstring.value = "<?php g_passph('wep',1); ?>";
    f.chiperType.selectedIndex=<?php echo g_cipher(); ?>;
    f.wpaMode.selectedIndex ="<?php echo g_wpamode(); ?>";
	f.wpaPPHstring.value ="<?php g_passph(); ?>";

	checkWiFienable();
}

function checkWiFienable()
{
	var f=getObj("form");
	
	getObj("showSecurityMode").style.display = "none";
	getObj("showSecurityWEP").style.display = "none";
	getObj("showSecurityWPA").style.display = "none";
	getObj("showPSKpph").style.display = "none";
    <?php if (g_mode() == 1) { ?>getObj("showMasterMode").style.display = "none";<?php }?>

	if(f.enable.checked == true) {
		getObj("showSecurityMode").style.display = "";
        getObj("showWifi").style.display = "";
        <?php if (g_mode() == 1) { ?>getObj("showMasterMode").style.display = "";<?php }?>
		changeSecurityType()
	} else {
        getObj("showWifi").style.display = "none";
    }
}

function changeSecurityType()
{
	var f=getObj("form");

	getObj("showSecurityWEP").style.display = "none";
	getObj("showSecurityWPA").style.display = "none";
	getObj("showPSKpph").style.display = "none";

	if(f.securityType.value == 1) {
		getObj("showSecurityWEP").style.display = "";
	} else if(f.securityType.value == 2) {
		getObj("showSecurityWPA").style.display = "";
		getObj("showPSKpph").style.display = "";
	}
}

function doWnetScan()
{
    var options='toolbar=0,status=0,menubar=0,scrollbars=1,location=0,directories=0,resizable=1,width=700,height=400';
	window.open("wifi_survey.php",'_blank',options);
}

function checkPage()
{
	var f=getObj("form");

    if(isFieldBlank(f.ssid.value))
    {
        alert("The Wireless Network Name (SSID) field cannot be blank.");
	    f.ssid.focus();
        return false;
    }
    if(!isFieldAscii(f.ssid.value))
    {
        alert("The Wireless Network Name (SSID) field has invalid characters.");
	    f.ssid.focus();
        return false;
    }

    f.f_enable.value = (f.enable.checked==true? 1:0);
    f.f_ssid.value   = f.ssid.value;

    if (f.securityType.value == 1) {  // wep
        if(isFieldBlank(f.wepPPHstring.value))
        {
            alert("The WEP Key field cannot be blank.");
	        f.wepPPHstring.focus();
            return false;
        }
        if(!isFieldAscii(f.wepPPHstring.value))
        {
            alert("The WEP Key field has invalid characters.");
	        f.wepPPHstring.focus();
            return false;
        }
        f.f_auth_type.value = 1;

    } else if(f.securityType.value == 2) { // wpa(2)
        if(isFieldBlank(f.wpaPPHstring.value))
        {
            alert("The Passphrase (WPA-PSK) field cannot be blank.");
	        f.wpaPPHstring.focus();
	        return false;
        }
        if(!isFieldAscii(f.wpaPPHstring.value))
        {
            alert("The Passphrase (WPA-PSK) field has invalid characters.");
	        f.wpaPPHstring.focus();
	        return false;
        }
        if (f.wpaMode.value == 1)      
            f.f_auth_type.value = 3;
        else
            f.f_auth_type.value = 2;
    }

    f.f_cipher.value  = f.chiperType.value==""? "na":f.chiperType.value;
    f.f_wep_pw.value  = f.wepPPHstring.value==""? "na":f.wepPPHstring.value;
    f.f_wpa_psk.value = f.wpaPPHstring.value==""? "na":f.wpaPPHstring.value;

    f.POST_ACTION.value = "OK";
	f.submit();

	return true;
}
        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input type="hidden" name="POST_ACTION" value="" >
        <input type="hidden" name="f_enable"    value="">
        <input type="hidden" name="f_ssid"      value="">
        <input type="hidden" name="f_auth_type" value="">
        <input type="hidden" name="f_cipher"    value="">
        <input type="hidden" name="f_wep_pw"    value="">
        <input type="hidden" name="f_wpa_psk"   value="">
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
                            <li><div class="leftnavLink"><a href="/network.php">Network</a></div></li>
                            <li><div id="left" class="navThis">Wireless</div></li> 
                            <li><div class="leftnavLink"><a href="/dhcp.php">DHCP</a></div></li>
                            <li><div class="leftnavLink"><a href="/dns.php">DNS</a></div></li>
                            <li><div class="leftnavLink"><a href="/firewall.php">Security</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Wireless Network Settings</h1>
                        Use this section to configure the wireless settings for your <?php p_mode(); ?>.
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
		            </div><div class="vbr"></div>

		            <div class="actionBox">
			            <h2 class="actionHeader">SSID Profile</h2>
			            <table>
                            <tr>
				                <td class="raCB" style="width: 40%">Enable Wireless :&nbsp;</td>
				                <td class="laCB">
					                <input id="enable" type="checkbox" onClick="checkWiFienable()" value="0">
				                </td>
			                </tr>
                        </table>
                        <div id="showWifi">
                            <table><?php if (g_mode() == 2) { ?>
			                    <tr>
				                    <td class="raCB" style="width: 40%">Wireless Sites :&nbsp;</td>
				                    <td class="laCB">
				                        <input type="button" value="Survey" onClick="doWnetScan()">
				                    </td>
			                    </tr><?php }?>

			                    <tr>
				                    <td class="raCB" style="width: 40%">Wireless Network Name (SSID) :&nbsp;</td>
				                    <td class='laCN'>
					                    <input id="ssid" type="text" size="20" maxlength="32" value="">
				                    </td>
			                    </tr>
                            </table>
		                </div>
		            </div><div class="vbr"></div>

                    <div class="actionBox" id="showSecurityMode">
			            <h2 class="actionHeader">Wireless Security Mode</h2>
			            <table>
			                <tr>
				                <td class="raCB" style="width: 40%">Security Mode :&nbsp;</td>
				                <td class="laCB">
				                    <div id="get_ap_sec">
					                    <select id="securityType" onchange="changeSecurityType()">
                                            <option value="1"<?php if (g_mode() == 1) { ?> style="display:none"<?php }?>>WEP</option>
                                            <option value="2">WPA-Personal</option>
                                        </select>
				                    </div>
				                </td>
			                </tr>
			            </table>
		            </div><div class="vbr"></div>

                    <div class="actionBox" id="showSecurityWEP">
			            <h2 class="actionHeader">WEP</h2>
			            <table>
			                <tr>
				                <td class="raCB" style="width: 40%">WEP Key Length :&nbsp;</td>
				                <td class="laCB">
					                <select id="wepKeyLenght" onchange="changeWEPlenght()">
						                <option value="1">64Bit (10 hex digits)</option>
						                <option value="2">64Bit (5 ascii characters)</option>
						                <option value="3">128Bit (26 hex digits)</option>
						                <option value="4">128Bit (13 ascii characters)</option>
					                </select>
				                </td>
			                </tr>
			                <tr>
				                <td class="raCB">WEP Key value :&nbsp;</td>
				                <td class="laCB">
					                <input id="wepPPHstring" size="26" maxlength="26" value="" type="text">
				                </td>
			                </tr>
			            </table>
		            </div>

                    <div class="actionBox" id="showSecurityWPA">
			            <h2 class="actionHeader">WPA</h2>
			            <table>
			                <tr>
				                <td class="raCB" style="width: 40%">WPA Mode :&nbsp;</td>
				                <td class="laCB">
					                <select id="wpaMode">
						                <option value="1">WPA2</option>
						                <option value="2">WPA</option>
					                </select>
				                </td>
			                </tr>
			                <tr>
				                <td class="raCB">Cipher Type :&nbsp;</td>
				                <td class="laCB">
					                <select id="chiperType">
					                    <option value="2">TKIP</option>
					                    <option value="3">AES</option>
					                </select>
				                </td>
			                </tr>
                        </table>
		            </div>

                    <div class="vbr"></div>
                    <div class="actionBox" id="showPSKpph">
			            <h2 class="actionHeader">Pre-Shared Key</h2>
			            <table>
				            <tr>
					            <td class="raCB" style="width: 40%">Passphrase (WPA-PSK) :&nbsp;</td>
					            <td class="laCB">
						            <input id="wpaPPHstring" size="40" maxlength="64" value="" type="text">
					            </td>
				            </tr>
			            </table>
		            </div>

                    <?php if (g_mode() == 1) { ?>
                    <div class="vbr"></div>
                    <div class="actionBox" id="showMasterMode">
			            <h2 class="actionHeader">Wireless Radio</h2>
			            <table>
				             <tr>
				                <td class="raCB" style="width: 40%">Wireless Channel :&nbsp;</td>
				                <td class="laCB">
					                <select id="WChannel" name="WChannel">
					                    <option value="1"<?php p_chansel(1)?>>1</option>
                                        <option value="2"<?php p_chansel(2)?>>2</option>
                                        <option value="3"<?php p_chansel(3)?>>3</option>
                                        <option value="4"<?php p_chansel(4)?>>4</option>
                                        <option value="5"<?php p_chansel(5)?>>5</option>
                                        <option value="6"<?php p_chansel(6)?>>6</option>
                                        <option value="7"<?php p_chansel(7)?>>7</option>
                                        <option value="8"<?php p_chansel(8)?>>8</option>
                                        <option value="9"<?php p_chansel(9)?>>9</option>
                                        <option value="10"<?php p_chansel(10)?>>10</option>
                                        <option value="11"<?php p_chansel(11)?>>11</option>
                                        <option value="12"<?php p_chansel(12)?>>12</option>
                                        <option value="13"<?php p_chansel(13)?>>13</option>
					                </select>
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB" style="width: 40%">Wireless Mode :&nbsp;</td>
				                <td class="laCB">
					                <select id="WMode" name="WMode">
                                        <option value="a"<?php p_modesel("a")?>>802.11a only</option>
                                        <option value="b"<?php p_modesel("b")?>>802.11b only</option>
                                        <option value="g"<?php p_modesel("g")?>>802.11g only</option>
					                    <option value="gn"<?php p_modesel("gn")?>>Mixed 802.11g and 802.11n</option>    
					                </select>
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB" style="width: 40%">Country :&nbsp;</td>
				                <td class="laCB">
					                <select id="WCountry" name="WCountry">

<?php
    $sel="";
    $csel=g_countrysel();
    foreach ($ccodes as $cc => $name) {
        if ($csel == $cc) $sel=' selected="selected"'; else $sel="";

        echo  "                                        <option value=\"$cc\"$sel>$name</option>\n";
    }
?> 
					                </select>
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB" style="width: 40%">Visibility Status :&nbsp;</td>
				                <td class="laCB">
					                <select id="WVisibility" name="WVisibility">
                                        <option value="0"<?php echo g_hiddenssid()=="0"? ' selected="selected"':""?>>Visible</option>
                                        <option value="1"<?php echo g_hiddenssid()=="1"? ' selected="selected"':""?>>Invisible</option>
					                </select>
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB" style="width: 40%">Driver :&nbsp;</td>
				                <td class="laCB">
					                <select id="WDriver" name="WDriver">
                                        <option  title="Set to rtl871xdrv for Raspberry Pi" value="rtl871xdrv"<?php echo g_wdriver()=="0"? ' selected="selected"':""?>>rtl871xdrv</option>
                                        <option value="nl80211"<?php echo g_wdriver()=="1"? ' selected="selected"':""?>>nl80211</option>
					                </select>
				                </td>
			                </tr>
			            </table>
		            </div>
                    <?php }?>

                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                    <br>
                    Specify the SSID which you want your <?php p_mode(); ?> to <?php echo g_mode() == 1? 'manage':'connect to'; ?> as well as
                    the authentication data.<?php if (g_mode() == 2) {?><br><br>You may use the Survey feature to connect
                    to a  wireless Access Point (AP) in the  WiFi  neighborhood with a
                    passphrase as the sole typed input.<?php }?>               
                    <br><br>
                    <strong>NOTE!</strong> You may have to re-enable the watchdog (advanced settings tab)
                        if changes has  been made here.
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
