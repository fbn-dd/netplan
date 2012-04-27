<?php
require_once(dirname(__FILE__) . "/../include/time_start.php");
require_once(dirname(__FILE__) . "/../include/layout.php");
require_once(dirname(__FILE__) . "/../include/db.php");
require_once('HTML/QuickForm.php');

// var_dump() Ersatz
function vd($i = NULL)
{
    echo '<pre>'.htmlspecialchars(var_export($i, 1)).'</pre>';
}

function password($size)
{
    $result = '';
    $chars = array('!', '"', '#', '$', '%', '&', '`', '(', ')', '*', '+', ',', '-', '.', '/', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';', '<', '=', '>', '?', '@', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '[', '\\', ']', '^', '_', '\'', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '{', '|', '}', '~');
    $alphanum = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
    srand((double)microtime()*1000000);
    for($i=0; $i<$size; $i++)
    {
        $num = rand(0, count($chars));
        $result .= $alphanum[$num];
    }
  return $result;
}

function wpaPassphraseUpdate($input = NULL)
{
    gserverl $form;

    if ( ($input == NULL) or !isset($input['mac']) or empty($input['mac'] ))
    {
        return false;
    }

    $oldmacpw = $input['macpw'];

    $radreply = DB_DataObject::factory('radius_reply');
    $radreply->whereAdd("UserName LIKE '".$input['mac']."'");
    $radreply->whereAdd("Attribute LIKE 'LCS-WPA-Passphrase'");
    $radreply->find();
    while ($radreply->fetch())
    {
        // lösche alle Einträge mit LCS-WPA-Passphrase zu der Mac
        if ($radreply->delete() == FALSE)
        {
            echo "<p>Fehler: Die Passphrase für die Mac-Adresse ".$radreply->UserName." konnte nicht gelöscht werden.</p>";
            $dirty = 1;
        } else {
            //echo "<p>Passphrase für ".$radreply->UserName." gelöscht.</p>";
            $dirty = 0;
        }
    }
    if (!$dirty)
    {
        $radreply_new = DB_DataObject::factory('radius_reply');
        $radreply_new->UserName  = $input['mac'];
        $radreply_new->Attribute = 'LCS-WPA-Passphrase';
        $radreply_new->op        = '=';
        $radreply_new->Value     = password(63);
        $id = $radreply_new->insert();
/*
// konnte leider noch keinen error erzeugen um zu prüfen ob wirklich PEAR_Error Exception geworfen wird
if (PEAR::isError($id))
{
    echo $id->getMessage();
}
*/
        $hinweis = '<span style="color:green;">Die unten stehende Passphrase kann nun verwendet werden.</span>';
        //$hinweis = 'Die unten stehende Passphrase kann nun verwendet werden.';
    } else {
        $hinweis = '<span style="color:red;">Die Passphrase konnte nicht in die Datenbank eingetragen werden.<br />'.
                   'Es gilt immernoch die alte Passphrase:<br />'.
                   htmlspecialchars($oldmacpw).'</span>';
    }
    // echo $hinweis;
    $form->addElement('static', 'hinweis', 'Hinweis', $hinweis);


}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
  <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
  <title>WPA-Generator</title>
</head>

<body>
<?php printMenu(); ?>

<div class='normalbox'>

<h1>WPA-Generator</h1>

<p>Du kannst hier für jeder deiner Mac-Adressen ein individuelles 
WPA-Passwort erzeugen lassen, welches dann nur für diese Mac-Adresse 
auf allen Accesspoints des Vereins gültig ist.</p>

<p>Bitte prüfe vorher ob du mit deinem WLAN-Router <a href="https://www.example.org/wiki/PEAP">PEAP</a> nutzen kannst. Prüfe bitte auch ob du an einem Accesspoint angeschlossen bist, der mit Technik der Firma LANCOM arbeitet. Dazu gehst du in die <a href="../stats/">Routerstatistik</a> und klickst auf den AP-Standort und dann auf das Gerät an dem du hängst. Die verwendete Hardware steht über der Tabelle verbundener WLAN-Clients.</p>
<?php

if (!empty($_SERVER['PHP_AUTH_USER']))
{

$mitglied = DB_DataObject::factory('Mitglieder');
$mitglied->get('Username', $_SERVER['PHP_AUTH_USER']);

echo utf8_encode('<p>Du bist angemeldet als: '.$mitglied->Vorname.' '.
     $mitglied->Nachname.' (BNID '.$mitglied->BNID.')</p>');

$macs = DB_DataObject::factory('Macs');
$macs->BNID = $mitglied->BNID;
$macs->WhereAdd('ValidTo > NOW() OR ValidTo IS NULL');
$macs->find();

// Elemente einfügen
while ($macs->fetch())
{
    // Formular erstellen
    $form = new HTML_QuickForm('editWPA'.$macs->Mac, 'post', null, null, null, true);
    // man kann nur Formularelemente updaten, die auch existieren, also erstmal alle Objekte hinzufügen
    $form->addElement('header', null, 'Mac-Adresse '.$macs->Mac);
    $form->addElement('static', null, 'Bemerkung', ($macs->Bemerkung?$macs->Bemerkung:'keine'));

    if($form->isSubmitted())
    {
        // Callback-Funktion aufrufen 
        $form->process('wpaPassphraseUpdate'); 
    }

    $form->addElement('text', 'macpw', 'Passphrase', array('readonly'=>'readonly', 'size'=>70));
    $form->addElement('hidden', 'mac', $macs->Mac);
    $form->addElement('submit', 'action', 'neue WPA-Passphrase für diese Mac erzeugen');

    $radreply = DB_DataObject::factory('radius_reply');
    $radreply->whereAdd("UserName LIKE '".$macs->Mac."'");
    $radreply->whereAdd("Attribute LIKE 'LCS-WPA-Passphrase'");
    $radreply->find();
    $wpapassphrase = '';
    // es sollte jede Mac nur einmal mit LCS-WPA-Passphrase in der DB stehen
    // wenn nicht, wird der letzte Eintrag angezeigt
    // wpaPassphraseUpdate() löscht Mehrfacheinträge
    while ($radreply->fetch())
    {
        $wpapassphrase = $radreply->Value;
    }
    if (empty($wpapassphrase))
    {
        $hinweis = &HTML_QuickForm::createElement('static', 'hinweis', 'Hinweis', 'Für diese Mac existiert noch keine WPA-Passphrase.');
        $form->insertElementBefore($hinweis, 'action');
        $form->removeElement('macpw');
    } else {
        // Formular-Objekt mit wpapassphrase füllen
        $form->updateElementAttr('macpw', array('value' => $wpapassphrase));
    }

    // Formular anzeigen
    $form->display();

} // Ende while($macs->fetch())

} // Ende if(epmty($_SERVER['PHP_AUTH_USER']))
else
{
echo "<p>Fehler: Der angemeldete Nutzer konnte nicht ermittelt werden.</p>";
}
?>

</div>

<div class='normalbox'>
<?php require(dirname(__FILE__) . "/../include/time_end.php"); ?>
</div>

</body>
</html>
