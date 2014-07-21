<?
    /*
     * wifi_survey.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';
    @system("iwscan ".g_wan()." >/tmp/scanlist");
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title><? p_title(); ?></title>
        <script>

var ckssidnum = 0;
var stoptimer = 0;

function ClickMenu(num)
{
   if (!stoptimer)
        stoptimer = 1;

	ckssidnum = num;
}

function StopTimer(num)
{
   if (!stoptimer)
        stoptimer = 1;
    document.getElementById("sel_item"+num).checked = true;
    ckssidnum = num;
}

function valid_pp(pp)
{
    if (pp) return 0;
    alert("A passphrase is required!");
    return 1;
}
function do_connect()
{
	var str="";
    var pp = document.getElementById("ck_passp"+ckssidnum).value;
    var cp = document.getElementById("ck_cipher"+ckssidnum).value;
    if (valid_pp(pp)) return;
	str+="/wireless.php?connect=1&ckssidnum="+ckssidnum+"&ck_passp="+encodeURIComponent(pp)+"&ck_cipher="+cp;
	opener.location.href=str;
	window.close();
}

function do_exit()
{
	window.close();
}

function initPage()
{
    setTimeout("re_scan()",7000);

    var targetWidth = 700;
    var targetHeight = 400;

    window.resizeTo(targetWidth,targetHeight);

    var body = document.body,
    html = document.documentElement;

    var height = Math.max( body.scrollHeight, body.offsetHeight, 
                       html.clientHeight, html.scrollHeight, html.offsetHeight );

    var innerWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    var innerHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

    window.resizeBy(targetWidth-innerWidth, height-innerHeight);
}

function re_scan()
{
    var cnt = document.getElementById("cnt").value;
    if (++cnt > 9)
        window.close();

    if (stoptimer > 0) {
      initPage();
      if (stoptimer++ < 9)
         return true;
      else window.close();
    }
   
	var str="/wifi_survey.php?cnt="+cnt;
	self.location.href=str;
}

</script>
<style>
html, body, td, input
{
	font-family: Tahoma, Helvetica, Geneva, Arial, sans-serif;
	font-size: 0.9em;
    empty-cells: show;
}

html>body
{
    margin: 0;
    padding: 0;
    border: 0;
}

table
{
    width: 100%;
}

td
{   
    background: #c0c0c0;
    text-align:left;
    padding: 4px;
    font-weight: bold;
}

</style>
    </head>
    <body onload="initPage();" style="background-color: #dfdfdf">
        <table>
        <tr>
            <td style="width:20%;">SSID</td>            
            <td>BSSID</td>
            <td>CH</td>
            <td>Security</td>
            <td>Signal</td>
            <td>Type</td>
            <td>Passphrase</td>
            <td>Select</td>
        </tr>
<?
    $fd = fopen("/tmp/scanlist", "r");
    $indx=1;
    while (!feof($fd)) {     
        $a=explode("," ,fgets($fd),7);
        if (!strlen($a[1])) continue;
        if (count($a) == 5) {
            $a[6] = $a[4];
            $a[4] = "";
            $a[5] = "WEP"; 
            $cipher="0";         
        } else {
            $cipher="4";
            switch (trim($a[4])) {
                case 'TKIP': $cipher="2"; break;
                default: if (trim($a[5]) == "WPA2-PSK" && trim($a[4]) == "") {$cipher="3";} break;
            }
        }
        echo '        <tr>'."\n";
        echo '            <td>'.trim($a[1]).'</td>'."\n"; 
        echo '            <td>'.trim($a[0]).'</td>'."\n";
        echo '            <td>'.trim($a[3]).'</td>'."\n";
        echo '            <td>'.trim($a[5]).'</td>'."\n";
        echo '            <td>'.trim($a[6]).'</td>'."\n";
        echo '            <td>'.trim($a[2]).'</td>'."\n";
        echo '            <td><input id="ck_passp'.$indx.'" style="width:96%" name="passphrase" onclick="StopTimer('.$indx.')" value=""></td>'."\n";
        echo '            <td>'."\n";
        echo '                <input type="radio"  name="station" id="sel_item'.$indx.'" onclick="ClickMenu('.$indx.')">'."\n";
        echo '                <input type="hidden" id="ck_ssid'.$indx.'" name="ssid" value="'.trim($a[1]).'">'."\n";
        echo '                <input type="hidden" id="ck_chan'.$indx.'" name="ch" value="'.trim($a[3]).'">'."\n";
        echo '                <input type="hidden" id="ck_cipher'.$indx.'" name="cipher" value="'.$cipher.'">'."\n";
        echo '            </td>'."\n";
        echo '        </tr>'."\n";
        $indx++;
    }
    @fclose($fd);
?>
            
            <tr>
                <td colspan="8" style="text-align:right;">Connect with DHCP&nbsp;&nbsp;
                    <input type="button" value="OK" name="connect" onclick="do_connect()">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
   	 		        <input type="button" value="Cancel" name="exit" onclick="do_exit()">
                    <input type="hidden" id="cnt" name="cnt" value="0<? echo $_GET['cnt']; ?>">
   	            </td>
            </tr>
        </table>
    </body> 
</html>

