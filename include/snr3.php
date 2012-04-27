<?php
/* SNR-Statistik für MikroTik RouterOS */
require_once(dirname(__FILE__) . "/db.php");
require_once(dirname(__FILE__) . "/routerosapi.class.php");

function cmp_snr($a, $b)
{
  if ($a["signal-to-noise"] == $b["signal-to-noise"]) return 0;

  return ($a["signal-to-noise"] > $b["signal-to-noise"]) ? -1 : 1;
}

function getSNRTable($ip, $username, $password)
{
  $replace_ssid = array(
				"www.example.org/", "www.fbn-ftl.de/",
				"www.example.org_", "www.fbn-ftl.de_",
				"www.example.org "
			);

  $API = new routeros_api();
  $result = array();

if ($API->connect($ip, $username, $password)) {
 
// Namen des RBs herausbekommen 
  $ARRAY = $API->query("/system/identity/print");

  $temp_device["name"] = $ARRAY[0]["name"];

  unset($ARRAY);

// Informationen zum Board holen
  $ARRAY = $API->query("/system/routerboard/print");

  $temp_device["model"] = "RouterBoard ".$ARRAY[0]["model"];
  $temp_device["serial"] = $ARRAY[0]["serial-number"];
  $temp_device["bootloader"] = (isset($ARRAY[0]["current-firmware"])?$ARRAY[0]["current-firmware"]:'<span title="bitte Firmware upgraden" style="border-bottom: 1px dotted black;">unbekannt?</span>');

  unset($ARRAY);

// Version von RouterOS holen
  $ARRAY = $API->query("/system/package/print");

  $temp_device["firmware"] = '<span title="bitte Firmware upgraden" style="border-bottom: 1px dotted black;">unbekannt?</span>';
  foreach($ARRAY as $package)
  {
    if (stristr($package["name"], 'routeros'))
      $temp_device["firmware"] = $package["version"];
  }

  unset($ARRAY);

// Details des Boards holen
  $ARRAY = $API->query("/system/routerboard/settings/print");

  $temp_device["freq"] = $ARRAY[0]["cpu-frequency"];

  unset($ARRAY);

// Wireless Interfaces abgrasen
  $ARRAY = $API->query("/interface/wireless/print");

  if (is_array($ARRAY))
  {
    foreach ($ARRAY as $key => $attribute)
    {
      $temp_wif[$attribute["name"]] = str_replace($replace_ssid, "", $attribute["ssid"]);
      if(!isset($attribute["master-interface"]) OR !empty($attribute["master-interface"]))
      {
        $temp_device["card"][] = array(	"name" => $attribute["name"],
					"interface-type" => (isset($attribute["interface-type"])?$attribute["interface-type"]:'<span title="bitte Firmware upgraden" style="border-bottom: 1px dotted black;">unbekannt?</span>'),
					"channel" => (isset($attribute["frequency"])?$attribute["frequency"].' MHz':'siehe '.$attribute["master-interface"])
					);
      }
    }
  }

  unset($ARRAY);

// Stationstabelle durchgehen
  $ARRAY = $API->query("/interface/wireless/registration-table/print");

  if (is_array($ARRAY))
  {
    $temp_client = $temp_mac = $temp_wds = array();
    foreach ($ARRAY as $key => $attribute)
    {
	$attribute["mac-address"] = strtolower(str_replace(":","",$attribute["mac-address"]));
	$attribute["mac-address"] = substr($attribute["mac-address"],0,6)."-".substr($attribute["mac-address"],6,6);

	$attribute["tx-rate"] = str_replace(array("-SP", "Mbps"), array("", " Mbps"), $attribute["tx-rate"]);
        $attribute["rx-rate"] = str_replace(array("-SP", "Mbps"), array("", " Mbps"), $attribute["rx-rate"]);
	if ( strpos($attribute["tx-rate"], "*") )
        {
	        $attribute["tx-rate"] = str_replace(" Mbps", "", $attribute["tx-rate"]);
                $tmp = explode("*", $attribute["tx-rate"]);
                $attribute["tx-rate"] = ($tmp[0] * $tmp[1])." Mbps";
        }
	if ( strpos($attribute["rx-rate"], "*") )
        {
	        $attribute["rx-rate"] = str_replace(" Mbps", "", $attribute["rx-rate"]);
                $tmp = explode("*", $attribute["rx-rate"]);
                $attribute["rx-rate"] = ($tmp[0] * $tmp[1])." Mbps";
        }
	if ($attribute["ap"] == "false" && $attribute["wds"] == "false")
        {
		$attribute["ssid"] = $temp_wif[$attribute["interface"]];
		$temp_client[] = $attribute;
		$temp_mac[] = $attribute["mac-address"];
        } else if ($attribute["wds"] == "true") {
		$attribute["ssid"] = $temp_wif[$attribute["interface"]];
		$temp_wds[] = $attribute;
	}
    }
  }

   $API->disconnect();
}

  if (is_array($temp_device))
  {
    $result["device"] = $temp_device;
  }
  if (is_array($temp_client))
  {
    uasort($temp_client, "cmp_snr");
    $result["client"] = $temp_client;
  }
  if (is_array($temp_wds))
  {
    uasort($temp_wds, "cmp_snr");
    $result["wds"] = $temp_wds;
  }
  if (is_array($temp_mac))
  {
     $result["mac"] = $temp_mac;
  }

  return $result;
}


function printSNRTable($id_node, $admin = FALSE)
{
  $html = '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'."\n";

  $interface = DB_DataObject::factory('interface');
  $interface->id_node = $id_node;
  $interface->whereAdd('id_mode in (1,3)');
  $interface->whereAdd('ip != ""');
  $interface->whereAdd('ip != "0.0.0.0"');

  if ($interface->find(TRUE)) {
    $ip = $interface->ip;
  } else {
    $node = DB_DataObject::factory('node');
    $node->get($interface->id_node);
    $location = DB_DataObject::factory('location');
    $location->get($node->id_location);
    $html .= '<p>Eine SNR-Anzeige erhalten Sie nur f&uuml;r APs.</p>';
    $html .= '<p>Zur <a href="/cgi-bin/smokeping.cgi?target=id_section_'.$location->id_section.'.id_location_'.$location->id_location.'.id_node_'.$node->id_node.'">Pingstatistik des Ger&auml;tes '.$node->description.'</a></p>';
    return $html;
  }

  $node = $interface->getLink('id_node', 'node');

  $table = array();
  list($username, $password) = split(':', $node->config_password);
  $table = getSNRTable($interface->ip, $username, $password);


  if (is_array($table["mac"]))
  {
    $mac = DB_DataObject::factory('Macs');
    $mac->whereAdd("Mac IN ('".implode("', '", $table["mac"])."')");
    $mac->find();

    $macs = array();
    // MAC-Liste mit Mitgliedsdaten vorbelegen, falls mit mac->fetch() kein Eintrag in Supportportal-DB auffindbar
    foreach($table["mac"] as $m) { $macs[$m]['name'] = 'Systemkonto B&uuml;rgernetz Dresden'; $macs[$m]['bnid'] = 0; }

    while ($mac->fetch())
    {
      $mitglied = $mac->getLink('BNID', 'Mitglieder', 'BNID');
      $macs[$mac->Mac]["name"] = $mitglied->Vorname.' '.$mitglied->Nachname;
      $macs[$mac->Mac]["bnid"] = $mac->BNID;
    }
    unset($table["mac"]);
  }

  $html .= '<h3>'.$table["device"]["name"]."</h3>\n";
  $html .= 'Typ: '.$table["device"]["model"].', CPU-Frequenz: '.$table["device"]["freq"].', Firmware: '.$table["device"]["firmware"].', Bootloader: '.$table["device"]["bootloader"]."\n\n";

  if (is_array($table["device"]["card"]) && $admin) {
    $html .= '<table border="1" class="sortable"><tr><th>Name</th><th>Typ</th><th>Kanal</th></tr>';
    foreach($table["device"]["card"] as $attribute)
    {
      $html .= '<tr><td>'.$attribute["name"].'</td><td>'.$attribute["interface-type"].'</td><td>'.$attribute["channel"].'</td></tr>';
    }
  }

  $html .= "</table>\n";

  unset($table["device"]);

  $clients = $clientsSec = 0;

  foreach($table as $key => $target)
  {
    $html .= "<h5>".str_replace(array('client', 'wds'), array('AP', 'P2P'), $key)."</h5>\n";
    $html .= "
	<table border=\"1\" class=\"sortable\">
	<tr>
	<th>SSID</th>
	<th>MAC-Adresse</th>\n".
        (($key == 'client') ? "<th>Mitgliedsname</th>\n" : '')."
	<th>SNR</th>
	<th>TX-Rate</th>
	<th>RX-Rate</th>
	<th>Auth/Enc</th>
	</tr>";

    foreach($target as $id => $attribute)
    {
      $wpainfo = '';
      if ( !empty($attribute["authentication-type"]) || !empty($attribute["encryption"]) || stristr($attribute["ssid"], 'EAP') )
      {
          if ($key == 'client') { $clientsSec++; }
          $wpainfo = '<a href="https://www.example.org/EAP" title="Anleitung: Wie richte ich eine WPA2-verschl&uuml;sselte Verbindung mit EAP ein?"><img src="../images/ssl-symbol.png" alt="WPA-verschlüsselt" width="13" height="15" align="absmiddle" /></a>';
      }
      $html .= "
	<tr>
	<td>".$wpainfo.$attribute["ssid"]."</td>
	<td>".($admin?$attribute["mac-address"]:'xxxxxx-xx'.substr($attribute["mac-address"],9,4))."</td>\n".
	(($key=='client') ? "<td>".($macs[$attribute["mac-address"]]["name"]?$macs[$attribute["mac-address"]]["name"]:$attribute["mac-address"])."</td>" : '')."
	<td>".$attribute["signal-to-noise"]."</td>
	<td>".$attribute["tx-rate"]."</td>
	<td>".$attribute["rx-rate"]."</td>
	<td>".(!empty($attribute["authentication-type"])?$attribute["authentication-type"]:'-').'/'.(!empty($attribute["encryption"])?$attribute["encryption"]:'-')."</td>
	</tr>";
    if ($key=='client') { $clients++; }
    }
    $html .= "</table>\n";
  }

  if ( $clients > 0) {
    $html .= '<p>verbundene Clients am AP: '.$clients.', davon '.$clientsSec.' verschl&uuml;sselt ('.sprintf('%.2f',$clientsSec/$clients*100).'%)</p>'.
             '<p>Wenn die Sendegeschwindigkeit des Clients niedriger ist, als die Sendegeschwindigkeit des AP oder ihr SNR unter 10 liegt, '.
             'deutet dies auf einen schlechten Empfang hin.  Sie sollten ihre Installation/Antenne &uuml;berpr&uuml;fen.</p>';
  }

  $location = DB_DataObject::factory('location');
  $location->get($node->id_location);
  $html .= '<p>Zur <a href="/cgi-bin/smokeping.cgi?target=id_section_'.$location->id_section.'.id_location_'.$location->id_location.'.id_node_'.$node->id_node.'">Pingstatistik des Ger&auml;tes '.$node->description.'</a></p>';

  return utf8_encode($html);

}
?>
