## PHP Hierarchy tree reader class

This PHP class handles the reading of hierarchical structures in SQL databases (using PDO) and displays HTML lists and HTML selects with the data, using arrays and recursivity.

## Public Functions

* __getNode($id)__: Returns a single node
* __getFullTree()__: Returns the full data tree on a multidimensional array
* __getSubTree($parent_id, $exclude_parent = false, $depth = -1, $exclude_nodes = array())__: Returns a subtree
* __getSiblings($id, $exclude_self = false)__: Returns the siblings of the the selected node
* __getList($tree = null, $list_class = 'tree')__: Returns a nested HTML list of the selected tree
* __getSelect($tree = null, $select_name = 'tree', $level_marker = '-')__: Returns a HTML select combo of the selected tree