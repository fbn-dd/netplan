<?php

function getRoutesBySNMP($ip, $community)
{
  $er = error_reporting(0);
  snmp_set_quick_print(TRUE);
  $output = array();

  $networks = snmpwalk($ip, $community, '.1.3.6.1.2.1.4.21.1.1',100000,1);
  if ($networks === FALSE)
  {
    error_reporting($er);
    return FALSE;
  }

  $netmasks = snmpwalk($ip, $community, '.1.3.6.1.2.1.4.21.1.11',100000,1);
  if ($netmasks === FALSE)
  {
    error_reporting($er);
    return FALSE;
  }

  $gateways = snmpwalk($ip, $community, '.1.3.6.1.2.1.4.21.1.7',100000,1);
  if ($gateways === FALSE)
  {
    error_reporting($er);
    return FALSE;
  }

  for ($i = 0; $i < count($networks); $i++)
  {
    array_push($output, array('network' => $networks["$i"],
                              'netmask' => $netmasks["$i"],
                              'gateway' => $gateways["$i"]));
  }

  error_reporting($er);
  return $output;
}

function getRoutesByDB($id_node = NULL)
{
  if ($id_node == NULL) return FALSE;

  $route = DB_DataObject::factory('route');
  $route->id_node = $id_node;
  $route->find();

  $routeMapping = array();
  while ($route->fetch())
  {
    $routeMapping[$route->id_route] = $route->toArray();
  }

  unset($route);

  return $routeMapping;
}

function collectRoutes($node)
{
  if ( !isset($node) ) return FALSE;

  $interface = DB_DataObject::factory('interface');
  $interface->id_node = $node->id_node;
  $interface->find();

  $snmp_routes = array();
  while ($interface->fetch())
  {
    $er=error_reporting(0);

    // Schuss ins Dunkel, wenn erfolgreich, dann mit den zeitaufwaendigen Sachen anfangen
    $status = snmpget($interface->ip, $node->snmp_community, '.1.3.6.1.2.1.1.1.0', '1000');
    if ($status)
    {
      $snmp_routes = getRoutesBySNMP($interface->ip, $node->snmp_community);
      break;
    }
  }

  // Routen aus der Datenbank holen
  $db_routes = getRoutesByDB($node->id_node);
 
  if ( !is_array($snmp_routes) && !is_array($db_routes) ) return FALSE;

  $netmask = DB_DataObject::factory('netmask');
  $netmask->find();
  
  $netmaskMapping = array();
  $netmaskRevMapping = array();
  while($netmask->fetch()) {
    $netmaskMapping[$netmask->id_netmask] = $netmask->description;
    $netmaskRevMapping[$netmask->description] = $netmask->id_netmask;
  }
  
  $subnet = DB_DataObject::factory('subnet');
  $subnet->find();
  
  $subnetMapping = array();
  while ($subnet->fetch()) {
    $subnetMapping[$subnet->id_subnet] = $subnet->toArray();
  }

  $out = array();

  // erster Durchlauf, es wird alles abgefangen, was in per SNMP und in der DB identisch ist
  if (is_array($snmp_routes))
  {
    $snmpRoutes = $dbRoutes = array();
    foreach ($snmp_routes as $key => $row)
    {
      foreach ($db_routes as $id_route => $route)
      {
        if ($row['network'] == $route['network'] && $row['netmask'] == $netmaskMapping[$route['id_netmask']] && $row['gateway'] == $route['gateway'] )
        {
          foreach ($subnetMapping as $id_subnet => $subnet)
          {
            $subnet_id = '0';
            $bnid = '-1';
            if ($id_route == $subnet['id_route'])
            {
              $subnet_id = $subnet['id_subnet'];
              $bnid = $subnet['bnid'];
              break;
            }
          }

          array_push($out, array( 'color' => '#00FF00',
                                  'id_route' => $id_route,
                                  'id_node' => $node->id_node,
                                  'network' => $route['network'],
                                  'id_netmask' => $route['id_netmask'],
                                  'netmask' => $netmaskMapping[$route['id_netmask']],
                                  'gateway' => $route['gateway'],
                                  'description' => $route['description'],
                                  'id_subnet' => $subnet_id,
                                  'bnid' => ($bnid != '-1'?$bnid:''),
                                  'option' => 'Speichern'
                                 ));
          $snmpRoutes[$key] = $row;
          $dbRoutes[$id_route] = $route;
        }
      }
    }
  }

  // alle Routen die durch den 1. Durchlauf abgefangen wurden abziehen
  if (is_array($snmp_routes) && is_array($snmpRoutes))
  {
    $snmpRoutes = array_diff_assoc($snmp_routes, $snmpRoutes);
  // wenn nix abgefangen wurde, einfache Zuweisung
  } else if (is_array($snmp_routes)) {
    $snmpRoutes = $snmp_routes;
  }

  // alle Routen die durch den 1. Durchlauf abgefangen wurden abziehen
  if (is_array($db_routes) && is_array($dbRoutes))
  {
    $dbRoutes = array_diff_assoc($db_routes, $dbRoutes);
  // wenn nix abgefangen wurde, einfache Zuweisung
  } else if (is_array($db_routes)) {
    $dbRoutes = $db_routes;
  }

  // alle Routen die nicht in der DB stehen und per SNMP kommen (gelb)
  if (is_array($snmpRoutes))
  {
    foreach ($snmpRoutes as $key => $route)
    {
      array_push($out, array( 'color' => '#FFFF00', 
                              'id_route' => '0',
                              'id_node' => $node->id_node,
                              'network' => $route['network'],
                              'id_netmask' => $netmaskRevMapping[$route['netmask']],
                              'netmask' => $route['netmask'],
                              'gateway' => $route['gateway'],
                              'description' => '',
                              'id_subnet' => '0',
                              'bnid' => '',
                              'option' => 'Speichern'
                            ));
    }
  }

  // alle Routen die nicht in per SNMP kommen und in der DB stehen (rot/loeschen)
  if (is_array($dbRoutes))
  {
    foreach ($dbRoutes as $id_route => $route)
    {
      foreach ($subnetMapping as $id_subnet => $subnet)
      {
        $subnet_id = '0';
        $bnid = '-1';
        if ($id_route == $subnet['id_route']) {
          $subnet_id = $subnet['id_subnet'];
          $bnid = $subnet['bnid'];
          break;
        }
      }
      
      array_push($out, array( 'color' => '#FF0000', 
                              'id_route' => $id_route,
                              'id_node' => $node->id_node,
                              'network' => $route['network'],
                              'id_netmask' => $route['id_netmask'],
                              'netmask' => $netmaskMapping[$route['id_netmask']],
                              'gateway' => $route['gateway'],
                              'description' => $route['description'],
                              'id_subnet' => $subnet_id,
                              'bnid' => ($bnid != '-1'?$bnid:''),
                              'option' => 'Löschen'
                            ));
      
    }
  }

  if (empty($out))
  {
    return FALSE;
  } else {
    return $out;
  }
}

// Funktion die aus der Edit.php aufgerufen wird und ein Objekt vom Type Node erwartet
function printRoutes($node) {
  $out = FALSE;

  // Array mit den Routen einsammeln
  $routes = collectRoutes($node);

  if (!$routes) return FALSE;
  
  if (is_array($routes))
  {
    // Tabbelnkopf
    $out =  '<table border="0">'.
            '<colgroup><col width="150" /><col width="150" /><col width="150" /><col width="150" /><col width="150" /><col width="150" /></colgroup>'.
            '<tr><td style="white-space: nowrap; background-color: #CCCCCC;" align="left2 valign="top" colspan=23"><b>Routen</b></td></tr>'.
            '<tr><td><b>Subnetz</b></td><td><b>Netzmaske</b></td><td><b>Gateway</b></td><td><b>Beschreibung</b></td><td><b>BNID</b></td><td><b>Option</b></td></tr></table>';
    $out =  '<div style="display:block-row; width:800px; background-color: #CCCCCC;"><b>Routen</b></div>'.
            '<div style="display:table-row">'.
            '<div style="display:table-cell; width:140px;"><b>Subnetz</b></div>'.
            '<div style="display:table-cell; width:140px;"><b>Netzmaske</b></div>'.
            '<div style="display:table-cell; width:140px;"><b>Gateway</b></div>'.
            '<div style="display:table-cell; width:240px;"><b>Beschreibung</b></div>'.
            '<div style="display:table-cell; width:50px;"><b>BNID</b></div>'.
            '<div style="display:table-cell; width:50px;"><b>Option</b></div>'.
            '</div>';

    // Array mit Routen verarbeiten
    foreach ($routes as $key => $route) {
      $out .= '<form name="editRoute_'.$key.'" id="editRoute_'.$key.'" onsubmit="remoteedit.onupdate(\'route\', HTML_AJAX.formEncode(\'editRoute_'.$key.'\',true)); remoteedit.onedit(\'node_'.$route['id_node'].'_1\'); return false;" 
action="/netplan/ajax/server.php" method="post">'.
              '<input name="id_route" type="hidden" value="'.$route['id_route'].'">'.
              '<input name="id_node" type="hidden" value="'.$route['id_node'].'">'.
              '<input name="network" type="hidden" value="'.$route['network'].'">'.
              '<input name="gateway" type="hidden" value="'.$route['gateway'].'">'.
              '<input name="id_netmask" type="hidden" value="'.$route['id_netmask'].'">'.
              '<input name="id_subnet" type="hidden" value="'.$route['id_subnet'].'">'.
              '<div style="display:table-row">'.
              '<div style="display:table-cell; width:140px; background-color:'.$route['color'].';">'.
              $route['network'].
              '</div>'.
              '<div style="display:table-cell; width:140px;">'.
              $route['netmask'].
              '</div>'.
              '<div style="display:table-cell; width:140px;">'.
              $route['gateway'].
              '</div>'.
              '<div style="display:table-cell; width:240px;">'.
              '<input name="description" type="text" size="32" value="'.$route['description'].'">'.
              '</div>'.
              '<div style="display:table-cell; width:50px;">'.
              '<input name="bnid" type="text" size="3" value="'.$route['bnid'].'">'.
              '</div>'.
              '<div style="display:table-cell; width:50px;">'.
              '<input name="action" type="'.($route['option']=='Speichern'?'submit':'button').'" value="'.$route['option'].'"'.($route['option']!='Speichern'?' onclick="remoteedit.ondelete(\'route\','.$route['id_route'].'); remoteedit.onedit(\'node_'.$route['id_node'].'_1\'); return false;"':'').'>'.
              '</div>'.
              '</div>'.
              '</form>';
    }
  }
  
  return $out;
}

?>
