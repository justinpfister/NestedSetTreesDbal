<html>
<head>
<?php 

/*
  Nested Set Tree Library
  
  Author:  Rolf Brugger, edutech
  Version: 0.1, 21.6.2004
  URL:     http://www.edutech.ch/contribution/nstrees
*/

include "nstrees.php";
include "treetest-config.php";

$dbh = mysql_connect($ttest_host, $ttest_user, $ttest_pwd);
if (!mysql_select_db($ttest_db))
  echo mysql_errno().": ".mysql_error()."<BR>";
  
?>
  <title> nested set trees: edit tree </title>
</head>
<body>
<h2>Nested Set Trees: test page</h2>

<?php 

$thandle['table'] = "tree1";
$thandle['lvalname'] = "lft";
$thandle['rvalname'] = "rgt";

/* *************************************************************** */
/* *** parameters *** */
if (isset($node)){
  list ($paramnode['l'], $paramnode['r']) = explode("-", $node);
}
else{
  $paramnode = array('l'=>0, 'r'=>'0');
}


/* *************************************************************** */
/* *** do action *** */
if (isset($act) and ($act == "new") and !isset($node)){
  // create a new tree/root
  print "<p>new root</p>";
  nstDeleteTree($thandle);
  $root = nstNewRoot($thandle, "name='root'");
}
if (isset($act) and isset($node)){
  if     ($act == "new"){
    print "<p>append new child node</p>";
    $paramnode = nstNewLastChild ($thandle, $paramnode, "name='newnode'");
  }
  elseif (strcmp($act, "del")==0){
    print "<p>delete node/subtree</p>";
    $paramnode = nstDelete ($thandle, $paramnode);
  }

  elseif ($act == "up"){
    $sibling = nstPrevSibling($thandle, $paramnode);
    if (nstValidNode($thandle, $sibling)){
      print "<p>move node/subtree up</p>";
      $paramnode = nstMoveToPrevSibling ($thandle, $paramnode, $sibling);
	}
	else{
	  print "<p><b>can't move up: no previous sibling!</b></p>";
	}
  }
  elseif ($act == "dwn"){
    $sibling = nstNextSibling($thandle, $paramnode);
    if (nstValidNode($thandle, $sibling)){
      print "<p>move node/subtree down</p>";
      $paramnode = nstMoveToNextSibling ($thandle, $paramnode, $sibling);
	}
	else{
	  print "<p><b>can't move down: no next sibling!</b></p>";
	}
  }
  elseif ($act == "in"){ 
    // indent: move to last child of previous sibling
    $sibling = nstPrevSibling($thandle, $paramnode);
    if (nstValidNode($thandle, $sibling)){
      print "<p>indent node/subtree</p>";
      $paramnode = nstMoveToLastChild ($thandle, $paramnode, $sibling);
	}
	else{
	  print "<p><b>can't indent: no previous sibling!</b></p>";
	}
  }
  elseif ($act == "out"){
    // outdent: move to next sibling of ancestor (ancestor must not be root!)
    $ancestor = nstAncestor($thandle, $paramnode);
    if (nstValidNode($thandle, $ancestor) and !(nstEqual($ancestor, nstRoot ($thandle)))){
      print "<p>outdent node/subtree</p>";
      $paramnode = nstMoveToNextSibling ($thandle, $paramnode, $ancestor);
	}
	else{
	  print "<p><b>can't outdent: no ancestor!</b></p>";
	}
  }

  elseif (strcmp($act, "upd")==0){
    print "<p>update</p>";
	$setstr = "";
    while (list ($key, $val) = each ($HTTP_POST_VARS)) {
	  // extract all "edt_" parameters
      if (preg_match ("/^edt_(.+)/", $key, $matches)) {
		$setstr .= " ".$matches[1]."='".$val."',";
      }
    }
    $setstr = rtrim($setstr, ","); // chop off trailing comma
    mysql_query("UPDATE ".$thandle['table']." SET ".$setstr." WHERE ".$thandle['lvalname']."=".$paramnode['l']);
  }
}


/* *************************************************************** */
/* *** print tree *** */

print ("<form action=\"treetest-edit.php\" method=\"post\">");
#print ('<form action="treetest-edit.php" method="get">');



$walk = nstWalkPreorder ($thandle, nstRoot($thandle));
while($node = nstWalkNext($thandle, $walk)){
  if (isset($act) and $act!="edt"){
    print ('<input type="radio" name="node" value="'.$node['l'].'-'.$node['r'].'"');
    if (nstEqual($paramnode, $node)) {print (" checked");}
    print ('>');
  }
  print (str_repeat("&nbsp;", nstWalkLevel($thandle, $walk)*4));
  while(list($col_name, $col_value) = each($walk['row'])) {
    if ($col_name!=$thandle['lvalname'] and $col_name!=$thandle['rvalname'])
    if (isset($act) and $act=="edt" and nstEqual($paramnode, $node)){
      print ($col_name.':<input type="text" name="edt_'.$col_name.'" value="'.$col_value.'">&nbsp;&nbsp;');
	}
	else{
      print "$col_name='$col_value' \n";
	}
  }
  print ("<br>");
}

print ('<br>');


if (isset($act) and  $act=="edt"){
  print ('<button name="act" value="upd" type="submit">Update</button>');
  print ('<button name="act" value="can" type="submit">Cancel</button>');
  print ('<input type="hidden" name="node" value="'.$paramnode['l'].'-'.$paramnode['r'].'"');
}
else{
  print ('<button name="act" value="del" type="submit">Delete</button>');
  print ('<button name="act" value="new" type="submit">New</button>');
  print ('<button name="act" value="edt" type="submit">Edit</button>');
  print ('<br>');
  print ('<button name="act" value="up"  type="submit">up</button>');
  print ('<button name="act" value="dwn" type="submit">down</button>');
  print ('<button name="act" value="in"  type="submit">in</button>');
  print ('<button name="act" value="out" type="submit">out</button>');
}
print ("</form>");





mysql_close ($dbh);
?>
</body>
</html>
