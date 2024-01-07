<?php
    /*
     * virtual.php
     *
     *  Copyright (C) 2013-2019 by Erland Hedman <erland@hedmanshome.se>
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
    return true;
}

function checkPage()
{
	var f=getObj("form");

    var maxrow = getObj("vtable").getElementsByTagName("tbody")[0].getElementsByTagName("tr").length-1;
    
    maxrow=maxrow/2;

    for (var n=0; n <maxrow; n++) {

        var nm=getObj("name_"+n);

        if (nm.value == "0" && n == 0) {
            continue;
        }

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

function  onshowstatic(obj)
{
       getObj("show_static").style.display = obj.checked? "block" : "none";
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

function onhname(n)
{
    var cn=getObj("hosts_"+n);
    var ip=getObj("ip_"+n);
	var nm=getObj("hname_"+n);
    ip.value=cn.options[cn.selectedIndex].value;	
	nm.value=cn.options[cn.selectedIndex].text;

    return true;
}

function onproto(n)
{
    var cn=getObj("hosts_"+n);
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
    getObj("sname_"+n).value = srv.options[srv.selectedIndex].text;
    var cn=getObj("hosts_"+n);
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
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION" value="" type="hidden">
        <input name="macro_prefix" value="<?php echo MPRF; ?>" type="hidden">
        <table id="topContainer">
            <tr>
	            <td class="laCN">Project Page&nbsp;:&nbsp;<a href="<?php p_productHome(); ?>" target="_blank"><?php p_serverName(); ?></a></td>
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
                        The Virtual Server option allows you to define a single public port on your <?php p_mode(); ?> for
                        redirection to an internal LAN IP Address and Private LAN port if required.
                        This feature is useful for hosting online services such as FTP or Web Servers.
                        <br><br><?php if (g_srvstat("shorewall") == true) { ?>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button"><?php } ?>
                        <div style="text-align:right"><?php echo g_srvstat("shorewall")? '<b style="color:#090;">Firewall: active</b>':'<b style="color:#f00;">Firewall: stopped</b>' ?></div>
		            </div><div class="vbr"></div>
                    <?php if (g_srvstat("shorewall") == true) { ?>
		            <div class="actionBox" style="overflow-y:auto;max-height:600px;">        
	                    <h2 class="actionHeader">VIRTUAL SERVERS RULES</h2>
			            <table id="vtable"> 
                        <tr>       
                            <td colspan="3"></td>
                            <td style="text-align:center">Ports</td>
                            <td style="text-align:center">Traffic Type</td>
                            <td style="text-align:center">Status</td>
                        </tr>
<?php 
    @system("grep ".MPRF." /etc/shorewall/rules | sed s/'\t'/!/g | sed 's/:/!/g' | sed 's/".MPRF."//;s/(DNAT)//' >/tmp/rlist");
  	if (($fd = fopen("/tmp/rlist", "r")) == NULL) {
    	die("bummer");
    }
   	$i=0;
    $first=true;
    $ret=0;
    $enabled=0;

	while (!feof($fd)) {

        $chk="";
        $enabled=0;
       
        if ($first==true) {
            $a=array_fill (0 , 9, "0" );
            $sname="0";
            $hname="Select";
        } else {
            $str=trim(fgets($fd));
            //echo $str;
            $a=preg_split("/!/", $str);
			if (count($a) <9) continue;
            //print_r($a);
            $hname=substr($a[7],2);
            if (!strncmp("#", $a[0], 1)) {
                $chk="";
                $sname=substr($a[0],1);
            } else {
                $chk="checked ";
                $sname=$a[0];
            }
        }
?>

                        <tr>
                            <td style="text-align:center; vertical-align:middle" rowspan="2">
                                <input <?php echo $chk ?>type="checkbox" title="Disable/Enable rule" id="cb_enable_<?php echo $i ?>" onChange="onenable(<?php echo $i ?>)">
                                <?php if ($first == false ) { ?><br><img alt="delete" id="cb_delete_<?php echo $i ?>" title="Delete rule" onclick="ondelete(<?php echo $i ?>)" src="/img/delete.png">                               
                                <?php }?><input name="enable_<?php echo $i ?>" id="enable_<?php echo $i ?>" value="<?php echo $enabled ?>" type="hidden">
                                <input name="delete_<?php echo $i ?>" id="delete_<?php echo $i ?>" value="0" type="hidden">
                                <input name="hname_<?php echo $i ?>" id="hname_<?php echo $i ?>" value="<?php echo $hname ?>" type="hidden">
                                <input name="sname_<?php echo $i ?>" id="sname_<?php echo $i ?>" value="<?php echo $sname ?>" type="hidden">
                            </td>
                            <td style="vertical-align:bottom">Name<br>
                                <input type="text" id="name_<?php echo $i ?>" name="name_<?php echo $i ?>" value="<?php echo $a[8]; ?>" size="16" maxlength="31">
                            </td>
			                <td style="text-align:left;vertical-align:bottom">Service Name<br>
                                <select style="width:110px" id="srv_<?php echo $i ?>" name="srv_<?php echo $i ?>" onChange="onsrv(<?php echo $i ?>)">
                                    <option <?php echo isset($a[4])? "selected ":"" ?>value="<?php echo isset($a[4])? $a[4]:"0"; ?>/<?php echo isset($a[6])? $a[6]:"0";?>"><?php echo isset($sname)? $sname:"Custom"; ?></option>
                                    <option value="21/21/TCP">FTP</option>
                                    <option value="9418/9418/TCP">GIT</option>
                                    <option value="80/80/TCP">HTTP</option>
                                    <option value="8080/8080/TCP">PROXY</option>
                                    <option value="443/443/TCP">HTTPS</option>
                                    <option value="1723/1723/TCP">PPTP-VPN</option>
                                    <option value="22/22/TCP">SSH</option>
                                    <option value="4040/4040/TCP">Subsonic</option>
                                    <option value="9091/9091/TCP">Transmission</option>
                                    <option value="5901/5901/TCP">VNC</option>
                                    <option value="554/554/TCP">RTP</option>
                                    <option value="10110/10110/TCP">NMEA</option>
                                </select>
                            </td>
                            <td style="text-align:center;vertical-align:bottom">Public Port<br>
                                <input type="text" id="publ_port_<?php echo $i ?>" name="publ_port_<?php echo $i ?>" value="<?php echo $a[6]; ?>" size="5" maxlength="5">
                            </td>
                            <td style="text-align:center">Protocol<br>
                                <select style="width:80px" id="proto_<?php echo $i ?>" name="proto_<?php echo $i ?>" onChange="onproto(<?php echo $i ?>)">
                                    <option <?php echo $a[5]=="tcp"? "selected ":"" ?>value="tcp">TCP</option>
                                    <option <?php echo $a[5]=="udp"? "selected ":"" ?>value="udp">UDP</option>
                                    <option <?php echo $a[5]=="tcp,udp"? "selected ":"" ?>value="tcp,udp">Both</option>
                                    <option <?php echo is_numeric($a[5])&&$a[5]>0? "selected ":"" ?>value="oth">Other</option>
                                </select>
                            </td>
                            <td style="text-align:center" rowspan="2"><div id="status_<?php echo $i ?>"><?php if ($first==false) {echo strlen($chk)? '<b style="color:#090">Enabled</b>':'<b style="color:#900;">Disabled</b>'; } ?></div>

                                <div id="show_status_<?php echo $i ?>" style="display:<?php echo $first==true? "inline":"none" ?>">
                                <?php if ($first == true ) { ?>
                                    <b style="color:#378">Add<br>New</b>
                                <?php } else { ?>    <img alt="deleted" src="/img/deleted.png" style="height:12px"><?php }?>
                                </div>
                            </td>
                        </tr>
                        <tr>         
                            <td style="vertical-align:bottom">IP Address<br>
                                <input type=text id="ip_<?php echo $i ?>" name="ip_<?php echo $i ?>" value="<?php echo $a[3]; ?>" size="16" maxlength="31">
                            </td>
			                <td style="text-align:left;vertical-align:bottom">Computer Name<br>                                
                                <select style="width:110px" id="hosts_<?php echo $i ?>" onChange="onhname(<?php echo $i ?>)">
                                    <option <?php echo isset($a[3])? "selected ":"" ?>value="<?php echo $a[3]; ?>"><?php echo $hname ?></option>
                                    <?php echo exec("cat /tmp/hostopts"); ?>

                                </select>
                            </td>
                            <td style="text-align:center;vertical-align:bottom">Private Port<br>
                                <input type="text" id="priv_port_<?php echo $i ?>" name="priv_port_<?php echo $i ?>" value="<?php echo $a[4]; ?>" size="5" maxlength="5">
                            </td>
                            <td style="text-align:center;vertical-align:bottom">Other protocoll<br>
                                <input <?php echo is_numeric($a[5])&&$a[5]>0? "":"disabled "; ?>type="text" id="other_proto_t<?php echo $i ?>" value="<?php echo is_numeric($a[5])? $a[5]:"0"; ?>" size="5" maxlength="5" title="See: /etc/protocols">
                                <input type="hidden" id="other_proto_<?php echo $i ?>" name="other_proto_<?php echo $i ?>" value="0">
                            </td>
                        </tr>
<?php	
	$i++;
    $first=false;
	}
	@fclose($fd);
    @unlink("/tmp/rlist");
?>

			            </table>
		            </div><?php } ?>
		            <div class="actionBox" style="overflow-y:auto;max-height:600px;">       
	                    <h2 title="/etc/shorewall/rules" class="actionHeader">STATIC SERVERS RULES</h2>
                        <div id="show_static" style="display:none;">
			            <table id="stable"> 
                            <tr>       
                                <td><b>Service</b></td>
                                <td><b>From</b></td>
                                <td><b>To</b></td>
                                <td><b>Protocol</b></td>
                                <td><b>Port</b></td>
                            </tr>
                            <?php
                                $str1="grep \"ACCEPT\" /etc/shorewall/rules|grep -E -v 'dnat|DROP'|sed s/loc/LAN/g|sed s/\\\$FW/Firewall/g|";
                                $str2="sed s/net/Internet/g|awk '{printf \$1 \"|\" \$2 \"|\" \$3 \"|\" \$4 \"|\" \$5 \"\\n\"}'>/tmp/srules";
                                @system($str1.$str2);

                          	if (($fd = fopen("/tmp/srules", "r")) == NULL) {
                            	die("bummer");
                            }

	                        while (!feof($fd)) {
                                
                                $str=trim(fgets($fd));
                                $a=preg_split("/\|/", $str);
                                if (count($a) <3) continue;
                                echo "\r                            <tr>\n";
                                foreach ($a as &$value) {
                                    echo '                                <td>';
                                    echo $value = empty($value)? "-": $value;
                                    echo "</td>\n";
                                }
                                echo "\n                            </tr>\n";
                            }
                            @fclose($fd);
                            @unlink("/tmp/srules");
                        ?>  
                        </table>                       
                        </div>
                        <b>Show/Hide </b><input type="checkbox" onChange="onshowstatic(this)">
                    </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    You can select a computer from the list of DHCP clients in the Computer Name drop down menu,
                    or you can manually enter the IP address of the computer at which you would like to open the specified port.<br><br>
                    <strong>NOTE!</strong> This service will not work if your provider does not allow inbound traffic to your
                    premises or if any downstream firewalls (your external modem) is active without having this <?php p_mode(); ?> in a DMZ zone.<br><br>
                    <strong>NOTE!</strong> For this service a DDNS service may be usefull for you. Check the page Security->Inbound Access
                    fort more information.<br><br>
                    Select the protocol used by the service. The common choices UDP, TCP, and both UDP and TCP, can be selected from the drop-down menu.
                    To specify any other protocol, select "Other" from the list, then enter the corresponding protocol number as assigned by the
                    <a target="_blank" href="http://www.iana.org/assignments/protocol-numbers">IANA</a> in the Protocol box. 
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
