<?php

    /*
     * logfw.php
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

   if (count($_POST) && $_POST["POST_ACTION"] == "OK") {

        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child
            do_fwlog(implode(" ", $_POST));
            @system("/usr/sbin/service ulogd restart");
            @system("/sbin/shorewall restart");
        } else { 
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds=10&loc='.$_SERVER['SCRIPT_NAME']);
            exit;
        }
    }
    @system("grep REJECT /etc/shorewall/policy | grep ".'"\$LOG" >/dev/null', $rej);
    @system("grep DROP /etc/shorewall/policy | grep ".'"\$LOG" >/dev/null', $drp);
    $rej=$rej==0? 1:0;
    $drp=$drp==0? 1:0;   

    @exec('tail -n 300 /var/log/firewall | sed s/"Shorewall:*:"/" "/g | awk '."'".'{printf "%s,[%s] %s %s %s\n",$3,$5,$9,$17,$18}'."' >/tmp/fwlog"); 
   
    $totlines=@exec("wc -l /tmp/fwlog | awk '{print $1}'");

    $aPage=20;
    $lastpage=ceil($totlines/$aPage <1? 1:$totlines/$aPage);
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

function doSearch(obj) //str.toLowerCase()
{
    var i;
    var f=0;

    for (i=1; i< <?php echo $totlines ?>; i++) {
        if (getObj("msg_"+i).value.toLowerCase().search(obj.value.toLowerCase())!=-1) {
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

function enable_rj(objs)
{
    var f=getObj("form");
    f.f_rj_enabled=(objs.checked? 1:0);
    return true;
}
function enable_dr(objs)
{
    var f=getObj("form");
    f.f_dr_enabled=(objs.checked? 1:0);
    return true;
}

function checkPage()
{
    var f=getObj("form");

    f.f_dr_enabled.value=(getObj("dr_enablecb").checked? 1:0);
    f.f_rj_enabled.value=(getObj("rj_enablecb").checked? 1:0);
    
    f.POST_ACTION.value = "OK";
	f.submit();
    return true;
}
        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
     <form name="form" id="form" method="post" action="<?php echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION" value="" type="hidden">
        <input name="f_dr_enabled" value="<?php echo $drp ?>" type="hidden">
        <input name="f_rj_enabled" value="<?php echo $rej ?>" type="hidden">
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
                            <li><div class="leftnavLink"><a href="/blacklist.php">URL Blacklist</a></div></li>
                            <li><div id="left" class="navThis">Firewall Log</div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>View the firewall log.</h1>
                        <br>
                        <?php  if (g_srvstat("shorewall")) { ?>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
                        <?php }?>
                        <div style="text-align:right"><?php echo g_srvstat("shorewall")? '<b style="color:#090;">Firewall: active</b>':'<b style="color:#f00;">Firewall: stopped</b>' ?></div>
		            </div><div class="vbr"></div>
                    <div class="actionBox">         
	                    <h2 class="actionHeader">Log Options</h2>
                        <table> 
		                    <tr>
		                        <td class="raCB" style="width:40%;height:25px">Log dropped packets&nbsp;:</td>
		                        <td class="laCB">&nbsp;
		                            <input <?php echo $drp==1? "checked ":""; ?>type="checkbox" id="dr_enablecb" onChange="enable_dr(this)">
		                        </td>
		                    </tr>
                            <tr>
		                        <td class="raCB" style="height:25px">Log rejected packets&nbsp;:</td>
		                        <td class="laCB">&nbsp;
		                            <input <?php echo $rej==1? "checked ":""; ?>type="checkbox" id="rj_enablecb" onChange="enable_rj(this)">
		                        </td>
		                    </tr>
                        </table>
		            </div><div class="vbr"></div>

		            <div class="actionBox">
			            <h2 class="actionHeader">Log Details</h2>
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
                        <table class="logTable">
                            <tr><td style="width:60px"><b>Time</b></td><td><b>Message</b></td></tr>
<?php
    $fd = fopen("/tmp/fwlog", "r");
    $i=1;
    while (!feof($fd)) {     
        $a=explode("," ,trim(fgets($fd)));
        if (count($a) <2) continue;
        echo "			                <tr id='item-".$i."' style='display:none'><td>".$a[0]."</td>";
        echo "<td><input name='".$i."' id='msg_".$i."' value='".$a[1]."' type='hidden'>".$a[1]."</td></tr>\n";
        $i++;    
    }
    fclose($fd);
    unlink("/tmp/fwlog");
?>
                        </table>
                    </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br><br>
                    Check the log to view firewall activities.<br><br>
                    The log is resetted every 15 minutes.
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
