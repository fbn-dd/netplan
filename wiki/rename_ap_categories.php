<?php
/**
 * Script liest aus dem Netzplan alle APs aus und zerrt dann aus dem Wiki sämtliche existierende AP Seiten.
 * Wenn Wiki Seite vorhanden, dann ersetze [[Kategorie:*]] durch [[Kategorie:$section_AP]]
 * Wenn Seite nicht vorhanden, dann erstelle Seite anhand der netzplan-Daten basierend auf Vorlage:Accesspoints.
 **/

require_once(dirname(__FILE__).'/include/db.php');
require_once(dirname(__FILE__).'/include/wikiupdate.php');

define(WIKIURL,'https://www.example.org/wiki/');
//exit;

$LOG = fopen(dirname(__FILE__).'/tmp/rename.log','a+');

echo "<html>
<head>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<title/>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
<meta foo=bar bar=baz baz=foo>no meta</meta>
</head>
<body>
";

ob_flush();

$data = DB_DataObject::factory('section');
$data->find();
while($data->fetch()) {
	$sectionMapping[$data->id_section] = $data->description;
}

$data = DB_DataObject::factory('location');
$data->find();
while($data->fetch()) {
	$apMapping[$data->id_location] = Array(
	 'ap' => $data->description,
	 'street' => $data->street,
	 'postcode' => $data->postcode,
	 'city' => $data->city,
	 'longitude' => $data->longitude,
	 'latitude' => $data->latitude,
	 'contact' => $data->contact,
	 'section' => preg_replace('![/:]!','+',$sectionMapping[$data->id_section])
	);
}

set_time_limit(0);
ignore_user_abort(true);

echo "wikiLogin...\n";
fwrite($LOG, "wikiLogin...");
$c = new HTTP_Client();
if(wikiLogin($c)===FALSE)
 die("Could not login\n");
echo "done.<br/>\n";
fwrite($LOG,"done.\n");
ob_flush();

foreach($apMapping as $ap) {
	$page = $ap['ap'];
	echo "Working on ".$page."...<br/><ul>";
	fwrite($LOG, "\nWorking on ".$page."...\n");

	echo "<li>wikiToken...";
	fwrite($LOG, "	wikiToken...");
	$token = wikiGetToken($c,$page);
	if($token===FALSE) {
		echo "Achtung! Kann AP Seite ".WIKIURL.$page." nicht bearbeiten (edittoken).\n";
		echo "</li></ul>\n";
		fwrite($LOG, "Achtung! Kann AP Seite ".WIKIURL.$page." nicht bearbeiten (edittoken).\n");
		continue;
	}
	echo "done</li>\n";
	fwrite($LOG, "done\n");

	echo "<li>wikiExport...";
	fwrite($LOG, "	wikiExport...");
	$otmpl = wikiExport($c,$page);
	if($otmpl===FALSE) {	
		echo "Achtung! Kann AP Seite ".WIKIURL.$page." nicht laden (export).\n";
		echo "</li></ul>\n";
		fwrite($LOG, "Achtung! Kann AP Seite ".WIKIURL.$page." nicht laden (export).\n");
		continue;
	}
	echo "done</li>\n";
	fwrite($LOG, "done\n");

	$ntmpl = '[[Kategorie:'.$ap['section']."_AP]]\n".
		preg_replace(Array(
			'/\[\[Kategorie:.*?\]\]\r?\n?/s'
		),'',$otmpl);
	
	echo "<li>wikiEdit...";
	fwrite($LOG, "	wikiEdit...");
	if($otmpl == $ntmpl) {
		echo "useless. No changes needed.</li></ul>\n";
		fwrite($LOG, "useless. No changes needed.\n");
		continue;
	}
	if(wikiEdit($c,$token,$page,$ntmpl,Array(
		'recreate' => 1,
		'summary' => "Kategorie-Update; edited by script ".basename(__FILE__),
		'minor' => 1
	))===FALSE) {
		echo "Achtung! Kann AP Seite ".WIKIURL.$page." nicht updaten (wikiEdit).\n";
		echo "</li></ul>\n";
		fwrite($LOG, "Achtung! Kann AP Seite ".WIKIURL.$page." nicht updaten (wikiEdit).\n");
		continue;
	}
	echo "done</li>\n";
	fwrite($LOG, "done\n");
	echo "<hr/>".htmlentities($ntmpl)."<hr/>\n";

	echo "</ul><br/>done for $page<br/>\n";
	fwrite($LOG, "done ($page).\n");
	flush();
	ob_flush();
}
echo "finished</pre>\n";
fwrite($LOG, "finished\n");

fclose($LOG);

?>
