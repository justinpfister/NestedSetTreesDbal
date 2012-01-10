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

  <title> nested set trees: test page </title>
</head>
<body>
<h2>Nested Set Trees: test page</h2>

<?php 

$thandle['table'] = "tree1";
$thandle['lvalname'] = "lft";
$thandle['rvalname'] = "rgt";


nstDeleteTree($thandle);
print ("<br>create root<br>\n");
$root = nstNewRoot($thandle, "id=1,name='root'");

nstPrintTree($thandle, array("name"));


print ("<br>add 4 child nodes<br>\n");
nstNewLastChild($thandle, $root, "name='3'");
nstNewLastChild($thandle, nstRoot($thandle), "name='4'");
$child2 = nstNewFirstChild($thandle, nstRoot($thandle), "name='2'");
nstNewPrevSibling($thandle, $child2, "name='1'");

nstPrintTree($thandle, array("name"));


print ("<br>add 3 child subnodes<br>\n");
$pchild = nstNewFirstChild($thandle, nstGetNodeWhere($thandle, "name='3'"), "name='3.3'");
$pchild = nstNewPrevSibling($thandle, $pchild, "name='3.1'");
$pchild = nstNewNextSibling($thandle, $pchild, "name='3.2'");

nstPrintTree($thandle, array("name"));


print ("<br>add 2 child sub-subnodes<br>\n");
$pchild = nstNewFirstChild($thandle, $pchild, "name='3.2.1'");
$pchild = nstNewNextSibling($thandle, $pchild, "name='3.2.2'");

nstPrintTree($thandle, array("name"));


print ("<br>move subtree 3.2 after 2 <br>\n");
$pchild = nstMoveToNextSibling($thandle, nstGetNodeWhere($thandle, "name='3.2'"), nstGetNodeWhere($thandle, "name='2'"));

nstPrintTree($thandle, array("name"));


print ("<br>move subtree 3.2 back previous to 3.3 <br>\n");
$pchild = nstMoveToPrevSibling($thandle, nstGetNodeWhere($thandle, "name='3.2'"), nstGetNodeWhere($thandle, "name='3.3'"));

nstPrintTree($thandle, array("name"));


print ("<br>print node properties <br>\n");

$node = nstGetNodeWhere($thandle, "name='3.2'");
print ("<br>first child of 3.2:&nbsp; ".nstNodeAttribute($thandle, nstFirstChild($thandle, $node), "name")."<br>\n");
print ("last child of 3.2:&nbsp; ".nstNodeAttribute($thandle, nstLastChild($thandle, $node), "name")."<br>\n");
print ("prev sibling of 3.2:&nbsp; ".nstNodeAttribute($thandle, nstPrevSibling($thandle, $node), "name")."<br>\n");
print ("next sibling of 3.2:&nbsp; ".nstNodeAttribute($thandle, nstNextSibling($thandle, $node), "name")."<br>\n");
print ("ancestor of 3.2:&nbsp; ".nstNodeAttribute($thandle, nstAncestor($thandle, $node), "name")."<br>\n");

$node = nstGetNodeWhere($thandle, "name='3'");
print ("<br>first child of 3:&nbsp; ".nstNodeAttribute($thandle, nstFirstChild($thandle, $node), "name")."<br>\n");
print ("last child of 3:&nbsp; ".nstNodeAttribute($thandle, nstLastChild($thandle, $node), "name")."<br>\n");
print ("prev sibling of 3:&nbsp; ".nstNodeAttribute($thandle, nstPrevSibling($thandle, $node), "name")."<br>\n");
print ("next sibling of 3:&nbsp; ".nstNodeAttribute($thandle, nstNextSibling($thandle, $node), "name")."<br>\n");
print ("ancestor of 3:&nbsp; ".nstNodeAttribute($thandle, nstAncestor($thandle, $node), "name")."<br>\n");

$node = nstGetNodeWhere($thandle, "name='1'");
print ("<br>first child of 1:&nbsp; ".nstNodeAttribute($thandle, nstFirstChild($thandle, $node), "name")."<br>\n");
print ("last child of 1:&nbsp; ".nstNodeAttribute($thandle, nstLastChild($thandle, $node), "name")."<br>\n");
print ("prev sibling of 1:&nbsp; ".nstNodeAttribute($thandle, nstPrevSibling($thandle, $node), "name")."<br>\n");
print ("next sibling of 1:&nbsp; ".nstNodeAttribute($thandle, nstNextSibling($thandle, $node), "name")."<br>\n");
print ("ancestor of 1:&nbsp; ".nstNodeAttribute($thandle, nstAncestor($thandle, $node), "name")."<br>\n");



print ("<br>print boolean properties (1=yes, empty=no) <br>\n");

$node = nstGetNodeWhere($thandle, "name='3.2'");
print ("node 3.2:&nbsp;&nbsp; has anc:".nstHasAncestor($thandle, $node)
      ." &nbsp;has next sibl:".nstHasNextSibling($thandle, $node)
      ." &nbsp;has prev sibl:".nstHasPrevSibling($thandle, $node)
      ." &nbsp;has children:".nstHasChildren($thandle, $node)
      ."<br>\n");
$node = nstGetNodeWhere($thandle, "name='3'");
print ("node 3:&nbsp;&nbsp; has anc:".nstHasAncestor($thandle, $node)
      ." &nbsp;has next sibl:".nstHasNextSibling($thandle, $node)
      ." &nbsp;has prev sibl:".nstHasPrevSibling($thandle, $node)
      ." &nbsp;has children:".nstHasChildren($thandle, $node)
      ."<br>\n");
$node = nstGetNodeWhere($thandle, "name='1'");
print ("node 1:&nbsp;&nbsp; has anc:".nstHasAncestor($thandle, $node)
      ." &nbsp;has next sibl:".nstHasNextSibling($thandle, $node)
      ." &nbsp;has prev sibl:".nstHasPrevSibling($thandle, $node)
      ." &nbsp;has children:".nstHasChildren($thandle, $node)
      ."<br>\n");
$node = nstRoot($thandle);
print ("node root:&nbsp;&nbsp; has anc:".nstHasAncestor($thandle, $node)
      ." &nbsp;has next sibl:".nstHasNextSibling($thandle, $node)
      ." &nbsp;has prev sibl:".nstHasPrevSibling($thandle, $node)
      ." &nbsp;has children:".nstHasChildren($thandle, $node)
      ."<br>\n");



print ("<br>print level values <br>\n");

print ("level of root= ".nstLevel($thandle, nstRoot($thandle))."<br>\n");
print ("level of node 3= ".nstLevel($thandle, nstGetNodeWhere($thandle, "name='3'"))."<br>\n");
print ("level of node 3.2= ".nstLevel($thandle, nstGetNodeWhere($thandle, "name='3.2'"))."<br>\n");
print ("level of node 3.2.1= ".nstLevel($thandle, nstGetNodeWhere($thandle, "name='3.2.1'"))."<br>\n");

	  
	  
print ("<br>Walk tree - preorder: <br>\n");
$walk = nstWalkPreorder ($thandle, nstRoot($thandle));
print ("INIT-LEVEL=".nstWalkLevel($thandle, $walk)."<br>\n");
while($node = nstWalkNext($thandle, $walk)){
  print ("L".nstWalkLevel($thandle, $walk).":next"
        .nstNodeAttribute($thandle, $node, "name")."-curr"
        .nstNodeAttribute($thandle, nstWalkCurrent($thandle, $walk), "name")." "
        ." &nbsp; ");
}
print ("<br>\n");


print ("<br>delete subtree 3.2<br>\n");

$pchild = nstDelete($thandle, nstGetNodeWhere($thandle, "name='3.2'"));

nstPrintTree($thandle, array("name"));


?>

<h2>Edit</h2>
<p><a href="treetest-edit.php">interactively edit the tree</a></p>

<?php
mysql_close ($dbh);
?>
</body>
</html>
