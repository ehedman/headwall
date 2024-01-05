<?php 
    /*
     * dhcp.php
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

    $dest="";

    if (count($_GET) && $_GET["refresh"] == 1) {
        @system("leases " .g_lan()." ".g_domain().">/tmp/reslist");
    }

    if (count($_POST) && $_POST["POST_ACTION"] == "OK") {

        if ($_POST["f_dhcp_enable"] == 1) {
            if (snCheck($_POST) == false)
                die();
        }

        if (!function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child

            $dhs = g_srvstat("named");

            @system("/usr/bin/systemctl stop isc-dhcp-server");

            do_dhcpd(implode(" ", $_POST));

            if (g_connectionType(g_wan()) === "static")
                do_setStatic(g_ip(g_wan()),g_mask(g_wan()),g_gateway(),gethostname(),$_POST["f_server_ip"],$_POST["f_server_nmsk"], g_mode(), g_wan(), g_lan(), g_lan1());
            else
                do_setDhcp(gethostname(),$_POST["f_server_ip"],$_POST["f_server_nmsk"], g_mode(), g_wan(), g_lan(), g_lan1());

            ifsrv(g_srvlan().":srv", g_srvip());

            if($dhs == true)
                @system("/usr/bin/systemctl restart bind9");

            if ($_POST["f_dhcp_enable"] == 1) {
                @system("/usr/bin/systemctl enable isc-dhcp-server");
                @system("/usr/bin/systemctl restart isc-dhcp-server");
            } else {
                @system("/usr/bin/systemctl disable isc-dhcp-server");
            }

            @system("alleases ".g_domain().">/tmp/dmlist &");
            @system("leases " .g_lan()." ".g_domain().">/tmp/reslist");
            @system("echo 'DEST=127.0.0.1; INTERFACES=".g_lan().";' > /etc/default/watchdog.dest");

        } else {
            if ($_SERVER["SERVER_ADDR"] == g_srvip())
                $dest="&ip=".$_SERVER["SERVER_ADDR"];
            else {
                if ($_SERVER["SERVER_ADDR"] != g_ip(g_lan()))
                    $dest="&ip=".g_ip(g_wan());
            }
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds=25&loc='.$_SERVER['SCRIPT_NAME'].$dest);
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

function onchange_dhcp()
{
    var f=getObj("form");

    if (f.dhcp_enable.checked == false) {
        getObj("show_dhcp_res").style.display = "none"; 
        getObj("show_dhcp_rlist").style.display = "none";
        getObj("show_dhcp_domain").style.display = "none";
        getObj("show_dhcp_items").style.display = "none";
        getObj("show_dhcp_dlist").style.display = "none";
        f.f_dhcp_enable.value = 0;
    } else {
        getObj("show_dhcp_res").style.display = "block";
        getObj("show_dhcp_rlist").style.display = "block";
        getObj("show_dhcp_domain").style.display = "block";
        getObj("show_dhcp_items").style.display = "block";
        getObj("show_dhcp_dlist").style.display = "block";
        f.f_dhcp_enable.value = 1;
    }

    return true;
}

function onchange_dns_relay()
{
     var f=getObj("form");

    if (f.dns_relay.checked == true) {
        getObj("show_domain_items").style.display = "none";
        f.f_dns_relay.value = 1;
    } else {
        getObj("show_domain_items").style.display = "block";
        f.f_dns_relay.value = 0; 
    }
    return true;
}

function checkPage()
{
	var f=getObj("form");

    if (isFieldBlank(f.server_ip.value) == true) {
        f.server_ip.select();
        alert("Blank Server IP-address");
        return false;
    }
    if (isFieldBlank(f.server_nmsk.value) == true) {
        f.server_nmsk.select();
        alert("Blank Server Subnet Mask");
        return false;
    }
    if (isFieldBlank(f.start_ip.value) == true) {
        f.start_ip.select();
        alert("Blank Start IP-address");
        return false;
    }
    if (isFieldBlank(f.end_ip.value) == true) {
        f.end_ip.select();
        alert("Blank End IP-address");
        return false;
    }
    if (isFieldBlank(f.lease_time.value) == true) {
        f.lease_time.select();
        alert("Blank Lease Time");
        return false;
    }
    if (isDecimal(f.lease_time.value) == false) {
        alert("Invalid Lease Time value "+f.lease_time.value);
        f.lease_time.select();
        return false;
    }

    if (isIPValid(f.server_ip) == false) {
        alert("Invalid Server IP-address "+f.server_ip.value);
        f.server_ip.select();
        return false;
    }

    if (isSubnetSame("<?php echo VPNET.".0" ?>", f.server_ip.value, f.server_nmsk.value) == false) {
        alert("The Server IP address ("+f.server_ip.value+") conflicts with the LAN's VPN subnet.\nUse another subnet.");
        f.server_ip.select();
        return false;
    }

    if (isNetmaskValid(f.server_nmsk) == false) {
        alert ("Invalid Subnet Mask");
        f.server_nmsk.select();
        return false;
    }

    if (isFieldBlank(f.domain_name.value) == true || isFieldAscii(f.domain_name.value) == false || f.domain_name.value == "unknown") {
        alert ("Invalid Domain Name");
        f.domain_name.select();
        return false;
    }

    if (isSubnetSame("<?php p_ip(g_wan()) ?>", f.server_ip.value, f.server_nmsk.value) == false) {
        alert("The Server IP address ("+f.server_ip.value+") conflicts with the WAN's subnet (<?php p_ip(g_wan()) ?>).\nUse another subnet.");
        return false;
    }

    if (isIPValid(f.start_ip) == false) {
        alert("Invalid Start IP-address "+f.start_ip.value);
        f.start_ip.select();
        return false;
    }
    if (isIPValid(f.end_ip) == false) {
        alert("Invalid End IP-address "+f.end_ip.value);
        f.end_ip.select();
        return false;
    }

    if (isSubnetSame(f.start_ip.value, f.server_ip.value, f.server_nmsk.value) == true) {
        alert("Wrong subnet on Start IP-address "+f.start_ip.value);
        f.start_ip.select();
        return false;
    }
    if (isSubnetSame(f.end_ip.value, f.server_ip.value, f.server_nmsk.value) == true) {
        alert("Wrong subnet on End IP-address "+f.end_ip.value);
        f.end_ip.select();
        return false;
    }

    if (f.dns_relay.checked == false) {
        if (isFieldBlank(f.domain_dns1.value) == true) {
            f.domain_dns1.select();
            alert("Blank Primary DNS IP-address");
            return false;
        }
        if (isIPValid(f.domain_dns1) == false) {
            alert("Invalid Primary DNS IP-address "+f.domain_dns1.value);
            f.domain_dns1.select();
            return false;
        }

        if (<?php echo g_srvstat("named")? "1":"0" ?>) {
            if (f.domain_dns1.value != f.server_ip.value) {
                f.domain_dns1.select();
                if (confirm('The Primary DNS Server is not set to\n this <?php p_mode(); ?>s\' running DNS Server. Correct?')) {
                    f.domain_dns1.value = f.server_ip.value;
                } else return false;
            }
            if (<?php echo g_spfhere()? "1":"0" ?>) {
                if (f.server_ip.value != "<?php p_ip(g_lan()) ?>")
                    alert("The Redirect spam to I.P property\nin the DNS page needs to be updated");
            }
        } else {
            if (f.domain_dns1.value == "<?php p_ip(g_lan()) ?>" || f.domain_dns2.value == "<?php p_ip(g_lan()) ?>") {
              
                if (!confirm("You have chosen this <?php p_mode(); ?> as the Primary DNS server for your network.\nPlease also enable the DNS server under the DNS menu or choose another DNS server."))
                    return false;
            }
        }

        if (isFieldBlank(f.domain_dns2.value) == false) {
            if (isIPValid(f.domain_dns2) == false) {
                alert("Invalid Secondary DNS IP-address "+f.domain_dns2.value);
                f.domain_dns2.select();
                return false;
            } 
        }
    }

    if (f.f_remove_res.value == 1) {
        var rows = getObj("restable").getElementsByTagName("tbody")[0].getElementsByTagName("tr").length;
        for( var i=1; i < rows-2; i++ ) {
            if (getObj("reserved_" +i +"_st").value == 0)
                getObj("reserved_" +i +"_st").value = 3
        }
    }

    f.f_server_ip.value = f.server_ip.value;
    f.f_server_nmsk.value = f.server_nmsk.value;
    f.f_start_ip.value = f.start_ip.value;
    f.f_end_ip.value = f.end_ip.value;
    f.f_lease_time.value = f.lease_time.value;
    f.f_domain_name.value = f.domain_name.value;
    <?php if (g_srvstat("named") == false) { ?> 
    f.f_domain_dns1.value = f.domain_dns1.value;
    f.f_domain_dns2.value = f.domain_dns2.value;
    <?php }?>

    if (f.dns_relay.checked == true && <?php echo g_srvstat("named")? "1==0":"1==1" ?>) {
        f.f_dns_relay.value = 1;
    } else { f.f_dns_relay.value = 0; }

    f.POST_ACTION.value = "OK";
	f.submit();

    return true;
}

function check_res()
{
    var f=getObj("form");

     if (rowIndx >= <?php echo C_MAX ?>) {
        alert("Attempt to exceed the limit for a class C netwok (<?php echo C_MAX ?>)");
        return false;
    }
    if (isFieldBlank(f.res_ip.value) == true) {
        alert ("The IP Address field cannot be blank");
        f.res_ip.select();
        return false;
    }
    if (isFieldBlank(f.res_mac.value) == true) {
        alert ("The MAC Address field cannot be blank");
        f.res_mac.select();
        return false;
    }
    if (!isIPValid(f.res_ip)) {
        alert("Invalid IP-address "+f.res_ip.value);
        return false;
    }
    if (isFieldBlank(f.res_nm.value) == true) {
        f.res_nm.select();
        alert("A computer Name is required");
        return false;
    }
    if (! /^[a-zA-Z0-9\-]+$/.test(f.res_nm.value)) {
        field_focus(f.res_nm, "**");
        alert("Illegal characters in Computer Name");
        return false;
    }

	var rs=f.start_ip.value.split('.')[3];
	var re=f.end_ip.value.split('.')[3];

    for( var i=1; i < rowIndx-1; i++ ) {
        if (f.res_st.value != 2) {
            if (f.res_ip.value == getObj("reserved_"+i+"_ip").value) {
                alert("The IP address ("+f.res_ip.value+") is already used."); return false;
            }
            if (isSubnetSame("<?php p_ip(g_lan()) ?>", f.res_ip.value, "<?php p_mask(g_lan()) ?>")) {
                alert("The IP address ("+f.res_ip.value+") is not in this DHCP Server's subnet."); return false;
            }
            if (f.res_nm.value == getObj("reserved_"+i+"_nm").value) {
                alert("The Name ("+f.res_nm.value+") is already used."); return false;
            }
            if (f.res_mac.value == getObj("reserved_"+i+"_mac").value) {
                alert("The MAC Address ("+f.res_mac.value+") is already used."); return false;
            }
        }
    
		var nip=f.res_ip.value.split('.')[3];
		if (nip > re || nip < rs)
			continue;
		else {
            f.res_ip.select();
			alert("Attempt to make a static reservation within the \nDynamic Adress Range ("+f.start_ip.value+"-"+f.end_ip.value+")");
			return false;
		}
    }
    
    var rowIndxD = getObj("restable-d").getElementsByTagName("tbody")[0].getElementsByTagName("tr").length;
        
    for( var i=1; i < rowIndxD; i++ ) {
        if (f.res_st.value!=2) {

            if (f.res_ip.value == getObj("d-reserved_"+i+"_ip").value) {
                alert("The IP address ("+f.res_ip.value+") is already used."); return false;
            }
            if (isSubnetSame("<?php p_ip(g_lan()) ?>", f.res_ip.value, "<?php p_mask(g_lan()) ?>")) {
                alert("The IP address ("+f.res_ip.value+") is not on this DHCP Server's subnet."); return false;
            }
            if (f.res_nm.value == getObj("d-reserved_"+i+"_nm").value) {
                alert("Name ("+f.res_nm.value+") is already used."); return false;
            }
            if (f.res_mac.value == getObj("d-reserved_"+i+"_mac").value) {
                alert("The MAC Address ("+f.res_mac.value+") is already used."); return false;
            }
        }

        var nip=f.res_ip.value.split('.')[3];
		if (nip > re || nip < rs)
			continue;
		else {
            f.res_ip.select();
			alert("Attempt to make a static reservation within the \nDynamic Adress Range ("+f.start_ip.value+"-"+f.end_ip.value+")");
			return false;
		}
        if (f.res_st.value==2) break;
    }

    if (!isMacVaild(f.res_mac)) {
        alert("Invalid MAC-address");
        f.res_mac.select();
        return false;
    }

    var st_val = (f.res_st.value==2? 2:1);

    var table = getObj("restable");   
    var row = table.insertRow(rowIndx);
   
    var mac = row.insertCell(0);
    var ip = row.insertCell(1);
    var resn = row.insertCell(2);
    var data = row.insertCell(3);
   
    var nindx = rowIndx;
    mac.innerHTML = f.res_mac.value;
    ip.innerHTML = f.res_ip.value;
    var rnnm = f.res_nm.value;
    resn.innerHTML='<input name="reserved_'+nindx+'_st" id="reserved_'+nindx+'_st" value="'+st_val+'" type="hidden">'+rnnm;  

    var stat='<input type="checkbox" id="reserved_'+nindx+'_item" checked="checked" onclick="check_resitem('+nindx+')">';
    var nmac='<input name="reserved_'+nindx+'_mac" id="reserved_'+nindx+'_mac" value="'+f.res_mac.value+'" type="hidden">';
    var nip='<input name="reserved_'+nindx+'_ip" id="reserved_'+nindx+'_ip" value="'+f.res_ip.value+'" type="hidden">'; 
    var rname='<input name="reserved_'+nindx+'_nm" id="reserved_'+nindx+'_nm" value="'+rnnm+'" type="hidden">';  

    data.innerHTML = stat+nmac+nip+rname;   
    data.style.textAlign = "center";

    rowIndx++;
    restartTimeout();

    f.res_st.value=0;

    return true;
}

function check_copy()
{

    var f=getObj("form");
    f.res_ip.value="<?php echo $_SERVER['REMOTE_ADDR']; ?>";
    f.res_mac.value="<?php system('arp -n | grep '.$_SERVER['REMOTE_ADDR']." | grep ether | awk '{print $3}' | tr -d '\n'"); ?>";

   
    f.res_nm.value="<?php system("if [ \"`pidof named`\" ]; then nslookup ".$_SERVER['REMOTE_ADDR']." localhost | grep name | awk '{gsub(\".".g_domain().".\",\"\");printf \"%s\", $4}'; fi"); ?>";
    if (f.res_nm.value == "")
        f.res_nm.value="<?php system('nbtscan -q '.$_SERVER['REMOTE_ADDR']." | awk '{print tolower(".'$2'.")}' | tr -d '\n'") ?>";

    return true;
}

function check_resitem(n)
{    
    var cb=getObj("reserved_" +n +"_item");
    var st=getObj("reserved_" +n +"_st");
    st.value = (cb.checked ? "1" : "0");

    return true;
}


function check_dynresitem(n)
{  
    var f=getObj("form");  
    var rnmac=getObj("d-reserved_" +n +"_mac");
    var rnip=getObj("d-reserved_" +n +"_ip");
    var rnnm=getObj("d-reserved_" +n +"_nm");

    f.res_ip.value=rnip.value;
    f.res_mac.value=rnmac.value;
    f.res_nm.value=rnnm.value;
   
    restartTimeout();
    
    f.res_st.value=2;
    getObj("d-reserved_" +n +"_btn").disabled=true;

    return true;
}

function check_remove()
{    
    var f=getObj("form");
    f.f_remove_res.value = (f.remove_res.checked ? 1:0);

    return true;
}

function get_mac(val)
{
    var f=getObj("form");
    var a = val.split('-');

    f.res_mac.value = a[0]; 
    f.res_ip.value  = a[1];

    return true;
}

var rowIndx=0;

function initPage()
{
    var f=getObj("form");

    if (<?php if(g_srvstat("dhcpd")) echo "true"; else echo "false"; ?>) {
        getObj("show_dhcp_res").style.display = "block"; 
        getObj("show_dhcp_rlist").style.display = "block";
        getObj("show_dhcp_domain").style.display = "block";
        getObj("show_dhcp_items").style.display = "block";
        getObj("show_dhcp_dlist").style.display = "block";
        f.f_dhcp_enable.value = 1;
    } else {
        getObj("show_dhcp_res").style.display = "none";
        getObj("show_dhcp_rlist").style.display = "none";
        getObj("show_dhcp_domain").style.display = "none";
        getObj("show_dhcp_items").style.display = "none";
        getObj("show_dhcp_dlist").style.display = "none";
        f.f_dhcp_enable.value = 0;
    }

    if (<?php if(g_dnsrelay()) echo "true"; else echo "false"; ?>) {
        getObj("show_domain_items").style.display = "none";
        f.f_dns_relay.value = 1;
    } else {
        getObj("show_domain_items").style.display = "block";
        f.f_dns_relay.value = 0; 
    }
    if (<?php if(g_srvstat("named")) echo "0"; else echo "1"; ?>) {
        getObj("show_dhcp_relay").style.display = "block";
        f.f_dns_relay.value = 0;
    }
   
    rowIndx = getObj("restable").getElementsByTagName("tbody")[0].getElementsByTagName("tr").length
    rowIndx--;

    return true;

}
        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION" value="" type="hidden">
        <input name="f_dhcp_enable" id="f_dhcp_enable"  value="0"   type="hidden">
        <input name="f_server_ip"	id="f_server_ip"    value="0"   type="hidden">
        <input name="f_server_nmsk"	id="f_server_nmsk"  value="0"   type="hidden">
        <input name="f_start_ip"    id="f_start_ip"     value="0"   type="hidden">
        <input name="f_end_ip"      id="f_end_ip"       value="0"   type="hidden">
        <input name="f_lease_time"  id="f_lease_time"   value="0"   type="hidden">
        <input name="f_remove_res"  id="f_remove_res"   value="0"   type="hidden">      
        <input name="f_domain_name" id="f_domain_name"  value="0"   type="hidden">
        <input name="f_domain_dns1" id="f_domain_dns1"  value="<?php p_domains(1); ?>" type="hidden">
        <input name="f_domain_dns2" id="f_domain_dns2"  value="<?php p_domains(2); ?>" type="hidden">
        <input name="f_dns_relay"   id="f_dns_relay"    value="0"   type="hidden">
        <input name="f_wan"         id="f_wan"          value="<?php echo g_wan(); ?>" type="hidden">
        <input name="f_lan"         id="f_lan"          value="<?php echo g_lan(); ?>" type="hidden">
        <input name="f_lan1"        id="f_lan1"         value="<?php echo g_lan1(); ?>" type="hidden">

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
                            <li><div class="leftnavLink"><a href="/network.php">Network</a></div></li>
                            <?php if (g_haswifi()) { ?><li><div class="leftnavLink"><a href="/wireless.php">Wireless</a></div></li><?php }?>
                            <li><div id="left" class="navThis">DHCP</div></li>
                            <li><div class="leftnavLink"><a href="/dns.php">DNS</a></div></li>
                            <li><div class="leftnavLink"><a href="/firewall.php">Security</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>LAN and DHCP Settings</h1>
                        Use this section to configure the internal network settings of your
                        <?php p_mode(); ?> and also to configure the built-in DHCP Server to assign IP
                        addresses to the devices on your network. 
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
		            </div><div class="vbr"></div>
		            <div class="actionBox">
			            <h2 class="actionHeader">LAN and DHCP Server Settings</h2>
			            <p>The DHCP server automatically assigns IP addresses to devices on your local network.
                            <br>To configure a static LAN address only, uncheck Enable DHCP.
                        </p>		
                        <div>
			                <table>    
                             <tr>
				                <td class="raCB" style="width: 40%">Server IP Address :</td>
				                <td class="laCB">&nbsp;
					                <input type="text" id="server_ip" size="16" maxlength="15" value="<?php p_srvip(); ?>">
				                </td>
			                </tr>
                            <tr class="show_dhcp_data">
				                <td class="raCB">Server Subnet Mask :</td>
				                <td class="laCB">&nbsp;
					                <input type="text" id="server_nmsk" size="16" maxlength="15" value="<?php p_mask(g_lan()); ?>">
				                </td>
			                </tr>
                           </table>
                        </div>
                        <div id="show_dhcp_items">
                            <table>
                                <tr>
				                    <td class="raCB" style="width: 40%">DHCP Adress Range :</td>
				                    <td class="laCBb">&nbsp;
					                    <input id="start_ip" size="16" maxlength="15" value="<?php p_startip(); ?>" type="text">
				                    &nbsp;to&nbsp;
					                    <input id="end_ip" size="16" maxlength="15" value="<?php p_endip(); ?>" type="text">
				                    </td>
			                    </tr>
                                <tr>
				                    <td class="raCB">Lease Time :</td>
				                    <td class="laCB">&nbsp;
					                    <input id="lease_time" size="16" maxlength="15" value="<?php p_leasetime(); ?>" type="text">
				                    </td>
			                    </tr>
                            </table>
                        </div>
                        <table>
                            <tr>
                                <td style="text-align:right">
                                    Enable DHCP&nbsp;&nbsp;
                                    <input type="checkbox" id="dhcp_enable" onChange="onchange_dhcp();"<?php if(g_srvstat("dhcpd")) echo ' checked="checked"'; ?>>&nbsp;&nbsp;
                                </td>
                            </tr> 
		                </table>                   
		            </div><div class="vbr"></div> 

                    <div class="actionBox" id="show_dhcp_domain">
			            <h2 class="actionHeader">Local Domain</h2>
                        <div id="show_domain_items">
			                <table>
			                <tr>
				                <td class="raCB" style="width: 40%">Domain Name :</td>
				                <td class="laCB">&nbsp;
				                <input id="domain_name" size="16" maxlength="32" value="<?php p_domain(); ?>" type="text">
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB">Primary DNS Server :</td>
				                <td class="laCB">&nbsp;
				                    <input <?php echo g_srvstat("named")? "disabled title='Defined by this DNS' ":"" ?>id="domain_dns1" size="16" maxlength="15" value="<?php p_domains(1); ?>" type="text">
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB">Secondary DNS Server :</td>
                                <td class="laCB">&nbsp;
                                    <input <?php echo g_srvstat("named")? "disabled title='Defined by this DNS' ":"" ?>id="domain_dns2" size="16" maxlength="15" value="<?php p_domains(2); ?>" type="text">
				                </td>
			                </tr>
			                </table>
                        </div>
                        <div id="show_dhcp_relay" style="display:none">
                            <table>
                                <tr>
                                    <td style="text-align:right">
                                        Enable DNS Relay&nbsp;&nbsp;
                                        <input type="checkbox" id="dns_relay" onchange="onchange_dns_relay();"<?php if(g_dnsrelay()) echo ' checked="checked"'; ?>>&nbsp;&nbsp;
                                    </td>
                                </tr> 
		                    </table>
                        </div>		
		            </div><div class="vbr"></div>

                    <div class="actionBox" id="show_dhcp_res">
			            <h2 class="actionHeader">Add DHCP Reservation</h2>
                        <table>
                            <tr>
				                <td class="raCB" style="width: 40%">MAC Address :</td>
				                <td class="laCB">&nbsp;
					                <input id="res_mac" size="16" maxlength="17" value="" type="text">
                                    <input id="res_st" value="0" type="hidden">
				                </td>
                                <td style="text-align:right">&nbsp;&nbsp;
                                    <input type="button" value="Your PC" onclick="check_copy();">
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB">IP Address :</td>
				                <td colspan="2" class="laCB">&nbsp;
					                <input id="res_ip" size="16" maxlength="20" value="" type="text">
                                </td>
			                </tr>
                            <tr>
                                <td class="raCB">Computer Name :</td>
                                <td class="laCB">&nbsp;
					                <input id="res_nm" size="16" maxlength="20" value="" type="text">
                                </td>
                                <td style="text-align:right">&nbsp;&nbsp;                        
                                    <input type="button" value="Add" onclick="check_res();">&nbsp;&nbsp;
                                </td>
                            </tr>              
                        </table>
                        </div><div class="vbr"></div>

                        <div class="actionBox" id="show_dhcp_rlist" style="overflow-y:auto;max-height:250px;">
			                <h2 class="actionHeader">DHCP Reservations List</h2>           
                            <table id="restable">
                                <tbody>                 
                                    <tr>
                                        <td style="width:100px">Mac-address</td>
                                        <td style="width:70px">IP-address</td>
                                        <td style="width:200px">Host Name @<?php p_domain() ?></td>
                                        <td style="width:20px">Enabled</td>
                                    </tr>
                                    <tr>
                                        <td><?php p_mac(g_lan()) ?></td>
                                        <td><?php p_ip(g_lan()) ?></td>
                                        <td><?php p_nodeName(); if (g_spfhere()&&g_srvstat("named")) {echo ": with URL filter "; p_spfredir();} ?></td>
                                        <td style="text-align:center">this <?php p_mode(); ?></td>
                                    </tr>

<?php

    if (($fd = fopen("/tmp/reslist", "r")) == NULL)
        die("bummer");

    $record=1;

    while (!feof($fd)) {

        $str=trim(fgets($fd));
        if ($str == "") continue;

        if (preg_match("/#/", $str) == true) {
            $checked=""; $st=0; $str=preg_replace('/#/', "", $str);
        } else {
            $checked=' checked="checked"'; $st=1;
        }
        
        $a=explode(",", $str);
        if ($a[3] == 0) continue;

        $mac=$a[0]; $name=$a[1]; $ip=$a[2];
?> 
                                <tr>
                                    <td><?php echo $mac;?></td>
                                    <td><?php echo $ip;?></td>
                                    <td><input name="reserved_<?php echo $record;?>_st" id="reserved_<?php echo $record;?>_st" value="<?php echo $st;?>" type="hidden"><?php echo $name;?></td>
                                    <td style="text-align:center">
                                        <input type="checkbox" id="reserved_<?php echo $record;?>_item"<?php echo$checked;?> onchange="check_resitem(<?php echo $record;?>)">
                                        <input name="reserved_<?php echo $record;?>_mac" id="reserved_<?php echo $record;?>_mac" value="<?php echo $mac;?>" type="hidden">
                                        <input name="reserved_<?php echo $record;?>_ip" id="reserved_<?php echo $record;?>_ip" value="<?php echo $ip;?>" type="hidden">
                                        <input name="reserved_<?php echo $record;?>_nm" id="reserved_<?php echo $record;?>_nm" value="<?php echo $name;?>" type="hidden">
                                    </td>
                                </tr>
<?php
        $record++;

    } 
    @fclose($fd);

?>
                                    <tr>
                                        <td colspan="4" style="text-align:right">
                                            Delete disabled items&nbsp;&nbsp;
                                            <input type="checkbox" id="remove_res" onchange="check_remove();">&nbsp;&nbsp;
                                            <input id="n_res" value="<?php echo $record ?>" type="hidden">
                                        </td>
                                    </tr> 
                                </tbody>
                            </table>
                        </div><div class="vbr"></div>

                        <div class="actionBox" id="show_dhcp_dlist" style="overflow-y:auto;max-height:250px;">
			                <h2 class="actionHeader">Dynamic DHCP Clients</h2>
                            <table id="restable-d">
                                <tbody>                 
                                    <tr>         
                                        <td style="width:100px">Mac-address</td>
                                        <td style="width:70px">IP-address</td>
                                        <td style="width:200px">Host Name @<?php p_domain() ?></td>
                                        <td style="width:20px">Reserve</td>
                                    </tr>
<?php
    if (($fd = fopen("/tmp/reslist", "r")) == NULL)
        die("bummer");
 
    $record=1;

    while (!feof($fd)) {

        $str=trim(fgets($fd));
        if ($str == "") continue;
  
        $a=explode(",", $str);
        if ($a[3] == 1) continue;

        $mac=$a[0]; $name=$a[2]; $ip=$a[1];
?>
                                    <tr>
                                        <td><?php echo $mac;?></td>
                                        <td><?php echo$ip;?></td>
                                        <td><?php echo $name;?></td>
                                        <td>
                                            <input style="width:100%" title="Add as static reservation" type="button" value="Add" id="d-reserved_<?php echo $record;?>_btn" onclick="check_dynresitem(<?php echo $record;?>)">
                                            <input id="d-reserved_<?php echo $record;?>_mac" value="<?php echo $mac;?>" type="hidden">
                                            <input id="d-reserved_<?php echo $record;?>_ip" value="<?php echo$ip;?>" type="hidden">
                                            <input id="d-reserved_<?php echo $record;?>_nm" value="<?php echo $name;?>" type="hidden">
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

                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                   <br>
                    If you already have a DHCP server on your network or are using static IP addresses on all the devices on
                    your network, uncheck Enable DHCP to disable this feature.
                    <br><br>if you have devices on your network that should always have fixed IP addresses,
                    add a DHCP Reservation for each such device outside of the DHCP address range.
                    <?php if (!g_srvstat("named")) { ?>
                    <br><br>
                    When DNS Relay is enabled, this <?php p_mode(); ?> will use the DNS servers (if any) obtained from the WAN interface,
                    otherwise, define them here for your local network.
                    <?php }?>

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
