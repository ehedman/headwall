<?php
    /*
     * common.php
     *
     *  Copyright (C) 2013-2024 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    $globals = $GLOBALS;
    $rval = 1;
    system("arp -n | grep -q ". $_SERVER['REMOTE_ADDR'], $rval);
    if ( $rval != 0) {
        system("ip addr show | grep -q -E '".$_SERVER['REMOTE_ADDR'].".*ppp'", $rval);
        if ( $rval != 0 ) {
            header("HTTP/1.0 404 Not Found");
            echo "<h1>404 Not Found</h1>";
            die();
        }
    }

    include 'cgi-bin/netif.php';

    define('HOMENAME', "HedmansHome");
    define('HOMEURL', "https://github.com/ehedman/headwall");

    define('WAN',    $THEWAN);      // Set to match WAN side
    define('LAN',    $THELAN);      // Set to match LAN side
    define('LAN1',   $THELAN.":1"); // Set to match virtual LAN for URL (spam) redirection
    define('MODE',   $THEMODE);     // Set to current Staion Mode
    define('SRVLAN', $THESRV);      // Fixed Service if
    define('SRVIP',  $THESRVIP);    // Fixed Service ip

    define('C_MAX',251);            // Max usable hosts in a class C network

    define('VPNET', "192.168.255"); // Reserved for the VPN network range
   
    $CGI_BIN="cgi-bin";
    $WRAPPER=$_SERVER["DOCUMENT_ROOT"] . "/" .$CGI_BIN . "/wrapper";

    putenv('PATH='.getenv('PATH').':'.$_SERVER["DOCUMENT_ROOT"].'/'.$CGI_BIN);
    putenv('DOCUMENT_ROOT='.$_SERVER["DOCUMENT_ROOT"]);

    if ((g_spfhere() == true) && (g_spamfmode() == true) && g_srvstat("named") == true) {

        if ($_SERVER["SERVER_ADDR"] == g_spfredir()) { 
            if (isset($_SERVER["HTTP_REFERER"]) && (($host=parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST)))!=false)
                $_SERVER["HTTP_HOST"] = $host;
            include 'spam/index.php';
            die();
        }
        //phpinfo(INFO_VARIABLES);
    }

    // Inialize session
    if (!isset($nosess)) {
        session_start();

        if ($_SERVER["PHP_SELF"] != "/login.php" && $_SERVER["PHP_SELF"] != "/wifi_survey.php" ) {
            // Check, if username session is NOT set then this page will jump to login page
            // Note that a disabled java-script will have this effect.
            if (!isset($_SESSION['username'])) {
                header('Location: /login.php');
            }
        }
    }

    @unlink("/tmp/keep_wd");

function p_title($page="HOME")
{
    if (g_mode() == 2) $type="WIRELESS BRIDGE"; else $type="HOME GATEWAY";

    echo HOMENAME ." | $type | $page";
}

function p_productHome()
{
    echo HOMEURL;
}

function p_copyRight()
{
    echo "2013-" . date("Y", time()) . ' <a target="_blank" href="'.HOMEURL.'">'.HOMENAME.'</a>';
}

function g_mode()
{
    if (g_haswifi() == 1)
        return MODE;
    else
        return 1;
}

function p_mode()
{
    if (g_mode() == 1)
        echo "gateway";
    else if (g_mode() == 2)
        echo "bridge";
}

function g_wan()
{
    return WAN;
}

function g_lan()
{
    return LAN;
}

function g_lan1()
{
    return LAN1;
}

function g_srvlan()
{
    $a=explode(':', SRVLAN);
    return $a[0];
}

function g_srvip()
{
    return SRVIP;
}

function g_destip($ip0, $ip1, $nif)
{
    $rif=exec ("/sbin/ifconfig | grep -B1 ".$_SERVER["SERVER_ADDR"]." | head -1 | awk '{print \$1}' | sed /:srv/s///");
    $rip=(g_ip($rif));

    if ($nif == "0:srv") return $rip;

    $val=trim(exec("/sbin/ifconfig | grep -m1 :srv | awk '{print \$1'}"));
    if ($val != NULL)
        if ($nif != $val)
            return $rip;

    $a1ip=explode(".", $ip0);
    $a2ip=explode(".", $ip1);
    if ($a1ip[0].$a1ip[1].$a1ip[2] == $a2ip[0].$a2ip[1].$a2ip[2])
        return $ip1;
    return $rip;
}

function ifsrv($if, $ip)
{
    $val=trim(exec("/sbin/ifconfig | grep -m1 :srv | awk '{print \$1'}"));
    if ($val != NULL) {
        system("/sbin/ifconfig ".$val." down >/dev/null 2>&1", $rval);
    }
    if ( $if != "0:srv") {
        $a=explode(':', $if);
        if ($a[0] == g_wan())
            do_firewall_mgm("stop");

        system("/sbin/ifconfig ".$if." ".$ip." up >/dev/null 2>&1", $rval);
    }

    /*if (g_srvstat("shorewall"))
        do_firewall_mgm("restart");*/
}

function g_allwifidev()
{
    $devs=trim(exec('find /sys/devices/platform -name phy80211 | awk -F/ \'{printf $(NF - 1) "\n"}\' | sort | awk \'{printf $(NF) " "}\''));
    $devices = explode(" ", $devs);

$emptyArray = (array) null; 

    return $devices;
}

function g_awifidev()
{
    $dev=trim(exec('find /sys/devices/platform -name phy80211 | awk -F/ \'{printf $(NF - 1) "\n"}\' | sort | awk \'{printf $(NF) " "}\'| cut -d\' \' -f1'));
    return $dev;
}

function g_wlanif($if)
{
    if (!strcmp(LAN, $if))
        return $if;
    if (!strcmp(WAN, $if))
        return $if;
    return NULL;
}

function g_wstatus($if="wlan0")
{
    @system("/sbin/iwconfig ".$if." | grep -q unassociated", $ret);
    return $ret;
}

function g_haswifi()
{
    $val = trim(exec("/sbin/iwconfig 2>/dev/null | grep 802.11"));
    if (empty($val))
        return 0;
    return 1;
}
function do_wifilist()
{
    bash("wifilist");
}

function g_netif($if)
{
    system("/sbin/ifconfig ".$if." >/dev/null 2>&1", $rval); 
    if ($rval == 0) return true;
    return false;
}

function p_ifopts($if)
{
    bash("ifopts $if");
}

function p_wifopts($if)
{
    bash("wifopts $if");
}

function setTimeout($cond=1)
{
    if ($cond) {
?>
var timeoutCount=240;
var counter=setInterval(timer, 1000);
function timer()
{
    timeoutCount=timeoutCount-1;
    if (timeoutCount <= 0) {
        clearInterval(counter);
        do_timeout(); 
    }
    if (document.getElementById("timer"))
        document.getElementById("timer").innerHTML="Logout "+ timeoutCount + " s";
}

function restartTimeout()
{
    timeoutCount=240;
}
<?php
    } else { ?>function restartTimeout(){}<?php }
}

function bash($args)
{
    global $WRAPPER;
    system("$WRAPPER" . " " . $args, $rval);
    return $rval;
}

function p_firmware($args)
{
    $val=exec("uname " . $args);
    echo trim($val);
}

function p_nodeName()
{
    echo gethostname();
}

function p_uptime()
{
    bash("uptime");
}

function p_serverName()
{
    $var=exec("grep Model /proc/cpuinfo | cut -d: -f2");
    echo trim($var);
}

function  g_iftype($if)
{
    system("grep -q $if /proc/net/wireless", $rval);
    return $rval;
}

function g_wmode($if)
{
    $val=exec("iwconfig $if | grep Mode: | cut -d: -f2 | awk '{printf \"%s\", $1}'");
    if ($val == "Master") $val="Access Point";
    return $val;
}

function p_keyType($if)
{
    if (g_wmode($if) == "Access Point") {
        $res=exec("grep wpa_key_mgmt= /etc/hostapd/$if.conf | cut -d= -f2");
        echo trim($res);
    } else 
        bash("keytype");
}

function p_lanStat($if)
{
   $rx=(exec("cat /sys/class/net/$if/statistics/rx_bytes | numfmt --to=iec --format='%.2fB'"));
   $tx=(exec("cat /sys/class/net/$if/statistics/tx_bytes | numfmt --to=iec --format='%.2fB'"));
   $res="RX bytes $rx TX bytes $tx";
   echo $res;
}

function p_signalQuality()
{
    $res=exec("[ -x /usr/bin/mmcli ] && mmcli -m 0 --simple-status 2>/dev/null |grep \"signal quality:\"|awk -F\' '{print $2, $3}'");
    echo trim($res);
}

function g_nclients($if)
{
	$ret=trim(exec("arp -n -i ".$if."| grep ether| wc -l"));
	return $ret;
}

function g_connectionType($if)
{
    $val = trim(exec("grep -m1 \"iface $if\" /etc/network/interfaces | awk '{print $4}'"));
    if ($val == "" )
        $val = "dhcp";

    return $val;
}

function p_connectionType($if)
{
    echo strtoupper(g_connectionType($if));
}

function do_setStatic($ip,$mask,$gw,$hn,$sip,$smask,$mode,$wan,$lan,$lan1)
{
    bash("setstatic ".$ip." ".$mask." ".$gw." ".$hn." ".$sip." ".$smask." ".$mode." ".$wan." ".$lan." ".$lan1." ".g_srvlan()." ".g_srvip());
}
function do_setDhcp($hn,$sip,$smask,$mode,$wan,$lan,$lan1)
{
    bash("setdhcp ".$hn." ".$sip." ".$smask." ".$mode." ".$wan." ".$lan." ".$lan1." ".g_srvlan()." ".g_srvip());
}

function p_denycount()
{
    $var = exec("cat /tmp/deny_counter | sed ':a;N;$!ba;s/\\n/ /g' | awk '{ print $1\" since \"$2 }'");
    echo trim($var);
}

function g_ifip($if)
{
    $val=exec("ip addr show $if | grep -v secondary | awk -F/ '/inet /{ print $1 }' | awk '{ print \$NF }'");

    if (strlen(trim($val)))
        return true;
    return false;
}

function g_mac($if)
{
    if (substr("$if", -1) == ":")
        $if = substr("$if", 0, -1); 
    
    return trim(exec("cat /sys/class/net/$if/address"));
}

function p_mac($if)
{    
    echo g_mac($if);
}

function g_ip($if)
{
    $val=trim(exec("ip addr show $if | grep -v secondary | awk -F/ '/inet /{ print \$1 }' | awk '{ print \$NF }'"));
    if ($val == "") $val="0.0.0.0";
    return $val;
}

function p_ip($if)
{
    echo g_ip($if);
}

function g_mask($if)
{
    $val=trim(exec("/sbin/ifconfig $if | grep -i netmask | awk -F' ' '{print $4}'"));

    return trim($val);
}

function p_mask($if)
{
    echo g_mask($if);
}

function g_gateway()
{
    $val=exec("route -n | grep UG |  awk '{print $2}'");
    return trim($val);
}

function p_gateway()
{
    echo g_gateway();
}

function p_wstatus($if)
{
    bash("connect ". g_wlanif($if));
}

function g_wssid($if)
{
    $val="unknown";

    if (MODE == 1)
        $val=exec("grep -m1 ssid= /etc/hostapd/$if.conf | cut -d= -f2");
    else if (MODE == 2)
        $val=exec("grep ssid= /etc/wpa_supplicant/wpa_supplicant.conf | cut -d\\\" -f2");
        
    return trim($val);
}

function p_wssid($if)
{
    echo g_wssid($if);
}

function g_passph($how="wpa",$indx=1)
{
    $val = "";

    if (MODE == 1) {
        if ($how=="wpa")
            $val=exec("grep wpa_passphrase= /etc/hostapd/hostapd.conf | cut -d= -f2");
    } else if (MODE == 2) {
        if ($how=="wpa")
            $val = exec ("grep psk= /etc/wpa_supplicant/wpa_supplicant.conf | awk -F".'\"'." '{print $2}'");
        else
            $val = exec ("grep wep_key".$indx."= /etc/wpa_supplicant/wpa_supplicant.conf  | cut -d\\\" -f2");
    }
    echo $val;
    
}

function g_security()
{
    $rval = "1";
    system("grep -iq wpa /etc/wpa_supplicant/wpa_supplicant.conf",$rval);
    if (!$rval) return "1";
    system("grep -iq wep /etc/wpa_supplicant/wpa_supplicant.conf",$rval);
    if (!$rval) return "0";
}

function g_wpamode()
{
    if (MODE == 1) {
        $val=trim(exec("grep wpa= /etc/hostapd/hostapd.conf | cut -d= -f2"));
        if ($val == "2") return "0"; return "1";
    } else if (MODE == 2) {
        system("egrep -iq 'wpa2|rsn' /etc/wpa_supplicant/wpa_supplicant.conf",$rval);
        if (!$rval) return "0";
        system("grep -iq wpa /etc/wpa_supplicant/wpa_supplicant.conf",$rval);
        if (!$rval) return "1";
        return "0";
    }
}

function g_cipher()
{
    $rval = 0;

    if (MODE == 1) {
        $val=trim(exec("grep _pairwise= /etc/hostapd/hostapd.conf | awk -F= '{printf \$2}'"));
        if ($val == "TKIPCCMP") return "0"; return "1";
    } else if (MODE == 2) {
        system("grep -iq tkip /etc/wpa_supplicant/wpa_supplicant.conf",$rval);
        if (!$rval) return "0";

        system("egrep -iq 'wpa2|rsn' /etc/wpa_supplicant/wpa_supplicant.conf",$rval);
        if (!$rval) {
            system("grep -iq tkip /etc/wpa_supplicant/wpa_supplicant.conf",$rval);
            if ($rval)
                return "1";
        }
    }
    return "1";
}

function g_ssid($if)
{
    $val=trim(exec("grep -m 1 ssid= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function g_secm($if)
{
    $val=trim(exec("grep wpa_key_mgmt= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function g_wpam($if)
{
    $val=trim(exec("grep -m 1 wpa= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function g_wcip($if)
{
    $val=trim(exec("grep wpa_pairwise= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function g_wpsk($if)
{
    $val=trim(exec("grep wpa_passphrase= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function g_chan($if)
{
    $val=trim(exec("grep channel= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function g_hmod($if) 
{
    $val=trim(exec("grep hw_mode= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function g_ccod($if)
{
    $val=trim(exec("grep country_code= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function g_visi($if)
{
    $val=trim(exec("grep ignore_broadcast_ssid= /etc/hostapd/$if.conf | cut -d= -f2"));
    return $val;
}

function genChannels()
{
    $array = array(1,2,3,4,5,6,7,8,9,10,11,12,13,32,36,40,44,48,52,56,60,64,68,96,100,104,108,112,116,120,124,128,132,136,140,144,149,153,157,161,165,169,173,177);

    foreach ($array as $i => $value) {
        echo '<option value="'.$value.'">'.$value.'</option>';
    }
}

function p_wbitrate($if)
{
    bash("bitrate $if");
}

function p_wquality($if)
{
    bash("quality $if");
}

function do_fwlog($args)
{
    bash("fwlog $args");
}

function p_blist()
{
    bash("logs");
}

function do_rangeCheck($dip,$msk,$sip,$eip)
{
    bash ("rangecheck $dip $msk $sip $eip");
    $var = trim(exec("cat /tmp/netrange"));
    @unlink("/tmp/netrange");
    return $var;
}

function do_setwpapsk($wpx,$ssid,$pp,$cipher,$if)
{
    bash("setwpapsk $wpx $ssid $pp $cipher $if");
}

function do_setwep($ssid,$key, $if)
{
    bash("setwep $ssid $key $if");
}
function do_sethostapd($aut,$if,$ssid,$pp,$cipher,$ch,$mode,$country,$visi)
{
    bash("sethostapd $aut $if $ssid $pp $cipher $ch $mode $country $visi");
}

function p_srvip()
{
    $var = trim(exec("grep -m1 routers /etc/dhcp/dhcpd.conf| awk '{print $3}' | cut -d\; -f1"));
    echo $var;
}

function p_startip()
{
    $var = trim(exec("grep -m1 range /etc/dhcp/dhcpd.conf| awk '{print $2}'"));
    echo $var;
}

function p_endip()
{
    $var = trim(exec("grep -m1 range /etc/dhcp/dhcpd.conf| awk '{print $3}' | cut -d\; -f1"));
    echo $var;
}

function p_leasetime()
{
    $var = trim(exec("grep -m1 default-lease-time /etc/dhcp/dhcpd.conf| awk '{print $2}' | cut -d\; -f1"));
    echo $var;
}

function g_domain()
{
    $var="";

    if (g_srvstat("dhcpd") && g_srvstat("named"))
        $var = trim(exec("grep -m1 \"domain-name \" /etc/dhcp/dhcpd.conf| awk -F".'\"'." '{print $2}'"));
    else if (g_srvstat("named"))
        $var = trim(exec("grep zone /etc/bind/named.conf.local | awk -F".'\"'." 'NR>1{printf ".'"'."%s".'\n"'.", $2}'| head -1"));
    if (empty($var))
        $var = trim(exec("grep -m1 \"domain-name \" /etc/dhcp/dhcpd.conf| awk -F".'\"'." '{print $2}'"));
    if (empty($var))
        $var = "unknown";

    return $var;
}

function p_domain()
{
    echo g_domain();
}

function do_hostopts()
{
    bash ("sethostopts ".g_domain()." ".g_lan());  
}

function do_updatedhcp($prim, $sec="")
{
    bash ("updatedhcp $prim $sec");
}

function get_relaydns($indx=0)
{
    $var=exec("cat /etc/resolv.conf | grep nameserver | awk '{printf \"%s,\", $2}'");
    return(explode(",", $var)[$indx]);
}

function dnsIsConfigured($domain="", $zone="") 
{
	$r1=stat("/etc/bind/db.".$domain);
	$z=preg_split("/\./", $zone);
	$r2=stat("/etc/bind/db.".$z[2].".".$z[1].".".$z[0]);
	if ($r1 == false || $r2 == false)
		return false;
	return true;	
}

function p_domains($indx=1)
{
    if (g_srvstat("named")) {
        if ($indx == 1)
            $var = g_ip(LAN);
        else if ($indx == 2)
            $var=exec("grep -A2 'forwarders {' /etc/bind/named.conf.options | awk -F".'\;'." 'NR>2{print ".'$1'."}'");
    } else {
    if ($indx == 1)
        $var=exec("grep \"option domain-name-servers\" /etc/dhcp/dhcpd.conf| awk '{print $3}' | cut -d, -f1 | cut -d".'\;'." -f1");
    else if ($indx == 2)
        $var=exec("grep \"option domain-name-servers\" /etc/dhcp/dhcpd.conf| awk '{print $4}' | cut -d".'\;'." -f1");
    }

    echo trim($var);
}

function p_dnsforw($itm=0)
{
    $var=trim(exec("grep -A2 'forwarders {' /etc/bind/named.conf.options | grep ';' | cut -d\; -f1 | awk '{printf  \"%s,\", $1}'"));
    echo preg_split("/,/",$var)[$itm];
}

function do_firewall_mgm($action)
{
    if ($action == NULL)
        return;

	$rval=0;

    switch ($action) {
        case 'stop':
                exec("/sbin/shorewall stop");
                exec("/sbin/shorewall clear");
                exec("cat /etc/default/shorewall | sed /startup=1/s//startup=0/ > /tmp/shw; cp /tmp/shw /etc/default/shorewall");
                exec("/sbin/ifdown ".g_wan()."; sleep 3; /sbin/ifup ".g_wan()); // restore forwarding as of /etc/network/interfaces
            break;
        case 'start':
                exec("cat /etc/default/shorewall | sed /startup=0/s//startup=1/ > /tmp/shw; cp /tmp/shw /etc/default/shorewall");
                system("/sbin/shorewall start >/tmp/shwlog 2>&1", $rval);
            break;
        case 'restart':
                exec("cat /etc/default/shorewall | sed /startup=0/s//startup=1/ > /tmp/shw; cp /tmp/shw /etc/default/shorewall");
                system("/sbin/shorewall restart  >/tmp/shwlog 2>&1", $rval);
            break;
    }
	if ($rval) {
		exec("/sbin/shorewall stop");
       	exec("/sbin/shorewall clear");
       	exec("cat /etc/default/shorewall | sed /startup=1/s//startup=0/ > /tmp/shw; cp /tmp/shw /etc/default/shorewall");
     	exec("/sbin/ifdown ".g_wan()."; sleep 3; /sbin/ifup ".g_wan());
	} else { unlink("/tmp/shwlog"); }
    
} 

function g_srvstat($srv="nop")
{
    if ("$srv" == "shorewall") {
        @system('/sbin/shorewall status | grep "is stopped" >/dev/null', $ret);
        if ($ret == 1) return true; else return false;
    }
    if ($srv == "ddclient") {
        @system ("if [ -f /var/run/ddclient.pid ] && [ -d /proc/`cat /var/run/ddclient.pid` ]; then exit 1; else exit 0; fi", $ret);
        if ($ret == 1) return true; else return false; 
    }
    
    $var=trim(exec("pidof $srv"));
    if ($var >1) return true;
    return false;
}

function do_firewall_ts($args)
{
    bash("setfirewallts $args");
}

function do_firewall_pw($args)
{
    @system("ckpwroot $args", $rval);
    return $rval==0? true : false;
}

function do_firewall_fws($args)
{
   bash("setfirewallfws $args");
}

function do_firewall_masqif($ifs)
{
    bash("setfirewallmasqif $ifs");
}

function g_dnsrelay()
{
    system('grep -q "dns-relay true" /etc/dhcp/dhcpd.conf',$var);
    if ($var == 0) return true;
    return false;
}
function do_dhcpd($args)
{
    bash("setdhcpd $args");
}

function do_dbhome($args)
{
    bash("setdbhome $args");
}

function do_dbrev($args)
{
    bash("setdbrev $args");
}

function do_dbrecords($args)
{
	bash ("setdbrecords $args");
}

function do_updatedns($fwd1="0",$fwd2="0",$redir="0",$here="0",$dom="0",$ble="0",$zone="0",$lan="eth0",$lan1="eth0:1")
{
    bash("setupdatedns $fwd1 $fwd2 $redir $here $dom $ble $zone $lan $lan1");
}

function do_ddns($en, $dom, $acco, $pw, $prov, $if)
{
    bash("setddns $en $dom $acco $pw $prov $if");
}

function do_vpn($args)
{
    bash("setvpn $args");
}

function do_cifs($args)
{
    bash("setcifs $args");
}

function do_cifs_disk($args)
{
    bash("setcifsdisk $args");
}

function do_transmission($args)
{
    bash("settransmission $args");
}

function g_trcfg()
{
    $p=trim(exec("grep CONFIG_DIR= /etc/default/transmission-daemon | awk -F\\\" '{print $2}'"));
    return $p."/settings.json";
}

function g_spamfmode()
{
    system("grep -q blacklist /etc/bind/named.conf.local",$var);
    if ($var == 0) return true;
    return false;
}

function g_spfredir()
{
     return trim(exec("grep '*' /etc/bind/null.zone.file | awk '{print $4}'"));
}

function p_spfredir()
{
     echo g_spfredir();
}

function g_spfhere()
{
    @system("grep -q 'redir_here=y' /etc/bind/null.zone.file",$var);
    if ($var == 0) {
        @system("ifconfig | grep -q ".LAN1,$var);
        if($var == 0)
            return true;
    }
    return false;
}

function g_srvzone()
{
    $dm=g_domain();
    return trim(exec("grep -m1 subnet /etc/dhcp/dhcpd.conf| awk '{print $2}'"));
}

function p_srvzone()
{
    echo g_srvzone();
}

function do_vrules($args)
{
    bash("setvrules $args");
}

function snCheck($args)
{
    $dip=$args['f_server_ip'];
    $msk=$args['f_server_nmsk'];
    $sip=$args['f_start_ip'];
    $eip=$args['f_end_ip'];
    if (($snet = do_rangeCheck($dip,$msk,$sip,$eip)) !=NULL) {
?>
<html>
   <head>
      <link rel="stylesheet" href="/model/router.css" type="text/css">
      <meta http-equiv="Content-Type" content="no-cache">
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
      <title>BAD RANGE</title>
<script>

function init()
{
    alert("DHCP Adress Range: <?php echo $sip; ?> to <?php echo $eip; ?> not in subnet <?php echo $snet; ?> as of netmask <?php echo $msk; ?>");
    self.location.href="/bsc_dhcp.php";
}
</script>
</head>
<body onload="init();">
</body>
</html>
<?php
    return false;
    }
    return true;
}

?>
