<?php 

  require_once(dirname(__FILE__) . "/../include/db.php");

  if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
    header("Content-type: application/xhtml+xml");
  } else {
    header("Content-type: text/xml");
  } 

  echo('<?xml version="1.0" encoding="utf-8"?>'."\n");

  if (!isset($_GET['id']) || (isset($_GET['id']) && $_GET['id'] == '0')) {
    $section = DB_DataObject::factory('section');
    $section->orderBy('description');
    $section->find();

    echo("<tree id=\"0\">\n");
    echo("<item text=\"Netplan\" id=\"netplan\">\n");
    if (isset($_GET['addnew'])) {
      echo '<item text="Neue Sektion" id="section_0_netplan" child="0" />'."\n";
    }
    while ($section->fetch()) {
      echo '<item text="'.$section->description.'" id="section_'.$section->id_section.'" child="1" />'."\n";
    }
    echo '</item>'."\n";
    echo("</tree>\n");

  } else {
    list($key, $value) = explode('_',$_GET['id']);
    switch ($key) {
      case 'section':
        $location = DB_DataObject::factory('location');
        $location->id_section=$value;
        $location->orderBy('description');
        $location->find();

        echo('<tree id="'.$_GET['id'].'">'."\n");
        if (isset($_GET['addnew'])) {
          echo '<item text="Neuer Standort" id="location_0_'.$value.'" child="0" />'."\n";
        }
        while ($location->fetch()) {
          echo '<item text="'.$location->description.'" id="location_'.$location->id_location.'" child="1" />'."\n";
        }
        echo("</tree>\n");
        break; 

      case 'location':
        $node = DB_DataObject::factory('node');
        $node->id_location=$value;
        $node->orderBy('description');
        $node->find();

        echo('<tree id="'.$_GET['id'].'">'."\n");
        $child = 0;
        if (isset($_GET['addnew'])) {
          echo '<item text="Neues Gerät" id="node_0_'.$value.'" child="0" />'."\n";
          $child = 1;
        }
        while ($node->fetch()) {
          echo '<item text="'.$node->description.'" id="node_'.$node->id_node.'" child="'.$child.'" />'."\n";
        }
        echo("</tree>\n");
        break; 

      case 'node':
        $device = DB_DataObject::factory('device');
        $device->find();
        while ($device->fetch()) {
          $deviceMapping[$device->id_device] = $device->description;
        }

        $interface = DB_DataObject::factory('interface');
        $interface->id_node=$value;
        $interface->orderBy('id_device');
        $interface->find();

		// get list of services ( = nagios hostgroups)
		$service = DB_DataObject::factory('service');
        $service->find();
        while ($service->fetch()) {
          $serviceMapping[$service->id_service] = $service->description;
        }
		// get list of services associated to this node
		$nodeServices = DB_DataObject::factory('node_has_service');
        $nodeServices->id_node=$value;
        $nodeServices->find();
		
        echo('<tree id="'.$_GET['id'].'">'."\n");
        if (isset($_GET['addnew'])) {
          echo '<item text="Neues Interface" id="interface_0_'.$value.'" child="0" />'."\n";
		  echo '<item text="Neuer Service" id="nodeService_0_'.$value.'" child="0" />'."\n";
        }
        while ($interface->fetch()) {
          echo '<item text="'.$deviceMapping[$interface->id_device].($interface->id_vlan>1?'.'.$interface->id_vlan:'').'" id="interface_'.$interface->id_interface.'" child="1" />'."\n";
        }
		// put each service of this node into xmltree
		while ($nodeServices->fetch()) {
          echo '<item text="'.$serviceMapping[$nodeServices->id_service].'" id="nodeService_'.$nodeServices->id.'" child="0" />'."\n";
        }
        echo("</tree>\n");
        break; 

      case 'interface':
        $device = DB_DataObject::factory('device');
        $device->find();
        while ($device->fetch()) {
          $deviceMapping[$device->id_device] = $device->description;
        }

        $node = DB_DataObject::factory('node');
        $node->find();
        while ($node->fetch()) {
          $nodeMapping[$node->id_node] = $node->description;
        }

        $interface = DB_DataObject::factory('interface');
        $interface->find();
        while ($interface->fetch()) {
          $interfaceMapping[$interface->id_interface]['id_node']   = $interface->id_node;
          $interfaceMapping[$interface->id_interface]['id_device'] = $interface->id_device;
          $interfaceMapping[$interface->id_interface]['id_vlan']   = $interface->id_vlan;
        }

        $link = DB_DataObject::factory('link');
        $link->id_src_interface=$value;
        $link->find();

        echo('<tree id="'.$_GET['id'].'">'."\n");
        if (isset($_GET['addnew'])) {
          echo '<item text="Neuer Link" id="link_0_'.$value.'" child="0" />'."\n";
        }
        while ($link->fetch()) {
          $text = '--&gt; '.$nodeMapping[$interfaceMapping[$link->id_dst_interface]['id_node']].
                  ' ['.$deviceMapping[$interfaceMapping[$link->id_dst_interface]['id_device']].
                  ($interfaceMapping[$link->id_dst_interface]['id_vlan']>1?'.'.$interfaceMapping[$link->id_dst_interface]['id_vlan']:'').']';
          echo '<item text="'.$text.'" id="link_'.$link->id_link.'_'.$value.'" child="0" />'."\n";
        }

        $link = DB_DataObject::factory('link');
        $link->id_dst_interface=$value;
        $link->find();

        while ($link->fetch()) {
          $text = '--&gt; '.$nodeMapping[$interfaceMapping[$link->id_src_interface]['id_node']].
                  ' ['.$deviceMapping[$interfaceMapping[$link->id_src_interface]['id_device']].
                  ($interfaceMapping[$link->id_src_interface]['id_vlan']>1?'.'.$interfaceMapping[$link->id_src_interface]['id_vlan']:'').']';
          echo '<item text="'.$text.'" id="link_'.$link->id_link.'_'.$value.'" child="0" />'."\n";
        }
        echo("</tree>\n");

        break; 
   }

  }

?>
