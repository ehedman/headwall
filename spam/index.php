<?php

    /*******************************************************************************
    *  Title: PHP *click bypasser
    *  Version: 1.0 @ October 19, 2013
    *  Author: Erland Hedman
    *  Website: http:/www.hedmanshome.se
    *  Copyright: Free to use - the credit to me should remain in this header.
    ********************************************************************************
    * Description:
    * Instead of stopping on blacklisted sites when clicking on sponsored links in
    * google, bypass these links and go to the end destiantion that you still may
    * be interested in.
    * Prerequsites:
    * The "bind" DNS daemon installed on a (this) host that directs unwanted URLs
    * to this host with a HTTP server with PHP support.
    * The bind blacklist is maintained at http://pgl.yoyo.org/adservers/ and possibly others.
    * Apache must have option: ErrorDocument 404 /index.php # where index.php is this file
    * Lighthttpd must have option: server.error-handler-404 = "/index.php" # where index.php is this file
    */
    //error_reporting(E_ALL ^ E_NOTICE);
    error_reporting(0);
    $STOPPNG = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/stop.png";
    if (1) {
        // Go to the end destination without trackers, doubleclick etc.
        // This is mainly to handle ads from google search clicks.
            
        // Click sites to skip. Add as you hit them.
        $jump = array(
/*
            "googleadservices"  => array(   "/url\[\]=http:\/\//",
                                            "/lp=http:\/\//",
                                            "/dst=/",
                                            "/adurl=/",
                                            "/url\(/" ),
            "doubleclick"   => array("/http:\/\//"),   
            "adform"        => array("/http:\/\//"),
            "webgains"      => array("/http:\/\//"),
            "webgains"      => array("/http:\/\//"),
            "tradedoubler"  => array("/http:\/\//", "/url=/"),
            "marinsm"       => array("/lp=http:\/\//"),
            "intelliad"     => array("/https:\/\//"), 
*/
        );
        $log = TRUE;
        $DBG = FALSE;

        $ahost = preg_split("/\./", $_SERVER[HTTP_HOST], -1, PREG_SPLIT_NO_EMPTY);
        $dhost = $ahost[count($ahost)-2];
        $str=urldecode(rawurldecode($_SERVER[REQUEST_URI]));
        if ($log) {$fpl = @fopen("/tmp/rejected_log", 'a+');}
        $found = FALSE;
        foreach ($jump as $host => $val) { 
            if ($host == $dhost) {
                foreach ($val as $expr) {
                    $foud = TRUE;
                    $arr=preg_split($expr, $str,  -1, PREG_SPLIT_NO_EMPTY);
                    if ($log && $DBG) fwrite($fpl, "Trying to skip $dhost with pattern='".$expr."' against=$str\n");
                    if (count($arr) < 2) continue;                   
                    $url = preg_replace('/http:\/\//', '', preg_replace('/[)]/', '', $arr[count($arr)-1]));
                    if ($log && $DBG) fwrite($fpl, "host $dhost skipped - redirect to: http://" . $url . "\n");
                    header ("Location: http://" . $url);
                    break;
                }
            }
            if ($found) break;
        }
    }

    if ($log) {fwrite($fpl, $_SERVER[HTTP_HOST]."\n"); fclose($fpl);}

    // Still here? - bring up the stop sign!
    $fc = "/tmp/deny_counter";
    $max_count = 90000;     // before reset
    $count = 1;
    if ($fp = @fopen($fc, 'r+')) {
        $lck = flock($fp, LOCK_EX);
        if ($lck) {
            $count = intval(trim(stream_get_line($fp, 8, "\n"))); 
            $count = $count + 1;
            $date  = fread($fp,10);
            rewind($fp);
            fwrite($fp, $count . "\n");
            fwrite($fp, $date);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
        if ($count >= $max_count) unlink($fc); // reset the counter
    } else {
        if ($fp == FALSE) {
            // create it
            $fp = @fopen($fc, 'w+');
            fwrite($fp, "1" . "\n");
            fwrite($fp, date('Y-m-d') . "\n");
            $date = date('Y-m-d');
            fclose($fp);
        }
    }
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>Forbidden Web Access</title>
    </head>
    <body>
        <img alt="STOP" src="<?php echo $STOPPNG; ?>">
        <p>Access to this Web site is not allowed from this computer.</p>
        <p>The site <b><?php echo $_SERVER[HTTP_HOST] ?></b> is included in the DNS Blacklist Site List.</p>
        <p>This server has detected <?php echo $count; ?> attempts to access blacklisted sites since <?php echo $date ?></p>
    </body>
</html>

