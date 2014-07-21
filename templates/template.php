<? 
    include 'common.php';
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="/css/bridge.css">
        <title><? p_title(); ?></title>
        <script>

<? setTimeout(0); ?>

<? include 'inc/general.js.php' ?>

function initPage()
{
	var f=getObj("form");
    return true;
}

function checkPage()
{
	var f=getObj("form");

    f.POST_ACTION.value = "AVALUE";
	f.submit();

	return true;
}
        </script> 
    </head>
    <body onload="initPage();">
    <form name="form" id="form" method="post" action="<? echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION" value="" type="hidden">
        <table id="topContainer">
            <tr>
	            <td class="laCN">Project Page&nbsp;:&nbsp;<a href="<? p_productHome(); ?>" target=_blank><? p_serverName(); ?></a></td>
	            <td class="raCN">Version&nbsp;:&nbsp;<? p_firmware("-ro");?>&nbsp;</td>
            </tr>
        </table>
        <table id="topTable">
            <tr>
	            <td id="topBarLeft"><a href="<? p_productHome(); ?>"><img alt="imgLeft" src="/img/head_left.gif"></a></td>
	            <td id="topBarCenter"><img id="imgCenter" alt="imgCenter" src="/img/head_center.gif"></td>
	            <td id="topBarRight"><img alt="imgRight" src="/img/head_right.gif"></td>
            </tr>
        </table>
        <table id="topMenuTable">
            <tr>
	            <td class="topMenuThis"><img id="logo" alt="imgLogo" src="/img/bridge.gif"></td>
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
                            <li><div id="left" class="navThis">Security</div></li>
                            <li><div class="leftnavLink"><a href="/adv_virtual.php">Virtual Server</a></div></li> 
                            <li><div class="leftnavLink"><a href="/bsc_blist.php">URL Blacklist</a></div></li>
                            <li><div class="leftnavLink"><a href="/st_logfw.php">Firewall Log</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading"> <!-- Main Content Cell-->
                    <div id="contentBox">
		                <h1>Settings</h1>
                        Some text.
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">
		            </div><div class="vbr"></div>
		            <div class="actionBox">
			            <h2 class="actionHeader">Actions Title</h2>
                            Some text.<br><br>
			            <table>
			                <tr>
				                <td>actions</td>
			                </tr>
			            </table>
		            </div>
                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    You can 
                     <br><br>
                     <p class="quickHelp"></p>some text</b></p>
               </td>
            </tr>
            <tr>
	            <td colspan="3" id="footer">
                    <img alt="imgTail" style="height: 16px;" src="/img/tail.png">
                </td>                   
            </tr>
        </table>
        <br>
        <div style="text-align: center;">Copyright &copy; <? p_copyRight(); ?></div>
    </form>
    </body>
</html>
