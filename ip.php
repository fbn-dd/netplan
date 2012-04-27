<?php
require_once(dirname(__FILE__) . "/include/time_start.php");
require_once(dirname(__FILE__) . "/include/layout.php");
require_once(dirname(__FILE__) . "/include/db.php");
require_once("HTML/QuickForm.php");

function ip2ap($input = NULL)
{
    if ( ($input == NULL) or !isset($input['ip']) or empty($input['ip'] ))
    {
        return false;
    }

    $interfaces = DB_DataObject::factory('interface');
    $interfaces->whereAdd("ip LIKE '".$input['ip']."%'");
	$interfaces->groupBy('ip');
    $interfaces->find();

    $netmask = DB_DataObject::factory('netmask');
    $netmask->find();
    while($netmask->fetch())
    {
        $netmasks[$netmask->id_netmask] = $netmask->description;
    }

    $node = DB_DataObject::factory('node');
    $node->find();
    while ($node->fetch())
	{
		$nodes[$node->id_node] = $node->description;
	}

	echo "<h1>Ergebnis der Suche nach ".$input['ip']."</h1>\n".
		 "<table><tr><th>IP-Adresse</th><th>Netzmaske</th><th>Accesspoint</th></tr>\n";

    $i=0;
    while ($interfaces->fetch())
	{
		echo "<tr class=\"".($i%2?"odd":"even")."\">\n"
		."\t<td>".$interfaces->ip."</td>\n"
		."\t<td>".$netmasks[$interfaces->id_netmask]."</td>\n"
		."\t<td><a href=\"stats/".$nodes[$interfaces->id_node]."\">".$nodes[$interfaces->id_node]."</a></td>\n"
		."</tr>\n";

                $i++;
	}
	echo '</table>';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
  <link rel='stylesheet' href='css/gserverl.css' type='text/css'></link>
  <title>Netplan v2</title>
</head>

<body onLoad="document.getElementById('ip').focus();return true">
<?php printMenu(); ?>

<div class="normalbox">
<?php
// Formular erstellen
$form = new HTML_QuickForm("suche", "post", null, null, null, true);
//$form = new HTML_QuickForm("suche", "post");
// man kann nur Formularelemente updaten, die auch existieren, also erstmal alle Objekte hinzufügen
$form->addElement("header", null, "Zeige Accesspoints im IP-Bereich");
$form->addElement("text", "ip", "IP-Adresse", array("size"=>20, "id"=>"ip"));
$form->addElement("submit", "action", "Suche starten");
$form->addElement("static", null, null, "Wildcard-Suche wird so unterstützt, dass man den hinteren Teil der IP-Adresse weglassen kann.");
$form->display();

if($form->isSubmitted())
{
	// Callback-Funktion aufrufen
	$form->process("ip2ap");
}

?>
</div>

<div class="normalbox">
<?php require(dirname(__FILE__) . "/include/time_end.php"); ?>
</div>
</body>
</html>
