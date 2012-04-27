<?php

require_once(dirname(__FILE__) . "/db.php");
require_once(dirname(__FILE__) . "/rrd.php");
require_once 'File/Find.php';

class remotestats {

  function get_stats_for_section($id_section, $range=NULL) {
    if(!$range) {
      if(is_numeric($_COOKIE["range"])) {
        $range = $_COOKIE["range"];
      } else {
        $range = 86400;
      }
    }
    setCookie("range", $range, time()+2592000);
    setCookie("range", $range, time()+2592000,'/netplan/stats/');

    $section = DB_DataObject::factory('section');
    $section->get($id_section);
    $html = '<table>'.
            ' <tr><td class="titlebox">'.$this->get_header($section, $range).'</td></tr>'.
            ' <tr><td>'.$this->section_stats($section, $range).'</td></tr>'.
            '</table>';
    return($html);
  }


  function get_stats_for_location($id_location, $range=NULL) {
    if(!$range) {
      if(is_numeric($_COOKIE["range"])) {
        $range = $_COOKIE["range"];
      } else {
        $range = 86400;
      }
    }
    setCookie("range", $range, time()+2592000);
    setCookie("range", $range, time()+2592000,'/netplan/stats/');

    $location = DB_DataObject::factory('location');
    $location->get($id_location);
    $html = '<table>'.
            ' <tr><td class="titlebox">'.$this->get_header($location, $range).'</td></tr>'.
            ' <tr><td>'.$this->location_stats($location, $range).'</td></tr>'.
            '</table>';

    // Performanceprobleme und zweifelhafter Nutzen
    //$html .= $this->get_location_user_table($location, $range);

    return($html);
  }


  function get_stats_for_node($id_node, $range=NULL) {
    if($range == NULL) {
      if(isset($_COOKIE["range"]) && is_numeric($_COOKIE["range"])) {
        $range = $_COOKIE["range"];
      } else {
        $range = 86400;
      }
    }
    setCookie("range", $range, time()+2592000);
    setCookie("range", $range, time()+2592000,'/netplan/stats/');

    $node = DB_DataObject::factory('node');
    $node->get($id_node);
    $html = '<table>'.
            ' <tr><td class="titlebox">'.$this->get_header($node, $range).'</td></tr>'.
            ' <tr><td>'.$this->node_stats($node, $range).'</td></tr>'.
            '</table>';
    return($html);
  }

  function get_snr_table($id_node) {
    $admin = FALSE;

    if (is_string($_SERVER["PHP_AUTH_USER"])){
      $auth_username = $_SERVER["PHP_AUTH_USER"];

      $gruppen = DB_DataObject::factory('Gruppen');
      $gruppen->Username = $auth_username;
      $gruppen->find();

      while($gruppen->fetch()) {
        if ($gruppen->Gruppe == 'admin' OR $gruppen->Gruppe == 'Messtrupp') {
          $admin = TRUE;
          break;
        }
      }
    }
    $node = DB_DataObject::factory('node');
    $node->get($id_node);
    $node->find(TRUE);

    $type = $node->getLink('id_type', 'type');
    if ( preg_match("/^LANCOM/", $type->description) )
    {
      require_once(dirname(__FILE__) . "/snr2.php");
    }
     else if ( preg_match("/^Mikrotik/", $type->description) )
    {
      require_once(dirname(__FILE__) . "/snr3.php");
    }
    else
    {
      require_once(dirname(__FILE__) . "/snr.php");
    }
    return printSNRTable($id_node, $admin);
  }

  /**
   * Internal Helper
   */


  function get_header($do, $range) {
    eval('$id = $do->id_'.$do->__table.';');
    $html = 'Skalierung [ '.
            '<a href="#" onclick="remotestats.get_stats_for_'.$do->__table.'('.$id.', 3600);return false;">'.($range==3600?'<strong>1h</strong>':'1h').'</a> | '.
            '<a href="#" onclick="remotestats.get_stats_for_'.$do->__table.'('.$id.', 21600);return false;">'.($range==21600?'<strong>6h</strong>':'6h').'</a> | '.
            '<a href="#" onclick="remotestats.get_stats_for_'.$do->__table.'('.$id.', 86400);return false;">'.($range==86400?'<strong>1d</strong>':'1d').'</a> | '.
            '<a href="#" onclick="remotestats.get_stats_for_'.$do->__table.'('.$id.', 604800);return false;">'.($range==604800?'<strong>1w</strong>':'1w').'</a> | '.
            '<a href="#" onclick="remotestats.get_stats_for_'.$do->__table.'('.$id.', 2419200);return false;">'.($range==2419200?'<strong>1m</strong>':'1m').'</a> | '.
            '<a href="#" onclick="remotestats.get_stats_for_'.$do->__table.'('.$id.', 29030400);return false;">'.($range==29030400?'<strong>1y</strong>':'1y').'</a> | '.
            '<a href="#" onclick="remotestats.get_stats_for_'.$do->__table.'('.$id.', 290304000);return false;">'.($range==290304000?'<strong>10y</strong>':'10y').'</a> ]';
    return($html);
  }

  function section_stats($section, $range=86400) {

    $html = '<table>'.
            ' <tr><td class="titlebox">'.$section->description.'</td></tr>'.
            ' <tr><td class="normalbox">';

    $location = DB_DataObject::factory('location');
    $location->id_section = $section->id_section;
    $location->orderBy('description');
    $location->find();

    while ($location->fetch()) {
      $html .= $this->location_stats($location, $range);
    }

    $html .= '  </td></tr>'.
             '</table>';

    return($html);
  }

  function location_stats($location, $range=86400) {

    $html = '<table>'.
            ' <tr><td class="titlebox">'.$location->description.'</td></tr>'.
            ' <tr><td class="normalbox">';

    $node = DB_DataObject::factory('node');
    $node->id_location = $location->id_location;
    $node->orderBy('description');
    $node->find();

    while ($node->fetch()) {
      $html .= $this->node_stats($node, $range);
    }

    $html .= '  </td></tr>'.
             '</table>';

    return($html);
  }

  function get_location_user_table($location, $range=86400) {
  
  /**
   * 1) suche alle AP-Interfaces am Standort
   * 2) suche nach allen RRD-Files fuer die Inferfaces, die SNRs aufzeichnen
   * 3) suche fuer jede Mac nach Mitglied und E-Mail
   *
   * Hinweis: Ein AP kann Clients sehen, die niemals am AP eingebucht sind, 
   *          welche aber dennoch in der Macs-Tabelle vom FBN stehen.
   */

    $html = '<table>'.
            ' <tr><td class="titlebox">bisherige Nutzer am Standort '.$location->description.'</td></tr>'.
            ' <tr><td class="normalbox">';

    $node = DB_DataObject::factory('node');
    $node->id_location = $location->id_location;
    $node->find();
    $macs = array();
    while ($node->fetch()) {
        $interface = DB_DataObject::factory('interface');
        $interface->whereAdd('ip != ""');
        $interface->whereAdd('ip != "0.0.0.0"');
        $interface->id_node = $node->id_node;
        $interface->id_mode = 1; // AP
        $interface->find();
        while ($interface->fetch()) {
            // @TODO: get list of macs from files in directory ../stats/rrd/ named snr_'.$interface->id.'_<mac>.rrd
            $files = File_Find::glob('snr_'.$interface->id_interface.'_', dirname(__FILE__) . '/../stats/rrd/', 'php');
            foreach($files as $fileitem)
            {
                preg_match('/snr_'.$interface->id_interface.'_([0-9a-z]*).rrd/i', $fileitem, $mac);
                // array(mac => node)
                $macs[$mac[1]] = $node->description;
            }
        }
    }

    $mac = DB_DataObject::factory('Macs');
    // geht nicht, warum auch immer:
    //$mac->addWhere('BNID != 0');
    //$mac->addWhere("LastRadiusAuth > '0000-00-00 00:00:00'");
    $mac->find();
    $html .= '<table id="apusers"><tr><th>Sektor</th><th>Mitglied</th><th>E-Mail</th></tr>';
    while ($mac->fetch()) {
        // mac ever seen here?
        $current_mac = str_replace('-', '', $mac->Mac);
        if (!array_key_exists($current_mac, $macs)) continue;
        // mac ever logged in here?
        if ($mac->LastRadiusAuth == '0000-00-00 00:00:00') { $html .= $current_mac.' at '.$macs[$current_mac].' but LastRadiusAuth=0'; continue; }
        // hide FBN user 0
        if ($mac->BNID == 0) continue;
        // @TODO: $mac->ValidTo > now() oder "NULL"
        // @TODO: lastRadiusAuth > 3 Monate vielleicht?

        $kommunikation = DB_DataObject::factory('Kommunikation');
        $kommunikation->BNID = $mac->BNID;
        $kommunikation->TKommunikation = 'email';
        $kommunikation->find();
        while ($kommunikation->fetch()){ $email = $kommunikation->Value; break; }
        $mitglied = DB_DataObject::factory('Mitglieder');
        $mitglied->get('BNID', $mac->BNID);
        $html .= '<tr><td>'.$macs[$current_mac].'</td><td>'.$mitglied->Vorname.' '.$mitglied->Nachname.'</td><td><a href="mailto:'.$email.'">anschreiben</a></td></tr>';

    }
    $html .= '</table>';
    $html .= '  </td></tr>'.
             '</table>';

    return($html);
  }


  function node_stats($node, $range=86400) {

    $html = '<table>'.
            ' <tr><td class="titlebox">'.$node->description.'</td></tr>'.
            ' <tr><td>';

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

    $tmpnode = DB_DataObject::factory('node');
    $tmpnode->find();
    while ($tmpnode->fetch()) {
      $nodeMapping[$tmpnode->id_node] = $tmpnode->toArray();
    }

    $interface = DB_DataObject::factory('interface');
    $interface->id_node = $node->id_node;
    $interface->find();

    $count=0;
    $i=1; /* IE bricht Bilder nebeneinander nicht um, wenn Seite zu schmal -> manueller Umbruch aller 2 Bilder */
    while ($interface->fetch()) {

      if ( is_string($interface->description) && (strlen($interface->description) > 0) )
      {
        $description = $interface->description;
      } else {
        $description = $node->description.' - '.$deviceMapping[$interface->id_device].': '.
                       $interface->ip.' ('.$channelMapping[$interface->id_channel].')';
      }
      
      $cachestamp = floor(time() / floor($range/60)) * floor($range/60);



      rrd_graph_traffic('id_interface_'.$interface->id_interface.'.rrd', 'id_interface_'.$interface->id_interface.'-'.$range.'.png', $range, $description);
      $html .= '<img src="png/id_interface_'.$interface->id_interface.'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Traffic Daten f&uuml;r '.$description.' =- ">';
      if ($i%2==0) { $html.= "<br>\n"; }
      $i++;
	
	$isLancom=false;
	$type = $interface->getLink('id_node', 'node')->getLink('id_type', 'type');
	if (preg_match("/^LANCOM/", $type->description)) {
		$isLancom=true;
	}

      if ($interface->id_mode == 4 && $isLancom) {
        rrd_graph_signal('signal_'.$interface->id_interface.'.rrd', 'signal_'.$interface->id_interface.'-'.$range.'.png', $range, $description);
        $html .= '<img src="png/signal_'.$interface->id_interface.'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Signal Daten f&uuml;r '.$description.' =- ">';
        if ($i%2==0) { $html.= "<br>\n"; }
        $i++;

        rrd_graph_channel('channel_'.$interface->id_interface.'.rrd', 'channel_'.$interface->id_interface.'-'.$range.'.png', $range, $description);
        $html .= '<img src="png/channel_'.$interface->id_interface.'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Kanal Daten f&uuml;r '.$description.' =- ">';
        if ($i%2==0) { $html.= "<br>\n"; }
        $i++;

        rrd_graph_rxerrors('rxerrors_'.$interface->id_interface.'.rrd', 'rxerrors_'.$interface->id_interface.'-'.$range.'.png', $range, $description);
        $html .= '<img src="png/rxerrors_'.$interface->id_interface.'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine RX-Fehler Daten f&uuml;r '.$description.' =- ">';
        if ($i%2==0) { $html.= "<br>\n"; }
        $i++;
      }

      if ($interface->id_mode == 1 && $isLancom) {
        rrd_graph_noise('noise_'.$interface->id_interface.'.rrd', 'noise_'.$interface->id_interface.'-'.$range.'.png', $range, $description);
        $html .= '<img src="png/noise_'.$interface->id_interface.'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Noise Daten f&uuml;r '.$description.' =- ">';
        if ($i%2==0) { $html.= "<br>\n"; }
        $i++;

        rrd_graph_chload('chload_'.$interface->id_interface.'.rrd', 'chload_'.$interface->id_interface.'-'.$range.'.png', $range, $description);
        $html .= '<img src="png/chload_'.$interface->id_interface.'-'.$range.'.png?'.$cachestamp.'" alt=" -= Keine Kanallast Daten f&uuml;r '.$description.' =- ">';
        if ($i%2==0) { $html.= "<br>\n"; }
        $i++;
      }

      $count++;
    }

    $html .= '  </td></tr>'.
             '</table>';

    return($html);
  }

}

?>
