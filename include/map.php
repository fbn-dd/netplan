<?php

require_once(dirname(__FILE__) . "/time_start.php");
require_once(dirname(__FILE__) . "/layout.php");
require_once(dirname(__FILE__) . "/constants.inc.php");
require_once(dirname(__FILE__) . "/db.php");
require_once(dirname(__FILE__) . "/geocode.php");

function googleMapHeader($title) {
    gserverl $maponly;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo $title; ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
  <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
  <script src="http://maps.google.com/maps?file=api&amp;v=3&amp;key=<?php echo KEY; ?>&amp;sensor=false&amp;indexing=false" type="text/javascript"></script>
  <script type="text/javascript" src="../ajax/server.php?client=all&stub=remotemap"></script>
  <script type='text/javascript'>
    //<![CDATA[

    function GetTileUrl_TaH(a, z) {
        return "http://c.tah.openstreetmap.org/Tiles/tile/" +
                    z + "/" + a.x + "/" + a.y + ".png";
    }

    function getWindowWidth() {
      if (window.self && self.innerWidth) { return self.innerWidth; }
      if (document.documentElement && document.documentElement.clientWidth) { return document.documentElement.clientWidth; }
      return 0;
    }

    function getWindowHeight() {
      if (window.self && self.innerHeight) { return self.innerHeight; }
      if (document.documentElement && document.documentElement.clientHeight) { return document.documentElement.clientHeight; }
      return 0;
    }

    function resizeApp() {
      var offsetTop = <?php echo ($maponly?-70:0)?>;
      var mapElem = document.getElementById("map");
      for (var elem = mapElem; elem != null; elem = elem.offsetParent) {
        offsetTop += elem.offsetTop;
      }
      var width  = getWindowWidth() - 18;
      var height = getWindowHeight() - offsetTop - 50;

      if (height >= 0) {
        mapElem.style.height = height + "px";
      }
      if (width >= 0) {
        mapElem.style.width = width + "px";
      }
    }
    //]]>
  </script>
<?php }

function googleMapViewJs() { ?>
  <script type='text/javascript'>
    //<![CDATA[

    var map      = null;
    var aplink   = new Array(); // Array von Links zwischen Adresse und APs
    var apmarker = null;  // Marker für gesuchte Adresse
    var apradius = false; // Status Radius angezeigt
    var aplinks  = false; // Status Links zwischen AP angezeigt

    var callback = {
      get_aps_near_point: function(result) {
        hideAPLinks();
        eval(result);
        apmarker = createAP(point, html);
        addAPLinks();
        map.panTo(point);
      }
    }

    var remotemap = new remotemap(callback);

<?php
    $section = DB_DataObject::factory('section');
    $section->find();
    while ($section->fetch()) {
      $sectionMapping[$section->id_section] = $section->description;
    }

    $location = DB_DataObject::factory('location');
    $location->whereAdd("longitude LIKE '13.%' AND (latitude LIKE '51.%' OR latitude LIKE '50.%')");
    $location->find();

    $locationArray = "var locationArray = new Array();\n";
    $locations = $locationsRadius = '';
    while ($location->fetch()) {
        $html = '<b>AP-'.$location->description.'</b><br />'.
                'VB: '.$sectionMapping[$location->id_section].'<br />'.
                ($location->street==''?"keine Strasse":$location->street).'<br />'.
                ($location->postcode==''?"00000":$location->postcode).' '.
                ($location->city==''?$sectionMapping[$location->id_section]:$location->city);
        $locations     .= '    var location_'.$location->id_location.' = new GLatLng('.$location->latitude.', '.$location->longitude.');'."\n";
        $locationArray .= 'locationArray['.$location->id_location.'] = createAP(location_'.$location->id_location.', "'.$html.'");'."\n";
        $locationsRadius .= 'drawCircle('.$location->latitude.', '.$location->longitude.', 0.5, "#000000", 0, 1, "#65DC55",.5);'."\n";
    }

    echo $locations ."\n". 
         $locationArray ."\n". 
         getLinksAsPolylines()."\n";

?>
    function radius() {        
        if (apradius == true)
        {
            apradius = false;
            map.clearOverlays();
            addLocations();
            if (aplinks == true) { addLinks(); }
        }
        else
        {
            if (true == confirm("Sollen die Accesspoint-Symbole versteckt und nur die Radien angezeigt werden?\n\nAchtung:\nDie Reichweite der Accesspoints wird überall gleich dargestellt.\nSie ist aber von der jeweiligen Umgebung abhängig."))
            { hideLocations(); }
            apradius = true;
            <?php echo $locationsRadius; ?>
        }
    }

    // Accesspoints
    function addLocations() { for (var id in locationArray) { map.addOverlay(locationArray[id]); } }
    function hideLocations() { for (var id in locationArray) { map.removeOverlay(locationArray[id]); } }

    // Links zwischen APs
    function togglelinks()
    {
        if (aplinks == false) { addLinks(); } else { hideLinks(); }
    }
    function addLinks()
    {
        aplinks = true;
        for (var id in link) { map.addOverlay(link[id]); }
    }
    function hideLinks()
    {
        aplinks = false;
        for (var id in link) { map.removeOverlay(link[id]); }
    }

    // Links von Adresse zu APs
    function addAPLinks()
    {
        apbeams = true;
        map.addOverlay(apmarker);
        for (var id in aplink)
        {
            map.addOverlay(aplink[id]);
        }
    }

    function hideAPLinks()
    {
        apbeams = false;
        if (apmarker!=null)
        {
            map.removeOverlay(apmarker);
        }
        if (aplink!=null)
        {
            for (var id in aplink)
            {
                map.removeOverlay(aplink[id]);
            }
        }
    }

    function get_aps_near_address(address) {
      var geocoder = new GClientGeocoder();
      if (address=='') {
        document.getElementById('response').innerHTML = 'Bitte eine Adresse eingeben.';
      } else {
        geocoder.getLatLng(address+'+DE',
          function(point) {
            if (!point) {
              document.getElementById('response').innerHTML = 'Adresse nicht gefunden.';
            } else {
              document.getElementById('response').innerHTML = 'Adresse gefunden.';
              // ggf. alte Adresse ausblenden
              hideAPLinks();
              map.setZoom(15);
              map.panTo(point);
              // @TODO: obsolete, remove ajax-basded source
              //remotemap.get_aps_near_point(point); return true;

              var aps = new Array();
              <?php
              $location = DB_DataObject::factory('location');
              $location->find();
              while ($location->fetch()) {
              echo "              aps[".$location->id_location."] = '".$location->description."';\n";
              }
              ?>
              address += '<p style="height: 100px; overflow:auto;font-family:monospace;">';
              var d = new Array();
              aplink = new Array();
              for (var id in locationArray) { 
                var distance = point.distanceFrom(locationArray[id].getPoint());
                d[id] = {distance: distance.toFixed(0), ap:aps[id]}
                if (distance.toFixed(0) <= 2500)
                {
                  aplink[id] = new GPolyline([point, locationArray[id].getPoint()],'#00FF00', 3);
                }
              }
              d.sort(function(a, b){ return a.distance - b.distance })
              for (var id in d)
              {
                if (d[id].distance <= 2500)
                {
                  address += '<em>AP '+d[id].ap+': '+d[id].distance+'m</em><br />';
                }
                else
                {
                  address += 'AP '+d[id].ap+': '+d[id].distance+'m<br />';
                }
              }
              address += '</p>';
              // Adressmarker setzen und
              apmarker = createAP(point, address);
              // Adressmarker und Links zu APs zeichnen
              addAPLinks();
            }
          } // function(point)
        );
      }
    }

    function createAP(point, html) {
      var marker = new GMarker(point);
      GEvent.addListener(marker, "click", function() { marker.openInfoWindowHtml(html); });
      return marker;
    }

    function drawCircle(lat, lng, radius, strokeColor, strokeWidth, strokeOpacity, fillColor, fillOpacity) {
        var d2r = Math.PI/180; 
        var r2d = 180/Math.PI; 
        var Clat = (radius/3963)*r2d; 
        var Clng = Clat/Math.cos(lat*d2r); 
        var Cpoints = []; 
        for (var i=0; i < 33; i++) { 
            var theta = Math.PI * (i/16); 
            Cy = lat + (Clat * Math.sin(theta)); 
            Cx = lng + (Clng * Math.cos(theta)); 
            var P = new GPoint(Cx,Cy); 
            Cpoints.push(P); 
        }
        var polygon = new GPolygon(Cpoints, strokeColor, strokeWidth, strokeOpacity, fillColor, fillOpacity);
        map.addOverlay(polygon);
    }

  
    function load() {
      resizeApp();
      if (GBrowserIsCompatible()) {
        map = new GMap2(document.getElementById("map"));
        map.enableContinuousZoom();
        map.enableScrollWheelZoom();
        map.addControl(new GLargeMapControl());
        map.addControl(new GMapTypeControl());
        map.addControl(new GScaleControl());
        // StreetView-Support
/*
        var myPano = new GStreetviewPanorama(document.getElementById("pano"));
        GEvent.addListener(myPano, "error", alert("Error: Flash doesn't appear to be supported by your browser"));
        // markiert alle Straßen mit StreetView-Daten blau
        svOverlay = new GStreetviewOverlay();
        map.addOverlay(svOverlay);
        // bei Klick auf Karte wird StreetView-Ort gesetzt
        GEvent.addListener(map,"click", function(overlay,latlng) {
          myPano.setLocationAndPOV(latlng);
        });
*/
        var copyright = new GCopyright(1,
            new GLatLngBounds(new GLatLng(-90,-180), new GLatLng(90,180)), 0,
            '(<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>)');
        var copyrightCollection =
            new GCopyrightCollection('Kartendaten &copy; <?php echo date('Y') ?> <a href="http://www.openstreetmap.org/">OpenStreetMap</a> Contributors');
        copyrightCollection.addCopyright(copyright);
        var tilelayers_tah = new Array();
        tilelayers_tah[0] = new GTileLayer(copyrightCollection, 0, 17);
        tilelayers_tah[0].getTileUrl = GetTileUrl_TaH;
        tilelayers_tah[0].isPng = function () { return true; };
        tilelayers_tah[0].getOpacity = function () { return 1.0; };
        var tah_map = new GMapType(tilelayers_tah,
            new GMercatorProjection(19), "OSM",
            { urlArg: 'tah', linkColor: '#000000' });
        map.addMapType(tah_map);
		
		var href = window.location.search;
		var Ausdruck = /^\?lat\=(.*)\&lng\=(.*)\&zoom\=(\d+)\&type=(.*)$/;
		var RegResult;
		if (RegResult = Ausdruck.exec(window.location.search)) {
			var map_type;
			switch(RegResult[4]) {
				case "r":	map_type = "G_NORMAL_MAP";
							break;
				case "s":	map_type = "G_SATELLITE_MAP";
							break;
				case "h":	map_type = "G_HYBRID_MAP";
							break;
				case "o":	map_type = "OSM";
							break;
				default	:	map_type = "G_NORMAL_MAP";
			}
			map.setCenter(new GLatLng(parseFloat(RegResult[1]), parseFloat(RegResult[2])), parseFloat(RegResult[3]));
			//map.setMapType(map_type);
		} else if(href.match(/\?ap=/)) {
<?php
	if (isset($_GET['ap']))
	{
		$location = DB_DataObject::factory('location');
		$location->description = $_GET['ap'];
		$location->find(TRUE);

		if ($location->latitude != "" && $location->longitude != "")
		{
?>
			map.setCenter(new GLatLng(<?php echo $location->latitude ?>, <?php echo $location->longitude ?>), 16);
<?php
		}
	} else {
?>
			map.setCenter(new GLatLng(<?php echo FOC_LAT ?>, <?php echo FOC_LONG ?>), <?php echo FOC_HIGH ?>);
<?php
	} 
?>			
		} else {
        	map.setCenter(new GLatLng(<?php echo FOC_LAT.', '.FOC_LONG.'), '.FOC_HIGH; ?>);
		}

        // Fokus auf Adressfeld legen
        if(document.getElementById("address")) document.getElementById("address").focus();

	// alle Marker fuer die Standorte setzen
        addLocations();

	// falls eine Adresse angegeben wurde, auf diese zentrieren
        var address = window.location.search;
        if (address.match(/address=/)) {
          get_aps_near_address(unescape(address.substring(9)));
        }
      }
    }

	function create_link() {
		var old_href = window.location.href;
	        var short_href = old_href.replace(/\?.*/, "");

		var map_type;
		switch(map.getCurrentMapType().getName(true)) {
			case "Karte"	: 	map_type = "r";
								break;
			case "Sat"		: 	map_type = "s";
								break;
			case "Hyb"		: 	map_type = "h";
								break;
			case "OSM"		: 	map_type = "o";
								break;
			default			:	map_type = "r";
		}

		window.prompt("Link zur aktuellen Ansicht der AP-Karte:", short_href + "?lat=" + String(map.getCenter().lat()) + "&lng=" + String(map.getCenter().lng()) + "&zoom=" + String(map.getZoom()) + "&type=" + map_type);
	}

    //]]>
  </script>
</head>
<?php  }

function googleMapEditSave() {
  // save edited location
  if (isset($_GET["id_location"]) &&
      isset($_GET["latitude"]) && $_GET["latitude"] > '0' &&
      isset($_GET["longitude"]) && $_GET["longitude"] > '0') {
    $do = DB_DataObject::factory('location');
    $do->get($_GET["id_location"]);
    $do->setFrom($_GET);
    $val = $do->validate();
    if ($val === TRUE) {
      $do->update();
      echo "erfolgreich";
    } else {
      echo "fehlgeschlagen";
    }
    exit(0);
  }
}

function googleMapEditJs() {

  // Standorte ohne Koordinaten finden
  $location = DB_DataObject::factory('location');
  $location->whereAdd('(latitude NOT BETWEEN 49 and 52) OR longitude NOT LIKE "13.%"');
  $location->orderBy('description');
  $location->find();

  $locwoc = "<select id='id_location'>";
  while ($location->fetch()) {
    $locwoc .= "<option value='".$location->id_location."'>".$location->description."</option>";
  }
  $locwoc .= "</select>";
?>

  <script type='text/javascript'>
    //<![CDATA[

    function getHtml(description, point) {
        var html = "\
        <form method='get'>\
          <table border='0'>\
            <tr><td>Name:</td><td>" + description + "</td></tr>\
            <tr><td>Longitude:</td><td><input id='longitude' type='text' value="+point.lng().toString()+" /></td></tr>\
            <tr><td>Latitude:</td><td><input id='latitude' type='text' value="+point.lat().toString()+" /></td></tr>\
          </table>\
          <input type='button' value='Speichern' onclick=\"HTML_AJAX.replace('response',\
            '?id_location='+document.getElementById('id_location').value+\
            '&longitude='+document.getElementById('longitude').value+'&latitude='+document.getElementById('latitude').value);\">\
          <span id='response'></span>\
        </form>";
        return(html);
    }

    function addMarker(map,point,id_location,description) {
      var marker = new GMarker(point, {draggable: true});
      map.addOverlay(marker);

      if (description == 0) {
        description = "<?php echo $locwoc; ?>";
        marker.openInfoWindowHtml(getHtml(description, point));
      } else {
        description += "<input id='id_location' type='hidden' value='" + id_location + "' />";
      }

      GEvent.addListener(marker, "click", function() {
        marker.openInfoWindowHtml(getHtml(description, this.getPoint()));
      });

      GEvent.addListener(marker, "dblclick", function() {
        map.closeInfoWindow();
        map.removeOverlay(marker);
      });

      GEvent.addListener(marker, "dragstart", function() {
        map.closeInfoWindow();
      });

      GEvent.addListener(marker, "dragend", function() {
        marker.openInfoWindowHtml(getHtml(description, this.getPoint()));
      });
    }

    function load() {
      resizeApp();
      if (GBrowserIsCompatible()) {
        var map = new GMap2(document.getElementById("map"));
        map.enableContinuousZoom();
        map.enableScrollWheelZoom();
        map.addControl(new GLargeMapControl());
        map.addControl(new GMapTypeControl());
        map.setCenter(new GLatLng(<?php echo FOC_LAT.', '.FOC_LONG.'), '.FOC_HIGH; ?>);

        GEvent.addListener(map, "click", function(marker,point) {
          if (!marker) {
            addMarker(map,point,0,0);
            map.panTo(point);
          }
        });
<?php

   $location = DB_DataObject::factory('location');
   $location->whereAdd('(latitude LIKE "51.%" OR latitude LIKE "50.%") AND longitude LIKE "13.%"');
   $location->find();
   while ($location->fetch()) {
     echo 'addMarker(map,new GLatLng('.$location->latitude.', '.$location->longitude.'), '.$location->id_location.', "'.$location->description.'");'."\n";
   }

?>
      }
    }

    //]]>
  </script>
</head>
<?php }


function googleMapBody() {
    gserverl $maponly;
?>

<body onresize="resizeApp()" onload="load()" onunload="GUnload()" style="width:100%;height:100%">
  <?php if (!$maponly) { printMenu(); } ?>
  <div id="map" class="normalbox" style="width:100%;height:100%"></div>
<?php if(!$maponly) : ?>
  <div class="normalbox">
<?php include_once(dirname(__FILE__) . "/time_end.php"); ?>
  </div>
<?php endif; ?>
</body>
</html>
<?php }


function googleMapSearchBody() { 
    gserverl $maponly;
?>

<body onresize="resizeApp()" onload="load()" onunload="GUnload()" style="width:100%;height:100%">
<?php if(!$maponly) { printMenu(); } ?>
<?php if(!$maponly) : ?>
  <div class="normalbox">
    <form method='get' onsubmit="get_aps_near_address(document.getElementById('address').value); return false;">
      Adresse: <input id="address" type="text" value="" size="40"/>
      <input type="submit" value="Adresse anzeigen" />
      <input type="button" value="Adresse ausblenden" onclick="hideAPLinks();" />
      <input type="button" value="Funkverbindungen" onclick="togglelinks();" />
      <input type="button" value="AP-Reichweiten" onclick="radius();" disabled="disabled" />
      <input type="button" value="Link zur Karte" onclick="create_link();" />
      <span id='response'></span>
    </form>
  </div>
<?php endif; ?>
<div id="map" class="normalbox" style="width:100%;height:100%"></div>
<div id="pano" style="width: 100%; height: 0"></div>
<?php if(!$maponly) : ?>
  <div class='normalbox'>
<?php include_once(dirname(__FILE__) . "/time_end.php"); ?>
  </div>
<?php endif; ?>
</body>
</html>
<?php }

function connColor($conn) {

    if ( $conn == "802.11a" ) { return "#0000FF" ;
    } else { return "#FF0000" ; }

}

function encodeValue($value) {
  // step 1-3
  if ($value < 0) {
    $value = ceil($value * 100000);
  } else {
    $value = floor($value * 100000);
  }
  $bin = decbin($value);

  // step 3-4
  // x86 architecture
  //$bin = str_repeat('0',32-strlen($bin)).$bin.'0';
  // x64 architecture
  $bin = str_repeat('0',64-strlen($bin)).$bin.'0';

  // step 5
  if ($value < 0) $bin = strtr($bin,"01","10");

  // step 6-7
  // x86 architecture
  //$chunk = array(substr($bin,28,5), substr($bin,23,5),
  //               substr($bin,18,5), substr($bin,13,5),
  //               substr($bin,8,5),  substr($bin,3,5));
  // x64 architecture
  $chunk = array(substr($bin,32+28,5), substr($bin,32+23,5),
                 substr($bin,32+18,5), substr($bin,32+13,5),
                 substr($bin,32+8,5),  substr($bin,32+3,5));

  // prepare step 8
  for ($i=count($chunk)-1; $i>0; $i--) {
    if ($chunk[$i] == '00000')
      unset($chunk[$i]);
    else
      break;
  }

  // step 8-11
  $ret = '';
  for ($i=0; $i<count($chunk)-1; $i++) {
    $ret .= chr(63+bindec('1'.$chunk[$i]));
  }
  $ret .= chr(63+bindec('0'.$chunk[$i++]));

  return $ret;
}

function getEncodedPolyline($id, $coords, $color) {
  if (!isset($lastx) AND !isset($lasty)) { $lastx = $lasty = ''; }
  $points = '';
  while (list($i,list($x,$y)) = each($coords)) {
    $points .= encodeValue(bcsub($x,$lastx,20)).encodeValue(bcsub($y,$lasty,20));
    $lastx = $x;
    $lasty = $y;
  }
  return 'link['.$id.'] = new GPolyline.fromEncoded({'.
         'color: "'.$color.'",'.
         'weight: 3,'.
         'points: \''.str_replace('\\', '\\\\', $points).'\','.
         'levels: "'.str_repeat('B',count($coords)).'",'.
         'zoomFactor: 32,'.
         'numLevels: 4'.
         '});'."\n";
}

function getLinksAsPolylines() {

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

   $location = DB_DataObject::factory('location');
   $location->find();
   while ($location->fetch()) {
     $locationMapping[$location->id_location] = $location->toArray();
   }

   $interface = DB_DataObject::factory('interface');
   $interface->find();
   while ($interface->fetch()) {
     $interfaceMapping[$interface->id_interface] = $interface->toArray();
   }

   $link = DB_DataObject::factory('link');
   $link->find();
   $linkArray = "var link = new Array();\n";
   while ($link->fetch()) {
     if ($nodeMapping[$interfaceMapping[$link->id_src_interface]['id_node']] !=
         $nodeMapping[$interfaceMapping[$link->id_dst_interface]['id_node']]) {
       $src   = $locationMapping[$nodeMapping[$interfaceMapping[$link->id_src_interface]['id_node']]];
       $dst   = $locationMapping[$nodeMapping[$interfaceMapping[$link->id_dst_interface]['id_node']]];
       $color = connColor($channelMapping[$interfaceMapping[$link->id_src_interface]['id_channel']]);
       
       // do not draw links to points at 0,0 (location not set)
       if ( ($src['longitude'] == 0 AND $src['latitude'] == 0) OR ($dst['longitude'] == 0 AND $dst['latitude'] == 0) ) {
         // skip link
         continue;
       }
       $linkArray .= getEncodedPolyline($link->id_link, array(array($src['latitude'],$src['longitude']),
                                     array($dst['latitude'],$dst['longitude'])),
                                     $color);
     }
   } // while

   return $linkArray;
}

function fetchLocationInfos() {

   $output = array();
   $location = DB_DataObject::factory('location');
   $location->orderBy('description');
   $location->whereAdd("longitude LIKE '13.%' AND (latitude LIKE '51.%' OR latitude LIKE '50.%')");
   $location->find();
   while ($location->fetch()) {
     $location->getLinks();
     array_push( $output, $location->toArray() );
   }
   return $output;
}

?>
