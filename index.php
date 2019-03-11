<?php
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
    header('Location: http://'. $_SERVER["SERVER_ADDR"]. '/login.php');
    die();
?>
