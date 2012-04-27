<?php

require_once(dirname(__FILE__) . "/../include/db.php");

$interface = DB_DataObject::factory('interface');
$interface->find();
$interfaceMapping = array();
while ($interface->fetch())
{
  if (!empty($interface->ip))
  {
    $interfaceMapping[$interface->id_node] = $interface->id_interface;
  }
}

$node = DB_DataObject::factory('node');
$node->find();
$nodeMapping = array();
while ($node->fetch())
{
  if (!empty($node->description) && isset($interfaceMapping[$node->id_node]))
  {
    $nodeMapping[$node->id_location][] = $node->description;
  }
}

$location = DB_DataObject::factory('location');
$location->find();
$locationMapping = array();
$sections = array();
while ($location->fetch())
{
  if (!empty($location->description) && isset($nodeMapping[$location->id_location]))
  {
    $locationMapping[$location->id_location] = $location->toArray();
    $sections[$location->id_section][] = implode(",", $nodeMapping[$location->id_location]);
  }
}

$section = DB_DataObject::factory('section');
$section->find();
$sectionMapping = array();
while ($section->fetch())
{
  if(isset($sections[$section->id_section]))
  {
    $sectionMapping[$section->id_section] = $section->toArray();
  }
}

foreach($locationMapping as $id_location => $location_array)
{
?>
define hostgroup{
  hostgroup_name  <?php echo $location_array['description'] ?>

  alias  <?php echo ($location_array['street']?$location_array['street']:$location_array['description']) ?>

  members  <?php echo implode(",", $nodeMapping[$id_location]) ?>

}


<?php
}

foreach($sectionMapping as $id_section => $section_array)
{
?>
define hostgroup{
  hostgroup_name  <?php echo '-'.$section_array['description'] ?>

  alias  <?php echo $section_array['description'] ?>

  members  <?php echo implode(",", $sections[$id_section]) ?>

}


<?php
}
?>
