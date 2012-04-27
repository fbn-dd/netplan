<?php
require_once(dirname(__FILE__) . "/../include/time_start.php");
require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/layout.php");
require_once(dirname(__FILE__) . "/../include/password.php");
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
    <title>LANCOM-P2P-Verschl&uuml;sselung</title>
    <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
    <script type="text/javascript" src="../js/sorttable.js"></script>
</head>
<body>
<?php printMenu(); ?>
<div class="normalbox" style="float:left;">
<h1>Verschl&uuml;sselung auf P2P-Strecken zwischen LANCOM-Ger&auml;ten</h1>
<table class="sortable">
<thead>
	<tr>
		<th>Ger&auml;t</th>
		<th>Verschl&uuml;sselung</th>
	</tr>
</thead>
<tbody>
<?php
$er = error_reporting(0);

$oidsysObjectId = "sysObjectId";
$setInterWlaEncryptionEncr = ".2.23.20.3.1.2";
$setInterWlaEncryptionEncrArray = array(0 => "no", 2 => "yes");
$setInterWlaEncryptionMeth = ".2.23.20.3.1.4";
$setInterWlaEncryptionMethArray = array(5 => "wep-40-bits",
					13 => "wep-104-bits",
					16 => "wep-128-bits",
					32 => "e802-11i-wpa-psk");
$setInterWlaEncryptionWpav = ".2.23.20.3.1.9";
$setInterWlaEncryptionWpavArray = array(2 => "wpa1", 4 => "wpa2", 6 => "wpa1/2");
$setInterWlaEncryptionWpa2 = ".2.23.20.3.1.13";
$setInterWlaEncryptionWpa2Array = array(1 => "tkip", 2 => "aes", 3 => "tkip/aes");
$setInterWlaEncryptionKey = ".2.23.20.3.1.6";

$interface = DB_DataObject::factory('interface');
$interface->query('SELECT n.description as description, i.ip as ip, n.snmp_community as snmp_community, d.description as iface, t.description as type FROM interface as i, node as n, device as d, type as t WHERE i.id_mode=3 AND i.id_node=n.id_node AND i.id_device=d.id_device AND n.id_type=t.id_type AND t.description LIKE "LANCOM%" ORDER BY t.description ASC');

$lastoid="";
$i=$j=$k=$l=0;
while ($interface->fetch())
{
$color = "red";
$output = "";

// get sysObjectId
$sysObjectId="";
$community="public";
if ($interface->snmp_community != "") $community=$interface->snmp_community;
$sysObjectId=snmpget($interface->ip, $community, $oidsysObjectId, '15000');
if (!$sysObjectId) continue;

/*
if ($lastoid != $sysObjectId) {
  $lastoid = $sysObjectId;
  if (!snmp_read_mib($sysObjectId)) echo "load failed\n";
  echo "readmib\n";
}
*/

// check if encryption is active
$ifcId = ".1";
if ($interface->iface == "if4") $ifcId = ".2";

$EncryptionEncr = false;

$EncryptionEncr = snmpget($interface->ip, $community, $sysObjectId.$setInterWlaEncryptionEncr.$ifcId, '10000');


if ($EncryptionEncr == "") continue;
if ($EncryptionEncr) {
  $EncryptionMeth = false;
  $EncryptionMeth = snmpget($interface->ip, $community, $sysObjectId.$setInterWlaEncryptionMeth.$ifcId, '10000');

  if ($EncryptionMeth == 32) {
    $color = "maroon";

    $EncryptionWpav = false;
    $EncryptionWpav = snmpget($interface->ip, $community, $sysObjectId.$setInterWlaEncryptionWpav.$ifcId, '10000');
    if ($EncryptionWpav >= 4) {
      $EncryptionWpa2 = false;
      $EncryptionWpa2 = snmpget($interface->ip, $community, $sysObjectId.$setInterWlaEncryptionWpa2.$ifcId, '10000');
      if ($EncryptionWpa2 == 2 && $EncryptionWpav == 4) {
	$EncryptionKey = false;
	$EncryptionKey = snmpget($interface->ip, $community, $sysObjectId.$setInterWlaEncryptionKey.$ifcId, '10000');
	if (Password_Strength($EncryptionKey) == 100) $color = "green";
      }
      $output = $setInterWlaEncryptionWpavArray[$EncryptionWpav]." ".$setInterWlaEncryptionWpa2Array[$EncryptionWpa2]." ".($color!="green"?"weak passphrase":"");
      $k++;
    } else {
      $output = $setInterWlaEncryptionWpavArray[$EncryptionWpav];
      $j++;
    }
  } else {
    $output = $setInterWlaEncryptionMethArray[$EncryptionMeth];
  }
} else {
  $output = $setInterWlaEncryptionEncrArray[$EncryptionEncr];
  $l++;
}

echo "<tr class=\"".($i%2?"odd":"even")."\"><td>".$interface->description."-".$interface->iface."</td><td><font color=\"".$color."\">".$output."</font></td></tr>\n";

$i++;
flush();
}

?>
</tbody>
</table>
</div>
<div class="normalbox" style="float:left;">
<h1>Statistik</h1>
<pre><?php echo $i; ?> Datens&auml;tze 
- WPA1: <?php echo $j; ?> 
- WPA2: <?php echo $k; ?> 
- none: <?php echo $l; ?></pre>
</div>
<div class="normalbox" style="float:left;"><?php require(dirname(__FILE__) . "/../include/time_end.php"); ?></div>
</body>
</html>
