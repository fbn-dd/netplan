<?php
/***
 * siehe server.example.org /etc/apache2/fbn_redirects.conf
 * fuer rewriting/redirect
 * hier auch redirect, falls jemand nicht via server kommt
 */ 
header('Location: http' .
(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" ? "s" : "") .
'://www.example.org/netplan/cgi-bin/lancom_stock/stock.py');

// /usr/lib/cgi-bin/lancom_stock/stock.py
?>
