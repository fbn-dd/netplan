<?php
header('Content-Type: text/plain');
//header('Content-Disposition: attachment; filename="lanconf.ini"');

require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/lancom.php");

$secret = "Knuddelbaerchen";

$interface = DB_DataObject::factory('interface');
$interface->query('SELECT s.description as sektion, l.description as standort, n.description as geraet, n.config_password as password, i.ip as ip, t.description as geraetetyp FROM interface as i INNER JOIN node as n ON i.id_node=n.id_node INNER JOIN location as l ON n.id_location=l.id_location INNER JOIN section as s ON l.id_section=s.id_section INNER JOIN type as t ON t.id_type=n.id_type WHERE i.id_mode != 4 AND n.id_type IN ( 2, 5, 9) GROUP BY i.id_node ORDER BY s.description, l.description, n.description');

/* @TODO: evtl. noch abfragen ob Zubringer oder nicht */

while ($interface->fetch())
{
  $allLancoms[$interface->sektion][] =	array(  'standort'		=> $interface->standort,
						                    'geraet'		=> $interface->geraet,
						                    'ip'			=> $interface->ip,
						                    'geraetetyp'	=> $interface->geraetetyp,
						                    'password'		=> encodeLPW($interface->password, $secret));
}


$count=0;
$lastSection='';
foreach($allLancoms as $sektion => $sektionLancoms) {
  foreach($sektionLancoms as $ap) {

  if ($lastSection != $sektion) {
    $count=0; 
    echo "[DeviceList/".str_replace('/','-',$sektion)."]\n";
    echo 'Count='.count($sektionLancoms)."\n";
  }
  $lastSection = $sektion;

  $count++;

  echo $count.'_Name='.$ap['geraet']."\n";
  echo $count.'_Description='.$sektion."\n";
  echo $count.'_Parameters=1;'.($ap['password']?$ap['password']:'').';IP;'.$ap['ip'].';10;;4;'."\n";
  }
}

/*
[DeviceList]
Count=0
[DeviceList/Ordnername]
Count=0
[DeviceList/Ordnername\Unterordner]
Count=1
1_Name=AP-Name
1_Description=AP-Beschreibung
1_Parameters=1;;IP;192.168.4.72;10;;4;

Statuspruefung;Passwort;IP:TFTPPORT:HTTPPORT:HTTPSPORT;TIMEOUT;?;Optionen(wie Kommunikationsart);

Statuspruefung:
1: Status des Geraets beim Start pruefen und Firmware beim Start aktualisieren
2: deaktivert
3: nur Status des Geraets beim Start pruefen

Optionen:
1: nur TFTP
2: nur HTTP
3: TFTP und HTTP
4: nur HTTPS
5: TFTP und HTTPS
6: HTTP und HTTPS
7: TFTP/HTTP/HTTPS
256: Verfuegbarkeit per TFTP pruefen
1_SIParameters=LANCOM L-54ag Wireless;7.22.0016;058840600375;00a05711e655;D;826644;16400;201326594;03.09.2007

Erklaerung SIParameters: Geraetetyp;LCOSversion;serial;mac;<Anfangsbuchstabe Land (D/E)>;?;?;LCOSversionDatum
wird alles abgefragt, muss nicht gegeben sein
*/
?>
