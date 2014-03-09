<?php

/* Demo for TreeReaderHandle class */
require '../class.TreeReaderHandler.php';

// Database configuration
$host = 'localhost';
$db_name = '';
$db_username = '';
$db_password = '';

try {
    $db = new PDO('mysql:host=' . $host .';dbname=' . $db_name . '', $db_username, $db_password);
}
catch (PDOException $e){
    echo $e->getMessage();
    exit();
}

// Create class instance
$node_tree = new TreeReaderHandler($db, 'tree', 'id', 'parent_id', 'name');

echo "<h2>Full tree</h2>";
echo $node_tree->getList();
echo $node_tree->getSelect();
echo "<hr/>";

echo "<h2>Subtree: including parent, depth 1</h2>";
$subset = $node_tree->getSubTree(1, false, 1);
echo $node_tree->getList($subset);
echo $node_tree->getSelect($subset);
echo "<hr/>";

echo "<h2>Subtree: excluding parent, unlimited depth</h2>";
$subset = $node_tree->getSubTree(1, true);
echo $node_tree->getList($subset);
echo $node_tree->getSelect($subset);
echo "<hr/>";

echo "<h2>Subtree: unexisting parent</h2>";
$subset = $node_tree->getSubTree(59, false, 1);
echo $node_tree->getList($subset);
echo $node_tree->getSelect($subset);
echo "<hr/>";

echo "<h2>Siblings</h2>";
$subset = $node_tree->getSiblings(3);
echo $node_tree->getList($subset);
echo $node_tree->getSelect($subset);
echo "<hr/>";

echo "<h2>Siblings: excluding node</h2>";
$subset = $node_tree->getSiblings(3, true);
echo $node_tree->getList($subset);
echo $node_tree->getSelect($subset);
echo "<hr/>";

echo "<h2>Getting node</h2>";
$element = $node_tree->getNode(5);
print_r($element);