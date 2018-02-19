<? 
    /*
     * virtual.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';

    define('MPRF','dnat_');

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>";exit;}

    if (count($_POST) && $_POST["POST_ACTION"] == "OK") {

        $fwstat=g_srvstat("shorewall");
        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child
            do_vrules(implode(" ", $_POST));
            if ($fwstat == true)
                exec("/sbin/shorewall restart");
        } else {
            $del = $fwstat == true? 16:5;
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds="'.$del.'"&loc='.$_SERVER['SCRIPT_NAME']);
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
	var f=getObj("form");
    return true;
}

function checkPage()
{
	var f=getObj("form");

    var maxrow = getObj("vtable").getElementsByTagName("tbody")[0].getElementsByTagName("tr").length-1;
    
    maxrow=maxrow/2;

    for (var n=0; n <maxrow; n++) {

        var nm=getObj("name_"+n);

        if (nm.value == "0" && n == 0) continue;

        if (isFieldBlank(nm.value) == true) {
            nm.select();
            alert("Blank Host Name");
            return false;
        }
        if (! /^[a-zA-Z0-9\-]+$/.test(nm.value)) {
            nm.select();
            alert("Illegal characters in Name");
            return false;
        }

        var ip=getObj("ip_"+n);
        if (isFieldBlank(ip.value) == true) {
            ip.select();
            alert("Blank IP-address");
            return false;
        }
        if (isIPValid(ip) == false) {
            alert("Invalid IP-address "+ip.value);
            ip.select();
            return false;
        }
     
        var pu=getObj("publ_port_"+n);
        if (isDecimal(pu.value) == false) {
            alert("Invalid value for public port "+pu.value);
            pu.select();
            return false;
        }
        var pr=getObj("priv_port_"+n);
        if (isDecimal(pr.value) == false) {
            alert("Invalid value for private port "+pr.value);
            pr.select();
            return false;
        }
        if (getObj("proto_"+n).value == "oth") {
            var op=getObj("other_proto_t"+n);
            if (! /^[0-9]+$/.test(op.value)) {
                op.select();
                alert("A number expected in Other protocol");
                return false;             
            }
            if (parseInt(op.value) <1) {
                op.select();
                alert("Illegal number in Other protocol ("+op.value+")");
                return false;             
            }
            getObj("other_proto_"+n).value = op.value;
        } else getObj("other_proto_"+n).value = 0;

        var cb=getObj("cb_enable_"+n);
        var en=getObj("enable_"+n);
        en.value=(cb.checked? "1":"0");
    }

    f.POST_ACTION.value = "OK";
	f.submit();

	return true;
}

function onenable(n)
{
    var cb=getObj("cb_enable_"+n);
    var en=getObj("enable_"+n);
    getObj("show_status_"+n).style.display = "none"; 
    en.value=(cb.checked? "1":"0");
    getObj("delete_"+n).value = 0;
    getObj("status_"+n).innerHTML = (cb.checked? "Enable":"Disable");
    return true;
}

function ondelete(n) 
{
    getObj("delete_"+n).value=1;
    getObj("show_status_"+n).style.display = "block"; 
    getObj("cb_enable_"+n).checked = false;
    getObj("status_"+n).innerHTML = "";

    return true;
}

function oncname(n)
{
    var cn=getObj("cname_"+n);
    var ip=getObj("ip_"+n);
	var nm=getObj("rname_"+n);
    ip.value=cn.options[cn.selectedIndex].value;	
	nm.value=cn.options[cn.selectedIndex].text;

    return true;
}

function onproto(n)
{
    var cn=getObj("cname_"+n);
    var pt=getObj("proto_"+n);
    var op=getObj("other_proto_t"+n);
    var oph=getObj("other_proto_"+n);
    if (pt.value == "oth") {
        oph.value = op.value;
        op.disabled=false;
    } else {
        oph.value = op.value = 0;
        op.disabled=true;
    }

    return true;
}

function onsrv(n)
{
    var srv=getObj("srv_"+n);   
    var a=srv.options[srv.selectedIndex].value.split("/");
    var nm=getObj("name_"+n);
    if (a[0] == 0) {
        nm.value=getObj("publ_port_"+n).value=getObj("priv_port_"+n).value="";
        return true;
    }   
    var cn=getObj("cname_"+n);
    nm.value=srv.options[srv.selectedIndex].text;   
    getObj("priv_port_"+n).value = a[0];
    getObj("publ_port_"+n).value = a[1];
    var sel;
    switch (a[3]) {
        case 'TCP': sel=0; break;
        case 'UDP': sel=1; break;
        case 'Both': sel=2; break;
        case 'Other': sel=3; break;
        default: sel=0; break;
    }
    getObj("proto_"+n).options.selectedIndex=sel;
    if (sel < 3) {
        getObj("other_proto_t"+n).value=getObj("other_proto_"+n).value=0;
        getObj("other_proto_t"+n).disabled=true;
    }

    return true;
}

        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<? echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION" value="" type="hidden">
        <input name="macro_prefix" value="<? echo MPRF; ?>" type="hidden">
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
                            <li><div class="leftnavLink"><a href="/firewall.php">Security</a></div></li>
                            <li><div id="left" class="navThis">Virtual Server</div></li>
                            <li><div class="leftnavLink"><a href="/vpn.php">Inbound Access</a></div></li> 
                            <li><div class="leftnavLink"><a href="/blacklist.php">URL Blacklist</a></div></li>
                            <li><div class="leftnavLink"><a href="/logfw.php">Firewall Log</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Virtual Server</h1>
                        The Virtual Server option allows you to define a single public port on your <? p_mode(); ?> for
                        redirection to an internal LAN IP Address and Private LAN port if required.
                        This feature is useful for hosting online services such as FTP or Web Servers.
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
                        <div style="text-align:right"><? echo g_srvstat("shorewall")? '<b style="color:#090;">Firewall: active</b>':'<b style="color:#f00;">Firewall: stopped</b>' ?></div>
		            </div><div class="vbr"></div>
		            <div class="actionBox" style="overflow-y:auto;max-height:600px;">
	                    <h2 class="actionHeader">VIRTUAL SERVERS RULES</h2>
			            <table id="vtable"> 
                        <tr>       
                            <td colspan="3"></td>
                            <td style="text-align:center">Ports</td>
                            <td style="text-align:center">Traffic Type</td>
                            <td style="text-align:center">Status</td>
                        </tr>
<? 
	@system("grep ".MPRF." /etc/shorewall/rules |  sed s/'\t'/,/g | sed 's/:/,/g;s/# //g'|sed 's/".MPRF."//;s/(DNAT)//' >/tmp/rlist");

  	if (($fd = fopen("/tmp/rlist", "r")) == NULL)
    	die("bummer");
   	$i=0;
    $first=true;
    $ret=0;
    $enabled=0;

	while (!feof($fd)) {

        $chk="";
        $enabled=0;

        if ($first==true) {
            $a=array_fill (0 , 8, "0" );
            $sname="0";
        } else {
            $str=trim(fgets($fd));
            //echo $str;
            $a=preg_split("/,/", $str);
			if (count($a) <8) continue;
            //print_r($a);
            if (!strncmp("#", $a[0], 1)) {
                $chk="";
                $sname=substr($a[0],1);
            } else { $sname=$a[0]; $chk="checked ";}
        }

?>

                        <tr>
                            <td style="text-align:center; vertical-align:middle" rowspan="2">
                                <input <? echo $chk ?>type="checkbox" title="Disable/Enable rule" id="cb_enable_<? echo $i ?>" onChange="onenable(<? echo $i ?>)">
                                <? if ($first == false ) { ?><br><img alt="delete" id="cb_delete_<? echo $i ?>" title="Delete rule" onclick="ondelete(<? echo $i ?>)" src="/img/delete.png">                               
                                <?}?><input name="enable_<? echo $i ?>" id="enable_<? echo $i ?>" value="<? echo $enabled ?>" type="hidden">
                                <input name="delete_<? echo $i ?>" id="delete_<? echo $i ?>" value="0" type="hidden">
                                <input name="rname_<? echo $i ?>" id="rname_<? echo $i ?>" value="<? echo $a[7]; ?>" type="hidden">                           			
                            </td>
                            <td style="vertical-align:bottom">Name<br>
                                <input type="text" id="name_<? echo $i ?>" name="name_<? echo $i ?>" value="<? echo $sname; ?>" size="16" maxlength="31">
                            </td>
			                <td style="text-align:left;vertical-align:bottom">Service Name<br>
                                <select style="width:110px" id="srv_<? echo $i ?>" onChange="onsrv(<? echo $i ?>)">
                                    <option <? echo isset($a[4])? "selected ":"" ?>value="<? echo isset($a[4])? $a[4]:"0"; ?>/<? echo isset($a[6])? $a[6]:"0";?>"><? echo isset($sname)? $sname:"Custom"; ?></option>
                                    <option value="21/21/TCP">FTP</option>
                                    <option value="9418/9418/TCP">GIT</option>
                                    <option value="80/80/TCP">HTTP</option>
                                    <option value="443/443/TCP">HTTPS</option>
                                    <option value="1723/1723/TCP">PPTP-VPN</option>
                                    <option value="22/22/TCP">SSH</option>
                                    <option value="4040/4040/TCP">Subsonic</option>
                                    <option value="9091/9091/TCP">Transmission</option>
                                    <option value="5900/5900/TCP">VNC</option>
                                </select>
                            </td>
                            <td style="text-align:center;vertical-align:bottom">Public Port<br>
                                <input type="text" id="publ_port_<? echo $i ?>" name="publ_port_<? echo $i ?>" value="<? echo $a[6]; ?>" size="5" maxlength="5">
                            </td>
                            <td style="text-align:center">Protocol<br>
                                <select style="width:80px" id="proto_<? echo $i ?>" name="proto_<? echo $i ?>" onChange="onproto(<? echo $i ?>)">
                                    <option <? echo $a[5]=="tcp"? "selected ":"" ?>value="tcp">TCP</option>
                                    <option <? echo $a[5]=="udp"? "selected ":"" ?>value="udp">UDP</option>
                                    <option <? echo $a[5]=="tcp,udp"? "selected ":"" ?>value="tcp,udp">Both</option>
                                    <option <? echo is_numeric($a[5])&&$a[5]>0? "selected ":"" ?>value="oth">Other</option>
                                </select>
                            </td>
                            <td style="text-align:center" rowspan="2"><div id="status_<? echo $i ?>"><? if ($first==false) {echo strlen($chk)? '<b style="color:#090">Enabled</b>':'<b style="color:#900;">Disabled</b>'; } ?></div>

                                <div id="show_status_<? echo $i ?>" style="display:<? echo $first==true? "inline":"none" ?>">
                                <? if ($first == true ) { ?>
                                    <b style="color:#378">Add<br>New</b>
                                <? } else { ?>    <img alt="deleted" src="/img/deleted.png" style="height:12px">
                                <?}?></div>
                            </td>
                        </tr>
                        <tr>         
                            <td style="vertical-align:bottom">IP Address<br>
                                <input type=text id="ip_<? echo $i ?>" name="ip_<? echo $i ?>" value="<? echo $a[3]; ?>" size="16" maxlength="31">
                            </td>
			                <td style="text-align:left;vertical-align:bottom">Computer Name<br>                                
                                <select style="width:110px" id="cname_<? echo $i ?>" onChange="oncname(<? echo $i ?>)">
                                    <option <? echo isset($a[3])? "selected ":"" ?>value="<? echo $a[3]; ?>"><? echo $a[7]; ?></option>
                                    <? echo exec("cat /tmp/hostopts"); ?>

                                </select>
                            </td>
                            <td style="text-align:center;vertical-align:bottom">Private Port<br>
                                <input type="text" id="priv_port_<? echo $i ?>" name="priv_port_<? echo $i ?>" value="<? echo $a[4]; ?>" size="5" maxlength="5">
                            </td>
                            <td style="text-align:center;vertical-align:bottom">Other protocoll<br>
                                <input <? echo is_numeric($a[5])&&$a[5]>0? "":"disabled "; ?>type="text" id="other_proto_t<? echo $i ?>" value="<? echo is_numeric($a[5])? $a[5]:"0"; ?>" size="5" maxlength="5">
                                <input type="hidden" id="other_proto_<? echo $i ?>" name="other_proto_<? echo $i ?>" value="0">
                            </td>
                        </tr>
<?	
	$i++;
    $first=false;
	}
	@fclose($fd);
	@unlink("/tmp/rlist");
?>

			            </table>
		            </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    You can select a computer from the list of DHCP clients in the Computer Name drop down menu,
                    or you can manually enter the IP address of the computer at which you would like to open the specified port.<br><br>
                    <strong>NOTE!</strong> This service will not work if your provider does not allow inbound traffic to your
                    premises or if any downstream firewalls (your external modem) is active without having this <? p_mode(); ?> in a DMZ zone.<br><br>
                    <strong>NOTE!</strong> For this service a DDNS service may be usefull for you. Check the page Security->Inbound Access
                    fort more information.
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
