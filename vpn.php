<?php
    /*
     * vpn.php
     *
     *  Copyright (C) 2013-2024 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

     include 'cgi-bin/common.php';

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>";exit;}

     if (count($_POST) && $_POST["POST_ACTION"] == "OK") {

        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child

            $i=1;
            while (isset($_POST['vpn_'.$i.'_secr'])) { // simple hiding of passwords
                if ($_POST['vpn_'.$i.'_sts'] == '2') {
                    $i++;
                    continue;
                }
                $_POST['vpn_'.$i.'_secr'] = base64_decode($_POST['vpn_'.$i.'_secr']);
                $i++;
            }
           
            $ns1=g_srvstat("named")? g_ip(g_lan()):"8.8.8.8";

            do_ddns($_POST['ddns_enabled'], $_POST['ddns_domain'],  $_POST['ddns_account'], $_POST['ddns_pw1'], $_POST['provider'], g_lan());               
            do_vpn(g_lan()." ".VPNET." ".$ns1." ".implode(" ", $_POST));

        } else { 
            //header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds=24&loc='.$_SERVER['SCRIPT_NAME']);
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds=20&loc='.$_SERVER['SCRIPT_NAME']);
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
function do_timeout()
{
	self.location.href="/logout.php";
}
<?php setTimeout(); ?>

<?php include 'inc/general.js.php' ?>

function initPage()
{
	var f = getObj("form");

	f.ddns_pw2.value = f.ddns_pw1.value = "<?php echo trim(@exec("grep password= /etc/ddclient.conf | awk -F\' '{printf \"%s\", \$2}'")); ?>";
    f.ddns_account.value = "<?php echo trim(@exec("grep login= /etc/ddclient.conf | awk -F= '{printf \"%s\", \$2}'")); ?>";
    f.ddns_domain.value = "<?php echo trim(@exec("tail -1 /etc/ddclient.conf")); ?>";

    f.ddns_enable.checked = <?php echo g_srvstat("ddclient")? "true":"false"; ?>;
    f.vpn_enable.checked  = <?php echo g_srvstat("pptpd")? "true":"false"; ?>;
    
    f.vpn_type.options.selectedIndex = "<?php echo trim(@exec("grep type= /etc/ppp/pptpd-options | awk -F= '{printf \"%s\", \$2}'")); ?>";

    f.provider.options.selectedIndex = "<?php echo trim(@exec("grep provider= /etc/ddclient.conf | awk -F= '{printf \"%s\", \$2}'")); ?>";
}

function addUser()
{

    var f=getObj("form");

    var table = getObj("vpn-users"); 
    var rowIndx = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr").length;  

    if (isFieldBlank(f.vpn_user.value) == true) {
        f.vpn_user.select();
        alert("User Login field is blank");
        return false;
	}

    if (isFieldAscii(f.vpn_user.value) == false) {
	    f.vpn_user.select();
		alert("Illegal characters in User Login field");
		return false;
    }

    if (isFieldBlank(f.vpn_pw1.value) == true) {
		f.vpn_pw1.select();
		alert("Password field is blank");
		return false;
    }

    if (isFieldAscii(f.vpn_pw1.value) == false) {
        f.vpn_pw1.select();
	    alert("Illegal characters in Password field");
	    return false;
    }

    if (f.vpn_pw1.value.length < 6) {
        f.vpn_pw1.select();
	    alert("Password field is too short");
	    return false;
    }

    if (f.vpn_pw1.value != f.vpn_pw2.value) {
		alert("The New Password and the Confirmation Password are not matched");
		f.vpn_pw1.select();
	    return false;
    }

    for( var i=1; i < rowIndx; i++ ) { 
        if (f.vpn_user.value == getObj('vpn_'+i+'_user').value) {
            f.vpn_user.select();
            alert("Duplicate Name field");
            return false;
        }  
    }
   
    var row = table.insertRow(rowIndx);
    var user = row.insertCell(0);
    var secr = row.insertCell(1);
    var del = row.insertCell(2);

    user.innerHTML='<input readonly style="width:98%" name="vpn_'+rowIndx+'_user" id="vpn_'+rowIndx+'_user" value="'+f.vpn_user.value+'" maxlength="60" type="text">';
    secr.innerHTML='<input readonly style="width:98%" name="vpn_'+rowIndx+'_secr" id="vpn_'+rowIndx+'_secr" value="'+f.vpn_pw1.value+'" maxlength="40" type="text">';
    del.innerHTML='<input id="vpn_'+rowIndx+'_del" type="checkbox" onchange="setStatus('+rowIndx+');"><input id="vpn_'+rowIndx+'_sts" name="vpn_'+rowIndx+'_sts" value="2" type="hidden">';
    
    restartTimeout();

    return true;
}

function setStatus(itm)
{
    getObj('vpn_'+itm+'_sts').value = getObj('vpn_'+itm+'_del').checked? "0":"1"
    return true;
}

function checkPage()
{
	var f=getObj("form");
	

    if (isFieldBlank(f.ddns_domain.value) == true) {
        f.ddns_domain.select();
		alert("Domain is blank for DDNS");
		return false;
    }

    if (isFieldAscii(f.ddns_domain.value) == false) {
        f.ddns_domain.select();
	    alert("Illegal characters in Domain field for DDNS");
	    return false;
    }

    if (isFieldBlank(f.ddns_account.value) == true) {
        f.ddns_account.select();
	    alert("Account login is blank for DDNS");
	    return false;
    }

    if (isFieldAscii(f.ddns_account.value) == false) {
        f.ddns_account.select();
	    alert("Illegal characters in Account login field for DDNS");
	    return false;
    }

    if (isFieldBlank(f.ddns_pw1.value) == true) {
        f.ddns_pw1.select();
	    alert("Password is blank for DDNS");
	    return false;
    }

    if (isFieldAscii(f.ddns_pw1.value) == false) {
		f.ddns_pw1.select();
	    alert("Illegal characters in Password field for DDNS");
		return false;
    }

    if (f.ddns_pw1.value.length < 6) {
        f.ddns_pw1.select();
	    alert("Password is too short for DDNS");
        return false;
    }

    if (f.ddns_pw1.value != f.ddns_pw2.value) {
		alert("The New Password and the Confirmation Password are not matched for DDNS.");
		f.ddns_pw1.select();
		return false;
    }


    if (isDecimal(f.vpn_connections.value) == false) {
            alert("Invalid value for Max Connections "+f.vpn_connections.value);
            f.vpn_connection.select();
            return false;
    }

    if (f.vpn_connections.value >100 || f.vpn_connections.value < 1) {
            alert("Invalid value ("+f.vpn_connections.value+") for Max Connections. Range is 1-100.");
            f.vpn_connections.select();
            return false;
    }
    
    f.ddns_enabled.value = f.ddns_enable.checked? "1":"0";
    f.vpn_enabled.value  = f.vpn_enable.checked?  "1":"0";

    f.POST_ACTION.value = "OK";
	f.submit();

	return true;
}

function showPass(id)
{
    var obj = document.getElementById(id);
    if (obj.type === "password") {
        obj.type = "text";
    } else {
        obj.type = "password";
    }
}

</script>
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION"   value="0"   type="hidden">
        <input name="ddns_enabled"  value="0"   id="ddns_enabled"   type="hidden">
        <input name="vpn_enabled"   value="0"   id="vpn_enabled"    type="hidden">
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
                            <li><div class="leftnavLink"><a href="/firewall.php">Security</a></div></li>
                            <li><div class="leftnavLink"><a href="/virtual.php">Virtual Server</a></div></li>
                            <li><div id="left" class="navThis">Inbound Acces</div></li> 
                            <li><div class="leftnavLink"><a href="/blacklist.php">URL Blacklist</a></div></li>
                            <li><div class="leftnavLink"><a href="/logfw.php">Firewall Log</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Inbound Access</h1>
                        Here you can setup your network for secure inbound acces using a Virtual Private Network (VPN).<br>
                        In order to conveniently access the VPN from the Internet by a Domain Name, you need to set up
                        a DDNS (Dynamic Name Resolution) service from a provider of such services for example www.dyn.com.<br>
                        If you have a fixed I.P address bound to your own registered domain, this DDNS service is not needed.
                        <br><br>
                        <?php if (g_srvstat("shorewall") == true && stat("/etc/shorewall/tunnels") != false) {?>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
                        <?php } else {?>
                        Enable VPN in the "Security" menu to see the Save button.    
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
                        <?php }?>
		            </div><br>
		            <div class="actionBox">
			            <h2 class="actionHeader">DDNS</h2>
                        DDNS is a dynamic DNS updating service that provides a corresponding domain
                        name for devices using dynamic IP addresses.<br><br>

			            <table>
                            <tr>
				                    <td class="raCB" style="width:40%; height:25px">Enable DDNS&nbsp;:</td>
				                    <td>&nbsp;
				                        <input type="checkbox" id="ddns_enable" checked="checked">
				                    </td>
				                </tr>
                             <tr>
				                <td class="raCN" style="width:40%"><b>Service Provider :</b></td>
				                <td>&nbsp;
                                    <select id="provider" name="provider">
                                        <option value="0">www.dyn.com</option>
                                        <option value="1">www.easydns.com</option>
                                        <option value="2">www.dslreports.com</option>
                                        <option value="3">www.zoneedit.com</option>
                                    </select>
                                </td>
			                </tr>
                            <tr>
				                <td class="raCN"><b>Domain :</b></td>
				                <td>&nbsp;<input style="width:50%" type="text" name="ddns_domain" maxlength="60" value=""></td>
			                </tr>
                            <tr>
				                <td class="raCN"><b>Account Login :</b></td>
				                <td>
                                    &nbsp;<input style="width:50%" type="password" name="ddns_account" id="ddns_account" maxlength="60" value="">
                                    <input type="checkbox" style="width: 20px;" onclick="showPass('ddns_account');">Show
                                    </td>
			                </tr>
			                <tr>
				                <td class="raCN"><b>Password :</b></td>
				                <td>
                                    &nbsp;<input style="width:50%" type="password" name="ddns_pw1" id="ddns_pw1" maxlength="40" onFocus="this.select();">
                                    <input type="checkbox" style="width: 20px;" onclick="showPass('ddns_pw1');">Show
                                </td>
			                </tr>
			                <tr>
				                <td class="raCN"><b>Verify Password :</b></td>
				                <td>&nbsp;<input style="width:50%" type="password" name="ddns_pw2" maxlength="40" onFocus="this.select();"></td>
			                </tr>
			            </table>
		            </div><div class="vbr"></div>

                    <div class="actionBox">
			            <h2 class="actionHeader">VPN</h2>
                        A virtual private network (VPN) extends your local network across a public network,
                        such as the Internet. It enables a remote computer to send and receive data across the Inetnet
                        as if it is directly connected to your local network<br><br>

			            <table>
                            <tr>
				                    <td class="raCB" style="width:40%; height:25px">Enable VPN&nbsp;:</td>
				                    <td colspan="2">&nbsp;
				                        <input type="checkbox" id="vpn_enable" checked="checked">
				                    </td>
				                </tr>
                             <tr>
				                <td class="raCN" style="width:40%"><b>Type :</b></td>
				                <td colspan="2">&nbsp;
                                    <select id="vpn_type" name="vpn_type">
                                        <option title="no encryption" value="0">PPTP</option>
                                        <option title="MPPE-128 encryption" value="1">PPTP-MPPE</option>
                                    </select>
                                </td>
			                </tr>
                            <tr>
				                <td class="raCN"><b>Max Connections :</b></td>
				                <td colspan="2">&nbsp;<input style="width:10%" type="text" name="vpn_connections" maxlength="3" value="10"></td>
			                </tr>
                            <tr>
				                <td class="raCN"><b>New User Login :</b></td>
				                <td colspan="2">&nbsp;<input type="text" id="vpn_user" maxlength="60" value=""></td>
			                </tr>
			                <tr>
				                <td class="raCN"><b>Password :</b></td>
				                <td colspan="2">&nbsp;<input type="text" id="vpn_pw1" maxlength="40"></td>
			                </tr>
			                <tr>
				                <td class="raCN"><b>Verify Password :</b></td>
				                <td>&nbsp;<input type="text" id="vpn_pw2" maxlength="40"></td>
                                <td>&nbsp;<input type="button" value="Add User" onclick="addUser();"></td>
			                </tr>
			            </table>
                        <div class="vbr"></div>
                        
                        <h2 class="actionHeader">VPN Users</h2><div class="vbr"></div>
                        <div style="overflow-y:auto;max-height:250px;">
                            <table id="vpn-users">
                                <tbody>                 
                                    <tr>         
                                        <td style="width:40%">User</td>
                                        <td style="width:40%">Secret</td>
                                        <td style="width:20%">Delete</td>
                                    </tr>


<?php
    if (($fd = fopen("/etc/ppp/chap-secrets", "r")) == NULL)
        die("bummer");
 
    $record=1;

    while (!feof($fd)) {

        $str=trim(fgets($fd));
        if ($str == "" || $str[0] == "#") continue;
  
        $a=explode("\t", $str);

        $user=$a[0]; $secr=$a[2]
?>
                                    <tr>
                                        <td><input readonly style="width:98%" id="vpn_<?php echo $record;?>_user" name="vpn_<?php echo $record;?>_user" value="<?php echo $user;?>" type="text"></td>
                                        <td><input readonly style="width:98%" id="vpn_<?php echo $record;?>_secr" name="vpn_<?php echo $record;?>_secr" value="<?php echo base64_encode($secr);?>" type="password"></td>
                                        <td>
                                            <input id="vpn_<?php echo $record;?>_del" type="checkbox" onchange="setStatus(<?php echo $record;?>);">
                                            <input id="vpn_<?php echo $record;?>_sts" name="vpn_<?php echo $record;?>_sts" value="1" type="hidden">
                                        </td>
                                    </tr>
<?php
        $record++; 
    } 
    @fclose($fd);
?>

                                </tbody>
                            </table>
                        </div>
		            </div>

                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                    <br>
                    <strong>NOTE!</strong> Please note that some service providers do not allow inbound access to home premises,
                    typically if the WAN side is through a 3G/4G modem.<br>Presumably they can be asked to
                    enable inbound access for your mobile subscription.<br><br>
                    <strong>NOTE!</strong> This service will not work if any downstream firewalls (your external modem) is active without having this <?php p_mode(); ?> in a DMZ zone.<br><br>
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
