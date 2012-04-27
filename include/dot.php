<?php

require_once(dirname(__FILE__) . "/db.php");

define('TMP_DIR', dirname(__FILE__) . '/../tmp');
//DB_DataObject::debuglevel(5);

function array_sort($array, $key)
{
   foreach ($array as $i => $k) {
       $sort_values[$i] = $array[$i][$key];
   }
   asort ($sort_values);
   reset ($sort_values);
   while (list ($arr_key, $arr_val) = each ($sort_values)) {
         $sorted_arr[$arr_key] = $array[$arr_key];;
   }
   return $sorted_arr;
}




function convertDB2Dot() {
  $section = DB_DataObject::factory('section');
  $section->find();

  $sectionMapping = array();
  while ($section->fetch()) {
    $sectionMapping[$section->id_section] = $section->description;
  }

  $location = DB_DataObject::factory('location');
  $location->find();
  
  $locationMapping = array();
  while ($location->fetch()) {
    $locationMapping[$location->id_location]['description'] = $location->description;
    $locationMapping[$location->id_location]['id_section'] = $location->id_section;
  }
  
  $node = DB_DataObject::factory('node');
  $node->find();
  
  $nodeMapping = array();
  while ($node->fetch()) {
    $nodeMapping[$node->id_node]['description'] = $node->description;
    $nodeMapping[$node->id_node]['id_type']     = $node->id_type;
    $nodeMapping[$node->id_node]['id_location'] = $node->id_location;
  }
  
  $channel = DB_DataObject::factory('channel');
  $channel->find();
  
  $channelMapping = array();
  while ($channel->fetch()) {
    $channelMapping[$channel->id_channel]['description'] = $channel->description;
    $channelMapping[$channel->id_channel]['id_medium'] = $channel->id_medium;
  }
  
  $medium = DB_DataObject::factory('medium');
  $medium->find();
  
  $mediumMapping = array();
  while ($medium->fetch()) {
    $mediumMapping[$medium->id_medium] = $medium->description;
  }
  
  $device = DB_DataObject::factory('device');
  $device->find();
  
  $deviceMapping = array();
  while ($device->fetch()) {
    $deviceMapping[$device->id_device] = $device->description;
  }
  
  $netmask = DB_DataObject::factory('netmask');
  $netmask->find();
  
  $netmaskMapping = array();
  while ($netmask->fetch()) {
    $netmaskMapping[$netmask->id_netmask] = $netmask->description;
  }

  $type = DB_DataObject::factory('type');
  $type->find();
  
  $typeMapping = array();
  while ($type->fetch()) {
    $typeMapping[$type->id_type] = $type->dot_color;
  }
  
  
  $interface = DB_DataObject::factory('interface');
  $interface->find();
  
  $interfaceMapping = array();
  while ($interface->fetch()) {
    $interfaceMapping[$interface->id_interface]['id_node'] = $interface->id_node;
    $interfaceMapping[$interface->id_interface]['channel'] = $channelMapping[$interface->id_channel]['description'];
    $interfaceMapping[$interface->id_interface]['medium']  = $mediumMapping[$channelMapping[$interface->id_channel]['id_medium']];
    $interfaceMapping[$interface->id_interface]['device']  = $deviceMapping[$interface->id_device];
    $interfaceMapping[$interface->id_interface]['netmask'] = ($interface->id_netmask==33?'0':$interface->id_netmask);
    $interfaceMapping[$interface->id_interface]['ip']      = $interface->ip;
    $interfaceMapping[$interface->id_interface]['id_mode'] = $interface->id_mode;
    $interfaceMapping[$interface->id_interface]['id_vlan'] = $interface->id_vlan;
  }
  $interfaceMapping = array_sort($interfaceMapping, "device");
  
  $nwinterfaces = array();
  foreach ($interfaceMapping as $id_interface => $array) {

    // for interfaces without ip, set dummy address
    if (empty($interfaceMapping[$id_interface]['ip'])) { $interfaceMapping[$id_interface]['ip'] = '0.0.0.0'; }
    if (empty($array['ip'])) { $array['ip'] = '0.0.0.0'; }
    
    if (array_key_exists($array['id_node'], $nwinterfaces)) {
      $nwinterfaces[$array['id_node']]['device'] .= ' | <'.$id_interface.'1> '.$interfaceMapping[$id_interface]['device'].($interfaceMapping[$id_interface]['id_vlan']>1?'.'.$interfaceMapping[$id_interface]['id_vlan']:'');
      $nwinterfaces[$array['id_node']]['ip'] .= ' | <'.$id_interface.'2> '.$interfaceMapping[$id_interface]['ip'].'/'.$interfaceMapping[$id_interface]['netmask'];
      $nwinterfaces[$array['id_node']]['type'] .= ' | <'.$id_interface.'3> '.$interfaceMapping[$id_interface]['medium'].($interfaceMapping[$id_interface]['channel']==' 0'?'':' ('.$interfaceMapping[$id_interface]['channel'].')');
      if (!isset($nwinterfaces[$array['id_node']]['ip_address']) && ($array['id_mode'] == '5' || $array['id_mode'] == '6')) {
        $nwinterfaces[$array['id_node']]['ip_address'] = $array['ip'];
      } 
    } else {
      $nwinterfaces[$array['id_node']]['device'] = '<'.$id_interface.'1> '.$interfaceMapping[$id_interface]['device'].($interfaceMapping[$id_interface]['id_vlan']>1?'.'.$interfaceMapping[$id_interface]['id_vlan']:'');
      $nwinterfaces[$array['id_node']]['ip'] = '<'.$id_interface.'2> '.$interfaceMapping[$id_interface]['ip'].'/'.$interfaceMapping[$id_interface]['netmask'];
      $nwinterfaces[$array['id_node']]['type'] = '<'.$id_interface.'3> '.$interfaceMapping[$id_interface]['medium'].($interfaceMapping[$id_interface]['channel']==' 0'?'':' ('.$interfaceMapping[$id_interface]['channel'].')');
      if ($array['id_mode'] == '5' || $array['id_mode'] == '6') { 
        $nwinterfaces[$array['id_node']]['ip_address'] = $array['ip'];
      }        
    }
  }

  $link = DB_DataObject::factory('link');
  $link->find();

  $links = array();
  while ($link->fetch()) {
/*    // Source Interface ist 'AP' oder 'P2P-Master'
    if ( $interfaceMapping[$link->id_src_interface]['id_mode'] == '1' OR $interfaceMapping[$link->id_src_interface]['id_mode'] == '3' ) {
      $dot = 'edge[samehead="'.$link->id_dst_interface.'1", sametail="'.$link->id_src_interface.'3"] struct'.$interfaceMapping[$link->id_src_interface]['id_node'].':'.$link->id_src_interface.'3 -> struct'.$interfaceMapping[$link->id_dst_interface]['id_node'].':'.$link->id_dst_interface.'1;'."\n";
    } else
    // Destination Interface ist 'AP' oder 'P2P-Master'
    if ( $interfaceMapping[$link->id_dst_interface]['id_mode'] == '1' OR $interfaceMapping[$link->id_dst_interface]['id_mode'] == '3' ) {
      $dot = 'edge[samehead="'.$link->id_src_interface.'1", sametail="'.$link->id_dst_interface.'3"] struct'.$interfaceMapping[$link->id_dst_interface]['id_node'].':'.$link->id_dst_interface.'3 -> struct'.$interfaceMapping[$link->id_src_interface]['id_node'].':'.$link->id_src_interface.'1;'."\n";
    } else { */
    // Augen zu und durch
      $dot = 'edge[samehead="'.$link->id_dst_interface.'1", sametail="'.$link->id_src_interface.'3"] struct'.$interfaceMapping[$link->id_src_interface]['id_node'].':'.$link->id_src_interface.'3 -> struct'.$interfaceMapping[$link->id_dst_interface]['id_node'].':'.$link->id_dst_interface.'1;'."\n";
    //}
    $links[$link->id_link] = $dot;
  }
  
  $fh = fopen (TMP_DIR."/dotplan.dot.new", "w");
  fputs( $fh, 'digraph netplan {'."\n".
     'rankdir=LR;'."\n".
     'node [shape=record, style=filled, fontsize=6.5];'."\n".
     'edge [style=solid, dir=forward, tailport=":e", headport=":w"];'."\n" );
    
    while (list($id_section, $description) = each($sectionMapping)) {
    while (list($id_location, $locations) = each($locationMapping)) {
      if ($locations['id_section'] == $id_section) {
        while (list($id_node, $nodes) = each($nodeMapping)) {
          if ($nodes['id_location'] == $id_location) {
            $fillcolor = $typeMapping[$nodes['id_type']];

            fputs( $fh, 'node[fillcolor="'.$fillcolor.(isset($nwinterfaces[$id_node]['ip_address'])?'", URL="https://'.$nwinterfaces[$id_node]['ip_address']:'').
              '", label="{<n'.$id_node.'> '.$nodes['description'].' } | {{'.(isset($nwinterfaces[$id_node]['device'])?$nwinterfaces[$id_node]['device']:'').'} | '.
              '{'.(isset($nwinterfaces[$id_node]['ip'])?$nwinterfaces[$id_node]['ip']:'').'} | {'.(isset($nwinterfaces[$id_node]['type'])?$nwinterfaces[$id_node]['type']:'').'}}"]{struct'.$id_node.'};'."\n");
          }
        } // list nodes
        reset($nodeMapping);
      }
    } // list locations
    reset($locationMapping);
  }

  // Links einfuegen
  foreach ($links as $id_link => $dot) {
    fputs( $fh, $dot );
  }

  fputs( $fh, "\n".'}' );
  fclose($fh);

  if ( 
    md5_file(TMP_DIR.'/dotplan.dot.new') != md5_file(TMP_DIR.'/dotplan.dot') || 
    !file_exists(TMP_DIR.'/dotplan.dot') || 
    !file_exists(TMP_DIR.'/dotplan.png')
  ) {
    system('mv '.TMP_DIR.'/dotplan.dot.new '.TMP_DIR.'/dotplan.dot');
    system('dot -Tcmapx -o '.TMP_DIR.'/dotplan.map -Tpng:gd:gd -o '.TMP_DIR.'/dotplan.png '.TMP_DIR.'/dotplan.dot');
    //system('circo -Tcmapx -o '.TMP_DIR.'/dotplan.map -Tpng -o '.TMP_DIR.'/dotplan.png '.TMP_DIR.'/dotplan.dot');
    //system('dot -Tcmapx -o '.TMP_DIR.'/dotplan.map -Tpng:gd:gd -o '.TMP_DIR.'/dotplan.png -Tps -o '.TMP_DIR.'/dotplan.ps '.TMP_DIR.'/dotplan.dot');
  }
}
?>
