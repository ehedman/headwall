<?php
    /*
     * wd_log.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */
 
    include 'cgi-bin/common.php';

    @exec("cat /var/log/watchdogd.log | awk '{printf \"%s,%s,\",$2,$1; $1=$2=\"\"; print $0}' > /tmp/wlog");
    
    $totlines=@exec("wc -l /tmp/wlog | awk '{print $1}'");

    $aPage=18;
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
}
        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form">
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
                            <li><div class="leftnavLink"><a href="/advanced.php">Advanced</a></div></li> 
                            <li><div id="left" class="navThis">Logs</div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Logs</h1>
                        View the watchdog log.
                        <br>
		            </div><div class="vbr"></div>
		            <div class="actionBox">
			            <h2 class="actionHeader">Log control</h2>
			            <div style="padding: 6px;">
                            <input id="First" value="First Page" onclick="initPage()" type="button">
                            <input id="Last"  value="Last Page"  onclick="lastPage()" type="button">
                            <input id="Prev"  value="Previous"   onclick="prevPage()" type="button">
                            <input id="Next"  value="Next"       onclick="nextPage()" type="button">
                            <input value="Refresh" onclick="cancelSettings()" type="button">
                            <br><br>Page <span id="curPage"></span> of <?php echo $lastpage; ?><br><br>
                        </div>
                        <table class="logTable">
                            <tr><td style="width: 14%">Time</td><td  style="width: 14%">Daemon</td><td>Message</td></tr>
<?php
    $fd = fopen("/tmp/wlog", "r");
    $i=1;
    while (!feof($fd)) {     
        $a=explode("," ,trim(fgets($fd)));
        echo "			                <tr id='item-".$i."' style='display:none'><td class='logCell'>".$a[0]."</td><td class='logCell'>".$a[1]."</td><td class='logCell'>".$a[2]."</td></tr>\n";
        $i++;    
    }
    fclose($fd);
    unlink("/tmp/wlog");
?>
			            </table>
		            </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    Check the log to view watchdog activities. 
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
