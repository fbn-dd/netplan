<?php
require_once(dirname(__FILE__) . "/include/time_start.php");
require_once(dirname(__FILE__) . "/include/layout.php");
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
  <link rel='stylesheet' href='css/gserverl.css' type='text/css'></link>
  <title>Netplan v2</title>
</head>

<body>
<?php printMenu(); ?>
<div class='normalbox'>
<p>Der Netplan bietet eine Übersicht über die Technik rund um das WLAN-Netz des Vereins.</p>

<h1>für alle Mitglieder</h1>
<ul>
    <li><a href="netzplan.php" title="grafische Übersicht über alle Router, Switches und Server sowie deren Netzwerkschnittstellen und Verbindungen untereinander">Netzplan</a> - grafische Übersicht über alle Router, Switches und Server sowie deren Netzwerkschnittstellen und Verbindungen untereinander</li>
    <li><a href="map/" title="Google-Maps-Karte unserer Accesspoints">AP-Karte</a> - Google-Maps-Karte unserer Accesspoints</li>
    <li><a href="earth/" title="Geodaten unserer Accesspoints für Google-Earth">Google-Earth</a> - Geodaten für <a href="http://earth.google.de/">Google Earth</a> als *.kml-Datei herunterladen (<a href="earth/index.php?noinfo=1" title="Geodaten unserer Accesspoints für unsere Webseite">Webseitenversion</a> ohne Links zum Intranet) (<a href="earth/index.php?kml=1">KML</a>, <a href="earth/index.php?kml=0">KMZ</a>)</li>
    <li><a href="stats/" title="bietet die Möglichkeit einzelne Geräte zu betrachten">Routerstatistik</a> - bietet die Möglichkeit Traffic und Signalverläufe einzelner Geräte zu betrachten</li>
    <li>
        <a href="stats/summary.php" title="Routerstatistik für alle WLAN-Router auf einem Blick">Routerübersicht</a> - den Netzwerkverkehr aller WLAN-Router auf einem Blick
        <ul><li>nur <a href="stats/internet.php">Internetzugänge</a> anzeigen</li></ul>
    </li>
    <li><a href="ip.php" title="bietet eine Suche nach WLAN-Routern bei bekanntem Anfang einer IP oder eines IP-Netzes">IP-Suche</a> - Suche nach WLAN-Routern bei mit bekannter IP-Adresse</li>
    <li><a href="/trafficmap/index.html" title="bietet eine Übersicht über den Datenverkehr im Netzwerk">Traffic-Map</a> - Übersicht über die geografische Verteilung des Datenverkehrs im Netzwerk</li>
    <li><a href="/chaski/cgi-bin/mailgraph.cgi">Mailgraph</a> - zeigt die Anzahl ein- und ausgehender E-Mails</li>
    <li><a href="/chaski/cgi-bin/queuegraph.cgi">Queuegraph</a> - zeigt die Größe der E-Mailwarteschlange auf dem E-Mailserver</li>
    <li><a href="/cgi-bin/smokeping.cgi" title="bietet Statistiken zu Pings, Paketverlusten und Latenzen im Netzwerk">Smokeping</a> - Statistiken zu Pings, Paketverlusten und Latenzen im Netzwerk</li>
    <li>
        <a href="/nagios/" title="bietet Netzwerküberwachung und Benachrichtigung">Nagios</a> - Netzwerk- und Serverüberwachung und Benachrichtigung
        <ul>
            <li>nur <a href="/cgi-bin/nagios3/statusmap.cgi?host=all">Statuskarte</a> anzeigen</li>
            <li>nur <a href="/cgi-bin/nagios3/status.cgi?host=all&servicestatustypes=28">ausgefallene Dienste</a> anzeigen</li>
            <li>nur <a href="/cgi-bin/nagios3/status.cgi?hostgroup=all&style=hostdetail&hoststatustypes=12">ausgefallene Geräte</a> anzeigen</li>
        </ul>
    </li>
</ul>
<h2>bearbeiten</h2>
<ul>
    <li><a href="wpa/" title="bietet das Erzeugen eines WPA-Passwortes für ihren WLAN-Client">WPA-Passphrasen</a> -  Erzeugen eines WPA-Passwortes für ihren WLAN-Client für die SSID <a href="http://www.example.org/WPA">www.example.org/WPA</a></li>
</ul>
<h1>für Messteam, Technik und Verwaltung</h1>
<ul>
    <li><a href="edit/" title="Verantwortungsbereiche, Standorte, Geräte, Schnittstellen, zu überwachende Dienste bearbeiten">Netzplan</a> - Verantwortungsbereiche, Standorte, Geräte, Schnittstellen, zu überwachende Dienste bearbeiten</li>
    <li><a href="map/edit.php" title="bietet die Möglichkeit neue Standorte auf der Google-Maps-Karte zu platzieren">AP-Karte</a> - neue Standorte auf der Google-Maps-Karte platzieren</li>
    <li><a href="lager/" title="Bestandsliste des Lagers im Vereinszentrum Dresden">Lager</a> - Bestandsliste des Lagers im Vereinszentrum Dresden</li>
    <li><a href="lanmgmt/" title="bietet Tools und Übersichten zum Verwalten von Switches sowie WLAN-Routern von MikroTik und LANCOM">LAN-Management</a> - Tools und Übersichten zum Verwalten von Switches sowie WLAN-Routern von MikroTik und LANCOM</li>
</ul>
</div>

<div class='normalbox'><?php require(dirname(__FILE__) . "/include/time_end.php"); ?></div>

</body>

</html>
