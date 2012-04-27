<!DOCTYPE html "-//W3C//DTD XHTML 1.0 Strict//EN" 
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Mitgliederkarte</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAOxdgGFlFzzfFqQmRHP5buBSkUkZlfUOJbe2nOPa-VC5--SVDOhR53yMC29HWcv9Jcko3X9UnKfFRcg&sensor=false&amp;indexing=false"
            type="text/javascript"></script>

  <script type='text/javascript'>
    //<![CDATA[

  tinyIcon = new GIcon();
  tinyIcon.image = "http://labs.google.com/ridefinder/images/mm_20_red.png";
  tinyIcon.shadow = "http://labs.google.com/ridefinder/images/mm_20_shadow.png";
  tinyIcon.iconSize = new GSize(12, 20);
  tinyIcon.shadowSize = new GSize(22, 20);
  tinyIcon.iconAnchor = new GPoint(6, 20);
  tinyIcon.infoWindowAnchor = new GPoint(5, 1);

  markerOptions = { icon:tinyIcon };


    function initialize() {
      if (GBrowserIsCompatible()) {
        map = new GMap2(document.getElementById("map"));
        map.enableContinuousZoom();
        map.enableScrollWheelZoom();
        map.addControl(new GLargeMapControl());
        map.addControl(new GMapTypeControl());
        map.setCenter(new GLatLng(51.0412410,13.6977528), 10);

      }
  }

  function members() {
<?php
readfile('geodata.txt');
?>
  } 
    //]]>
  </script>
</head>
  <body onload="initialize()" onunload="GUnload()">
    <div class="normalbox" id="map" style="width: 800px; height: 600px"></div>
    <div  class="normalbox">
      <input type="button" value="Mitglieder anzeigen" onclick="members();" />
    </div>
  </body>
</html>
