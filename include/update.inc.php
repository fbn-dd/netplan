<?php
require_once(dirname(__FILE__) . "/geocode.php");

function updateSection($section) {
  $html = 'Sektion: ';
  $do = DB_DataObject::factory('section');
  if ( $section['id_section'] > '0' ) {
    $do->get($section['id_section']);
    $do->setFrom($section);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->update()===FALSE?'fehlgeschlagen':'erfolgreich');
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('update' => 1, 'text' => $section['description'], 'html' => $html);
  } else {
    $do->setFrom($section);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->insert()===FALSE?'fehlgeschlagen':'erfolgreich');
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('insert' => 1, 'parent' => 'netplan', 'id' => 'section_'.$do->id_section,
                 'text' => $section['description'],
                 'new' => 'location_0_'.$do->id_section, 'newtext' => 'Neuer Standort',
                 'html' => $html);
  }
}

function updateLocation($location) {
  $html = 'Standort: ';
  $do = DB_DataObject::factory('location');
  if ( $location['id_location'] > '0' ) {
    $do->get($location['id_location']);
    $do->setFrom($location);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->update()===FALSE?'fehlgeschlagen':'erfolgreich');
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('update' => 1, 'text' => $location['description'], 'html' => $html);
  } else {
    if ( ((!isset($location['longitude']) OR $location['longitude'] == '') OR 
          (!isset($location['latitude'])  OR $location['latitude'] == '')) AND 
         (isset($location['street']) AND $location['street'] != '') AND 
         isset($location['postcode']) AND isset($location['city']) ) {
      $coords = array();
      $coords = getCoordinates(ereg_replace(' ', '+', $location["street"].','.$location["postcode"].','.$location["city"].',Deutschland'));
      $location['longitude'] = $coords[0];
      $location['latitude'] = $coords[1];
    }
    $do->setFrom($location);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->insert()===FALSE?'fehlgeschlagen':'erfolgreich');
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('insert' => 1, 'parent' => 'section_'.$do->id_section, 
                 'id' => 'location_'.$do->id_location, 'text' => $location['description'],
                 'new' => 'node_0_'.$do->id_location, 'newtext' => 'Neues Geraet',
                 'html' => $html);
  }
}

function updateNode($node) {
  $html = 'Gerät: ';
  $do = DB_DataObject::factory('node');
  if ( $node['id_node'] > '0' ) {
    $do->get($node['id_node']);
    $do->setFrom($node);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->update()===FALSE?'fehlgeschlagen':'erfolgreich');
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('update' => 1, 'text' => $node['description'], 'html' => $html);
  } else {
    $do->setFrom($node);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->insert()===FALSE?'fehlgeschlagen':'erfolgreich');

      // autoinsert von 2 Interfaces bei Lancom L-54g und Lancom L-54ag
      if ( $node["id_type"] == '2' OR $node["id_type"] == '5' ) {
        $if1 = array( 'id_interface' => 0, 'id_node' => $do->id_node, 'id_medium' => '1', 'id_device' => '3', 'id_channel' => '1', 'id_mode' => '5', 'id_netmask' => '24' );
        updateInterface($if1);
        $if2 = array( 'id_interface' => 0, 'id_node' => $do->id_node, 'id_medium' => '3', 'id_device' => '4', 'id_channel' => '7', 'id_mode' => '1', 'id_netmask' => '24' );
        updateInterface($if2);
	// @TODO: reload xml subtree to show new interfaces
      }
      // autoinsert von eth0 bei PC/Server-System
      if ( $node["id_type"] == '1' ) {
        $eth0 = array( 'id_interface' => 0, 'id_node' => $do->id_node, 'id_medium' => '1', 'id_device' => '1', 'id_channel' => '1', 'id_mode' => '5', 'id_netmask' => '24' );
        updateInterface($eth0);
	// @TODO: reload xml subtree to show new interface
	/* nach 10 Sekunden neuen Knoten aufklappen, um neue Interfaces anzuzeigen */
	/* funktioniert nicht, da nicht ausgewertet, da vermutlich Webseite schon gerendert und keine Events mehr ausgelöst werden:
	$html .= '<script type="text/javascript">
		  //<![CDATA[
		  windows.setTimeout("tree.openItem(\"node_'.$do->id_node.'\")", 10000);
		  //]]>
		  </script>';*/
      }
      // autoinsert von br0 bei Linksys WRT54G und Linksys WRT54GS
      if ( $node["id_type"] == '4' OR $node["id_type"] == '7' ) {
        $br0 = array( 'id_interface' => 0, 'id_node' => $do->id_node, 'id_medium' => '3', 'id_device' => '8', 'id_channel' => '7', 'id_mode' => '1', 'id_netmask' => '24' );
        updateInterface($br0);
	// @TODO: reload xml subtree to show new interface
      }
    } else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('insert' => 1, 'parent' => 'location_'.$do->id_location,
                 'id' => 'node_'.$do->id_node, 'text' => $node['description'],
                 'new' => 'interface_0_'.$do->id_node, 'newtext' => 'Neues Interface',
                 'html' => $html);
  }
}

function updateInterface($interface) {
  $html = '';

  $device = DB_DataObject::factory('device');
  $device->find();
  while ($device->fetch()) {
    $deviceMapping[$device->id_device] = $device->description;
  }

  $do = DB_DataObject::factory('interface');
  if ( isset($interface['id_interface']) && $interface['id_interface'] > '0' ) {
    $do->get($interface['id_interface']);
    $do->setFrom($interface);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->update()===FALSE?'fehlgeschlagen':'erfolgreich');
    } else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    // automatisch die gelinkten Interfaces updaten
    if ( $interface["id_mode"] == '3' ) {
      $link = DB_DataObject::factory('link');
      $link->whereAdd('id_src_interface = '.$interface['id_interface']);
      $link->whereAdd('id_dst_interface = '.$interface['id_interface'], 'OR');
      $link->whereAdd('NOT (id_src_interface = id_dst_interface)');
      $link->find();
      while ($link->fetch()) {
        if ( $interface['id_interface'] == $link->id_src_interface ) {
          $linked_interface = $link->id_dst_interface;
        } else {
          $linked_interface = $link->id_src_interface;
        }
        $update_interface = array('id_channel' => $interface["id_channel"], 'id_mode' => '4', 'id_netmask' => $interface["id_netmask"]);
        updateInterface($linked_interface, $update_interface);
      }
    }
    return array('update' => 1, 'text' => $deviceMapping[$interface['id_device']].(isset($interface['id_vlan']) && $interface['id_vlan']>1?'.'.$interface['id_vlan']:''), 'html' => $html);
  } else {
    $do->setFrom($interface);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->insert()===FALSE?'fehlgeschlagen':'erfolgreich');
    } else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('insert' => 1, 'parent' => 'node_'.$do->id_node,
                 'id' => 'interface_'.$do->id_interface, 'text' => $deviceMapping[$interface['id_device']].(isset($interface['id_vlan']) && $interface['id_vlan']>1?'.'.$interface['id_vlan']:''),
                 'new' => 'link_0_'.$do->id_interface, 'newtext' => 'Neuer Link',
                 'html' => $html);
  }
}

function updateLink($links) {
  $html = 'Link: ';

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

  if ($links['id_interface'] == $links['id_src_interface']) {
    $text = '--&gt; '.$nodeMapping[$interfaceMapping[$links['id_dst_interface']]['id_node']].
            ' ['.$deviceMapping[$interfaceMapping[$links['id_dst_interface']]['id_device']].($interfaceMapping[$links['id_dst_interface']]['id_vlan']>1?'.'.$interfaceMapping[$links['id_dst_interface']]['id_vlan']:'').']';
  } else {
    $text = '--&gt; '.$nodeMapping[$interfaceMapping[$links['id_src_interface']]['id_node']].
            ' ['.$deviceMapping[$interfaceMapping[$links['id_src_interface']]['id_device']].($interfaceMapping[$links['id_src_interface']]['id_vlan']>1?'.'.$interfaceMapping[$links['id_src_interface']]['id_vlan']:'').']';
  }

  $do = DB_DataObject::factory('link');
  if ( $links['id_link'] > '0' ) {
    $do->get($links['id_link']);
    $do->setFrom($links);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->update()===FALSE?'fehlgeschlagen':'erfolgreich');
    } else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }

    return array('update' => 1, 'text' => $text, 'html' => $html);
  } else {
    $do->setFrom($links);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->insert()===FALSE?'fehlgeschlagen':'erfolgreich');
    } else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array(
				'insert' => 1,
				'parent' => 'interface_'.$links['id_interface'],
				'id' => 'link_'.$do->id_link,
				'text' => $text,
				'html' => $html
				);
  }
}

function updateRoute($route) {
  $html = 'Route: ';
  $do = DB_DataObject::factory('route');
  if ( $route['id_route'] > '0' ) {
    $do->get($route['id_route']);
    $do->setFrom($route);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern ';
      if ($do->update()===FALSE) {
        $html .= 'fehlgeschlagen';
      } else {
        $html .= 'erfolgreich';
        if (isset($route['id_subnet']) && $route['id_subnet'] > 0 && is_numeric($route['bnid'])) {
          $subnet = array('id_subnet' => $route['id_subnet'], 'id_route' => $route['id_route'], 'bnid' => $route['bnid']);
          return updateSubnet($subnet);
        }
        if (isset($route['id_subnet']) && $route['id_subnet'] == 0 && is_numeric($route['bnid'])) {
          $subnet = array('id_subnet' => $route['id_subnet'], 'id_route' => $route['id_route'], 'bnid' => $route['bnid']);
          return updateSubnet($subnet);
        }
        if (isset($route['id_subnet']) && $route['id_subnet'] > 0 && $route['bnid'] == '') {
          return deleteSubnet($route['id_subnet']);
        }
      }
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
  } else {
    $do->setFrom($route);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Eintragen ';
      $route['id_route'] = $do->insert();
      if ($route['id_route'] === FALSE) {
        $html .= 'fehlgeschlagen';
      } else {
        $html .= 'erfolgreich';
        if ($route['id_subnet'] == '0' && $route['bnid'] >= '0') {
          $subnet = array('id_subnet' => $route['id_subnet'], 'id_route' => $route['id_route'], 'bnid' => $route['bnid']);
          return updateSubnet($subnet);
        }
      }
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
  }
  return array('html' => $html);
}

function updateSubnet($subnet) {
  $html = 'Route: Speichern erfolgreich'."<br />".'Subnet: ';
  $do = DB_DataObject::factory('subnet');
  if ( $subnet['id_subnet'] > '0' ) {
    $do->get($subnet['id_subnet']);
    $do->setFrom($subnet);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->update()===FALSE?'fehlgeschlagen':'erfolgreich');
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
  } else {
    $do->setFrom($subnet);
    $val = $do->validate();
    if ($val == TRUE) {
      $html .= 'Speichern '.($do->insert()===FALSE?'fehlgeschlagen':'erfolgreich');
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
  }
  return array('html' => $html);
}

function updateNodeService($nodeService) {
  $html = '';
  //$html .= 'todo: validate and save parameters for NodeService<br /><pre>'.var_export($nodeService,1).'</pre><br />';


  // service mapping id => description
  $service = DB_DataObject::factory('service');
  $service->find();
  while ($service->fetch()) {
	$serviceMapping[$service->id_service] = $service->description;
  }
  
  $do = DB_DataObject::factory('node_has_service');
  
  if ( isset($nodeService['id']) && $nodeService['id'] > 0 ) {
    // update
	// fetch from DB
    $do->get($nodeService['id']);
	// copy array to DB-object
    $do->setFrom($nodeService);
	// check DB-object
    $val = $do->validate();
    if ($val == TRUE) {
	  // update DB-object
      $html .= 'Speichern '.($do->update()===FALSE?'fehlgeschlagen':'erfolgreich');
    } else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('html' => $html);
  } else {
    // insert new
	// copy form input array to DB-object
	$do->setFrom($nodeService);
    $val = $do->validate();
    if ($val == TRUE) {
	  // update DB-object
      $html .= 'Speichern '.($do->insert()===FALSE?'fehlgeschlagen':'erfolgreich');
    }  else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
	/* return array
	 * insert item in xmltree = true
	 * parent item = node_$id_node
	 * id = nodeService_$id
	 * text = service description
	 * html = output in <span id="response"></span>
	 */
	return array(
			'insert' => 1,
			'parent' => 'node_'.$do->id_node,
            'id' => 'nodeService_'.$do->id,
			'text' => $serviceMapping[$do->id_service],
            'html' => $html
			);
  }
}

function updateIfid($form)
{
  $html = '';
  $do = DB_DataObject::factory('interface');
  $do->get($form['id_interface']);
  $do->setFrom($form);
  $val = $do->validate();
    if ($val == TRUE) {
          // update DB-object
      $html .= 'Speichern '.($do->update()===FALSE?'fehlgeschlagen':'erfolgreich');
    } else {
      foreach ($val as $k=>$v) {
        if ($v == false) {
          $html .= "There was something wrong with ($k)<br />";
        } else {
          $html .= "($k) validated OK<br />";
        }
      }
    }
    return array('html' => $html);

}
?>
