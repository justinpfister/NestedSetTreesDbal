<?php
/*
  Nested Set Tree Library
  
  Author:  Rolf Brugger, edutech
  Version: 0.02, 5. April 2005
  URL:     http://www.edutech.ch/contribution/nstrees
  
  DB-Model by Joe Celko (http://www.celko.com/)
  
  References:
    http://www.sitepoint.com/article/1105/2
    http://searchdatabase.techtarget.com/tip/1,289483,sid13_gci537290,00.html
    http://dbforums.com/arch/57/2002/6/400142



  Datastructures:
  ---------------
  
  Handle:
    key: 'table':    name of the table that contains the tree structure
	key: 'lvalname': name of the attribute (field) that contains the left value
	key: 'rvalname': name of the attribute (field) that contains the right value
	
  Node:
    key 'l': left value
	key 'r': right value
	
	
  Orientation
  -----------
  
      n0
	 / | \
   n1  N  n3
     /   \
   n4     n5
   
  directions from the perspective of the node N:
    n0: up / ancestor
	n1: previous (sibling)
	n3: next (sibling)
	n4: first (child)
	n5: last (child)
     
*/




/* ******************************************************************* */
/* Tree Constructors */
/* ******************************************************************* */

function nstNewRoot ($thandle, $othercols)
/* creates a new root record and returns the node 'l'=1, 'r'=2. */
{
  $newnode['l'] = 1;
  $newnode['r'] = 2;
  _insertNew ($thandle, $newnode, $othercols);
  return $newnode;
}

function nstNewFirstChild ($thandle, $node, $othercols)
/* creates a new first child of 'node'. */
{
  $newnode['l'] = $node['l']+1;
  $newnode['r'] = $node['l']+2;
  _shiftRLValues($thandle, $newnode['l'], 2);
  _insertNew ($thandle, $newnode, $othercols);
  return $newnode;
}

function nstNewLastChild ($thandle, $node, $othercols)
/* creates a new last child of 'node'. */
{
  $newnode['l'] = $node['r'];
  $newnode['r'] = $node['r']+1;
  _shiftRLValues($thandle, $newnode['l'], 2);
  _insertNew ($thandle, $newnode, $othercols);
  return $newnode;
}

function nstNewPrevSibling ($thandle, $node, $othercols)
{
  $newnode['l'] = $node['l'];
  $newnode['r'] = $node['l']+1;
  _shiftRLValues($thandle, $newnode['l'], 2);
  _insertNew ($thandle, $newnode, $othercols);
  return $newnode;
}

function nstNewNextSibling ($thandle, $node, $othercols)
{
  $newnode['l'] = $node['r']+1;
  $newnode['r'] = $node['r']+2;
  _shiftRLValues($thandle, $newnode['l'], 2);
  _insertNew ($thandle, $newnode, $othercols);
  return $newnode;
}


/* *** internal routines *** */

function _shiftRLValues ($thandle, $first, $delta)
/* adds '$delta' to all L and R values that are >= '$first'. '$delta' can also be negative. */
{ //print("SHIFT: add $delta to gr-eq than $first <br/>");
  mysql_query("UPDATE ".$thandle['table']." SET ".$thandle['lvalname']."=".$thandle['lvalname']."+$delta WHERE ".$thandle['lvalname'].">=$first");
  mysql_query("UPDATE ".$thandle['table']." SET ".$thandle['rvalname']."=".$thandle['rvalname']."+$delta WHERE ".$thandle['rvalname'].">=$first");
}
function _shiftRLRange ($thandle, $first, $last, $delta)
/* adds '$delta' to all L and R values that are >= '$first' and <= '$last'. '$delta' can also be negative. 
   returns the shifted first/last values as node array.
 */
{
  mysql_query("UPDATE ".$thandle['table']." SET ".$thandle['lvalname']."=".$thandle['lvalname']."+$delta WHERE ".$thandle['lvalname'].">=$first AND ".$thandle['lvalname']."<=$last");
  mysql_query("UPDATE ".$thandle['table']." SET ".$thandle['rvalname']."=".$thandle['rvalname']."+$delta WHERE ".$thandle['rvalname'].">=$first AND ".$thandle['rvalname']."<=$last");
  return array('l'=>$first+$delta, 'r'=>$last+$delta);
}

function _insertNew ($thandle, $node, $othercols)
/* creates a new root record and returns the node 'l'=1, 'r'=2. */
{
  if (strlen($othercols)>0){$othercols .= ",";}
  $res = mysql_query("INSERT INTO ".$thandle['table']." SET $othercols"
         .$thandle['lvalname']."=".$node['l'].", ".$thandle['rvalname']."=".$node['r']);
  if (!$res) {_prtError();}
}


/* ******************************************************************* */
/* Tree Reorganization */
/* ******************************************************************* */

/* all nstMove... functions return the new position of the moved subtree. */
function nstMoveToNextSibling ($thandle, $src, $dst)
/* moves the node '$src' and all its children (subtree) that it is the next sibling of '$dst'. */
{
  return _moveSubtree ($thandle, $src, $dst['r']+1);
}

function nstMoveToPrevSibling ($thandle, $src, $dst)
/* moves the node '$src' and all its children (subtree) that it is the prev sibling of '$dst'. */
{
  return _moveSubtree ($thandle, $src, $dst['l']);
}

function nstMoveToFirstChild ($thandle, $src, $dst)
/* moves the node '$src' and all its children (subtree) that it is the first child of '$dst'. */
{
  return _moveSubtree ($thandle, $src, $dst['l']+1);
}

function nstMoveToLastChild ($thandle, $src, $dst)
/* moves the node '$src' and all its children (subtree) that it is the last child of '$dst'. */
{
  return _moveSubtree ($thandle, $src, $dst['r']);
}

function _moveSubtree ($thandle, $src, $to)
/* '$src' is the node/subtree, '$to' is its destination l-value */
{ 
  $treesize = $src['r']-$src['l']+1;
  _shiftRLValues($thandle, $to, $treesize);
  if($src['l'] >= $to){ // src was shifted too?
	$src['l'] += $treesize;
    $src['r'] += $treesize;
  }
  /* now there's enough room next to target to move the subtree*/
  $newpos = 
  _shiftRLRange($thandle, $src['l'], $src['r'], $to-$src['l']);
  /* correct values after source */
  _shiftRLValues($thandle, $src['r']+1, -$treesize);
  if($src['l'] <= $to){ // dst was shifted too?
	$newpos['l'] -= $treesize;
    $newpos['r'] -= $treesize;
  }  
  return $newpos;
}

/* ******************************************************************* */
/* Tree Destructors */
/* ******************************************************************* */

function nstDeleteTree ($thandle)
/* deletes the entire tree structure including all records. */
{
  $res = mysql_query("DELETE FROM ".$thandle['table']);
  if (!$res) {_prtError();}
}

function nstDelete ($thandle, $node)
/* deletes the node '$node' and all its children (subtree). */
{
  $leftanchor = $node['l'];
  $res = mysql_query("DELETE FROM ".$thandle['table']." WHERE "
         .$thandle['lvalname'].">=".$node['l']." AND ".$thandle['rvalname']."<=".$node['r']);
  _shiftRLValues($thandle, $node['r']+1, $node['l'] - $node['r'] -1);
  if (!$res) {_prtError();}
  return nstGetNodeWhere ($thandle, 
                    $thandle['lvalname']."<".$leftanchor
		   ." ORDER BY ".$thandle['lvalname']." DESC"
		 );
}



/* ******************************************************************* */
/* Tree Queries */
/*
 * the following functions return a valid node (L and R-value), 
 * or L=0,R=0 if the result doesn't exist.
 */
/* ******************************************************************* */

function nstGetNodeWhere ($thandle, $whereclause)
/* returns the first node that matches the '$whereclause'. 
   The WHERE-caluse can optionally contain ORDER BY or LIMIT clauses too. 
 */
{
  $noderes['l'] = 0;
  $noderes['r'] = 0;
  $res = mysql_query("SELECT * FROM ".$thandle['table']." WHERE ".$whereclause);
  if (!$res) {_prtError();}
  else{
    if ($row = mysql_fetch_array ($res)) {
	  $noderes['l'] = $row[$thandle['lvalname']];
	  $noderes['r'] = $row[$thandle['rvalname']];
	}
  }
  return $noderes;
}

function nstGetNodeWhereLeft ($thandle, $leftval)
/* returns the node that matches the left value 'leftval'. 
 */
{ return nstGetNodeWhere($thandle, $thandle['lvalname']."=".$leftval);
}
function nstGetNodeWhereRight ($thandle, $rightval)
/* returns the node that matches the right value 'rightval'. 
 */
{ return nstGetNodeWhere($thandle, $thandle['rvalname']."=".$rightval);
}

function nstRoot ($thandle)
/* returns the first node that matches the '$whereclause' */
{ return nstGetNodeWhere ($thandle, $thandle['lvalname']."=1");
}

function nstFirstChild ($thandle, $node)
{ return nstGetNodeWhere ($thandle, $thandle['lvalname']."=".($node['l']+1));
}
function nstLastChild ($thandle, $node)
{ return nstGetNodeWhere ($thandle, $thandle['rvalname']."=".($node['r']-1));
}
function nstPrevSibling ($thandle, $node)
{ return nstGetNodeWhere ($thandle, $thandle['rvalname']."=".($node['l']-1));
}
function nstNextSibling ($thandle, $node)
{ return nstGetNodeWhere ($thandle, $thandle['lvalname']."=".($node['r']+1));
}
function nstAncestor ($thandle, $node)
{ return nstGetNodeWhere ($thandle, 
                    $thandle['lvalname']."<".($node['l'])
		   ." AND ".$thandle['rvalname'].">".($node['r'])
		   ." ORDER BY ".$thandle['rvalname']
		 );
}


/* ******************************************************************* */
/* Tree Functions */
/*
 * the following functions return a boolean value
 */
/* ******************************************************************* */

function nstValidNode ($thandle, $node)
/* only checks, if L-value < R-value (does no db-query)*/
{ return ($node['l'] < $node['r']);
}
function nstHasAncestor ($thandle, $node)
{ return nstValidNode($thandle, nstAncestor($thandle, $node));
}
function nstHasPrevSibling ($thandle, $node)
{ return nstValidNode($thandle, nstPrevSibling($thandle, $node));
}
function nstHasNextSibling ($thandle, $node)
{ return nstValidNode($thandle, nstNextSibling($thandle, $node));
}
function nstHasChildren ($thandle, $node)
{ return (($node['r']-$node['l'])>1);
}
function nstIsRoot ($thandle, $node)
{ return ($node['l']==1);
}
function nstIsLeaf ($thandle, $node)
{ return (($node['r']-$node['l'])==1);
}
function nstIsChild ($node1, $node2)
/* returns true, if 'node1' is a direct child or in the subtree of 'node2' */
{ return (($node1['l']>$node2['l']) and ($node1['r']<$node2['r']));
}
function nstIsChildOrEqual ($node1, $node2)
{ return (($node1['l']>=$node2['l']) and ($node1['r']<=$node2['r']));
}
function nstEqual ($node1, $node2)
{ return (($node1['l']==$node2['l']) and ($node1['r']==$node2['r']));
}


/* ******************************************************************* */
/* Tree Functions */
/*
 * the following functions return an integer value
 */
/* ******************************************************************* */

function nstNbChildren ($thandle, $node)
{ return (($node['r']-$node['l']-1)/2);
}

function nstLevel ($thandle, $node)
/* returns node level. (root level = 0)*/
{ 
  $res = mysql_query("SELECT COUNT(*) AS level FROM ".$thandle['table']." WHERE "
                   .$thandle['lvalname']."<".($node['l'])
		   ." AND ".$thandle['rvalname'].">".($node['r'])
		 );
		   
  if ($row = mysql_fetch_array ($res)) {
    return $row["level"];
  }else{
    return 0;
  }
}

/* ******************************************************************* */
/* Tree Walks  */
/* ******************************************************************* */

function nstWalkPreorder ($thandle, $node)
/* initializes preorder walk and returns a walk handle */
{
  $res = mysql_query("SELECT * FROM ".$thandle['table']
         ." WHERE ".$thandle['lvalname'].">=".$node['l']
         ."   AND ".$thandle['rvalname']."<=".$node['r']
         ." ORDER BY ".$thandle['lvalname']);

  return array('recset'=>$res,
               'prevl'=>$node['l'], 'prevr'=>$node['r'], // needed to efficiently calculate the level
               'level'=>-2 );
}

function nstWalkNext($thandle, &$walkhand)
{
  if ($row = mysql_fetch_array ($walkhand['recset'], MYSQL_ASSOC)){
    // calc level
	$walkhand['level']+= $walkhand['prevl'] - $row[$thandle['lvalname']] +2;
	// store current node
    $walkhand['prevl'] = $row[$thandle['lvalname']];
    $walkhand['prevr'] = $row[$thandle['rvalname']];
    $walkhand['row']   = $row;
    return array('l'=>$row[$thandle['lvalname']], 'r'=>$row[$thandle['rvalname']]);
  } else{
    return FALSE;
  }
}

function nstWalkAttribute($thandle, $walkhand, $attribute)
{
  return $walkhand['row'][$attribute];
}

function nstWalkCurrent($thandle, $walkhand)
{
  return array('l'=>$walkhand['prevl'], 'r'=>$walkhand['prevr']);
}
function nstWalkLevel($thandle, $walkhand)
{
  return $walkhand['level'];
}



/* ******************************************************************* */
/* Printing Tools */
/* ******************************************************************* */

function nstNodeAttribute ($thandle, $node, $attribute)
/* returns the attribute of the specified node */
{
  $res = mysql_query("SELECT * FROM ".$thandle['table']." WHERE ".$thandle['lvalname']."=".$node['l']);
  if ($row = mysql_fetch_array ($res)) {
    return $row[$attribute];
  }else{
    return "";
  }
}

function nstPrintSubtree ($thandle, $node, $attributes)
/*  */
{
  $wlk = nstWalkPreorder($thandle, $node);
  while ($curr = nstWalkNext($thandle, $wlk)) {
	// print indentation
	print (str_repeat("&nbsp;", nstWalkLevel($thandle, $wlk)*4));
	// print attributes
	$att = reset($attributes);
	while($att){
      // next line is more efficient:  print ($att.":".nstWalkAttribute($thandle, $wlk, $att));
	  print ($wlk['row'][$att]);
	  $att = next($attributes);
	}
	print ("<br/>");
  }
}

function nstPrintSubtreeOLD ($thandle, $node, $attributes)
/*  */
{
  $res = mysql_query("SELECT * FROM ".$thandle['table']." ORDER BY ".$thandle['lvalname']);
  if (!$res) {_prtError();}
  else{
    $level = -1;
	$prevl = 0;
    while ($row = mysql_fetch_array ($res)) {
	  // calc level
	  if      ($row[$thandle['lvalname']] == ($prevl+1)) {
	    $level+=1;
	  }elseif ($row[$thandle['lvalname']] != ($prevr+1)) {
	    $level-=1;
	  }
	  // print indentation
	  print (str_repeat("&nbsp;", $level*4));
	  // print attributes
	  $att = reset($attributes);
	  while($att){
        print ($att.":".$row[$att]);
		$att = next($attributes);
	  }
	  print ("<br/>");
	  $prevl = $row[$thandle['lvalname']];
	  $prevr = $row[$thandle['rvalname']];
	}
  }
}

function nstPrintTree ($thandle, $attributes)
/* Prints attributes of the entire tree. */
{ 
  nstPrintSubtree ($thandle, nstRoot($thandle), $attributes);
}


function nstBreadcrumbsString ($thandle, $node)
/* returns a string representing the breadcrumbs from $node to $root  
   Example: "root > a-node > another-node > current-node"

   Contributed by Nick Luethi
 */
{
  // current node
  $ret = nstNodeAttribute ($thandle, $node, "name");
  // treat ancestor nodes
  while(nstAncestor ($thandle, $node) != array("l"=>0,"r"=>0)){
    $ret = "".nstNodeAttribute($thandle, nstAncestor($thandle, $node), "name")." &gt; ".$ret;
    $node = nstAncestor ($thandle, $node);
  }
  return $ret;
  //return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;breadcrumb: <font size='1'>".$ret."</font>";
} 

/* ******************************************************************* */
/* internal functions */
/* ******************************************************************* */

function _prtError(){
  echo "<p>Error: ".mysql_errno().": ".mysql_error()."</p>";
}
?>
