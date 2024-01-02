<?php 
    /*
     * blacklist.php
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

    $needvars = exec("wc -l /etc/bind/blacklist | awk '{printf \"%s\", \$1}'");

    if ($needvars > ini_get('max_input_vars')) {       
        $needvars = round($needvars, -3);
        exec( "echo 'max_input_vars = ".$needvars."' > ".$_SERVER["DOCUMENT_ROOT"].".user.ini");
    } else $needvars = 0;

    if (g_spfhere() && g_srvstat("named")) $srv=true; else $srv=false;

    if (count($_POST)) {
        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            $fdo = fopen("/tmp/blist.out", "w");
            $fmt = "zone \"%s\" IN { type master; notify no; file \"/etc/bind/null.zone.file\"; };\n";
            $dom="";
            $del="";
          

            foreach ($_POST as $lin => $value) {
                if (is_numeric($lin) == false) continue;

                $dom = $value;
                $del="";
                if (!strncmp($value,"//",2)) {
                    $del="//";
                    $dom = substr($value,2);
                }           
                            
                if (strlen($dom)) {
                    $str=sprintf($del.$fmt, $dom);
                    fputs($fdo, $str);
                }             
            }

            @fclose($fdo);
            if (exec("wc -l /tmp/blist.out | awk '{printf \"%s\", \$1}'")  == exec("wc -l /etc/bind/blacklist | awk '{printf \"%s\", \$1}'")) {
                @system("cp /tmp/blist.out /etc/bind/blacklist");
                @system("/usr/bin/systemctl restart bind9");
            }
            @unlink("/tmp/blist");
            @unlink("/tmp/blist.out");
            @system("topspammers > /tmp/spamlist &");
        } else {
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds=30&loc='.$_SERVER['SCRIPT_NAME']);
            exit;
        }
    }

    @exec("grep '//zone' /etc/bind/blacklist | awk '".'{print $1","$2}' ."' | " .'sed s/\"//g | sort -o /tmp/blist');
    @exec("grep -v '//zone' /etc/bind/blacklist | awk '".'{print $1","$2}' ."' | " .'sed s/\"//g | sort  >> /tmp/blist');
    
    $totlines=@exec("wc -l /tmp/blist | awk '{print $1}'");

    $aPage=10;
    $lastpage=ceil($totlines/$aPage <1? 1:$totlines/$aPage);
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

var aPage=<?php echo $aPage ?>;
var curPage=1;
var curLine=1;

function doPage(from, to)
{
    var i;

    for (i=1; i< <?php echo $totlines ?>; i++) {
        if (i >=from && i <= to)
            getObj("item-"+i).style.display="";
        else
            getObj("item-"+i).style.display="none";
    }
}

function doSearch(obj)
{
    var i;
    var f=0;

    for (i=1; i< <?php echo $totlines ?>; i++) {
        if (getObj("dom_"+i).value.toLowerCase().search(obj.value.toLowerCase())!=-1) {
            getObj("item-"+i).style.display="";
            f++;
        } else
            getObj("item-"+i).style.display="none";
    }
    if (f) {
        getObj("Next").disabled=getObj("Prev").disabled=true;
        getObj("First").disabled=getObj("Last").disabled=false;
        getObj("show_pages").style.display="none";
    }
}

function submitEnter(myfield,e)
{
    var keycode;
    if (window.event) keycode = window.event.keyCode;
    else if (e) keycode = e.which;
    else return true;

    if (keycode == 13)
    {
        doSearch(myfield);
        return false;     
    } else
        return true;   
}


function nextPage()
{
    var lastline=curLine+aPage > <?php echo $totlines ?>? <?php echo $totlines ?>:curLine+aPage;
    if (lastline >= <?php echo $totlines ?>) {
        getObj("Next").disabled=true;
    }
    doPage(curLine, --lastline);
    curLine=lastline+1;
    curPage++;
    getObj("curPage").innerHTML=curPage;
    getObj("Prev").disabled= false;
    getObj("First").disabled=false;
}

function prevPage()
{
    curLine=curLine-(aPage*2) <1? 1:curLine-(aPage*2);
    curPage=curPage-1 <1? 1:curPage-1;
    doPage(curLine, curLine+(aPage-1));
    curLine+=aPage;
    getObj("curPage").innerHTML=curPage;
    if (curLine <=1)
        getObj("Prev").disabled= true;

    getObj("Next").disabled= false;
}

function initPage()
{
    curLine=1;
    curPage=1;
    doPage(curLine, aPage);
    curLine+=aPage;
    document.getElementById("curPage").innerHTML=1;
    if (<?php echo $totlines ?> < <?php echo $aPage ?>)
        getObj("Last").disabled= getObj("Next").disabled= true;
    else
        getObj("Last").disabled= getObj("Next").disabled= getObj("Prev").disabled= false;

    getObj("Prev").disabled=getObj("First").disabled= true;
    getObj("show_pages").style.display="block";

    return true;
}

function lastPage()
{
    var f=<?php echo $totlines ?> < <?php echo $aPage ?>? 1:(<?php echo $totlines ?>-<?php echo $aPage ?>);
    doPage(f, <?php echo $totlines ?>);
    curLine=<?php echo $totlines ?>;
    curPage=getObj("curPage").innerHTML=<?php echo $lastpage; ?>;
    
    getObj("Prev").disabled=getObj("First").disabled=false;
    getObj("Next").disabled=true;
    getObj("show_pages").style.display="block";
}

function checkItem(itm)
{

    if (getObj('cb_'+itm).checked == false) {
        getObj('dom_'+itm).value = '//'+getObj('cb_'+itm).value;
    } else {
        getObj('dom_'+itm).value = getObj('cb_'+itm).value;
    }

    return true;
}

function checkPage()
{
<?php if ($needvars > 0) {?>
    alert("The blacklist is too long to be handled properly.\nYou need to reboot the system to activate an extended blacklist.");
    return false;
<?php }?>
    return true;
}
        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
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
                            <li><div class="leftnavLink"><a href="/firewall.php">Security</a></div></li>
                            <li><div class="leftnavLink"><a href="/virtual.php">Virtual Server</a></div></li>
                            <li><div class="leftnavLink"><a href="/vpn.php">Inbound Access</a></div></li> 
                            <li><div id="left" class="navThis">URL Blacklist</div></li>
                            <li><div class="leftnavLink"><a href="/logfw.php">Firewall Log</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>URL Blacklist</h1>View and manage blacklisted domains.<br>
                        The list is sorted alphabetically with disabled domains shown first.
                        <br><br>
                        <?php if ($srv) { ?>

                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php } echo $srv? '<b style="color:#090;">Service Enabled</b>':'<b style="color:#f00;">This Service is Disabled. Check the "URL Filtering" section under the DNS setting page.</b>'; ?>
                        <div id="show_noactions" style="display:none">
                            <p>The DNS server must be enabled to manage this page. Check the DNS page.</p>
                        </div>	 
		            </div><div class="vbr"></div>
		            <div class="actionBox">
			            <h2 class="actionHeader">Blacklist Details</h2>
			            <div style="padding: 6px;">
                            <input id="First" value="First Page" onclick="initPage()" type="button">
                            <input id="Last"  value="Last Page"  onclick="lastPage()" type="button">
                            <input id="Prev"  value="Previous"   onclick="prevPage()" type="button">
                            <input id="Next"  value="Next"       onclick="nextPage()" type="button">
                            <input value="Refresh" onclick="cancelSettings()" type="button">
                            &nbsp;&nbsp;Find &nbsp;:&nbsp;
                            <input title="Press return" id="search" size="16" maxlength="40" value="" onKeyPress="return submitEnter(this,event)" type="text">
                            <br><br><div id="show_pages">Page <span id="curPage"></span> of <?php echo $lastpage; ?></div>
                        </div>
                        <div style="overflow-y:auto;max-height:400px;">
                        <table class="logTable">
                            <tr><td style="width:50px"><b>Enabled</b></td><td><b>Domain</b></td></tr>
<?php
    $fd = fopen("/tmp/blist", "r");
    $i=1;
    while (!feof($fd)) {     
        $a=explode("," ,trim(fgets($fd)));
        if (count($a) <2) continue;
        $dpre="";
        $d=trim($a[1]);
        if (!strlen($d)) continue;
        if (strncmp($a[0],"//",2)) {
            $chk=" checked='checked' ";
        } else {
            $chk=" ";
            $dpre="//";
        }

        echo "			                <tr id='item-".$i."' style='display:none'><td>";
        echo "<input id='cb_".$i."' type='checkbox'".$chk."value='".$a[1]."' onclick='checkItem(".$i.")'>";
        echo "</td><td><input name='".$i."' id='dom_".$i."' value='".$dpre.$d."' type='hidden'>".$d."</td></tr>\n";
        $i++;    
    }
    fclose($fd);
?>
			            </table>
                        </div>
		            </div><div class="vbr"></div>

                  <div class="actionBox" style="overflow-y:auto;max-height:200px;">
			            <h2 class="actionHeader">Top list of blacklisteds:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php p_denycount(); ?></h2>
			            <table class="logTable">
                        <tr><td style="width: 50px"><b>Hits</b></td><td><b>Domain</b></td></tr>
<?php
    $rval=0;
    while ($rval == 0) {
        @system("ps -e | grep -q topspammers", $rval);
        if ($rval == 0)
            sleep(1);
    }

    if (($fd = fopen("/tmp/spamlist", "r")) == NULL)
   		die("bummer");

    while (!feof($fd)) {
        $str=trim(fgets($fd));
        $a=preg_split("/,/", $str);
        if (count($a) !=2) continue;

        echo "                        <tr><td>".$a[0]."</td><td>".$a[1]."</td></tr>\n";
    }
    @fclose($fd);
?>
                        </table>
                    </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br><br>
                  This service is dependant on that the DNS service is enabled as well as
                        the "URL FILTERING" field in the same setup page.<br><br>
                        <b>NOTE:</b> Changes made here may not be effective immediately because
                        your browser can remember recently visited places.<br>
                        Clear your browser history in such cases.<br><br>
                        URL filtering is based on domain name resolution. Only outbound access,
                        which may trigger DNS can be identified and filtered.
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
