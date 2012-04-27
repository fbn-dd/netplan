<?php
/*
 * documentation:
 * KML 2.1 Reference        http://earth.google.com/kml/kml_tags_21.html
 * Google Earth KML 2.1 Tutorial  http://earth.google.com/kml/kml_21tutorial.html
 */

/* debugging [0|E_ALL] */
// error_reporting(E_ALL);

require_once(dirname(__FILE__) . "/constants.inc.php");
require_once(dirname(__FILE__) . "/db.php");

// enable or disable intranet links in AP info
// default: intranet/fbn-members: show internal links
$noinfo = 0;

if ( isset($_REQUEST["noinfo"]) )
{
  $noinfo = 1;
}

// URL to our Wiki
$wiki = 'https://www.example.org/wiki';
// URL to our Image Gallery
$imgg = 'https://www.example.org/gallery2/main.php?g2_itemId=';

function googleEarthHeader() {
  gserverl $wiki;
  $output = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://earth.google.com/kml/2.1">
  <Document>
    <name>Bürgernetz Dresden e. V.</name>
    <open>1</open>
    <description><![CDATA[<a href="'.$wiki.'/Kategorie:Standorte">Standorte im Wiki</a>]]></description>
    <TimeStamp>
      <when>'.date('Y-m-d', time()).'</when>
    </TimeStamp>
';
  return $output;
}

function googleEarthStyles() {

  // https://www.example.org/netplan/images/ap.png
  // ueberlegen wo platzieren damit auch oeffentlich erreichbar

  $output = '
    <Style id="link_LAN">
      <LineStyle>
        <color>FFFF0000</color>
        <width>4</width>
      </LineStyle>
    </Style>
    <Style id="link_802.11a">
      <LineStyle>
        <color>FF0000FF</color>
        <width>8</width>
      </LineStyle>
    </Style>
    <Style id="link_802.11b">
      <LineStyle>
        <color>FF0000FF</color>
        <width>8</width>
      </LineStyle>
    </Style>
    <Style id="link_802.11g">
      <LineStyle>
        <color>FF0000FF</color>
        <width>8</width>
      </LineStyle>
    </Style>
    <Style id="link_TC">
      <LineStyle>
        <color>FF0000FF</color>
        <width>8</width>
      </LineStyle>
    </Style>
	<Style id="AP">
		<IconStyle>
			<Icon>
				<href>http://maps.google.com/mapfiles/kml/shapes/target.png</href>
			</Icon>
		</IconStyle>
	</Style>
	<Style id="VZ">
		<IconStyle>
			<Icon>
				<href>http://maps.google.com/mapfiles/kml/pal3/icon56.png</href>
			</Icon>
		</IconStyle>
	</Style>
';
  return $output;
}

function googleEarthFooter() {
  $output = '
  </Document>
</kml>';
  return $output;
}

function googleEarthFolder($description, $info = '', $lookat = '') {
  gserverl $wiki, $noinfo;
  $output = '     <Folder>
        <name>'.$description.'</name>
        <description><![CDATA[';
  if ($noinfo!=1)
  {
      $output .= '<a href="'.$wiki.'/Kategorie:'.$description.'">Kategorie:'.$description.' im Wiki</a>';
    if (!empty($info)) {
    $output .= '
        <br />'.$info;
    }
  }

  $output .= ']]></description>';

  // viewpoints for folders - move and zoom to an area overview
  $sectionMapping = array(
    "Coswig" => array (
      'latitude'   => 51.12766462862287,
      'longtitude' => 13.58108137828723,
      'range'      => 4500,
      ),
    "Dresden" => array (
      'latitude'   => 51.05228638459002,
      'longtitude' => 13.76445515968465,
      'range'      => 16000,
      ),
    "Freital" => array (
      'latitude'   => 50.9901169985125,
      'longtitude' => 13.63881467208056,
      'range'      => 7000,
      ),
    "Radebeul" => array (
      'latitude'   => 51.11341338631245,
      'longtitude' => 13.62412168662772,
      'range'      => 10000,
      ),
    "LET" => array (
      'latitude'   => 51.12766462862287,
      'longtitude' => 13.58108137828723,
      'range'      => 4500,
      ),
    "Schoenfeld" => array (
      'latitude'   => 51.029944,
      'longtitude' => 13.893005,
      'range'      => 4500,
      ),
    );

  $output .= '
        <LookAt>
          <longitude>'.$sectionMapping[$description]['longtitude'].'</longitude>
          <latitude>'.$sectionMapping[$description]['latitude'].'</latitude>
          <altitude>0</altitude>
          <range>'.$sectionMapping[$description]['range'].'</range>
          <tilt>9.733975746110964e-012</tilt>
          <heading>-0.08753148561470861</heading>
        </LookAt>
';
  return $output;
}

function googleEarthPlacemark($location, $info = '', $style = 'AP') {
  gserverl $links;

  $output = '     <Placemark id="'.$style.'_'.$location->id_location.'">
        <name>'.$location->description.'</name>
        <description><![CDATA['.$info.']]></description>
        <address>'.$location->street.', '.$location->postcode.' '.$location->city.'</address>
        <LookAt>
          <longitude>'.$location->longitude.'</longitude>
          <latitude>'.$location->latitude.'</latitude>
          <altitude>0</altitude>
          <range>125.9881866232397</range>
          <tilt>0</tilt>
          <heading>0.0002908545724012025</heading>
        </LookAt>
        <styleUrl>#'.$style.'</styleUrl>
        <Point>
          <coordinates>'.$location->longitude.','.$location->latitude.',0</coordinates>
        </Point>
      </Placemark>
';
  return $output;
}

function fetchPolyInfos() {
  $output = array();
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

   $node = DB_DataObject::factory('node');
   $node->find();
   while ($node->fetch()) {
     $nodeMapping[$node->id_node] = $node->id_location;
   }

   $interface = DB_DataObject::factory('interface');
   $interface->find();
   while ($interface->fetch()) {
     $interfaceMapping[$interface->id_interface] = $interface->toArray();
   }

   $link = DB_DataObject::factory('link');
   $link->find();
   while ($link->fetch()) {
     if ($nodeMapping[$interfaceMapping[$link->id_src_interface]['id_node']] !=
         $nodeMapping[$interfaceMapping[$link->id_dst_interface]['id_node']]) {
       $output[$link->id_link]['medium'] = $channelMapping[$interfaceMapping[$link->id_src_interface]['id_channel']];
       $output[$link->id_link]['id_src_location'] = $nodeMapping[$interfaceMapping[$link->id_src_interface]['id_node']];
       $output[$link->id_link]['id_dst_location'] = $nodeMapping[$interfaceMapping[$link->id_dst_interface]['id_node']];
     }
   }
   return $output;
}

function googleEarthKml() {
  gserverl $wiki;
  gserverl $imgg;
  gserverl $noinfo;

  $output = '';

  $output .= googleEarthHeader();
  $output .= googleEarthStyles();

  // Vereinszentren
  // generiere genau 1 Objekt
  $location = DB_DataObject::factory('location');
  // hole FBG (id_location=2)
  $location->get(2);
  // Dresden
  $location->id_location = 'DD';
  $location->description = 'Vereinszentrum Dresden';
  $location->longitude = 13.72507894620661;
  $location->latitude = 51.05066837222173;
  $location->street = 'Freiberger Straße 8';
  $location->postcode = '01067';
  $location->city = 'Dresden';
  $output .= googleEarthPlacemark($location, '', 'VZ');
  // Radebeul
  $location->id_location = 'Rdbl';
  $location->description = 'Vereinszentrum Radebeul';
  $location->longitude = 13.62568331681061;
  $location->latitude = 51.10542897192698;
  $location->street = 'Kötitzer Straße 6';
  $location->postcode = '01445';
  $location->city = 'Radebeul';
  $output .= googleEarthPlacemark($location, '', 'VZ');
  // Freital (VZ)
  $location->id_location = 'Ftl_vz';
  $location->description = 'Vereinszentrum Freital';
  $location->longitude = 13.643149;
  $location->latitude = 50.992721;
  $location->street = 'Dresdner Straße 248';
  $location->postcode = '01705';
  $location->city = 'Freital';
  $output .= googleEarthPlacemark($location, '', 'VZ');
  // Freital (DRK)
  $location->id_location = 'Ftl_drk';
  $location->description = 'Vereinsräume Freital im DRK';
  $location->longitude = 13.6513486364462;
  $location->latitude = 51.00092380951836;
  $location->street = 'Dresdner Straße 207';
  $location->postcode = '01705';
  $location->city = 'Freital';
  // Schönfeld
  $location->id_location = 'DD_vzsch';
  $location->description = 'Vereinszentrum Schönfeld';
  $location->longitude = 13.895985782146454;
  $location->latitude = 51.03298443328391;
  $location->street = 'Borsbergstraße 1';
  $location->postcode = '';
  $location->city = 'Dresden';
  $output .= googleEarthPlacemark($location, '', 'VZ');
  unset($location);

  // get all links between APs
  $links = fetchPolyInfos();

  // fetch sections
  $section = DB_DataObject::factory('section');
  $section->orderBy('description');
  $section->find();

  while ($section->fetch()) {
    // $section->description
    $output .= googleEarthFolder($section->description);

    $location = DB_DataObject::factory('location');
    $location->whereAdd("longitude LIKE '13.%'");
    $location->whereAdd("latitude LIKE '51.%' OR latitude LIKE '50.%'");
    $location->whereAdd("id_section = '".$section->id_section."'");
    $location->orderBy('description');
    $location->find();

    while ($location->fetch()) {
      $info = '<ul>
<li><a href="'.$wiki.'/'.$location->description.'">'.$location->description.' im Wiki</a></li>
</ul>';
//<li><a href="'.$imgg.$location->description.'">'.$location->description.' in der Bildergalerie</a></li>

      if ($noinfo==1) { $info = ''; }
      $output .= googleEarthPlacemark($location, $info);
    }
    $output .= '
      </Folder>
';
  }

  $output .= googleEarthFooter();

  $output = mb_convert_encoding($output,"UTF-8","auto");

  if (isset($_REQUEST["kml"]) && intval($_REQUEST["kml"]) == "1")
  {
    header('Content-Type: application/vnd.google-earth.kml+xml');
    header('Content-Disposition: attachment; filename="FBN-APs_'.date('Y-m-d-H-i', time()).'.kml"');
  }
  else
  {
    header('Content-Type: application/vnd.google-earth.kmz');
    header('Content-Disposition: attachment; filename="FBN-APs_'.date('Y-m-d-H-i', time()).'.kmz"');
    header("Content-Encoding: gzip");
    $output = gzencode($output, 9);
  }

  echo $output;
}
?>
