<?php
/**
* This PHP class handles the reading of hierarchical structures in SQL databases (using PDO)
* and displays HTML lists and HTML selects with the data, using arrays and recursivity.
*
* @author Carlos Sánchez (carletex) <info@carletex.com>
* @link https://github.com/carletex/Hierarchy-tree-reader-PHP-class
*/

class TreeReaderHandler
{
	// Database variables
	protected $db;
	protected $table_name;
	protected $id_key;
	protected $parentid_key;
	protected $name_key;

	// Full hierarchical tree on a multidimensional array
	protected $full_tree = array();

	// Tree grouped by parent
	protected $parent_tree = array();

	// Tree keyed by id on a single-dimensional array
	protected $raw_tree = array();

	public $empty_tree_msg = 'Empty tree';

	/**
	 * Constructor function
	 *
	 * @param PDO $db A pdo instance connected to the database
	 * @param string $table_name The name of the hierarchy table
	 * @param string $id_key The table key for the element id
	 * @param string $parentid_key The table key for the element parent id
	 * @param string $name_key The table key for the element name
	 */

	function __construct($db, $table_name, $id_key, $parentid_key, $name_key) {
		$this->db = $db;
		$this->table_name = $table_name;
		$this->id_key = $id_key;
		$this->parentid_key = $parentid_key;
		$this->name_key = $name_key;
		$this->full_tree = $this->getTreeFromDB();
   	}

   	/**
	 * Public functions
 	 */

   	/**
	 * Returns a single node
	 *
	 * @param int $id the requested node id
	 * @return array|0 The node with the id provided
	 */
   	public function getNode($id) {
   		if (isset($this->raw_tree[$id])){
   			return $this->raw_tree[$id];
   		}
		return 0;
	}

	/**
	 * Returns the full data tree on a multidimensional array
	 *
	 * @return array The multidimensional array with all the tree data
	 */
   	public function getFullTree() {
		return $this->full_tree;
	}

	/**
	 * Returns a subtree
	 *
	 * @param int $parent_id The top parent id
	 * @param bool $exclude_parent
	 * 			   False(default): starts the tree by the parent
	 * 			   True: the tree starts on the children.
	 * @param int $depth The depth of the tree. It starts counting by the parent node. -1 for unlimited depth
	 * @param int array $exclude_nodes id's of the nodes to exclude in the subtree
	 * @return array The array with the subtree data
	 */
	public function getSubTree($parent_id, $exclude_parent = false, $depth = -1, $exclude_nodes = array()) {
		if ($exclude_parent || $parent_id == 0) {
			return $this->getChildren($parent_id, 0, $depth, $exclude_nodes);
		}
		$parent = $this->getNode($parent_id);
		$tree = array();
		if ($parent) {
			$parent['level'] = 0;
			$parent['children'] = $this->getChildren($parent_id, 1, $depth, $exclude_nodes);
			$tree[$parent_id] = $parent;
		}
		return $tree;
	}

	/**
	 * Returns the siblings of the the selected node
	 *
	 * @param int $id The node id
	 * @param bool $exclude_self
	 * 		       False(default): Includes all the nodes in the level
	 * 		       True: Exclude the selected node
	 * @return array The tree with the siblings of the provided node
	 */
	public function getSiblings($id, $exclude_self = false) {
		$node = $this->getNode($id);
		$exclude = array();
		if ($exclude_self) {
			$exclude[] = $node['id'] ;
		}
		return $this->getSubTree($node['parent_id'], true, 1, $exclude);
	}

	/**
	 * Returns a nested HTML list of the selected tree
	 *
	 * @param array $tree The tree to generate the list. Uses the full tree by default
	 * @param string $list_class Custom class for the list
	 * @return string The HTML nested list with the tree data
	 */
	public function getList($tree = null, $list_class = 'tree') {
		if (is_array($tree) && empty($tree)) {
			return $this->empty_tree_msg;
		}
		if (is_null($tree)) $tree = $this->full_tree;
		$html = '<ul class="' . $list_class .' level-0">';
		foreach ($tree as $node) {
			$html .= '<li class="node-' . $node['id'] .'">' . $node['name'] . '</li>';
			$html .= $this->getListChildItems($node);
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * Returns a HTML select combo of the selected tree
	 *
	 * @param array $tree The tree to generate the list. Uses the full tree by default
	 * @param string $select_name Custom class for the combo
	 * @param string $level_marker Custom marker to repeat and identify each level
	 * @return string The HTML select combo with the tree data
	 */
	public function getSelect($tree = null, $select_name = 'tree', $level_marker = '-') {
		if (is_array($tree) && empty($tree)) {
			return $this->empty_tree_msg;
		}
		if (is_null($tree)) $tree = $this->full_tree;
		$html = '<select name=' . $select_name . '>';
		$html .= '<option value="0">-- Select Item --</option>';
		foreach ($tree as $node) {
			$html .= $this->getSelectItems($node, $level_marker);
		}
		$html .= '</select>';
		return $html;
	}

	/**
	 * Returns the full path of a node
	 *
	 * @param int $id The node id
	 * @param string $separator Custom separator printed between nodes
	 * @return string The full path of the provided node id
	 */

	public function getPath($id, $separator = '>'){
		$node = $this->getNode($id);
		if (!$node) {
			return 0;
		}
		if ($node['parent_id']) {
			return $this->getPath($node['parent_id'], $separator) . ' ' . $separator . ' ' . $node['name'];
		}
		return $node['name'];
	}

	/**
	 * Private functions
 	 */

	/**
	 * Returns the full tree on a multidimensional array
	 *
	 * Also stores the following class properties:
	 * 		$raw_tree: Tree keyed by id on a single-dimensional array
	 *		$parent_tree: Tree grouped by parent
	 *
	 * @return array The multidimensional array with all the tree data
	 */

	private function getTreeFromDB() {
   		$query = $this->db->prepare("SELECT $this->id_key as id, $this->name_key as name , $this->parentid_key as parent_id
   									 FROM $this->table_name");
		$query->execute();
		$query_tree = $query->fetchAll(PDO::FETCH_ASSOC);

		$parent_tree = array();
		$raw_tree = array();
		$full_tree = array();

		foreach ($query_tree as $node) {
			$parent_tree[$node['parent_id']][$node['id']] = $node;
			$raw_tree[$node['id']] = $node;
		}

		$this->parent_tree = $parent_tree;
		$this->raw_tree = $raw_tree;

		foreach ($parent_tree['0'] as $parent_node) {
			$parent_node['level'] = 0;
			$parent_node['children'] = $this->getChildren($parent_node['id'], 1);
			$full_tree[$parent_node['id']] = $parent_node;
		}

		return $full_tree;

	}

	/**
	 * Returns all the children of the selected node
	 *
	 * @param int $parent_id The parent id
	 * @param int $level The level of the current nodes
	 * @param int $depth The depth of the tree. It starts counting by the parent node. -1 for unlimited depth
	 * @param int array $exclude_nodes id's of the nodes to exclude in the subtree
	 * @return array The tree data with the children of the provided node id
	 */

	private function getChildren($parent_id, $level, $depth = -1, $exclude_nodes = array()) {
		$subtree = array();
		if (isset($this->parent_tree[$parent_id]) && $depth != 0) {
			foreach ($this->parent_tree[$parent_id] as $node) {
				$skip_node = in_array($node['id'], $exclude_nodes);
				if (!$skip_node) {
					$node['level'] = $level;
					$node['children'] = $this->getChildren($node['id'], $level + 1, $depth - 1, $exclude_nodes);
					$subtree[$node['id']] = $node;
				}

			}
		}
		return $subtree;
	}

	/**
	 * Helper function for getList(). Returns the formatted children of the selected node
	 *
	 * @param int $node The parent node
	 * @return string The HTML nested list with the children tree data of the provided node
	 */
	private function getListChildItems($node) {
		if (empty($node['children'])) {
			return null;
		}
		$html_list = '<ul class="level-' . ($node['level'] + 1) .'">';
		foreach ($node['children'] as $child_node) {
			$html_list .= '<li class="node-' . $child_node['id'] .'">' . $child_node['name'] . '</li>';
			$html_list .= $this->getListChildItems($child_node);
		}
		$html_list .= '</ul>';
		return $html_list;
	}

	/**
	 * Helper function for getSelect(). Returns the formatted children of the selected node
	 *
	 * @param int $node The parent node
	 * @param string $level_marker Custom marker to repeat and identify each level
	 * @return string The HTML select options with the children tree data of the provided node
	 */
	private function getSelectItems ($node, $level_marker = '-') {
		$html_option = '<option value="' . $node['id'] . '">' . str_repeat($level_marker, $node['level']) . $node['name'] . '</option>';
		foreach ($node['children'] as $child_node) {
			$html_option .= $this->getSelectItems($child_node, $level_marker);
		}
		return $html_option;
	}

}