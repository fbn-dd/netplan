<?php
require_once(dirname(__FILE__) . "/time_start.php");
require_once(dirname(__FILE__) . "/layout.php");
require_once(dirname(__FILE__) . "/constants.inc.php");
require_once(dirname(__FILE__) . "/db.php");
require_once(dirname(__FILE__) . "/geocode.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
<head>
  <title><?php echo $title; ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
  <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
  <script src="http://maps.google.com/maps?file=api&amp;v=3&amp;key=<?php echo KEY; ?>&amp;sensor=false&amp;indexing=false" type="text/javascript"></script>
</head>

<body>
  <?php printMenu(); ?>
  <div id="map" class="normalbox"></div>
  <div class="normalbox"><?php include_once(dirname(__FILE__) . "/time_end.php"); ?></div>
</body>
</html>
