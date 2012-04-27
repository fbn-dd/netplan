<?php

require_once(dirname(__FILE__) . "/../include/db.php");

  $node = DB_DataObject::factory('node');
  $node->find();
  $nodeMapping = array();
  while ($node->fetch()) {
    $nodeMapping[$node->id_node] = $node->toArray();
  }
  
  $device = DB_DataObject::factory('device');
  $device->find();
  $deviceMapping = array();
  while ($device->fetch()) {
    $deviceMapping[$device->id_device] = $device->description;
  }

  $interface = DB_DataObject::factory('interface');
// distinct wirkt nicht wie gewuenscht, deshalb group by
  $interface->query("SELECT ip, id_node, id_device, id_interface FROM {$interface->__table} GROUP BY ip");
  $interfaceMapping = array();
  while ($interface->fetch()) {
    $interfaceMapping[$interface->id_interface] = $interface->toArray();
  }

  // Erstellen des Objekts für die Tabelle 'radius_nas'
  $radius_nas = DB_DataObject::factory('nas');

  // Tabelle 'radius_nas' einlesen und Vergleichsarray aufbauen
  $radius_nas->find();
  $radius_nasMapping = array();
  while ($radius_nas->fetch()) {
    $radius_nasMapping[$radius_nas->nasname] = $radius_nas->toArray();
  }
  $radius_nas->free();

  // Einfügen der neuen Clients
  $radius_nasHeap = array();
  foreach ($interfaceMapping as $id_interface => $interfaceData) {
    // Check, ob die IP schon in der Tabelle 'radius_nas' steht
    if (array_key_exists ($interfaceData['ip'], $radius_nasMapping)) {
      // Check, ob sich wichtige Werte geändert haben
      if ($nodeMapping[$interfaceData['id_node']]['description'].'-'.$deviceMapping[$interfaceData['id_device']] != $radius_nasMapping[$interfaceData['ip']]['shortname'] OR 
$nodeMapping[$interfaceData['id_node']]['radius_password'] != $radius_nasMapping[$interfaceData['ip']]['secret']) {
        $radius_nas->get($radius_nasMapping[$interfaceData['ip']]['id']);
        $radius_nas->nasname = $interfaceData['ip'];
        $radius_nas->shortname = $nodeMapping[$interfaceData['id_node']]['description'].'-'.$deviceMapping[$interfaceData['id_device']];
        $radius_nas->type = 'other';
        // $radius_nas->ports = ...
        $radius_nas->secret = ($nodeMapping[$interfaceData['id_node']]['radius_password']!=''?$nodeMapping[$interfaceData['id_node']]['radius_password']:'test');
        // $radius_nas->community = ...
        $radius_nas->description = 'RADIUS Client';
        $radius_nas->update();
        $radius_nas->free();
      }
      $radius_nasHeap[$interfaceData['ip']] = $radius_nasMapping[$interfaceData['ip']];
    } else {
        $radius_nas->nasname = $interfaceData['ip'];
        $radius_nas->shortname = $nodeMapping[$interfaceData['id_node']]['description'].'-'.$deviceMapping[$interfaceData['id_device']];
        $radius_nas->type = 'other';
        // $radius_nas->ports = ...
        $radius_nas->secret = ($nodeMapping[$interfaceData['id_node']]['radius_password']!=''?$nodeMapping[$interfaceData['id_node']]['radius_password']:'test');
        // $radius_nas->community = ...
        $radius_nas->description = 'RADIUS Client';
        $radius_nas->insert();
        $radius_nas->free();
    }
  }

  // Rest bilden, der Nas's welche in der Tabelle 'nas' stehen, aber nicht mehr vom netplan kommen
  $radius_nasHeap = array_diff_assoc($radius_nasMapping, $radius_nasHeap);

  // Löschen aller Nas's, die in der Tabelle 'radius_nas' stehen, aber nicht mehr im netplan
  foreach ($radius_nasHeap as $nasname => $nasData) {
    $radius_nas->get($nasData['id']);
    $radius_nas->delete();
    $radius_nas->free();
  }

?>
