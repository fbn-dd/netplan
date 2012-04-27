<?php

require_once(dirname(__FILE__) . "/../include/db.php");

$section = DB_DataObject::factory('section');
$section->find();
while ($section->fetch()) {
  $sectionMapping[$section->id_section] = $section->toArray();
}

$location = DB_DataObject::factory('location');
$location->find();
while ($location->fetch()) {
  $locationMapping[$location->id_location] = $sectionMapping[$location->id_section];
}

$type = DB_DataObject::factory('type');
$type->find();
while ($type->fetch()) {
  $typeMapping[$type->id_type] = $type->description;
}

$interface = DB_DataObject::factory('interface');
$interface->find();
while ($interface->fetch()) {
  $interfaceMapping[$interface->id_interface] = $interface->id_node;
  if ($interface->ip != '')
    $ipMapping[$interface->id_node] = $interface->ip;
}

$node = DB_DataObject::factory('node');
$node->orderBy('description');
$node->find();
while ($node->fetch()) {

  if (isset($ipMapping[$node->id_node])) {

      $x = $node->x_coord;
      $y = $node->y_coord;

      switch ($locationMapping[$node->id_location]['description']) {

        case 'Dresden':
                $x += 2900;
		break;
        case 'Radebeul':
                $x += 1250;
		break;
        case 'Coswig':
		break;
        case 'Freital':
		$y += 1200;
		break;
      }

      echo( "define hostextinfo {\n".
            "    host_name               ".$node->description."\n".
            "    icon_image              img_id_type_".$node->id_type.".png\n".
            "    icon_image_alt          ".$typeMapping[$node->id_type]."\n".
            "    vrml_image              img_id_type_".$node->id_type.".png\n".
            "    statusmap_image         img_id_type_".$node->id_type.".gd2\n".
            "    2d_coords               ".$x.",".$y."\n".
            "}\n\n" );
  }
}

?>
