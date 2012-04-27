<?php

define( "URL_INDEX",     '/netplan/index.php' );
define( "URL_NETPLAN",   '/netplan/netzplan.php' );
define( "URL_EDIT",      '/netplan/edit/index.php' );
define( "URL_MAP",       '/netplan/map/index.php' );
define( "URL_MAPEDIT",   '/netplan/map/edit.php' );
define( "URL_EARTH",     '/netplan/earth/index.php' );
define( "URL_STATS",     '/netplan/stats/' );
define( "URL_SUMMARY",   '/netplan/stats/summary.php' );
define( "URL_TRAFFIC",   '/trafficmap/index.html' );
define( "SCRIPT_TRAFFIC",'/opt/weathermap/weathermap' );
define( "URL_NAGIOS",    '/nagios/index.php' );
define( "URL_SMOKEPING", '/cgi-bin/smokeping.cgi' );
define( "URL_INTRANET",  '/wiki/' );
define( "URL_WPAEDIT",   '/netplan/wpa/index.php' );
define( "URL_IPSEARCH",  '/netplan/ip.php');
define( "URL_LAGER",     '/netplan/lager/');
define( "URL_LANMGMT",   '/netplan/lanmgmt/index.php');

function printMenu($returnoutput = FALSE) {
  if ( $returnoutput == TRUE ) {
    // Ausgabepufferung aktivieren
   ob_start();
  }
?>
<div class='normalbox'>
  [
<?php echo $_SERVER['SCRIPT_NAME'] != URL_INDEX?'<a href="'.URL_INDEX.'">Startseite</a>':'<b>Startseite</b>'; ?> |
<?php echo $_SERVER['SCRIPT_NAME'] != URL_NETPLAN?'<a href="'.URL_NETPLAN.'">Netzplan</a> | ':'<b>Netzplan</b> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != URL_MAP?'<a href="'.URL_MAP.'">AP-Karte</a> | ':'<b>AP-Karte</b> | '; ?>
<?php echo '<a href="'.URL_EARTH.'" title="Geodaten für Google Earth als *.kml-Datei herunterladen">Google Earth</a> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != URL_STATS?'<a href="'.URL_STATS.'">Routerstatistik</a> | ':'<b>Routerstatistik</b> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != URL_SUMMARY?'<a href="'.URL_SUMMARY.'">Router&uuml;bersicht</a> | ':'<b>Router&uuml;bersicht</b> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != URL_IPSEARCH?'<a href="'.URL_IPSEARCH.'">IP-Suche</a> | ':'<b>IP-Suche</b> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != SCRIPT_TRAFFIC?'<a href="'.URL_TRAFFIC.'">Traffic-Map</a> | ':'<b>Traffic-Map</b> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != '/cgi-bin/smokeping.cgi' ? '<a href="'.URL_SMOKEPING.'">SmokePing</a> | ' : '<b>SmokePing</b>'; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != '/netplan/nagios/nav.php' ? '<a href="'.URL_NAGIOS.'">Nagios</a> | ' : '<b>Nagios</b> | '; ?>
<?php echo '<a href="'.URL_INTRANET.'">zur&uuml;ck zum Intranet</a>'; ?>
  ]
</div>
<div class='normalbox'>
bearbeiten
  [
<?php echo $_SERVER['SCRIPT_NAME'] != URL_EDIT?'<a href="'.URL_EDIT.'">Netzplan</a> | ':'<b>Netzplan</b> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != URL_MAPEDIT?'<a href="'.URL_MAPEDIT.'">AP-Karte</a> | ':'<b>AP-Karte</b> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != URL_WPAEDIT?'<a href="'.URL_WPAEDIT.'">WPA-Passphrasen</a> | ':'<b>WPA-Passphrase</b> | '; ?>
<?php echo $_SERVER['SCRIPT_NAME'] != URL_LAGER?'<a href="'.URL_LAGER.'">Lager</a>':'<b>Lager</b>'; ?> | 
<?php echo $_SERVER['SCRIPT_NAME'] != URL_LANMGMT?'<a href="'.URL_LANMGMT.'">LAN-Management</a>':'<b>LAN-Management</b>'; ?>
  ]
</div>

<?php
  if ( $returnoutput == TRUE ) {
    // bisherige Ausgabe zwischenspeichern
    $out = ob_get_contents();
    // Ausgabepuffer leeren und deaktivieren
    ob_end_clean();
    // Ausgabe zurueckgeben
    return $out;
  }
}

?>
