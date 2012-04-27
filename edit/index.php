<?php

require_once(dirname(__FILE__) . "/../include/time_start.php");
require_once(dirname(__FILE__) . "/../include/layout.php");

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"></meta>
  <link rel='stylesheet' href='../css/gserverl.css' type='text/css'></link>
  <title>Netplan v2</title>
  <link rel='stylesheet' type='text/css' href='../css/dhtmlXTree.css'></link>
  <script src="../js/dhtmlXCommon.js"></script>
  <script src="../js/dhtmlXTree.js"></script>
  <script type="text/javascript" src="../ajax/server.php?client=all&stub=remoteedit"></script>
  <script type='text/javascript'>
    //<![CDATA[

    var callback = {
      onedit: function(result) {
        document.getElementById('edit').innerHTML = result;
      },
      onupdate: function(result) {
        document.getElementById('response').innerHTML = result['html'];
        if (result['insert']) {
          tree.insertNewChild(result['parent'], result['id'], result['text'] ,onNodeSelect);
          if (result['new'] != null)
            tree.insertNewChild(result['id'], result['new'], result['newtext'] ,onNodeSelect);
        }
        if (result['update']) {
          tree.setItemText(tree.getSelectedItemId(), result['text']);
        }
      },
      ondelete: function(result) {
        document.getElementById('response').innerHTML = result['html'];
        if (result['delete']) {
          tree.deleteItem(tree.getSelectedItemId(), true);
        }
      }
    }

    var remoteedit = new remoteedit(callback);

    function onNodeSelect(nodeId){ 
      remoteedit.onedit(nodeId);
    }

    function afterLoad() {
      tree.openItem("netplan");
    }
  //]]>
  </script>
</head>

<body>
<?php printMenu(); ?>
<table width='100%' border='0' cellpadding='0' cellspacing='0'>
  <tr>
    <td width='20%' valign='top'>
      <div id="treeBox" class='normalbox'></div>
        <script>
          tree=new dhtmlXTreeObject(document.getElementById('treeBox'),"100%","100%",0);
          tree.setImagePath("../treeimgs/");
          tree.enableCheckBoxes(false);
          tree.enableDragAndDrop(false);
          tree.setOnClickHandler(onNodeSelect);
          tree.setXMLAutoLoading("../ajax/xmlmenu.php?addnew"); 
          tree.loadXML("../ajax/xmlmenu.php?addnew", afterLoad);
        </script>
    </td>
    <td valign='top'>
    <div id='edit' class='normalbox'>
      <h4>Im Menu links bitte ein Objekt zum editieren auswählen</h4>
    </div>
    </td>
  </tr>
</table>
<div class='normalbox'>
<?php require(dirname(__FILE__) . "/../include/time_end.php"); ?>
</div>
</body>
</html>
