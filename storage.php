<? 
    /*
     * storage.php
     *
     *  Copyright (C) 2013-2014 by Erland Hedman <erland@hedmanshome.se>
     *
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version
     * 2 of the License, or (at your option) any later version.
     */

    include 'cgi-bin/common.php';

    //if (count($_POST)) {echo "<pre>"; print_r($_POST); echo "</pre>";}

    if (count($_POST) && $_POST["POST_ACTION"] == "OK") {

        unset($_POST['dodisk']);

        if (! function_exists('pcntl_fork')) die('PCNTL functions not available on this PHP installation');
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo("<pre>pcntl_fork: failed - check the extension for pcntl.so\n");
            print_r(error_get_last());
            echo "</pre>\n"; exit;
        } else if (!$pid) {
            // We are the child
            @system("/usr/sbin/service transmission-daemon stop");
            @system("/usr/sbin/update-rc.d transmission-daemon disable");

            if ($_POST["disk_uuid"] != "0" &&  $_POST["disk_action"] > 0)
                do_cifs_disk(implode(" ", $_POST));

            if ($_POST["disk_action"] == 4) {
                $ret=1;
                if ($_POST['disk_inuse'] != "0") {
                    @system("grep -q ".$_POST['disk_inuse']." /proc/mounts", $ret);
                    if ($ret != 0) {
                        unlink($_SERVER["DOCUMENT_ROOT"]."/inc/disk-uuid");
                    }
                }
            } else {
                $ret=1;
                if (strlen($_POST['disk_uuid']) > 10 && $_POST["disk_action"] >0) {
                    @system("grep -q ".$_POST['disk_uuid']." /proc/mounts", $ret);
                    if ($ret == 0) {
                        exec("echo ".$_POST['disk_uuid']." > ".$_SERVER["DOCUMENT_ROOT"]."/inc/disk-uuid");
                        $_POST['disk_inuse'] = $_POST['disk_uuid'];
                    }
                } else if ($_POST["disk_action"] >0) {
                    unlink($_SERVER["DOCUMENT_ROOT"]."/inc/disk-uuid");     
                }

                if ($_POST['disk_inuse'] != "0")
                    do_cifs(implode(" ", $_POST));
            }
        } else {
            // We are the parent
            if ($_POST["disk_action"] == 3) $tmo=40; else $tmo=20;
            header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/wait.php?seconds='.$tmo.'&loc='.$_SERVER['SCRIPT_NAME']);
            exit;
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

<? include 'inc/general.js.php' ?>

<? setTimeout(); ?>

function initPage()
{
    var f=getObj("form");

    if (f.disk_inuse.value == "0")
        getObj("show_cifs_info").style.display = "none";

    getObj("cifs_workgroup").value = "<? echo trim(exec("grep 'workgroup =' /etc/samba/smb.conf | awk -F= '{print \$2}'")); ?>";

    return true;
}

function setInuse(uuid)
{
    var f=getObj("form");

    f.disk_inuse.value=uuid;
    return true;
}

function checkPage()
{
	var f=getObj("form");

    if (isFieldBlank(getObj("cifs_workgroup").value) == true) {
        getObj("cifs_workgroup").select();
        alert("Workgroup field is blank");
        return false;
	}

    if (isFieldAscii(getObj("cifs_workgroup").value) == false) {
	    getObj("cifs_workgroup").select();
		alert("Illegal characters in Workgroup field");
		return false;
    }

    f.POST_ACTION.value = "OK";
	f.submit();

	return true;
}

function setDevice(itm)
{
    var f=getObj("form");

    if (getObj("disk_"+itm+"_lfs").value == "none") {
        alert("You must select a file system first");
        getObj("disk_"+itm+"_act").checked=false;
        return false;
    }

    f.disk_device.value = getObj("disk_"+itm+"_dev").value;
    f.disk_size.value   = getObj("disk_"+itm+"_siz").value;
    f.disk_fsys.value   = getObj("disk_"+itm+"_lfs").value;
    f.disk_uuid.value   = getObj("disk_"+itm+"_uuid").value;
    f.disk_action.value = getObj("disk_"+itm+"_action").value;

    return true;
}

function setPrivate(obj)
{
    if (obj.checked == true) {
        getObj("show_user").style.display = "table-row";
        getObj("show_pw").style.display = "table-row";
    } else {
        getObj("show_user").style.display = "none";
        getObj("show_pw").style.display = "none";
    }
    return true;
}

function resetInput()
{
    getObj("cifs_share_name").value = "";
    getObj("cifs_share_user").value = "";     
    getObj("cifs_share_user_pw").value = "";
    getObj("cifs_share_private").checked = false;
    setPrivate(getObj("cifs_share_private"));
    getObj("cifs_share_name").readOnly = false;

    return true;
}

var editRow = 0;

function addShare(butt)
{

    if (isFieldBlank(getObj("cifs_share_name").value) == true) {
        getObj("cifs_share_name").select();
        alert("Shared folder field is blank");
        return false;
	}

    if (isFieldAscii(getObj("cifs_share_name").value) == false) {
	    getObj("cifs_share_name").select();
		alert("Illegal characters in Shared folder field");
		return false;
    }
    if (getObj("cifs_share_private").checked == true) {
        if (isFieldBlank(getObj("cifs_share_user").value) == true) {
            getObj("cifs_share_user").select();
            alert("User field is blank");
            return false;
	    }
        if (isFieldAscii(getObj("cifs_share_user").value) == false) {
	        getObj("cifs_share_user").select();
		    alert("Illegal characters in User field");
		    return false;
        }
        if (isFieldBlank(getObj("cifs_share_user_pw").value) == true) {
            getObj("cifs_share_user_pw").select();
            alert("Password field is blank");
            return false;
	    }
        if (isFieldAscii(getObj("cifs_share_user_pw").value) == false) {
	        getObj("cifs_share_user_pw").select();
		    alert("Illegal characters in Password field");
		    return false;
        }
        if (getObj("cifs_share_user_pw").value.length < 6) {
            getObj("cifs_share_user_pw").select();
	        alert("Password field is too short");
	        return false;
        }
    }

    if (butt.value == "Update" && editRow >0) {

        var stat = getObj('cifs_share_'+editRow+'_user_privcb').checked = getObj("cifs_share_private").checked; 
        
        if (stat == true) {   
            getObj('cifs_share_'+editRow+'_user').value = getObj("cifs_share_user").value;     
            getObj('cifs_share_'+editRow+'_user_pw').value = getObj("cifs_share_user_pw").value;
            getObj('cifs_share_'+editRow+'_user_priv').value = 1;
        } else {
            getObj('cifs_share_'+editRow+'_user').value = "N/A";   
            getObj('cifs_share_'+editRow+'_user_pw').value = "N/A";
            getObj('cifs_share_'+editRow+'_user_priv').value = 0;
        }
        getObj('cifs_share_'+editRow+'_user_touched').value = 1;
        
        resetInput();
        butt.value = "Save";
        editRow = 0;
        return true;
    }
    var rowIndx = getObj("cifs-shares").getElementsByTagName("tbody")[0].getElementsByTagName("tr").length;

    var table = getObj("cifs-shares");   
    var row = table.insertRow(rowIndx);
   
    var rsh = row.insertCell(0);
    var rus = row.insertCell(1);
    var rpw = row.insertCell(2);
    var rpr = row.insertCell(3);
    var dl = row.insertCell(4);
    var ed = row.insertCell(5);

    var user = "";
    var user_pw ="";
    var chkpriv = "";
    var ckpriv_val = "0";

    var share = getObj("cifs_share_name").value;
    if (getObj("cifs_share_private").checked == true) {
        user = getObj("cifs_share_user").value;
        user_pw = getObj("cifs_share_user_pw").value;
        chkpriv = 'checked="checked" ';
        ckpriv_val = "1";
    } else {
        user = user_pw = "N/A";
    }

    rsh.innerHTML='<input readonly="readonly" style="width:98%" name="cifs_share_'+rowIndx+'_name" id="cifs_share_'+rowIndx+'_name" value="'+share+'" maxlength="40" type="text">';
    rus.innerHTML='<input readonly="readonly" style="width:98%" name="cifs_share_'+rowIndx+'_user" id="cifs_share_'+rowIndx+'_user" value="'+user+'" maxlength="40" type="text">';
    rpw.innerHTML='<input readonly="readonly" style="width:98%" name="cifs_share_'+rowIndx+'_user_pw" id="cifs_share_'+rowIndx+'_user_pw" value="'+user_pw+'" maxlength="40" type="text">';
    
    var rp1 = '<input disabled="disabled" '+chkpriv+'id="cifs_share_'+rowIndx+'_user_privcb" type="checkbox">';
    var rp2 = '<input type="hidden" name="cifs_share_'+rowIndx+'_user_priv" id="cifs_share_'+rowIndx+'_user_priv" value="'+ckpriv_val+'">';
    var rp3 = '<input name="cifs_share_'+rowIndx+'_user_touched" id="cifs_share_'+rowIndx+'_user_touched" value="1" type="hidden">';
    
    rpr.innerHTML= rp1+rp2+rp3;
    
    dl.innerHTML='<img alt="unsh" title="unshare" id="cifs_share_'+rowIndx+'_unsh" onclick="rm_row('+rowIndx+')" src="/img/delete.jpg" style="border:0">'+"\n";
    ed.innerHTML='<img alt="edit" title="edit" onclick="ed_row('+rowIndx+')" src="/img/edit.jpg" style="border:0">';

    restartTimeout();
    resetInput();

    return true;
    
}

function ed_row(rowIndx)
{
    getObj("cifs_share_name").value  = getObj('cifs_share_'+rowIndx+'_name').value;  
    getObj("cifs_share_user").value = getObj('cifs_share_'+rowIndx+'_user').value;     
    getObj("cifs_share_user_pw").value = ""; //getObj('cifs_share_'+rowIndx+'_user_pw').value;
    getObj("cifs_share_private").checked = getObj('cifs_share_'+rowIndx+'_user_privcb').checked == true? true:false;
    getObj("addEdit").value = "Update";
    editRow = rowIndx;
    setPrivate(getObj("cifs_share_private"));
    getObj("cifs_share_name").readOnly = true;
    getObj('cifs_share_'+rowIndx+'_unsh').src = "/img/delete.jpg";

    restartTimeout();
    return true;
}

function rm_row(rowIndx)
{
    getObj('cifs_share_'+rowIndx+'_unsh').src = "/img/deleted.jpg";
    getObj('cifs_share_'+rowIndx+'_user_touched').value = 3;
    restartTimeout();
    return true;
}


        </script> 
    </head>
    <body onload="initPage();"><script>onbody();</script>
    <form name="form" id="form" method="post" action="<? echo $_SERVER['SCRIPT_NAME']; ?>" onsubmit="return checkPage();">
        <input name="POST_ACTION"   value="0"   type="hidden">
        <input name="disk_device"   value="0"   id="disk_device"    type="hidden">
        <input name="disk_size"     value="0"   id="disk_size"      type="hidden">
        <input name="disk_fsys"     value="0"   id="disk_fsys"      type="hidden">
        <input name="disk_uuid"     value="0"   id="disk_uuid"      type="hidden">
        <input name="disk_action"   value="0"   id="disk_action"    type="hidden">
        <input name="disk_inuse"    value="0"   id="disk_inuse"     type="hidden">

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
                            <li><div id="left" class="navThis">Storage</div></li>
                            <li><div class="leftnavLink"><a href="/transmission.php">Tramsmission</a></div></li>
                        </ul>
                    </div>                 
	            </td>
	            <td id="contentHeading">
                    <div id="contentBox">
		                <h1>Settings</h1>
                        Here you can setup your <? p_mode(); ?> to act as a CIFS File Server for most computers on your LAN.
                        <br><br>
                        <input value="Save Settings" type="submit">&nbsp;
                        <input value="Don't Save Settings" onclick="cancelSettings()" type="button">&nbsp;&nbsp;&nbsp;&nbsp;
                        <? echo g_srvstat("smbd")? '<b style="color:#090;">CIFS Service is active</b>':'<b style="color:#f00;">CIFS Service is disabled.</b>'; ?>
		            </div><div class="vbr"></div>

		            <div class="actionBox" id="show_disk_info">
	                    <h2 class="actionHeader">Avaiable Disks and Disk Partitions</h2>
                        This section show disk partitions that can be used by the <? p_mode(); ?>
                        as a network shared resource.<br><br>
			            <table id="disk-parts">      
			               <tbody>                 
                                    <tr>         
                                        <td style="width:20%">Status</td>
                                        <td style="width:20%">Device</td>
                                        <td style="width:10%">Size</td>
                                        <td style="width:10%">Filesystem</td>
                                        <td style="width:10%">Usage</td>
                                        <td style="width:30%">Action</td>
                                    </tr>
<?
    $ourpath="";
    if (stat($_SERVER["DOCUMENT_ROOT"]."/inc/disk-uuid") != false) {
        $ourpart=trim(exec("head -n 1 ".$_SERVER["DOCUMENT_ROOT"]."/inc/disk-uuid"));
    }
    
    @system("freedisk ".$ourpart." > /tmp/freedlist");
    if (($fd = fopen("/tmp/freedlist", "r")) == NULL)
        die("bummer");
 
    $record=1;

    while (!feof($fd)) {

        $str=trim(fgets($fd));
        if ($str == "") continue;
  
        $a=explode(",", $str);
        $script=$name=$rdfs=$rd="";

        $status=$sts=trim($a[0]); $dev=trim($a[1]); $siz=trim($a[2]); $lfs=trim($a[3]); $uuid=trim($a[4]); $usage=trim($a[5]);

        switch ($sts) {
            case 1:
                $sts="Empty partition";
                $com="Format and use";
                $rd="readonly ";
                break;
            case 2:
                $sts="Ready";
                $com="Use as is";
                $rdfs=$rd="readonly ";
                break;
            case 3:
                $sts="Uninitialized disk";
                $com="Set size and use";
                break;
            case 4:
                $sts="In use as share";
                $rdfs=$rd="readonly ";
                $com="Unshare";
                //$script='<script>getObj("form").disk_inuse.value="'.$uuid.'";</script>';
                $script="<script>setInuse('".$uuid."')</script>";
                break;
            default:
                die("Internal Error");
                break;
        }
?>
                                    <tr>
                                        <td><input readonly style="width:90%" id="disk_<?echo $record;?>_sts" value="<?echo $sts;?>" type="text"></td>
                                        <td><input readonly style="width:90%" id="disk_<?echo $record;?>_dev" value="<?echo $dev;?>" type="text"></td>
                                        
                                        <? if ($lfs == "none") {?><td><select id="disk_<?echo $record;?>_siz">
                                            <option value="1.0"><?echo $siz;?></option>
                                            <option value="0.2">20%</option>
                                            <option value="0.4">40%</option>
                                            <option value="0.5">50%</option>
                                            <option value="0.6">60%</option>
                                            <option value="0.8">80%</option>
                                        </select></td>
                                            <td><select id="disk_<?echo $record;?>_lfs">
                                            <option value="none">empty</option>
                                            <option value="ext4" title="recommended">ext4</option>
                                            <option value="ext3">ext3</option>
                                            <option value="xfs">xfs</option>
                                            <option value="reiserfs">reiserfs</option>
                                            <option value="fat32">vfat</option>
                                        </select>
                                        <?}else{?><td><input <? echo $rd; ?>style="width:90%" id="disk_<?echo $record;?>_siz" value="<?echo $siz;?>" type="text"></td>
                                        <td><input <? echo $rdfs; ?>style="width:90%" id="disk_<?echo $record;?>_lfs" value="<?echo $lfs;?>" type="text"></td>
                                        <?}?><td><input readonly style="width:90%" id="disk_<?echo $record;?>_usage" value="<?echo $usage; ?>" type="text"></td>
                                        <td>
                                            <input name="dodisk" id="disk_<?echo $record;?>_act" type="radio" onchange="setDevice(<?echo $record;?>)"><?echo $com."\n"; ?>
                                            <input id="disk_<?echo $record;?>_uuid" value="<?echo $uuid; ?>" type="hidden">
                                            <input id="disk_<?echo $record;?>_action" value="<?echo $status; ?>" type="hidden"><? echo $script; ?>
                                        </td>
                                    </tr>
<?
        $record++; 
    } 
    @fclose($fd);

?>
			            </table>
		            </div><div class="vbr"></div>

                    <div id="show_cifs_info">
                    <div class="actionBox">
	                    <h2 class="actionHeader">CIFS File Sharing</h2>
                        This section manages network shares for a named Workgroup.<br><br>
                        <table>
                            <tr>
				                <td style="width:30%" class="raCN"><b>Workgroup :</b></td>
				                <td>&nbsp;<input style="width:30%" type="text" id="cifs_workgroup" name="cifs_workgroup" maxlength="60" value=""></td>
			                </tr>
                            <tr>
				                <td style="width:30%" class="raCN"><b>Charset :</b></td>
				                <td>&nbsp;
                                    <select id="cifs_charset" name="cifs_charset">
                                        <option value="utf8">utf8</option>
                                    </select>
                                </td>
			                </tr>
                            <tr><td>&nbsp;</td></tr>
                            <tr>
				                <td class="raCN"><b>Shared folder :</b></td>
				                <td>&nbsp;<input type="text" id="cifs_share_name" maxlength="60" value=""></td>
			                </tr>
                            <tr>
				                <td class="raCN"><b>Private :</b></td>
				                <td>&nbsp;
                                    <input type="checkbox" id="cifs_share_private" onchange="setPrivate(this);">
                                </td>
                            </tr>
                            <tr id="show_user" style="display:none">
                                <td class="raCN"><b>User :</b></td>
                                <td><input type="text" id="cifs_share_user" maxlength="60" value=""></td>
                            </tr>
                            <tr id="show_pw" style="display:none">
                                <td class="raCN"><b>Password :</b></td>
                                <td><input type="text" id="cifs_share_user_pw" maxlength="60" value=""></td>
			                </tr>
                            <tr>
                                <td colspan="2" class="raCN"><b>This share :</b>&nbsp;<input type="button" id="addEdit" value="Add" onclick="addShare(this);">&nbsp;&nbsp;</td>
			                </tr>
                        </table>
                    </div><div class="vbr"></div>
                    <div class="actionBox" style="overflow-y:auto;max-height:500px;">
	                    <h2 class="actionHeader">Avaiable Network Shares</h2>
                        This section show publiched network shares.<br><br>
			            <table id="cifs-shares">      
			               <tbody>                 
                                    <tr>         
                                        <td style="width:30%">Share</td>
                                        <td style="width:25%">User</td>
                                        <td style="width:25%">Password</td>
                                        <td style="width:10%">Private</td>
                                        <td colspan="2" style="width:10%">Action</td>
                                    </tr>
<?
    
    @system("cifsshares > /tmp/cifsshares");
    if (($fd = fopen("/tmp/cifsshares", "r")) == NULL)
        die("bummer");
 
    $record=1;

    while (!feof($fd)) {

        $str=trim(fgets($fd));
        if ($str == "") continue;
  
        $a=explode(",", $str);
        $share=$a[0]; $user=$a[1]; $path=$a[2];

        if ($user != "PUBLIC") {$checked='checked="checked" ';$pw="xxxxxxxx";$priv=1;}else{$user="N/A";$pw="N/A";$checked="";$priv=0;}

?>
                                <tr>
                                    <td><input readonly="readonly" style="width:98%" name="cifs_share_<?echo $record;?>_name" id="cifs_share_<?echo $record;?>_name" value="<?echo $share;?>" maxlength="40" type="text"></td>
                                    <td><input readonly="readonly" style="width:98%" name="cifs_share_<?echo $record;?>_user" id="cifs_share_<?echo $record;?>_user" value="<?echo $user;?>" maxlength="40" type="text"></td>
                                    <td><input readonly="readonly" style="width:98%" name="cifs_share_<?echo $record;?>_user_pw" id="cifs_share_<?echo $record;?>_user_pw" value="<? echo $pw?>" maxlength="40" type="text"></td>
                                    <td>
                                        <input disabled="disabled" <?echo $checked;?>id="cifs_share_<?echo $record;?>_user_privcb" type="checkbox">
                                        <input name="cifs_share_<?echo $record;?>_user_priv" id="cifs_share_<?echo $record;?>_user_priv" value="<?echo $priv;?>" type="hidden">
                                        <input name="cifs_share_<?echo $record;?>_user_touched" id="cifs_share_<?echo $record;?>_user_touched" value="0" type="hidden">
                                    </td>
                                    <td><img alt="unsh" title="unshare" id="cifs_share_<?echo $record;?>_unsh" onclick="rm_row(<?echo $record;?>)" src="/img/delete.jpg" style="border:0"></td>
                                    <td><img alt="edit" title="edit" onclick="ed_row(<?echo $record;?>)" src="/img/edit.jpg" style="border:0"></td>
                                </tr>
<?
            $record++; 
    } 
    @fclose($fd);
?>
                            </tbody>
                        </table>
                    </div> 
                    </div>                

                </td>
	            <td id="quickHelpContent"><strong>Help...</strong><br>
                  <br>
                    Changes made here do not affect the contents of a disk, but only how the content
                    is presented on the local network.<br><br>
                    A selected disk that already contains a file system  will preserve their data,
                    but previous data will not be shared from this service.<br><br>
                    A blank disk with no partitions can be configured and assigned to a new file system of your choice.
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
