<?php

require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/rrd.php");

$range=86400;

if (!isset($_GET['id_interface'])) exit(1);
if ($_GET['id_interface'] != 'traffic_summary' && !is_numeric($_GET['id_interface'])) exit(1);

if($_GET['id_interface'] != 'traffic_summary') {
	$device = DB_DataObject::factory('device');
	$device->find();
	while ($device->fetch()) {
	  $deviceMapping[$device->id_device] = $device->description;
	}

	$medium = DB_DataObject::factory('medium');
	$medium->find();
	while ($medium->fetch()) {
	  $mediumMapping[$medium->id_medium] = $medium->description;
	}

	$channel = DB_DataObject::factory('channel');
	$channel->find();
	while ($channel->fetch()) {
	  $channelMapping[$channel->id_channel] = $mediumMapping[$channel->id_medium];
	}

	$interface = DB_DataObject::factory('interface');
	$interface->get($_GET['id_interface']);
	$node = $interface->getLink('id_node','node');

	if ( is_string($interface->description) && (strlen($interface->description) > 0) )
	{
  	$description = $interface->description;
	} else {
	  $description = str_replace('/','-',$node->description).' - '.$deviceMapping[$interface->id_device].': '.
		               $interface->ip.' ('.$channelMapping[$interface->id_channel].')';
	}
	$theimage = 'id_interface_'.$interface->id_interface;
	rrd_graph_traffic('id_interface_'.$interface->id_interface.'.rrd',
                  	'id_interface_'.$interface->id_interface.'.png',
                  	$range,
                  	$description);
} else {
	$theimage = 'traffic_summary-'.$range;
#	$interface = DB_DataObject::factory('interface');
	@rrd_graph_internet_summary($interface, $range);
}


header('Content-type: image/png');
readfile(PNG_DIR . '/'.$theimage.'.png');

?>
