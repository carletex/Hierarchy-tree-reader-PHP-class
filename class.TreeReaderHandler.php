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

   	public function getNode($id) {
   		if (isset($this->raw_tree[$id])){
   			return $this->raw_tree[$id];
   		}
		return 0;
	}

   	public function getFullTree() {
		return $this->full_tree;
	}

	public function getSubTree($parent_id, $exclude_parent = false, $depth = -1, $exclude_nodes = array()) {
		if ($exclude_parent || $parent_id == 0) {
			return $this->getChilds($parent_id, 0, $exclude_nodes, $depth);
		}
		$parent = $this->getNode($parent_id);
		$tree = array();
		if ($parent) {
			$parent['level'] = 0;
			$parent['childs'] = $this->getChilds($parent_id, 1, $exclude_nodes, $depth);
			$tree[$parent_id] = $parent;
		}
		return $tree;
	}

	public function getSiblings($id, $exclude_self = false) {
		$node = $this->getNode($id);
		$exclude = array();
		if ($exclude_self) {
			$exclude[] = $node['id'] ;
		}
		return $this->getSubTree($node['parent_id'], true, 1, $exclude);
	}

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

	public function getSelect($tree = null, $selectName = 'tree', $level_marker = '-') {
		if (is_array($tree) && empty($tree)) {
			return $this->empty_tree_msg;
		}
		if (is_null($tree)) $tree = $this->full_tree;
		$html = '<select name=' . $selectName . '>';
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
			$parent_node['childs'] = $this->getChilds($parent_node['id'], 1);
			$full_tree[$parent_node['id']] = $parent_node;
		}

		return $full_tree;

	}

	private function getChilds($parent_id, $level, $exclude_nodes = array(), $depth = -1) {
		$subtree = array();
		if (isset($this->parent_tree[$parent_id]) && $depth != 0) {
			foreach ($this->parent_tree[$parent_id] as $node) {
				$skip_node = in_array($node['id'], $exclude_nodes);
				if (!$skip_node) {
					$node['level'] = $level;
					$node['childs'] = $this->getChilds($node['id'], $level + 1, $exclude_nodes, $depth - 1);
					$subtree[$node['id']] = $node;
				}

			}
		}
		return $subtree;
	}
	private function getListChildItems($node) {
		if (empty($node['childs'])) {
			return null;
		}
		$html_list = '<ul class="level-' . ($node['level'] + 1) .'">';
		foreach ($node['childs'] as $child_node) {
			$html_list .= '<li class="node-' . $child_node['id'] .'">' . $child_node['name'] . '</li>';
			$html_list .= $this->getListChildItems($child_node);
		}
		$html_list .= '</ul>';
		return $html_list;
	}

	private function getSelectItems ($node, $level_marker = '-') {
		$html_option = '<option value="' . $node['id'] . '">' . str_repeat($level_marker, $node['level']) . $node['name'] . '</option>';
		foreach ($node['childs'] as $child_node) {
			$html_option .= $this->getSelectItems($child_node, $level_marker);
		}
		return $html_option;
	}

}