<?php

require_once(dirname(__FILE__) . "/../include/db.php");
require_once(dirname(__FILE__) . "/../include/time_start.php");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
  <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
  <title>AP-&Uuml;bersicht</title>
  <script type="text/javascript" src="../js/overlib.js"><!-- overLIB (c) Erik Bosrup --></script>
  <script type="text/javascript" src="../js/sorttable.js"></script>
</head>
<body>
  <div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
  <div class='normalbox'>
<?php
    $id_node = $_GET['id_node'];

    if (empty($id_node)) {
      echo "<p>Es wurde keine Geri&auml;te-ID angegeben!</p>";
    } else if (is_numeric($id_node)) {
      $node = DB_DataObject::factory('node');
      $node->get($id_node);
      $node->find(TRUE);

      if (is_string($node->description)) {
        echo ("<div class='titlebox'>$node->description</div>");

        $admin = FALSE;
        if (is_string($_SERVER['PHP_AUTH_USER']))
        {
          $auth_username = $_SERVER['PHP_AUTH_USER'];
    
          $gruppen = DB_DataObject::factory('Gruppen');
          $gruppen->Username = $auth_username;
          $gruppen->find();

          while($gruppen->fetch()) {
            if ($gruppen->Gruppe == 'admin' OR $gruppen->Gruppe == 'Messtrupp') {
              $admin = TRUE;
              break;
            }
          }
        }
	$type = $node->getLink('id_type', 'type');
    	if ( preg_match("/^LANCOM/", $type->description) )
	{
	  require_once(dirname(__FILE__) . "/../include/snr2.php");
	} else if (  preg_match("/^Mikrotik/", $type->description) ) {
	  require_once(dirname(__FILE__) . "/../include/snr3.php");
        } else {
	  require_once(dirname(__FILE__) . "/../include/snr.php");
	}
        echo printSNRTable($id_node, $admin);
      } else {
        echo "<p>F&uuml;r diese Ger&auml;te-ID existiert kein Ger&auml;t.</p>";
      }
    } else { echo "<p>Schwerer Ausnahmefehler!</p>"; }
?>
  </div>
  <div class="normalbox">
    <?php require_once(dirname(__FILE__) . "/../include/time_end.php") ?>
  </div>
</body>
</html>
