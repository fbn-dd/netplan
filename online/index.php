<?php
require_once(dirname(__FILE__) . "/../include/db.php");

$conn = mysql_connect("dbserver", "radacct-select", "KyutudwR2KXH7fR2");

if (!$conn) {
    echo "Keine Verbindung zur DB: " . mysql_error();
    exit;
}

if (!mysql_select_db("radius")) {
    echo "Kann radius nicht auswählen: " . mysql_error();
    exit;
}

$sql = 'SELECT DISTINCT(calledstationid) FROM radacct WHERE calledstationid != "";'; 

$result = mysql_query($sql);

if (!$result) {
    echo "Anfrage ($sql) konnte nicht ausgeführt werden : " . mysql_error();
    exit;
}

if (mysql_num_rows($result) == 0) {
    echo "Keine Zeilen gefunden, nichts auszugeben, daher Abbruch";
    exit;
}

?>
<html>
	<head>
		<title>Mitglieder-Session zu bestimmten Datum</title>
		<link rel="stylesheet" type="text/css" href="anytime.css" />
		<script type="text/javascript" src="jquery.js"></script>
		<script type="text/javascript" src="anytime.js"></script>
	</head>
	<body>
		<h1>Welche Mitglieder waren auf welchem Einwahlserver zu welcher Zeit online?</h1>
                <form action="./"  method="POST">
			<table border="0">
				<tr>
					<td><label for="nas">NAS-IP-Adresse</label></td>
					<td><select id="nas" name="nas">
			<? while($row = mysql_fetch_assoc($result)): ?>
				<option><?= $row['calledstationid'] ?></option>
			<? endwhile; ?>
			</select>
					</td>
				</tr>
				<tr>
					<td><label for="date">Datum/Zeit</label></td>
					<td><input type="text" id="date" name="date"/></td>
				</tr>
				<tr>
					<td />
					<td><input type="submit"/></td>
				</tr>
			</table>
		</form>
		<br />
		<? if($_POST['date']): ?>
		<table>
			<tr>
				<th>Benutzername</th>
			</tr>
<?
$timestamp = $_POST['date'];
$sql = 'SELECT distinct(username) FROM radacct WHERE acctstarttime <= "'.$timestamp.'" AND (acctstoptime >= "'.$timestamp.'" OR acctstoptime IS NULL) AND calledstationid = "'.$_POST['nas'].'";';

$result = mysql_query($sql);

if (!$result) {
    echo "Anfrage ($sql) konnte nicht ausgeführt werden : " . mysql_error();
    exit;
}

if (mysql_num_rows($result) == 0) {
    echo "Keine Zeilen gefunden, nichts auszugeben, daher Abbruch";
}
?>
		<? while($row = mysql_fetch_assoc($result)): ?>
			<tr>
				<td><?= $row["username"] ?></td>
			</tr>
		<? endwhile; ?>
		</table>
		<? endif; ?>
                <script type="text/javascript">
                        AnyTime.picker("date", { format: "%Y-%m-%d %H:%i:%s", firstDOW: 1 } );
                </script>

	</body>
</html>
<? mysql_free_result($result); ?>
