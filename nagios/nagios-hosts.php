<?php

require_once(dirname(__FILE__) . "/../include/db.php");

$interface = DB_DataObject::factory('interface');
$interface->orderBy('id_interface desc');
$interface->find();
while ($interface->fetch()) {
  $interfaceMapping[$interface->id_interface] = $interface->id_node;
  if ($interface->ip != '')
    $ipMapping[$interface->id_node] = $interface->ip;
}

$node = DB_DataObject::factory('node');
$node->find();
while ($node->fetch()) {
  if ($node->description != '')
    $nodeMapping[$node->id_node] = $node->description;
}

$location = DB_DataObject::factory('location');
$location->find();
while ($location->fetch()) {
  if ($location->description != '')
    $locationMapping[$location->id_location] = $location->toArray();
}

$section = DB_DataObject::factory('section');
$section->find();
while ($section->fetch()) {
    $sectionMapping[$section->id_section] = $section->toArray();
}

$link = DB_DataObject::factory('link');
$link->find();
while ($link->fetch()) {
  $parents[$interfaceMapping[$link->id_dst_interface]][$link->id_src_interface] = 
    $nodeMapping[$interfaceMapping[$link->id_src_interface]];
}

$node = DB_DataObject::factory('node');
$node->orderBy('description');
$node->find();
while ($node->fetch()) {

  if (isset($parents[$node->id_node])) {
    $parent = "    parents                 ".implode(',',$parents[$node->id_node])."\n";
  } else {
    $parent = "";
  }

//  $contacts = 
'contact_groups  nagios-admins,nagios-'.$sectionMapping[$locationMapping[$node->id_location]['id_section']]['description'].',nagios-'.$locationMapping[$node->id_location]['description']."\n";

  if (isset($ipMapping[$node->id_node])) {
      echo( "define host{\n".
            "    use                     generic-host\n".
            "    host_name               ".$node->description."\n".
            "    address                 ".$ipMapping[$node->id_node]."\n".
            $parent.
    //        $contacts.
            "}\n\n" );
  }
}

?>
