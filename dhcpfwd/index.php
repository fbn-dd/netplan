<?php

require_once(dirname(__FILE__) . "/../include/db.php");

error_reporting(1);

echo "<h1> WRT DHCP Relay check</h1><br>";
echo "<table>";
echo "<tr>";
echo "<th>AP-Name</th>";
echo "<th>IP-Adresse</th>";
echo "<th>Status</th>";
echo "</tr>";

// Abfrage in Tabelle 'node' nach allen WRT54G Devices
$node = DB_DataObject::factory('node');
$node->whereAdd('id_type = 4');
$node->whereAdd('id_type = 7', 'OR');
$node->whereAdd('id_type = 11', 'OR');
$node->whereAdd('id_type = 12', 'OR');
$node->find();

while($node->fetch())
{
  $nodeMapping[$node->id_node] = $node->description;
}

$interface = DB_DataObject::factory('interface');
$interface->id_mode=1;
$interface->whereAdd('id_node IN ('.implode(', ', array_keys($nodeMapping)).')');
$interface->groupBy('ip');
$interface->find();

while($interface->fetch()) {
	echo "<tr>";
   	echo "<td>".$nodeMapping[$interface->id_node]."</td>";

 	$ip = $interface->ip; // test
    	
	echo "<td>".$ip."</td>";

    $a = snmpwalk($ip, "public", ".1.3.6.1.2.1.25.4.2.1.4");

    if (!is_array($a)) {
      print "<td>unbekannt</td>";
    } else {
      $a = preg_grep('/dhcp(-fwd|fwd)/', $a);

      if (count($a)!=0) {
        echo "<td><font color=green>aktiv</font></td>";
      } else {
        echo "<td><font color=red>inaktiv</font></td>";
      }
    }	

	echo "</tr>";
    flush();
    ob_flush();
}

echo "</table>";
echo "</body>";
echo "</html>";
?>
