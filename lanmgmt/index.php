<?php
require_once(dirname(__FILE__) . "/../include/time_start.php");
//require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/layout.php");
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
    <title>LAN-Management</title>
    <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
</head>
<body>
<?php printMenu(); ?>
<div class="normalbox">
<h1>LAN-Management</h1>
<h2>für Messteam und Technik</h2>
<ul>
    <li><a href="inventur.php">Inventur</a> - Übersicht über alle LANCOM-Geräte, -Typen und -Firmwserver</li>
    <li><a href="p2pwpa.php">P2P-WPA</a> - Übersicht der Verschlüsselung aller P2P-Strecken zwischen LANCOM-Geräten</li>
    <li><a href="channel.php">Channel</a> - Übersicht der DFS-Einstellungen aller LANCOM-Geräte</li>
    <li><a href="../lanscan/">LANCOM-Scan</a> - Liest SNMP-Daten eines LANCOM-Gerätes aus</li>
</ul>
<h2>für Administratoren</h2>
<ul>
    <li><a href="../lanconfig/">LANconfig-Konfiguration</a> - Enthält alle APs zum Kopieren in die eigene lanconf.ini</li>
    <li><a href="../winbox/">MikroTik-Konfiguration</a> - Enthält alle APs zum Importieren in der Winbox</li>
    <li><a href="../websvn/">Backups der Konfigurationen</a> - Enthält alle Backups von Switches, Lancoms und MikroTik zum Betrachten der Unterschiede und Herunterladen für die Wiederherstellung</li>
</ul>
</div>
</body>
</html>
