<?
    /*
     * login.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman<erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */
 
    include 'cgi-bin/common.php';

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>";exit;}

    $msg = null;
    if (count($_POST) && trim($_POST["login"]) == "Login" && trim($_POST["USER_ADMIN"]) == "admin") {

        $pw1=trim(exec("cat ".$_SERVER["DOCUMENT_ROOT"]."/inc/gui.secrets"));
        $pw2=md5(trim($_POST["LOGIN_PASSWD"])); 

        if (strlen($pw1) && strcmp($pw1, $pw2)) {
            session_destroy();
            $msg = "<b>Login incorrect</b>"; 
        } else {
            $_SESSION['username'] = $_POST["USER_ADMIN"];
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/status.php');
            die("header error");
        }
    } else {
        session_destroy();
        if (!strlen(trim($_POST["USER_ADMIN"])) && count($_POST))
            $msg = "<b>Error: Java Script must be enabled</b>";
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link rel="stylesheet" type="text/css" href="/css/bridge.css">
        <title><? p_title(); ?></title>
        <script>

<? include 'inc/general.js.php' ?>

function initPage()
{
	var f=getObj("form");
	f.USER_LOGIN.value = "admin";
	f.USER_LOGIN.disabled = true;
    f.LOGIN_PASSWD.focus();
}

function checkPage()
{
	var f=getObj("form");
	if(f.USER_LOGIN.value=="")
	{
		alert("Please input the User Name.");
		f.USER_LOGIN.focus();
		return false;
	}
    f.USER_ADMIN.value=f.USER_LOGIN.value;
	return true;
}
</script>
    </head>
    <body onload="initPage();" style="width:600px;"><script>onbody();</script>
    <form style="height:0" name="form" id="form" method="post" action="<? echo $_SERVER['SCRIPT_NAME']; ?>">
        <input type="hidden" name="USER_ADMIN" value="">
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
        <table id="mainContentTable">
            <tr style="vertical-align: top;">
	            <td style="padding: 4% 8% 5% 8%">
		            <div class="actionBox" style="background-color: #DEDEDE">
			            <h2 class="actionHeader">Login</h2>
			            <table>
			                <tr>
				                <td class="raCB" style="width:40%; height:30px">User Name :</td>
				                <td style="width:20%"><input type="text" name="USER_LOGIN"></td>
                                <td></td>
			                </tr>
			                <tr>
				                <td class="raCB">Password :</td>
				                <td><input type="password" name="LOGIN_PASSWD" id="LOGIN_PASSWD"></td>
				                <td><input type="submit" name="login" value=" Login " onclick="return checkPage()"></td>
			                </tr>
                            <tr><td colspan="3"><? echo $msg; ?></td></tr>
			            </table>
		            </div>
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
