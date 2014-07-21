#!/usr/bin/php-cgi -q
<?php
    include '/var/www/cgi-bin/netif.php';
    echo( $THELAN.":1,".$THESRV.",".$THESRVIP."\n");
?>
