<?php

require_once(dirname(__FILE__) . "/../include/db.php");

$mode = strval($_GET["mode"]);

switch ($mode)
{
  /* Interface ist WLAN-AP */
  case 'ap': $mode = array(1);       break;
  /* alle anderen Interfaces */
  case 'bb': $mode = array(3,4,5,6); break;
  /* failsafe */
  default:   $mode = array(3,4,5,6);
}

$node = DB_DataObject::factory('node');
$node->find();
$nodeMapping = array();
while ($node->fetch()) {
  $nodeMapping[$node->id_node] = str_replace(array("/", "_"),"-",$node->description);
  $nodeMapping[$node->id_node] = str_replace(" ","-",$nodeMapping[$node->id_node]);
  $nodeMapping[$node->id_node] = strtolower($nodeMapping[$node->id_node]);
}

$interface = DB_DataObject::factory('interface');
$interface->whereAdd('ip != "0.0.0.0"');
$interface->whereAdd('ip != "127.0.0.1"');
$interface->whereAdd('ip != "255.255.255.255"');
$interface->whereAdd('ip != ""');
$interface->whereAdd('id_mode IN ('.implode(',', $mode).')');
$interface->groupBy('ip');
$interface->find();

while ($interface->fetch())
{
  echo $nodeMapping[$interface->id_node]."\tIN\tA\t".$interface->ip."\n";
}
?>
