<?php

require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/time_start.php");
require_once(dirname(__FILE__) . "/../include/layout.php");

/* URL-Rewriting Einstiegspunkt */
$onLoad = '';
$onLoadError = '';

/**
* $_GET['any'] causes to look for node, location, section (in this order) stops by first hit
*/
if (isset($_GET['ap']) || isset($_GET['any']))
{
    $_GET['ap'] = isset($_GET['any'])?strval($_GET['any']):strval($_GET['ap']);
    $node = DB_DataObject::factory('node');
    $ret = $node->get('description',$_GET['ap']);
    if ($ret == 1)
    {
        // node found, unset $_GET['any'] to not look further
        unset($_GET['any']);
        $onLoad = ' onLoad="get_node('.$node->id_node.')"';
        // dhtmlXTree open path to node
        $treeOpenNode = 'node_'.$node->id_node;
        $treeOpenLocation = 'location_'.$node->id_location;
        $location = DB_DataObject::factory('location');
        $ret2 = $location->get($node->id_location);
        if ($ret2 == 1)
        {
            $treeOpenSection = 'section_'.$location->id_section;
        }
        else
        {
            // node ohne location?
            $onLoadError = 'Der <abbr title="Accesspoint">AP</abbr> mit der Kennung &quot;'.$_GET['ap'].'&quot; ist keinem Standort zugeordnet.';
        }
    }
    else
    {
        // nicht eindeutig
        $onLoadError = 'Es wurde kein <abbr title="Accesspoint">AP</abbr> mit der Kennung &quot;'.$_GET['ap'].'&quot; gefunden.';
    }
}

if (isset($_GET['standort']) || isset($_GET['any']))
{
    $_GET['standort'] = isset($_GET['any'])?strval($_GET['any']):strval($_GET['standort']);
    $location = DB_DataObject::factory('location');
    $ret = $location->get('description',$_GET['standort']);
    if ($ret == 1)
    {
        // location found, unset $_GET['any'] to not look further
        unset($_GET['any']);
        $onLoad = ' onLoad="get_location('.$location->id_location.')"';
        // dhtmlXTree open path to location
        $treeOpenLocation = 'location_'.$location->id_location;
        $treeOpenSection = 'section_'.$location->id_section;
    }
    else
    {
        // nicht eindeutig
        $onLoadError = 'Es wurde kein Standort mit der Kennung &quot;'.$_GET['standort'].'&quot; gefunden.';
    }
}

if (isset($_GET['sektion']) || isset($_GET['any']))
{
    $_GET['sektion'] = isset($_GET['any'])?strval($_GET['any']):strval($_GET['sektion']);
    $section = DB_DataObject::factory('section');
    $ret = $section->get('description',$_GET['sektion']);
    if ($ret == 1)
    {
        // section found, unset $_GET['any'] to not look further
        unset($_GET['any']);
        $onLoad = ' onLoad="get_section('.$section->id_section.')"';
        // dhtmlXTree open path to section
        $treeOpenSection = 'section_'.$section->id_section;
    }
    else
    {
        // nicht eindeutig
        $onLoadError = 'Es wurde keine Sektion mit der Kennung &quot;'.$_GET['sektion'].'&quot; gefunden.';
        // $_GET['any'] still set? then nothing found
        if(isset($_GET['any']))
        {
            $onLoadError = 'Es wurden keine <abbr title="Accesspoints">AP</abbr>, Standorte oder Sektionen mit der Kennung &quot;'.$_GET['any'].'&quot; gefunden.';
        }
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <base href="<?php echo "https://".($_SERVER["REMOTE_ADDR"]=="192.168.0.242"?"www.example.org":$_SERVER["SERVER_NAME"]).dirname($_SERVER["PHP_SELF"])."/"; ?>" />
  <title>Routerstatistik</title>
  <link rel="stylesheet" href="../css/gserverl.css" type="text/css" />
  <link rel="stylesheet" type="text/css" href="../css/dhtmlXTree.css" />
  <script type="text/javascript" src="../js/dhtmlXCommon.js"></script>
  <script type="text/javascript" src="../js/dhtmlXTree.js"></script>
  <script type="text/javascript" src="../js/overlib.js"></script>
  <script type="text/javascript" src="../ajax/server.php?client=all&stub=remotestats"></script>
  <script type="text/javascript">
    //<![CDATA[

    var basehref = document.getElementsByTagName("base")[0].getAttribute("href");

    var callback = {
      get_stats_for_section: function(result) {
        document.getElementById('stats').innerHTML = result;
      },
      get_stats_for_location: function(result) {
        document.getElementById('stats').innerHTML = result;
      },
      get_stats_for_node: function(result) {
        document.getElementById('stats').innerHTML = result;
      },
      get_snr_table: function(result) {
        document.getElementById('snr').innerHTML = result;
      }
    }

    var remotestats = new remotestats(callback);

    function onNodeSelect(nodeId){
      var part = nodeId.split('_');
      switch (part[0]) {
        case 'section':  get_section(part[1]); break;
        case 'location': get_location(part[1]); break;
        case 'node':     get_node(part[1]); break;
      }
      if(nodeId=="netplan") { location.href=basehref; }
    }

    function afterLoad() {
      tree.openItem("netplan");
<?php
      // open section
      if(!empty($treeOpenSection))
      {
        echo "      window.setTimeout(\"tree.openItem('".$treeOpenSection."')\",200);\n";
      }

      // open location
      if(!empty($treeOpenLocation))
      {
        echo "      window.setTimeout(\"tree.openItem('".$treeOpenLocation."')\",1000);\n";
      }
      // or select section
      else if(!empty($treeOpenSection))
      {
        echo "      window.setTimeout(\"tree.selectItem('".$treeOpenSection."','false')\",500);\n";
      }

      // open and select node
      if(!empty($treeOpenNode))
      {
        echo "      window.setTimeout(\"tree.openItem('".$treeOpenNode."')\",3000);\n";
        echo "      window.setTimeout(\"tree.selectItem('".$treeOpenNode."','false')\",3500);\n";
      }
      // or select location
      else if(!empty($treeOpenLocation))
      {
        echo "      window.setTimeout(\"tree.selectItem('".$treeOpenLocation."','false')\",1500);\n";
      }
?>
        if(document.getElementById('suche')) { document.getElementById('suche').focus(); }
    }

    function get_section(id_section) {
      document.getElementById('stats').innerHTML = "Lade Traffic Statistik ...";
      document.getElementById('snr').innerHTML = 'Keine SNR Anzeige f&uuml;r Sektionen.';
      remotestats.get_stats_for_section(id_section);
    }

    function get_location(id_location) {
      document.getElementById('stats').innerHTML = "Lade Traffic Statistik ...";
      document.getElementById('snr').innerHTML = 'Keine SNR Anzeige f&uuml;r Standorte.';
      remotestats.get_stats_for_location(id_location);
    }

    function get_node(id_node) {
      document.getElementById('stats').innerHTML = "Lade Traffic Statistik ...";
      document.getElementById('snr').innerHTML   = "Lade Client SNR Tabelle ...";
      remotestats.get_stats_for_node(id_node);
      remotestats.get_snr_table(id_node);
    }

  //]]>
  </script>
</head>

<body<?php echo $onLoad; ?>>
<?php printMenu(); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="20%" valign="top">
      <div id="treeBox" class="normalbox"></div>
        <script type="text/javascript">
          tree=new dhtmlXTreeObject(document.getElementById('treeBox'),"100%","100%",0);
          tree.setImagePath(basehref+"../treeimgs/");
          tree.enableCheckBoxes(false);
          tree.enableDragAndDrop(false);
          tree.setOnClickHandler(onNodeSelect);
          tree.setXMLAutoLoading(basehref+"../ajax/xmlmenu.php");
          tree.loadXML(basehref+"../ajax/xmlmenu.php", afterLoad);
        </script>
    </td>
    <td valign='top'>
      <div id='stats' class='normalbox'>
<?php if($onLoadError!='') { echo "        <p>Hinweis: ".$onLoadError."</p>\n"; } ?>
        <h4>Im Menu links bitte ein Objekt ausw&auml;hlen</h4>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>">
        AP, Standort oder Verantwortungsbereich:&nbsp;
        <input name="any" type="text" size="5" id="suche" 
               style="color:black;background-color:white;border:1px solid black;"
               title="AP, Standort oder Sektion eintragen und finden klicken"/>&nbsp;
        <input type="submit" value="finden!"
               style="color:black;background-color:white;border:1px solid black;"
               onClick="location.href=basehref+document.getElementsByName('any')[0].value; return false;"/>
        </form>
      </div>
      <div id='snr' class='normalbox'>F&uuml;r eine Client-<abbr title="signal-to-noise ratio =
Signal-Rausch-Verh&auml;ltnis">SNR</abbr>-Tabelle einen <abbr title="Accesspoint">AP</abbr> ausw&auml;hlen.</div>
    </td>
  </tr>
</table>
<div class="normalbox">
<?php require(dirname(__FILE__) . "/../include/time_end.php"); ?>
</div>
</body>
</html>
