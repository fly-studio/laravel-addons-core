<?php

namespace Addons\Core\Tools;

use Closure;
use ArrayAccess;
use JsonSerializable;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class TreeNode implements ArrayAccess, JsonSerializable, Jsonable, Arrayable {

	protected $items = [];
	protected $prev = null;
	protected $next = null;
	protected $parent = null;
	protected $children;

	public function __construct(array $attributes = [])
	{
		$this->children = new Collection();

		$this->attributes($attributes);
	}

	public function compare($key, $value)
	{
		return isset($this->items[$key]) ? $this->items[$key] === $value : false;
	}

	public function search($key, $defaultValue = null, $searchField = 'name')
	{
		if (empty($key)) {
			return $this;
		}

		if ($this->compare($key, $searchField)) {
			return $this;
		}

		$dot = strpos($key, '.');
		$dot === false && $dot = strlen($key);

		$segment = substr($key, 0, $dot);

		foreach($this->children as $node)
		{
			if ($node->compare($searchField, $segment))
				return $node->search(substr($key, $dot + 1), $defaultValue, $searchField);
		}

		return value($defaultValue);
	}

	public function childSet($key, array $value) {
		return $this->children[$key] = new static(Arr::except($value, 'children'));
	}

	public function childGet($key) {
		return $this->children[$key];
	}

	public function hasChildren()
	{
		return count($this->children) > 0;
	}

	public function children()
	{
		return $this->children;
	}

	public function offspring(Collection $offspring = null)
	{
		return $this->leaves($offspring);
	}

	public function leaves(Collection $leaves = null)
	{
		is_null($leaves) && $leaves = new Collection();

		foreach($this->children as $key => $node)
		{
			$leaves[$key] = $node;
			if ($node->hasChildren())
				$node->leaves($leaves);
		}

		return $leaves;
	}

	public function attributes(array $attributes = null)
	{
		if (is_null($attributes)) return $this->items;

		$this->items = Arr::except($attributes, 'children');
		return $this;
	}

	public function prev(TreeNode $node = null)
	{
		if (is_null($node)) return $this->prev;

		$this->prev = $node;
		return $this;
	}

	public function next(TreeNode $node = null)
	{
		if (is_null($node)) return $this->next;

		$this->next = $node;
		return $this;
	}

	public function parent(TreeNode $node = null)
	{
		if (is_null($node)) return $this->parent;

		$this->parent = $node;
		return $this;
	}

	public function offsetExists($key)
	{
		return $key == 'children' || array_key_exists($key, $this->items);
	}

	public function offsetGet($key)
	{
		return $key == 'children' ? $this->children : Arr::get($this->items, $key);
	}

	public function offsetSet($key, $value): void
	{
		if ($key == 'children')
			throw new \Exception('Cannot set children');
		else
			$this->items[$key] = $value;
	}

	public function offsetUnset($key)
	{
		if ($key == 'children')
			$this->children = new Collection();
		else
			unset($this->items[$key]);
	}

	public function __get($method)
	{
		if ($this->offsetExists($method))
			return $this->offsetGet($method);

		throw new BadMethodCallException(sprintf(
			'Method %s::%s does not exist.', static::class, $method
		));
	}

	public function __set($method, $value)
	{
		if ($this->offsetExists($method))
			return $this->offsetSet($method, $value);

		throw new BadMethodCallException(sprintf(
			'Method %s::%s does not exist.', static::class, $method
		));
	}

	public function fillToModel(Model $blankModel, Closure $callback = null)
	{
		$data = is_callable($callback) ? call_user_func($callback, $blankModel, $this->items) : $this->items;

		return $blankModel->setRawAttributes($data);
	}

	public function toArray()
	{
		return $this->items + ['children' => $this->children->toArray()];
	}

	public function jsonSerialize()
	{
		return $this->toArray();
	}

	public function toJson($options = 0)
	{
		return json_encode($this->jsonSerialize(), $options);
	}

}
