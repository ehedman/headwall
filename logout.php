<?php
    /*
     * logout.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';
    session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="/css/bridge.css">
        <title><?php p_title(); ?></title>
        <script>

<?php include 'inc/general.js.php' ?>

function doLogin()
{
    self.location.href="/login.php";
}
</script>
    </head>
    <body style="width:600px;"><script>onbody();</script>
        <input type="hidden" name="USER_ADMIN" value="">
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
        <table id="mainContentTable">
            <tr style="vertical-align: top;">
	            <td style="padding: 4% 8% 5% 8%">
		            <div class="actionBox" style="background-color: #DEDEDE">
			            <h2 class="actionHeader">Logout</h2>
			            <table>
			                <tr>
				                <td class="laCB" style="text-align: center; height:57px;">
				                    You have logout.<br><br>
                                    <input type="button" value="Return to login page" onclick="doLogin();">
                                </td>
			                </tr>
			            </table>
		            </div>
                </td>
            </tr>
            <tr>
	            <td colspan="3" id="footer">
                    Copyright &copy; <?php p_copyRight(); ?>
                </td>                   
            </tr>
        </table>
    </body>
</html>
