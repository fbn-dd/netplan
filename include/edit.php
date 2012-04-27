<?php

require_once(dirname(__FILE__) . '/db.php');
require_once(dirname(__FILE__) . '/route.php');
require_once(dirname(__FILE__) . '/snmp.php');
require_once('HTML/QuickForm.php');


function editSection($id_section, $id_parent) {
  $section = DB_DataObject::factory('section');
  if ( $id_section > '0' ) {
    $section->get($id_section);
  }
  $section_array = $section->toArray();

  $form =& new HTML_QuickForm('editSection', 'post', '', '',
                              'onsubmit="remoteedit.onupdate(\'section\', HTML_AJAX.formEncode(\'editSection\',true)); return false;"');
  $form->addElement('header',   null,   'Sektion Editor');
  $form->addElement('hidden',   'id_section',  $id_section);
  $form->addElement('text',     'description', 'Beschreibung:');
  $form->addElement('text',     'tm_width',    'TM Width:');
  $form->addElement('text',     'tm_height',   'TM Height:');
  $button[] = &HTML_QuickForm::createElement('reset',  'action', 'Zurücksetzen');
  $button[] = &HTML_QuickForm::createElement('submit', 'action', 'Speichern');
  $button[] = &HTML_QuickForm::createElement('button', 'action', 'Löschen',       array('onclick' => 'remoteedit.ondelete(\'section\', '.$id_section.');'));
  $form->addGroup($button,'','',' ');
  $form->setDefaults($section_array);
 
  return($form->toHtml().  "<span id='response'></span>".
                           "<hr />\n".
                           "<h1>Erläuterung</h1>\n".
                           "<ul>\n".
                           "  <li>Beschreibung: Name des Verantwortungsbereichs</li>\n".
                           "  <li>TM Width: Breite der generierten Trafficmap</li>\n".
                           "  <li>TM Height: Höhe der generierten Trafficmap</li>\n".
                           "</ul>");
}

function editLocation($id_location, $id_section) {
  if ( $id_location > '0' ) {
    $location = DB_DataObject::factory('location');
    $location->get($id_location);
    $location_array = $location->toArray();
  } else {
    $location_array = array('id_section' => $id_section);
  }

  $do =& DB_DataObject::factory('section');
  $do->orderBy('description');
  $do->find();
  while ($do->fetch()) {
    $sectionMapping[$do->id_section] = $do->description;
  }

  // Form erstellen
  $form = new HTML_QuickForm('editLocation', 'post', '', '',
                             'onsubmit="remoteedit.onupdate(\'location\', HTML_AJAX.formEncode(\'editLocation\',true)); return false;"');
  // Elemente einfügen
  $form->addElement('header', null, 'Standort Editor');
  $form->addElement('hidden', 'id_location');
  $form->addElement('select', 'id_section', 'Sektion:', $sectionMapping);
  $form->addElement('text', 'description', 'Beschreibung:');
  $form->addElement('text', 'street', 'Straße:');
  $form->addElement('text', 'postcode', 'Postleitzahl:');
  $form->addElement('text', 'city', 'Ort:');
  $form->addElement('text', 'longitude', 'Longitude:');
  $form->addElement('text', 'latitude', 'Latitude:');
  $form->addElement('textarea', 'contact', 'Kontakt:', array('cols' => 50, 'rows' => 4));
  // Buttons einfügen
  $buttons[] = &HTML_QuickForm::createElement('reset', null, 'Zurücksetzen');
  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Speichern');
  $buttons[] = &HTML_QuickForm::createElement('button', null, 'Löschen', array('onclick' => 'remoteedit.ondelete(\'location\', '.$id_location.');'));
  $form->addGroup($buttons, null, '', ' ');
  $form->setDefaults($location_array);

  return($form->toHtml().  "<span id='response'></span>".
                           "<hr />\n".
                           "<h1>Erläuterung</h1>\n".
                           "<em>Unbedingt die <a href=\"https://www.example.org/wiki/Namenskonvention\">Namenskonvention</a> beachten!</em>".
                           "<ul>\n".
                           "  <li>Beschreibung: Kürzel des Standorts auf 3 Buchstaben</li>\n".
                           "  <li>wenn Adresse, Postleitzahl und Ort eingetragen sind, aber keine Koordinaten, werden diese automatisch ermittelt</li>\n".
                           "  <li>die Koordinaten entweder per Hand ermitteln oder über den Map Editor den Standort lokalisieren</li>\n".
                           "  <li>Kontakt: Ansprechpartner, Telefon falls Anmeldung n&ouml;tig usw.</li>\n".
                           "</ul>");
}

function editNode($id_node, $id_location) {
  // Daten holen
  $serial = '';
  if ( $id_node > '0' ) {
    $node = DB_DataObject::factory('node');
    $node->get($id_node);
    $node_array = $node->toArray();
    $snmpRoutes = printRoutes($node);
    $type = $node->getLink('id_type', 'type');
    if ( preg_match("/^LANCOM/", $type->description) ) 
    {
        $serial     = getSerialBySNMP($node);
    }
  } else {
    $node_array = array('id_location' => $id_location);
    $snmpRoutes = '<table border="0"><colgroup><col width="150" /><col width="150" /><col width="150" /><col width="150" /><col width="150" /><col width="150" /></colgroup><tr><td colspan="3">Konnte keine Routen per SNMP ermitteln.</td></tr></table>';
  }

  $do =& DB_DataObject::factory('location');
  $do->orderBy('description');
  $do->find();
  while ($do->fetch()) {
    $locationMapping[$do->id_location] = $do->description;
  }

  $do =& DB_DataObject::factory('type');
  $do->orderBy('description');
  $do->find();
  while ($do->fetch()) {
    $typeMapping[$do->id_type] = $do->description;
  }

  // Form erstellen
  $form = new HTML_QuickForm('editNode', 'post', '', '',
                             'onsubmit="remoteedit.onupdate(\'node\', HTML_AJAX.formEncode(\'editNode\',true)); return false;"');
  // Elemente einfügen
  $form->addElement('header', null, 'Gerät Editor');
  $form->addElement('hidden', 'id_node');
  $form->addElement('select', 'id_location', 'Standort:', $locationMapping);
  $form->addElement('text', 'description', 'Beschreibung:');
  $form->addElement('select', 'id_type', 'Typ:', $typeMapping);
  $form->addElement('text', 'nr_inventar', 'Inventarnummer:');
  // nur bei bestehenden Node und wenn es ein LANCOM-Gerät ist
  if ( $id_node > 0 AND preg_match("/^LANCOM/", $type->description) )
  {
    $attr = array("readonly" => "readonly");
    if ( $serial == $node_array['serial'] )
    {
      $attr['style'] = 'color:green;';
      $form->addElement('text', 'serial', 'Lancom-Seriennummer:', $attr);
    } else if ( empty($serial) && !empty($node_array['serial']) ) {
      $node_array['serial'] = $node_array['serial'];
      $form->addElement('text', 'serial', 'Lancom-Seriennummer:', $attr);
    } else {
      $node_array['serial_old'] = $node_array['serial']; 
      $node_array['serial'] = $serial;
      $attr['style'] = 'background-color:yellow;';
      $form->addElement('text', 'serial', 'Lancom-Seriennummer:', $attr);
      if ( !empty($node_array['serial_old']) )
      {
        $form->addElement('static', 'serial_old', ' alte Lancom-Seriennummer:');
      }
    }
  }
  if (is_string($_SERVER['PHP_AUTH_USER']))
  {
    $auth_username = $_SERVER['PHP_AUTH_USER'];
    $gruppen = DB_DataObject::factory('Gruppen');
    $gruppen->Username = $auth_username;
    $gruppen->find();
    while($gruppen->fetch()) {
      if ($gruppen->Gruppe == 'admin') {
        $form->addElement('password', 'config_password', 'Config Passwort:');
        break;
      }
    }
  }
  $form->addElement('text', 'snmp_community', 'SNMP Community:');
  $form->addElement('text', 'snmp_password', 'SNMP Passwort:');
  $form->addElement('text', 'radius_password', 'RADIUS Passwort:');
  $form->addElement('text', 'x_coord', 'TM X Koordinate:');
  $form->addElement('text', 'y_coord', 'TM Y Koordinate:');
  // Buttons einfügen
  $buttons[] = &HTML_QuickForm::createElement('reset', null, 'Zurücksetzen');
  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Speichern');
  $buttons[] = &HTML_QuickForm::createElement('button', null, 'Loeschen', array('onclick' => 'remoteedit.ondelete(\'node\',' .$id_node.');'));
  $form->addGroup($buttons, null, '', ' ');
  // Anzeigewerte aktualisieren
  $form->setDefaults($node_array);

  return($form->toHtml().  "<span id='response'></span>".
                           "<hr />".
                           (isset($snmpRoutes)?$snmpRoutes:'').
                           "<hr />\n".
                           "<h1>Erläuterung</h1>\n".
                           "<em>Unbedingt die <a href=\"https://www.example.org/wiki/Namenskonvention\">Namenskonvention</a> beachten!</em>".
                           "<ul>\n".
                           "  <li>Beschreibung: Name des Geräts (nur Buchstaben und Bindestrich, d.&nbsp;h. <em>keine Sonderzeichen, keine Leerzeichen)</em></li>\n".
                           "  <li>Typ: den Gerätetyp richtig auswählen, der Typ hat Folgen für weitere Funktionen</li>".
                           "  <li>SNMP Community: wichtig für die Routerstatistik und die Routing Informationen</li>\n".
                           "  <li>TM X: X Punkt in der Trafficmap der Sektion</li>\n".
                           "  <li>TM Y: Y Punkt in der Trafficmap der Sektion</li>\n".
                           "</ul>");
}

function editInterface($id_interface, $id_node) {
  $iftable = null;
  if ( $id_interface > '0' ) {
    $interface = DB_DataObject::factory('interface');
    $interface->get($id_interface);
    $interface_array = $interface->toArray();

    $node = $interface->getLink('id_node', 'node');
    $type = $node->getLink('id_type', 'type');
    if (!preg_match("/^LANCOM/", $type->description) && $interface_array['ip'] && $interface_array['oid_override'] && $node->snmp_community != 'disabled')
    {
      $iftable = getIftable($interface, $node);
    }
  } else {
    $interface_array = array('id_node' => $id_node);
  }

  $do =& DB_DataObject::factory('node');
  $do->orderBy('description');
  $do->find();
  while ($do->fetch()) {
    $nodeMapping[$do->id_node] = $do->description;
  }

  $do =& DB_DataObject::factory('device');
  $do->orderBy('description');
  $do->find();
  while ($do->fetch()) {
    $deviceMapping[$do->id_device] = $do->description;
  }

  $do =& DB_DataObject::factory('mode');
  $do->orderBy('description');
  $do->find();
  while ($do->fetch()) {
    $modeMapping[$do->id_mode] = $do->description;
  }

  $do =& DB_DataObject::factory('polarisation');
  $do->orderBy('description');
  $do->find();
  $polarisationMapping = array();
  while ($do->fetch()) {
    $polarisationMapping[$do->id_polarisation] = $do->description;
  }

  $do =& DB_DataObject::factory('vlan');
  $do->orderBy('id_vlan');
  $do->find();
  $vlanMapping = array();
  while ($do->fetch()) {
    $vlanMapping[$do->id_vlan] = $do->id_vlan." > ".$do->description;
  }

  $do =& DB_DataObject::factory('medium');
  $do->find();
  $mediumMapping = array();
  while ($do->fetch()) {
    $mediumMapping[$do->id_medium] = $do->description;
  }

  $do =& DB_DataObject::factory('channel');
  $do->find();
  while ($do->fetch()) {
    $channelMapping[$do->id_channel] = $mediumMapping[$do->id_medium].' > '.$do->description;
    $channel[$do->id_channel] = $do->id_medium;
  }
  asort($channelMapping);

  $do =& DB_DataObject::factory('netmask');
  $do->find();
  while ($do->fetch()) {
    $netmaskMapping[$do->id_netmask] = $do->description." [".($do->id_netmask==33?"0":$do->id_netmask)."]";
  }
  arsort($netmaskMapping);

  // Form erstellen
  $form = new HTML_QuickForm('editInterface', 'post', '', '',
                             'onsubmit="remoteedit.onupdate(\'interface\', HTML_AJAX.formEncode(\'editInterface\',true)); remoteedit.onedit(\'interface_'.$id_interface.'_1\'); return false;"');
  // Elemente einfügen
  $form->addElement('header', null, 'Interface Editor');
  $form->addElement('hidden', 'id_interface');
  $form->addElement('select', 'id_node', 'Gerät:', $nodeMapping);
  $form->addElement('select', 'id_device', 'Interface:', $deviceMapping);
  $form->addElement('text', 'description', 'Beschreibung:');
  $form->addElement('select', 'id_channel','Kanal:',  $channelMapping);
  $form->addElement('select', 'id_mode', 'Modus:', $modeMapping);

  // Das Objekt $interface gibt es nur, wenn ein bestehendes Interface geändert wird. Polarisation auch bei neuem Interface anlegen (id=0) anzeigen
  if ($id_interface == 0 OR $interface->id_mode == '1' OR $interface->id_mode == '3')
  {
    $form->addElement('select', 'id_polarisation', 'Polarisation:', $polarisationMapping);
  }

  $form->addElement('select', 'id_vlan', 'VLAN ID:', $vlanMapping);
  $form->addElement('text', 'ip', 'IP-Adresse:');
  $form->addElement('select', 'id_netmask', 'Netzmaske:', $netmaskMapping);

  $group[] = &HTML_QuickForm::createElement('radio', null, null, 'Ja', 1, 'onClick="return confirm(\'Klemmt an diesem Interface wirklich als NÄCHSTES GERÄT ein DSL-Modem?\');"');
  $group[] = &HTML_QuickForm::createElement('radio', null, null, 'Nein', 0);
  $form->addGroup($group, 'isWAN', 'Internetzugang?', '&nbsp;');
  unset($group);
  
  $form->addElement('text', 'dhcp_start', 'DHCP Start:');
  $form->addElement('text', 'dhcp_end', 'DHCP End:');

  if ( $id_interface > 0 AND !preg_match("/^LANCOM/", $type->description) AND $node->snmp_community != 'disabled' )
  {
    $group[] = &HTML_QuickForm::createElement('radio', null, null, 'Ja', 1, 'onClick="return confirm(\'Ist die SNMP-Community beim Gerät eingetragen UND werden die Routen unter dem Geräteformular angezeigt? ERST WENN man die live ausgelesenen Routen sieht, darf das Interface wieder ausgewählt werden und diese Option aktiviert werden!\');"');
    $group[] = &HTML_QuickForm::createElement('radio', null, null, 'Nein', 0);
    $form->addGroup($group, 'oid_override', 'Interface Id festlegen', '&nbsp;');
  }

  // Buttons einfügen
  $buttons[] = &HTML_QuickForm::createElement('reset', null, 'Zurücksetzen');
  // @TODO: JavaScript validation of IP addresses:
  //  return ip.value.match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/'); dann noch hübsches alert() bei Fehler
  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Speichern');
  $buttons[] = &HTML_QuickForm::createElement('button', null, 'Loeschen', array('onclick' => 'remoteedit.ondelete(\'interface\', '.$id_interface.');'));
  $form->addGroup($buttons, null, null, ' ');

  // 'isWAN' auf 0 setzen damit die Radio-Group bei nichtgesetzen Wert default auf 'Nein' steht 
  if (!isset($interface_array['isWAN']))        $interface_array['isWAN'] = 0;
  if (!isset($interface_array['oid_override'])) $interface_array['oid_override'] = 0;

  // Werte setzen
  $form->setDefaults($interface_array);

  return($form->toHtml().  "<span id='response'></span>".
                           "<hr />\n".
                           (isset($iftable)?$iftable:'').
                           "<hr />\n".
                           "<h1>Erläuterung</h1>\n".
                           "<ul>\n".
                           "  <li>Interface: die richtige Bezeichnung des Interfaces auswählen, hat weitreichende Konsequenzen</li>\n".
                           "  <ul>\n".
                           "    <li>LANCOM L-54ag: if1 ist immer die LAN-Schnittstelle und if2 ist immer die WLAN-Schnittstelle</li>\n".
                           "    <li>LANCOM L-54 dual: if1 und if2 sind immer die LAN-Schnittstellen, if3 und if4 sind immer die WLAN-Schnittstellen</li>\n".
                           "    <li>LANCOM 1711 VPN: if1 bis if4 sind immer die Schnittstellen LAN-1 bis LAN-4</li>\n".
                           "    <li>LANCOM 8011 VPN: if1 bis if4 sind immer die Schnittstellen LAN-1 bis LAN-4, WAN1 bis WAN8 sind die acht WAN-Schnittstellen</li>\n".
                           "    <li>Linksys WRT54 Client: eth1 ist das WLAN Interface, br0 ist LAN</li>\n".
                           "    <li>Linksys WRT54 AP: vlan1 ist das WAN Interface, br0 ist LAN+WLAN(wird als Wlan gekennzeichnet, da AP)</li>\n".
                           "    <li>MikroTik: <em>Immer</em> wlan1 einstellen und restliche Eintragungen vornehmen, dann speichern. Danach beim Gerät prüfen ob die SNMP-Community eingetragen ist und die Routentabelle angezeigt wird. Erst wenn man die live ausgelesenen Routen sieht, darf das Interface wieder ausgewählt werden. Jetzt setzt man &quot;Interface Id festlegen&quot; aktiv und speichert die Einstellung. Mit etwas Geduld wird nun darunter die &quot;Tabelle der Netzwerkschnittstellen&quot; geladen. Hier wählt man das gewünschte Interface aus und klickt auf die <em>unteren</em> Speichern-Schaltfläche.</li>\n".
                           "    <li>Orinoco: if1 ist LAN, if2 und if3 sind 1. und 2. WLAN-Karte</li>\n".
                           "    <li>PC: genau die Benennung wie sie ifconfig ausgibt</li>\n".
                           "  </ul>\n".
                           "  <li>Beschreibung: Dieser Text wird als Titel in den Trafficgrafiken eingef&uuml;gt.</li>".
                           "  <li>Channel: selbsterklärend, hat Auswirkung auf die Trafficmap(maximale Bandbreite) und die Farben der Beams in der Google Map</li>".
                           "  <li>Mode: auch das hat Folgen ;)</li>\n".
                           "  <ul>\n".
                           "    <li>P2P-Master: bei P2P und P2MP den Master richtig angeben, alle verlinkten Interfaces erben die Einstellungen</li>\n".
                           "    <li>AP: die Einstellung bei APs und auch beim br0 Interface von WRTs im AP-Modus</li>\n".
                           "    <li>Client: WRT-Interfaces im Clientmodus bekommen diese Bezeichnung, und auch alles was per Ethernet miteinander verbunden ist</li>\n".
                           "  </ul>\n".
                           "  <li>Internetzugang: an dieser Schnittstelle klemmt <em>unmittelbar</em> ein DSL-Modem; Ja = die Schnittstelle steht in der Routerübersicht unter Internet/Loadbalancer</li>".
                           "</ul>");
}

function editLink($id_link, $id_interface) {
  if ( $id_link > '0' ) {
    $link = DB_DataObject::factory('link');
    $link->get($id_link);
    $link_array = $link->toArray();
  } else {
    $link_array = array('id_src_interface' => $id_interface, 'id_dst_interface' => $id_interface);
  }
  $link_array['id_interface'] = $id_interface;

  $do =& DB_DataObject::factory('device');
  $do->find();
  while ($do->fetch()) {
    $deviceMapping[$do->id_device] = $do->description;
  }

  $do =& DB_DataObject::factory('node');
  $do->find();
  while ($do->fetch()) {
    $nodeMapping[$do->id_node] = $do->description;
  }

  $do =& DB_DataObject::factory('interface');
  $do->find();
  while ($do->fetch()) {
    $interfaceMapping[$do->id_interface] = $nodeMapping[$do->id_node].' ['.$deviceMapping[$do->id_device].($do->id_vlan>1?'.'.$do->id_vlan:'').']';
    $interfaceNode[$do->id_interface] = $do->id_node;
  }
  asort($interfaceMapping);

  // Form erstellen
  $form = new HTML_QuickForm('editLink', 'post', '', '',
                             'onsubmit="remoteedit.onupdate(\'link\', HTML_AJAX.formEncode(\'editLink\',true)); return false;"');
  // Elemente einfügen
  $form->addElement('header', null, 'Link Editor');
  $form->addElement('hidden', 'id_link');
  $form->addElement('hidden', 'id_interface');
  $form->addElement('select', 'id_src_interface','Quelle:', $interfaceMapping);
  $form->addElement('select', 'id_dst_interface','Ziel:',   $interfaceMapping);
  // Buttons einfügen
  $buttons[] = &HTML_QuickForm::createElement('reset', null, 'Zurücksetzen');
  $buttons[] = &HTML_QuickForm::createElement('submit', null, 'Speichern');
  $buttons[] = &HTML_QuickForm::createElement('button', null, 'Löschen', array('onclick' => 'remoteedit.ondelete(\'link\', '.$id_link.');'));
  $form->addGroup($buttons, null, null, ' ');
  // Werte setzen
  $form->setDefaults($link_array);

  return($form->toHtml().  "<span id='response'></span>".
                           "<hr />\n".
                           "<h1>Erläuterung</h1>\n".
                           "<ul>\n".
                           "  <li>Quelle: immer das Interface welches am nächsten zum Monitoring-Server Ares/Reportserver am Standort FBG steht</li>\n".
                           "  <li>Ziel: das Interface, welches einen Hop weiter weg ist</li>\n".
                           "  <li>Baue keine zwei Links zwischen den selben Interfaces auf! Diese Link stellen physikalische Verbindungen dar, das hei&szlig;t zum Beispiel virtuelle Maschinen haben nur Links zu ihrem Host, nicht zum Switch an dem der Host hängt.</li>\n".
                           "  <li>Gehe bei Geräten im LAN immer der Hierarchie von Quelle zu Ziel nach und erstelle keine Kreuzverlinkungen!</li>\n".
                           "</ul>\n");
}

function editNodeService($id, $id_node) {
	$html = '';
	
	$service = DB_DataObject::factory('service');
	$service->orderBy('description ASC');
    $service->find();
	while ($service->fetch()) {
		$serviceMapping[$service->id_service] = $service->description;
    }
	
	if ( $id > 0 ) {
		$nodeService = DB_DataObject::factory('node_has_service');
        $nodeService->get($id);
		$html .= 'todo: show form to edit service parameters for NodeService '.$nodeService->id;
		
		// Form erstellen
		$form = new HTML_QuickForm('editNodeService', 'post', '', '',
                             'onsubmit="remoteedit.onupdate(\'nodeService\', HTML_AJAX.formEncode(\'editNodeService\',true)); return false;"');
		// Elemente einfügen
		$form->addElement('header', null, 'Service-Check Editor');
		$form->addElement('hidden', 'id', $id);
		$form->addElement('static', 'id_service', 'Service:', $serviceMapping[$nodeService->id_service]);
//		$form->addElement('static', 'parameter1', 'Parameter #1');
//		$form->addElement('static', 'parameter2', 'Parameter #2');
		// Buttons einfügen
		$buttons[] = &HTML_QuickForm::createElement('reset', null, 'Zurücksetzen');
		$buttons[] = &HTML_QuickForm::createElement('submit', null, 'Speichern');
		$buttons[] = &HTML_QuickForm::createElement('button', null, 'Löschen', array('onclick' => 'remoteedit.ondelete(\'nodeService\',' .$id.');'));
		$form->addGroup($buttons, null, null, ' ');
		return($form->toHtml(). "<span id='response'></span>".
		                        "<hr />\n".
		                        "<h1>Erläuterung</h1>\n".
		                        "<ul>\n".
		                        "  <li>Service: Der Service der bei diesem Gerät/Server im Nagios überprüft wird.</li>\n".
//								"  <li>Parameter #1: Einstellung ...</li>\n".
//								"  <li>Parameter #2: Einstellung ...</li>\n".
		                        "</ul>");
		
		return $html;
	} else {
		// new entry into node_has_service
		// show selection of services
		// Form erstellen
		$form = new HTML_QuickForm('editNodeService', 'post', '', '',
                             'onsubmit="remoteedit.onupdate(\'nodeService\', HTML_AJAX.formEncode(\'editNodeService\',true)); return false;"');
		// Elemente einfügen
		$form->addElement('header', null, 'Service-Check im Nagios hinzufügen');
		$form->addElement('hidden', 'id_node', $id_node);
		$form->addElement('select', 'id_service', 'Service:', $serviceMapping);
		// Buttons einfügen
		$buttons[] = &HTML_QuickForm::createElement('reset', null, 'Zurücksetzen');
		$buttons[] = &HTML_QuickForm::createElement('submit', null, 'Speichern');
		$form->addGroup($buttons, null, null, ' ');
		return($form->toHtml(). "<span id='response'></span>".
		                        "<hr />\n".
		                        "<h1>Erläuterung</h1>\n".
		                        "<ul>\n".
		                        "  <li>Service: Wähle einen Service der bei diesem Gerät/Server im Nagios überprüft werden soll.</li>\n".
		                        "</ul>");
	}
}
?>
