<?php

require_once(dirname(__FILE__) . "/../include/db.php");

$interface = DB_DataObject::factory('interface');
$interface->id_mode = 1; // AP Modus
$interface->find();

while ($interface->fetch()) {
  $interfaceMapping[$interface->id_interface] = $interface->id_node;
  if ($interface->ip != '')
    $ipMapping[$interface->id_node] = $interface->ip;
}

$node = DB_DataObject::factory('node');
$node->whereAdd('snmp_community != "disabled"');
$node->orderBy('description');
$node->find();

while ($node->fetch()) {
  if (isset($ipMapping[$node->id_node])) {
    echo( "define service{\n".
          "	use			generic-service\n".
	  "	host_name		".$node->description."\n".
	  "	service_description     Wireless Clients Count\n".
	  "	check_command		check_wireless_clients!".$node->snmp_community."!".$node->id_type."!50!60\n".
	  "}\n\n" );
  }
}

?>
