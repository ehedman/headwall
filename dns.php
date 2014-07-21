<? 
    /*
     * dns.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';

    $rowIndx=1;

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>";exit;}

    if (count($_GET) && $_GET["refresh"] == 1) {
        @system("alleases ".g_domain().">/tmp/dmlist");
    }

    if (count($_POST) && $_POST["POST_ACTION"] == "OK") {

        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            if ($_POST["f_dns_enable"] == 1) {
	            if (dnsIsConfigured($_POST["f_local_domain"], $_POST["f_serving_zone"]) == false) {
		            do_dbhome(implode(" ", $_POST));
		            do_dbrev(implode(" ", $_POST));
		        } else {
                    do_updatedns($_POST["f_forwarder_1"],$_POST["f_forwarder_2"],
                                 $_POST["f_redir_spf"],$_POST["f_spf_here"],$_POST["f_local_domain"],
                                 $_POST["f_bl_enable"],$_POST["f_serving_zone"],g_lan(),g_lan1());
                    if (g_srvstat("dhcpd") == false) {
                        if (g_srvstat("named") == false)
                            @system("/usr/sbin/service bind9 start");
			            do_dbrecords(implode(" ", $_POST));
				        sleep(5);
                    }
				    @system("alleases ".g_domain().">/tmp/dmlist");
                }
                @system("/usr/sbin/update-rc.d bind9 enable");
                @system("/usr/sbin/service bind9 restart");
                do_updatedhcp($_POST["ip_local_host_1"]);
            } else {
                do_updatedhcp($_POST["f_forwarder_1"], $_POST["f_forwarder_2"]);
                @system("/usr/sbin/service bind9 stop");
                @system("/usr/sbin/update-rc.d bind9 disable");
            }
        } else {
            if ($_POST["f_dns_enable"] == 1) {
                $tm=20;            
            } else $tm=6;
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds='.$tm.'&loc='.$_SERVER['SCRIPT_NAME']);
            exit;
        }
    }

function tt($msg=0)
{
    switch ($msg) {
        case '1':
            echo "127.0.0.1 to kill the URL locally on your PC or a\nlocal host to post-process the offending url";
            break;
        case '2':
            echo "The virtual IP-Address to the left must\nbe statically free i.e, outside any DHCP ranges";
            break;
        default:
             break;
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

function checkPage()
{
	var f=getObj("form");
    var n=1;
    
    restartTimeout();
   
    if (f.dns_enable.checked == true) {

        while (ip=getObj("ip_local_host_"+n++)) {
            try {
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
            }
            catch(err) { break; }
        } 

        if (f.do_spamft.checked == true) {
            if (isFieldBlank(f.redir_spf.value) == true) {
                f.redir_spf.select();
                alert("Blank IP-address");
                return false;
            }
            if (isIPValid(f.redir_spf) == false) {
                alert("Invalid IP-address "+f.redir_spf.value);
                f.redir_spf.select();
                return false;
            }
        }

        if (isFieldBlank(f.serving_zone.value) == true) {
            f.serving_zone.select();
            alert("Blank IP-address");
            return false;
        }
 
        check_blu();
        check_ggj();
    

        f.f_redir_spf.value = f.redir_spf.value;
        f.f_forwarder_1.value = f.forwarder_1.value;
        f.f_forwarder_2.value = f.forwarder_2.value;
        f.f_serving_zone.value = f.serving_zone.value;
    }

    if (isFieldBlank(f.forwarder_1.value) == true) {
        getObj("show_dns_items").style.display = "block";
        f.forwarder_1.select();
        alert("Blank IP-address");
        return false;
    }
    if (isIPValid(f.forwarder_1) == false) {
        getObj("show_dns_items").style.display = "block";
        alert("Invalid IP-address "+f.forwarder_1.value);
        f.forwarder_1.select();
        return false;
    }
    if (isFieldBlank(f.forwarder_2.value) == true) {
        getObj("show_dns_items").style.display = "block";
        f.forwarder_2.select();
        alert("Blank IP-address");
        return false;
    }
    if (isIPValid(f.forwarder_2) == false) {
        getObj("show_dns_items").style.display = "block";
        alert("Invalid IP-address "+f.forwarder_2.value);
        f.forwarder_2.select();
        return false;
    }

    f.f_forwarder_1.value = f.forwarder_1.value;
    f.f_forwarder_2.value = f.forwarder_2.value;

    f.POST_ACTION.value = "OK";
	f.submit();

    return true;
}

/* page init functoin */
function initPage()
{
	// init here ...
    var f=getObj("form");

	if (<? if(g_srvstat("named")) echo "true"; else echo "false"; ?>) {
        getObj("show_dns_items").style.display = "block";
        getObj("show_local_items").style.display = "block";
        getObj("show_spam_items").style.display = "block";
        getObj("show_add_host").style.display = "block";
        f.f_dns_enable.value = 1;
	} else {
        getObj("show_dns_items").style.display = "none";
        getObj("show_local_items").style.display = "none";
        getObj("show_spam_items").style.display = "none";
        getObj("show_add_host").style.display = "none";
        f.f_dns_enable.value = 0;
	}

    if (<? if(g_spamfmode() == true) echo "1"; else echo "0"; ?>) {
        getObj("show_spam_features").style.display = "block";
        getObj("show_bl_help").style.display = "block";
        f.do_spamft.checked = true;
    } else {
        getObj("show_spam_features").style.display = "none";
         getObj("show_bl_help").style.display = "none";
    } 

    if (<? echo g_spfhere()? '1':'0'; ?>) {
        getObj("show_bl_options").style.display = "block";
    } else {
        getObj("show_bl_options").style.display = "none";
    }

    f.do_ggj.checked = true; // tbd

    return true;

}

function onchange_dns()
{
    var f=getObj("form");

    <? if (g_srvstat("named")) { ?>
    if (f.dns_enable.checked == false) {
        getObj("show_dns_items").style.display = "none";
        getObj("show_local_items").style.display = "none";
        getObj("show_spam_items").style.display = "none";
        getObj("show_add_host").style.display = "none";
        f.f_dns_enable.value = 0;
    } else {
        getObj("show_dns_items").style.display = "block";
        getObj("show_local_items").style.display = "block";
        getObj("show_spam_items").style.display = "block";
        getObj("show_add_host").style.display = "block";
        f.f_dns_enable.value = 1;
    }
    <?} else {?>

    f.f_dns_enable.value = (f.dns_enable.checked? "1":"0");
    <?}?>

    return true;
}

function check_spamft()
{
    var f=getObj("form");

    if (f.do_spamft.checked == false) {
        getObj("show_spam_features").style.display = "none";
        getObj("show_bl_help").style.display = "none";
        f.f_bl_enable.value = 0;
    } else {
        getObj("show_spam_features").style.display = "block";
        getObj("show_bl_help").style.display = "block";
        f.f_bl_enable.value = 1;
    }
}

function check_blu()
{
    var f=getObj("form");
    if (f.do_blu.checked == false)
        f.f_bl_updates.value = 0;
    else
        f.f_bl_updates.value = 1;
}

function check_ggj()
{
    var f=getObj("form");

    if (f.do_ggj.checked == false)
        f.f_gg_jumps.value = 0;
    else
        f.f_gg_jumps.value = 1;
}

function check_spfhere()
{
    var f=getObj("form");
    var ck=getObj("spf_here");

    if (ck.checked == true) {
        f.f_spf_here.value = 1;
        getObj("show_bl_options").style.display = "block";
    } else {
       f.f_spf_here.value = 0; 
        getObj("show_bl_options").style.display = "none";
    }
}

function check_copy()
{
    var f=getObj("form");
    f.res_ip.value="<? echo $_SERVER['REMOTE_ADDR']; ?>";  
    f.res_name.value="<? system("if [ \"`pidof named`\" ]; then nslookup ".$_SERVER['REMOTE_ADDR']." localhost | grep name | awk '{gsub(\".".g_domain().".\",\"\");printf \"%s\", $4}'; fi"); ?>";
    if (f.res_name.value == "")
        f.res_name.value="<? system('nbtscan -q '.$_SERVER['REMOTE_ADDR']." | awk '{print tolower(".'$2'.")}' | tr -d '\n'") ?>";
    f.res_indx.value=0;
    restartTimeout();
    return true;
}

function ed_row(n)
{
    var f=getObj("form");

    f.res_ip.value=getObj("ip_local_host_"+n).value;     
    f.res_name.value=getObj("nm_local_host_"+n).value;
    getObj("st_local_host_"+n).value = 1;
    getObj("im_local_host_"+n).src = "/img/delete.jpg";
    f.res_indx.value=n;
    restartTimeout();
    return true;
}

function rm_row(n)
{
    var f=getObj("form");
    getObj("st_local_host_"+n).value = 2;
    getObj("im_local_host_"+n).src = "/img/deleted.jpg";
    f.res_indx.value=0;
    f.res_ip.value = f.res_name.value ="";
    restartTimeout();
    return true;
}

function check_res()
{
    var f=getObj("form");
    var n=0;

    var rowIndx = getObj("restable").getElementsByTagName("tbody")[0].getElementsByTagName("tr").length;
    if (rowIndx >= <? echo C_MAX ?>) {
        alert("Attempt to exceed the limit for a class C netwok (<? echo C_MAX ?>)");
        return false;
    }
   
    if (isFieldBlank(f.res_ip.value) == true) {
        f.res_ip.select();
        alert("Blank new IP-address");
        return false;
    }
    if (isIPValid(f.res_ip) == false) {
        alert("Invalid new IP-address "+f.res_ip.value);
        f.res_ip.select();
        return false;
    }
    if (isFieldBlank(f.res_name.value) == true) {
        f.res_name.select();
        alert("Blank new Host Name");
        return false;
    }

    n = f.res_indx.value;
    
    for( var i=2; i < rowIndx; i++ ) {
        if (i==n) continue;
        if (f.res_ip.value == getObj("ip_local_host_"+i).value) {
            alert("Reserved IP address ("+f.res_ip.value+") is already used."); return false; 
        }
        if (f.res_name.value == getObj("nm_local_host_"+i).value) {
            alert("Name ("+f.res_name.value+") is already used."); return false;
        }
        if (isSubnetSame("<? p_srvzone() ?>", f.res_ip.value, "<? p_mask(g_lan()) ?>")) {
            alert("The IP address ("+f.res_ip.value+") is not in this DNS Server's served Local Zone."); return false;
        }
    }

    if (n > 0) {
        getObj("ip_local_host_"+n).value = f.res_ip.value;    
        getObj("nm_local_host_"+n).value = f.res_name.value;
        f.res_ip.value = f.res_name.value ="";
        f.res_indx.value=0;
        return true;
    }  

    var table = getObj("restable");   
    var row = table.insertRow(rowIndx);
   
    var stn = row.insertCell(0);
    var ip = row.insertCell(1);
    var dl = row.insertCell(2);
    var ed = row.insertCell(3);
    
   
    var st_str="\n<input name='st_local_host_"+rowIndx+"' id='st_local_host_"+rowIndx+"' value='1' type='hidden'>";
    var nm_str="<input readonly='readonly' name='nm_local_host_"+rowIndx+"' id='nm_local_host_"+rowIndx+"' size='16' maxlength='15' value='"+f.res_name.value+"' type='text'>";
    stn.innerHTML = st_str + nm_str;
    ip.innerHTML = "<input readonly='readonly' name='ip_local_host_"+rowIndx+"' id='ip_local_host_"+rowIndx+"' size='16' maxlength='15' value='"+f.res_ip.value+"' type='text'>";   
    dl.innerHTML='<img alt="del" id="im_local_host_'+rowIndx+'" onclick="rm_row('+rowIndx+')" src="/img/delete.jpg" style="border:0">'+"\n";
    ed.innerHTML='<img alt="edit" onclick="ed_row('+rowIndx+')" src="/img/edit.jpg" style="border:0">';

    restartTimeout();
    f.res_indx.value=0;
    f.res_ip.value = f.res_name.value ="";
}
        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<? echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION"       value="" type="hidden">
        <input name="f_dns_enable"	    id="f_dns_enable"       value="0"  type="hidden">
        <input name="f_bl_enable"	    id="f_bl_enable"        value="<? echo g_spamfmode()? '1':'0'; ?>"  type="hidden">
        <input name="f_bl_updates"	    id="f_bl_updates"       value="0"  type="hidden">
        <input name="f_gg_jumps"	    id="f_gg_jumps"         value="0"  type="hidden">
        <input name="f_forwarder_1"	    id="f_forwarder_1"      value="<? p_dnsforw(0); ?>"  type="hidden">
        <input name="f_forwarder_2"	    id="f_forwarder_2"      value="<? p_dnsforw(1); ?>"  type="hidden">
        <input name="f_serving_zone"    id="f_serving_zone"     value="<? p_srvzone() ?>"  type="hidden">
        <input name="f_local_domain"    id="f_local_domain"     value="<? p_domain(); ?>" type="hidden">
        <input name="f_redir_spf"       id="f_redir_spf"        value="<? p_spfredir(); ?>"  type="hidden">
        <input name="f_spf_here"        id="f_spf_here"         value="<? echo g_spfhere()? '1':'0'; ?>" type="hidden">
        <input name="f_lan"             id="f_lan"              value="<? echo g_lan(); ?>" type="hidden">
        <input name="f_lan1"            id="f_lan1"             value="<? echo g_lan1(); ?>" type="hidden">

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
                            <li><div class="leftnavLink"><a href="/network.php">Network</a></div></li>
                            <? if (g_haswifi()) { ?><li><div class="leftnavLink"><a href="/wireless.php">Wireless</a></div></li><?}?>
                            <li><div class="leftnavLink"><a href="/dhcp.php">DHCP</a></div></li>
                            <li><div id="left" class="navThis">DNS</div></li>
                            <li><div class="leftnavLink"><a href="/firewall.php">Security</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>DNS Settings</h1>
                        The Domain Name System (DNS) is responsible of assigning domain names and mapping those names
                        to IP addresses of devices on your LAN.
                        The DNS is maintained by a database residing on this <? p_mode(); ?> to maintain local
                        addresses and names of your network devices.<br>
                        A DNS Server running on this <? p_mode(); ?> is then linked to root DNS servers on the internet and thus this <? p_mode(); ?> can serve your
                        LAN as the primary DNS Server with caching capabilities to reduce network traffic to the internet.
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
		            </div><div class="vbr"></div>
		            <div class="actionBox">
                        <h2 class="actionHeader">DNS Server Settings</h2>
                        <div id="show_dns_items">
			                <table>
                             <tr>
				                <td class="raCB" style="width: 40%">Local Domain Name :</td>
				                <td class="laCB">&nbsp;
					                <input <? echo g_srvstat("dhcpd")? "disabled title='DHCP defined' ":"" ?> id="local_domain" size="16" maxlength="15" value="<? p_domain(); ?>" type="text">
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB">Serving local zone :</td>
				                <td class="laCB">&nbsp;
					                <input <? echo g_srvstat("dhcpd")? "disabled title='DHCP defined' ":"" ?>id="serving_zone" size="16" maxlength="15" value="<? p_srvzone() ?>" type="text">
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB">Forwarding DNS Server 1 :</td>
				                <td class="laCB">&nbsp;
					                <input id="forwarder_1" size="16" maxlength="15" value="<? p_dnsforw(0); ?>" type="text">
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB">Forwarding DNS Server 2 :</td>
				                <td class="laCB">&nbsp;
					                <input id="forwarder_2" size="16" maxlength="15" value="<? p_dnsforw(1); ?>" type="text">
				                </td>
			                </tr>
                            </table>
                        </div>
                        <table>
                            <tr>
                                <td class="raCB" style="text-align:right">
                                    Enable DNS Server&nbsp;&nbsp;
                                    <input type="checkbox" id="dns_enable" onChange="onchange_dns();"<? echo g_srvstat("named")? ' checked="checked"':''; ?>>&nbsp;&nbsp;
                                </td>
                            </tr> 
                        </table>
		            </div><div class="vbr"></div>

                    <div class="actionBox" id="show_spam_items">
			        <h2 class="actionHeader">URL Filtering</h2>         
			        <table>
                        <tr>
                            <td class="raCB" style="width: 40%">Enable URL blacklist :</td>
                            <td><input id="do_spamft" onclick="check_spamft();" type="checkbox"<? echo g_spamfmode()? ' checked="checked"':''; ?>></td>
                        </tr>
                    </table>
                    <div id="show_spam_features">
                        <table>
                            <tr>
                                <td class="raCB" style="width: 40%">Redirect blacklisted sites to I.P :</td>
                                <td>
                                    <input id="redir_spf" size="16" title="<? tt(1); ?>" maxlength="15" value="<? p_spfredir(); ?>" type="text">&nbsp;<b>On this <? p_mode(); ?>:</b>&nbsp;<input id="spf_here" title="<? tt(2); ?>" onclick="check_spfhere()" type="checkbox"<? if(g_spfhere()) {echo " checked";} ?>>
                                </td>
                            </tr>
                        </table>
                        <div id="show_bl_options">
                        <table>
                            <tr><td class="raCB" style="width: 40%">Enable blacklist monthly updates :</td><td><input id="do_blu" onclick="check_blu();" type="checkbox"></td></tr>
                            <tr><td class="raCB">Enable Google tracking jump through : </td><td><input id="do_ggj" onclick="check_ggj();" type="checkbox"></td></tr>
                        </table>
                        </div>
                    </div>
                </div><div class="vbr"></div>

                <div class="actionBox" id="show_add_host">
			        <h2 class="actionHeader">Add a Host to Local Domain</h2>
                    <table>
                        <? if (g_srvstat("dhcpd") == false) { ?>
                             <tr>
				                <td class="raCB" style="width: 40%">IP Address :</td>
				                <td class="laCB">&nbsp;
					                <input style="text-align:right;" id="res_ip" size="16" maxlength="20" value="" type="text">
                                </td>
                                <td>&nbsp;&nbsp;
                                    <input type="button" value="Copy Your PC's IP Address" onclick="check_copy();">
				                </td>
			                </tr>
                            <tr>
				                <td class="raCB">Host Name :</td>
				                <td class="laCB" colspan="2">&nbsp;
					                <input style="text-align:right;" id="res_name" size="16" maxlength="20" value="" type="text">.<? p_domain() ?>
                                <input id="res_indx" value="0" type="hidden">
                                </td>
			                </tr>
                            <tr>
                                <td colspan="3" style="text-align:right">
                                    &nbsp;&nbsp;
                                    <input type="button" value="Add" onclick="check_res();">&nbsp;&nbsp;
                                </td>
                            </tr> 
                        <? } else { ?>
                            <tr>			        
				                <td class="laCB">
					                Your configuration dictates that host names and IP addresses are managed from the DHCP section.
                                    Editing is permitted only when this <? p_mode(); ?> provides the DNS service standalone.
                                </td>    
			                </tr>
                        <?}?>             
                    </table>
                </div><div class="vbr"></div>

                <div class="actionBox" id="show_local_items" style="overflow-y:auto;max-height:500px;">
                    <h2 class="actionHeader">Local Domain hosts</h2>
                    <table id="restable">
                        <tr>       
                            <td style="width:50%">Name</td>
                            <td style="width:50%">IP-Address</td>
                            <td<? echo g_srvstat("dhcpd")==false? ' colspan="2"':""; ?>></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="st_local_host_<? echo $rowIndx ?>" value="1" type="hidden">
                                <input name="nm_local_host_<? echo $rowIndx ?>" value="<? p_nodeName(); ?>" type="hidden">
                                <input name="ip_local_host_<? echo $rowIndx ?>" value="<? p_srvip(); ?>" type="hidden">
                                <input disabled="disabled" size="10" maxlength="40" value="<? p_nodeName(); ?>" type="text">&nbsp;(this <? p_mode(); ?><? if (g_spfhere()) echo " with URL filter"; ?>)
                            </td>
                            <td>
                                <input disabled="disabled" size="12" maxlength="15" value="<? p_srvip(); ?>" type="text">
                                <? if (g_spfhere()) {
                                    echo '                        &&nbsp;<input disabled="disabled" size="12" value="'.g_spfredir().'" type="text">';                       
                                } ?>
                            </td>
                            <td<? echo g_srvstat("dhcpd")==false? ' colspan="2"':""; ?>></td>
                        </tr>
<?
    $dstat=g_srvstat("dhcpd");

    if (($fd = fopen("/tmp/dmlist", "r")) == NULL)
   		die("bummer");

    $indx=++$rowIndx;
    while (!feof($fd)) {
        $str=trim(fgets($fd));
        $a=preg_split("/,/", $str);
        if (count($a) !=3) continue;
        if ($a[2] == "1" && $dstat == false) continue;
        $stat=$a[2]=="1"? "disabled":""; 
        $statval=$a[2]=="1"? "0":"1";      
        echo "                <tr>\n";
        echo "                    <td>\n";
        echo '                        <input name="st_local_host_'.$indx.'" id="st_local_host_'.$indx.'" value="'.$statval.'" type="hidden">'."\n";
        echo '                        <input readonly="readonly" name="nm_local_host_'.$indx.'" id="nm_local_host_'.$indx.'" style="width:100%;border:0;background-color:#dfdfdf" maxlength="40" value="'.$a[0].'" type="text">'."\n";
        echo "                    </td>\n";
        echo "                    <td>\n";
        echo '                        <input readonly="readonly" name="ip_local_host_'.$indx.'" id="ip_local_host_'.$indx.'" style="width:100%;border:0;background-color:#dfdfdf" maxlength="15" value="'.$a[1].'" type="text">'."\n";
        echo "                    </td>\n";
        if ($a[2] == "1") {
            echo '                    <td><img alt="dyn" src="/img/dyn.png" style="border:0"></td>'."\n";
        } else {
            if (g_srvstat("dhcpd") == false) {
                echo '                        <td><img alt="del" id="im_local_host_'.$indx.'" onclick="rm_row('.$indx.')" src="/img/delete.jpg" style="border:0"></td>'."\n";             
                echo '                        <td><img alt="edit" onclick="ed_row('.$indx.')" src="/img/edit.jpg" style="border:0"></td>'."\n";
            } else {
                echo '                        <td><img alt="fixed" src="/img/fixed.png" style="border:0"></td>'."\n";
            }
        }
        echo "                </tr>\n";
        $indx++;
	        
    }
    @fclose($fd);

?>                    </table>
                </div>
                
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                     Optionally this DNS server can act as a URL filter to block blacklisted sites on the internet by using a
                    list of known spam-and tracker sites on the internet.
                    <div id="show_bl_help" style="display:none"><br>
                        With the option "Enable blacklist monthly updates", the blacklist is regulary and
                        automatically updated from the internet from:<br>
                        <a style="color:#FFFFFF" href="http://pgl.yoyo.org/adservers/" target="_blank"><b>pgl.yoyo.org</b></a>
                        <br><br>The option "Enable Google tracking jump through" will attempt to bypass the
                        hidden trackers that the google search site imposes on sponsored links.<br><br>
                        Monthly  updates and "tracking jump through" are meaningsfull only when this <? p_mode(); ?> is
                        the handler of blacklisted sites i.e, the  "On this <? p_mode(); ?>" feature must be checked.
                    </div>
                    <? if (g_srvstat("dhcpd")) { ?><br><br>Local Domain Hosts marked as
                    <a><img style="background-color:#FFFFFF; height:12px; border:0" src="/img/dyn.png" alt="x"></a> are
                    dynamic I.P addresses managed by the DHCP server.<br>
                    The &nbsp;<a><img style="background-color:#FFFFFF; height:12px;border:0" alt="x" src="/img/fixed.png"></a>&nbsp;indicates a static IP DHCP reservation.<?}?>
                    <br><br><b>NOTICE</b><br>In an established dhcp/dns environment it is highly inappropriate to change the policy of having the dhcp
                    service and the dns service working together or not.<br>
                    Changing the policy will probably mean lost DNS records or lost DHCP asignements or both.
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
