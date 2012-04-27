<?php
$loader = FALSE; // Bootloader auslesen und anzeigen?
$snmpTimeout = 90000; // Timeout für SNMP-Abfragen in Millisekunden; hoch wählen, da einige Geräte lange brauchen

require_once(dirname(__FILE__) . "/../include/time_start.php");
require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/layout.php");
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
    <title>LANCOM-Inventur</title>
    <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
    <script type="text/javascript" src="../js/sorttable.js"></script>
</head>

<body>
<?php printMenu(); ?>
<div class="normalbox">
<h1>LANCOM-Inventur</h1>

<table class="sortable">
<thead>
<tr>
<th>Sektion</th>
<th>Standort</th>
<th>Ger&auml;t</th>
<th>Ger&auml;tetyp</th>
<th>Firmware</th>
<?php if ($loader) echo "<th>Loader</th>"; ?>
<th>IP-Adresse</th>
<th>Seriennr.</th>
<th>Seriennr. (DB)</th>
<!-- <th>Lager-Status</th> -->
</tr>
</thead>
<tbody>
<?php
$er = error_reporting(0);
$loader_version_oid = ".3.2.1.3.3";
/*
$lancomstock = DB_DataObject::factory('lancom_stock');
$lancomstock->find(TRUE);

while($lancomstock->fetch())
{
  $stock[$lancomstock->serial] = $lancomstock->serial;
}
*/
$interface = DB_DataObject::factory('interface');
$interface->query('SELECT s.description as sektion, l.description as standort, n.description as geraet, n.serial as serial, i.id_node as id_node, i.ip as ip, n.snmp_community as snmp_community, t.description as geraetetype FROM interface as i INNER JOIN node as n ON i.id_node=n.id_node INNER JOIN location as l ON n.id_location=l.id_location INNER JOIN section as s ON l.id_section=s.id_section INNER JOIN type as t ON t.id_type=n.id_type WHERE t.description LIKE "LANCOM%" AND n.snmp_community != "" AND n.snmp_community != "disabled" GROUP BY i.id_node ORDER BY s.description, l.description, n.description');

// Statistikzähler Firmware-Versionen, Gerätetypen, Bootloader-Versionen
$fws = $gts = $lds = array();

$i=0;
while ($interface->fetch())
{
  $description = snmpget($interface->ip, $interface->snmp_community, '.1.3.6.1.2.1.1.1.0', $snmpTimeout);
  $description = str_replace('"', '', $description);
  $description_part = explode('/', $description);
  $description_part1 = explode(' ', $description_part[0]);
  $description_part2 = explode(' ', $description_part[1]);
  $serial = $description_part2[2];
  $last = count($description_part1)-2;
  $firmware = $description_part1[$last];

  $fws[$firmware]++;
  $gts[$interface->geraetetype]++;
  
  if (!$serial)
  {
    continue;
  }

  if ($loader) {
    $sysObjectId = snmpget($interface->ip, $interface->snmp_community, 'sysObjectId', $snmpTimeout);
    $loader_version = snmpget($interface->ip, $interface->snmp_community, $sysObjectId.$loader_version_oid, $snmpTimeout);
    $loader_version = str_replace('"', '', $loader_version);
    $lds[$loader_version]++;
  }

  echo  "<tr class=\"".($i%2?"odd":"even")."\">\n".
        "<td>$interface->sektion</td>\n".
        "<td>$interface->standort</td>\n".
        "<td>$interface->geraet</td>\n".
        "<td>$interface->geraetetype</td>\n".
        "<td>$firmware</td>\n";

 if ($loader) echo "<td>$loader_version</td>\n";

 echo   '<td><a href="https://'.$interface->ip.'">'.$interface->ip."</a></td>\n".
        "<td>$serial</td>\n".
        "<td style=\"color:".($serial==$interface->serial?'green':'red').";\">".$interface->serial."</td>".
//	"<td style=\"color:".(array_key_exists('400'.$serial,$stock)?'green':'red').";\">Lager</td>".
        "</tr>\n";

  // Seriennummer des Lancoms in Netplan-DB aktualisieren
  //$interface->query("UPDATE node SET serial = '".$serial."' WHERE id_node = ".$interface->id_node);

  $i++;
  flush();
  ob_flush();
}
?>
</tbody>
</table>
</div>

<div class="normalbox">
<h1>Statistik</h1>
<table style="border: 1px dashed grey; margin:1em;">
<tr>
<th>Firmware</th>
<th>Anzahl</th>
</tr>
<?php
arsort($fws,SORT_NUMERIC);
$sum=$i=0;
foreach ($fws as $fw => $count)
{
?>
<tr class="<?php echo ($i%2?"odd":"even"); ?>">
<?php
echo "<td>".($fw?$fw:"nicht erreichbar")."</td>\n".
     "<td>$count</td>\n";
$sum += $count;
$i++;
?>
</tr>
<?php
}
?>
<tr style="background-color: silver;">
<td>Summe</td>
<td><?php echo $sum; ?></td>
</tr>
</table>

<table style="border: 1px dashed grey; margin:1em;">
<tr>
<th>Ger&auml;tetyp</th>
<th>Anzahl</th>
</tr>
<?php
arsort($gts,SORT_NUMERIC);
$sum=$i=0;
foreach ($gts as $gt => $count)
{
?>
<tr class="<?php echo ($i%2?"odd":"even"); ?>">
<?php
echo "<td>".($gt?$gt:"nicht erreichbar")."</td>\n".
     "<td>$count</td>\n";
$sum += $count;
$i++;
?>
</tr>
<?php
}
?>
<tr style="background-color: silver;">
<td>Summe</td>
<td><?php echo $sum; ?></td>
</tr>
</table>

<?php if ($loader) { ?>
<table style="border: 1px dashed grey; margin:1em;">
<tr>
<th>Loader-Version</th>
<th>Anzahl</th>
</tr>
<?php
arsort($lds,SORT_NUMERIC);
$sum=$i=0;
foreach ($lds as $ld => $count)
{
?>
<tr class="<?php echo ($i%2?"odd":"even"); ?>">
<?php
echo "<td>".($ld?$ld:"nicht erreichbar")."</td>\n".
     "<td>$count</td>\n";
     $sum += $count;
     $i++;
     ?>
     </tr>
     <?php
     }
     ?>
     <tr style="background-color: silver;">
     <td>Summe</td>
     <td><?php echo $sum; ?></td>
     </tr>
     </table>
<?php } ?>
</div>
<div class="normalbox" style="clear:both;"><?php require(dirname(__FILE__) . "/../include/time_end.php"); ?></div>
</body>
</html>
