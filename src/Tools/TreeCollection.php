<?php

namespace Addons\Core\Tools;

use Addons\Core\Tools\TreeNode;
use Illuminate\Support\Collection;

class TreeCollection extends Collection {

	protected $root;

	public function search($key, $defaultValue = null, $searchField = 'name')
	{
		return $this->offsetExists($key) ? $this->offsetGet($key) :
			(empty($this->root) ? value($defaultValue) : $this->root->search($key, $defaultValue, $searchField));
	}

	public static function make($tree = [])
	{
		$self = new static();

		$node = new TreeNode();
		$self->setRoot($node);
		$self->makeNodes($tree, $node);

		return $self;
	}

	public function makeNodes(array $tree, TreeNode $node)
	{
		$prev = null;
		foreach($tree as $key => $value)
		{
			$children = $value['children'] ?? [];


			$n = $node->childSet($key, $value);
			$n->parent($node)->prev($prev);

			$this[$key] = $n;
			!empty($prev) && $prev->next($n);
			$prev = $n;

			if (!empty($children))
				$this->makeNodes($children, $node->childGet($key));
		}
	}

	public function setRoot(TreeNode $node)
	{
		$this->root = $node;
	}

	public function root()
	{
		return $this->root;
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value): void
	{
		$value = $value instanceof TreeNode ? $value : new TreeNode($value);

		if (is_null($key)) {
			$this->items[] = $value;
		} else {
			$this->items[$key] = $value;
		}
	}

}
