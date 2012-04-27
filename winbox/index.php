<?php
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="addresses.wbx"');

require_once(dirname(__FILE__) . "/../include/db.php");

$interface = DB_DataObject::factory('interface');
$interface->query('
    SELECT
        n.description as note,
        SUBSTRING_INDEX(n.config_password, ":", 1) as login,
        SUBSTRING_INDEX(n.config_password, ":", -1) as password, i.ip as ip
    FROM interface as i
    INNER JOIN node as n ON i.id_node=n.id_node
    WHERE i.id_mode != 4 AND n.id_type = 30
    GROUP BY i.id_node
    ORDER BY n.description;
');

/*** Ausgabe ***/
/* Header */
echo sprintf("\x0f\x10\xc0\xbe");
/* Items */
while ($interface->fetch())
{
    echo sprintf("\x09\x00\x04typeaddr");
    echo sprintf("%s\x00\x04host%s",  chr(strlen($interface->ip)+5),    $interface->ip);
    echo sprintf("%s\x00\x05login%s", chr(strlen($interface->login)+6), $interface->login);
    echo sprintf("%s\x00\x04note%s",  chr(strlen($interface->note)+5),  $interface->note);
    echo sprintf("\x0d\x00\x0bsecure-mode\x01");
    echo sprintf("\x0a\x00\x08keep-pwd\x01");
    echo sprintf("%s\x00\x03pwd%s\x00\x00", chr(strlen($interface->password)+4), $interface->password);
}
/*** Ende ***/

/*
def winbox_item(ip,login,note,password):
  """
  Writes item to winbox \"addresses.wbx\" - usefull to import to
  winbox, or maybe to set as a winbox.cfg configuration.
  """
  f_winbox.write("\x09\x00\x04typeaddr")
  f_winbox.write("%s\x00\x04host%s" % (chr(len(ip)+5),ip))
  f_winbox.write("%s\x00\x05login%s" % (chr(len(login)+6),login))
  f_winbox.write("%s\x00\x04note%s" % (chr(len(note)+5),note))
  f_winbox.write("\x0d\x00\x0bsecure-mode\x01")
  f_winbox.write("\x0a\x00\x08keep-pwd\x01")
  f_winbox.write("%s\x00\x03pwd%s\x00\x00" % (chr(len(password)+4),password))

To use it from main:
if __name__ == "__main__":
  f_winbox = open("addresses.wbx",'w')
  f_winbox.write("\x0f\x10\xc0\xbe") #header
  winbox_item("10.9.8.7","some_user","some_note","some_password")
  f_winbox.close()
*/
?>
