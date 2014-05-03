<?php

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

	/*
	 * Constructor function
	 *
	 * PDO $db: A pdo instance connected to the database
	 * string $table_name: The name of the hierarchy table
	 * string $id_key: The table key for the element id
	 * string $parentid_key: The table key for the element parent id
	 * string $name_key: The table key for the element name
	 */

	function __construct($db, $table_name, $id_key, $parentid_key, $name_key) {
		$this->db = $db;
		$this->table_name = $table_name;
		$this->id_key = $id_key;
		$this->parentid_key = $parentid_key;
		$this->name_key = $name_key;
		$this->full_tree = $this->getTreeFromDB();
   	}

   	/*
	 * Public functions
 	 */

   	/*
	 * Returns a single node
	 *
	 * int $id: the requested node id
	 */
   	public function getNode($id) {
   		if (isset($this->raw_tree[$id])){
   			return $this->raw_tree[$id];
   		}
		return 0;
	}

	/*
	 * Returns the full data tree on a multidimensional array
	 */
   	public function getFullTree() {
		return $this->full_tree;
	}

	/*
	 * Returns a subtree
	 *
	 * int $parent_id: The top parent id
	 * bool $exclude_parent:
	 * 		False(default): starts the tree by the parent
	 * 		True: the tree starts on the children.
	 * int $depth: The depth of the tree. It starts counting by the parent node. -1 for unlimited depth
	 * int array $exclude_nodes: id's of the nodes to exclude in the subtree
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

	/*
	 * Returns the siblings of the the selected node
	 *
	 * int $id: The node id
	 * bool $exclude_self:
	 * 		False(default): Includes all the nodes in the level
	 * 		True: Exclude the selected node
	 */
	public function getSiblings($id, $exclude_self = false) {
		$node = $this->getNode($id);
		$exclude = array();
		if ($exclude_self) {
			$exclude[] = $node['id'] ;
		}
		return $this->getSubTree($node['parent_id'], true, 1, $exclude);
	}

	/*
	 * Returns a nested HTML list of the selected tree
	 *
	 * array $tree: The tree to generate the list. Uses the full tree by default
	 * string $list_class: Custom class for the list
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

	/*
	 * Returns a HTML select combo of the selected tree
	 *
	 * array $tree: The tree to generate the list. Uses the full tree by default
	 * string $select_name: Custom class for the combo
	 * string $level_marker: Custom marker to repeat and identify each level
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

	/*
	 * Private functions
 	 */

	/*
	 * Returns the full tree on a multidimensional array
	 *
	 * Also stores the following class properties:
	 * 		$raw_tree: Tree keyed by id on a single-dimensional array
	 *		$parent_tree: Tree grouped by parent
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

	/*
	 * Returns all the children of the selected node
	 *
	 * int $parent_id: The parent id
	 * int $level: The level of the current nodes
	 * int $depth: The depth of the tree. It starts counting by the parent node. -1 for unlimited depth
	 * int array $exclude_nodes: id's of the nodes to exclude in the subtree
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

	/*
	 * Helper function for getList(). Returns the formatted children of the selected node
	 *
	 * int $node: The parent node
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

	/*
	 * Helper function for getSelect(). Returns the formatted children of the selected node
	 *
	 * int $node: The parent node
	 * string $level_marker: Custom marker to repeat and identify each level
	 */
	private function getSelectItems ($node, $level_marker = '-') {
		$html_option = '<option value="' . $node['id'] . '">' . str_repeat($level_marker, $node['level']) . $node['name'] . '</option>';
		foreach ($node['children'] as $child_node) {
			$html_option .= $this->getSelectItems($child_node, $level_marker);
		}
		return $html_option;
	}

}