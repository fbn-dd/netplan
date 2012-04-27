<?php
require_once(dirname(__FILE__) . "/../include/time_start.php");
require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/layout.php");

$snmpTimeout = 30000; /*in Millisekunden */

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
    <title>LANCOM-Wireless-Settings</title>
    <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
    <script src="../js/sorttable.js"></script>
</head>
<body>
<?php printMenu(); ?>
<div class="normalbox" style="float:left;">
<h1></h1>
<table class="sortable">
<thead>
	<tr>
		<th>Ger&auml;t</th>
		<th>Land</th>
		<th>Interface</th>
		<th>Kanal</th>
	</tr>
</thead>
<tbody>
<?php
$er = error_reporting(0);

$oidsysObjectId = "sysObjectId";

$interface = DB_DataObject::factory('interface');
$interface->query('SELECT n.description as description, i.ip as ip, n.snmp_community as snmp_community, d.description as iface, t.description as type FROM interface as i, node as n, device as d, type as t WHERE i.id_mode IN (1,2,3,4) AND i.id_node=n.id_node AND i.id_device=d.id_device AND n.id_type=t.id_type AND t.description LIKE "LANCOM%" ORDER BY t.description ASC');

$color_good = 'green';
$color_bad  = 'red';

/* Zähler für Interfaces, Land, Indoor-Kanäle, Wetterradar-Kanäle */
$i=$j=$k=$l=0;

while ($interface->fetch())
{
$color_country = false;
$color_channel = false;

// get sysObjectId
$sysObjectId="";
$community="public";
if ($interface->snmp_community != "") $community=$interface->snmp_community;
$sysObjectId=snmpget($interface->ip, $community, $oidsysObjectId, $snmpTimeout);
if (!$sysObjectId) continue;

// get Country
$country = "";
$countryId = null;
$countryOid = '.2.12.36';
$countryOid = $sysObjectId.$countryOid;

$countryId=snmpget($interface->ip, $community, $countryOid, $snmpTimeout);

if($countryId==false) $countryId=snmpget($interface->ip, $community, $countryOid, $snmpTimeout);

if ($countryId==276) {
	$j++;
	$color_country = $color_good;
	$country = 'Deutschland';
} else {
	$color_country = $color_bad;
	$country = 'nicht Deutschland';
}

$ifcId = $sysObjectId;
$ifcId .= ".1.3.55.1.3.6.87.76.65.78.45.49";
$ifc = "WLAN-1";
if ($interface->iface == "if4") {
	$ifcId = $sysObjectId.".1.3.55.1.3.6.87.76.65.78.45.50";
	$ifc = "WLAN-2";
}

$channel =snmpget($interface->ip, $community,$ifcId, $snmpTimeout);

/* zulässige Kanäle:
 * 2,4 GHz-Band: Channels 1-13
 *   5 GHz-Band: Channels 100-140 (outdoor), Ausnahme Wetterradar auf Kanal 128 +/-2 = 126-130 gesperrt
 */
if (($channel >= 1 AND $channel <= 13) OR ($channel >= 100 AND $channel < 126) OR ($channel > 130 AND $channel <= 140)) {
	$color_channel = $color_good;
} else {
	$color_channel = $color_bad;
	if ($channel >= 36 && $channel < 100)
	{
		$k++; /* Zähler Indoor-Kanäle */
	}
	if ($channel >= 126 && $channel <= 130)
	{
		$l++; /* Zähler Wetterradar-Kanäle */
	}
}
?>

<tr>
	<td><?php echo $interface->description ?></td>
	<td><?php echo '<div style="color:'.$color_country.'">'.$country.'</div>' ?></td>
	<td><?php echo $ifc ?></td>
	<td><?php echo '<div style="color:'.$color_channel.'">'.$channel.'</div>' ?></td>
</tr>
<?php

flush();
$i++;
}

?>
</tbody>
</table>
</div>

<div class="normalbox" style="float:left;">
<h1>Statistik</h1>
<p><?php echo $i; ?> Datens&auml;tze
<ul>
<li>Deutschland: <?php echo $j.sprintf(' (%2.2f%%)', $j/$i*100); ?></li>
<li>Indoor-Kanal: <?php echo $k.sprintf(' (%2.2f%%)', $k/$i*100); ?></li>
<li>Wetterradar-Kanal: <?php echo $l.sprintf(' (%2.2f%%)', $l/$i*100); ?></li>
</ul>
</p>
</div>

<div class="normalbox" style="float:left;"><?php require(dirname(__FILE__) . "/../include/time_end.php"); ?></div>

</body>
</html>
