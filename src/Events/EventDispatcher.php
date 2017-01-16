<?php

namespace Addons\Core\Events;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

class EventDispatcher {

	/**
	 * The route group attribute stack.
	 *
	 * @var array
	 */
	protected $groupStack = [];

	public function execute($prefix, $class, $listener, $priority = 0)
	{
		$attributes = $this->mergeWithLastGroup(compact('class', 'listener', 'prefix', 'priority'));

		extract($attributes);

		if (strpos($class, '\\') !== 0)
			$class = $namespace.'\\'.$class;

		$class = ltrim($class, '\\');
		Event::listen($prefix.$class, $listener, $priority);
	}

	public function listen($events, $listener, $priority = 0)
	{
		Event::listen($events, $listener, $priority);
	}

	public function model($model, $type, $listener, $priority = 0)
	{
		$this->execute("eloquent.{$type}: ", $model, $listener, $priority);
	}

	public function models($models, $listener, $priority = 0)
	{
		foreach ($models as $action => $listener)
		{
			list($model, $type) = explode('@', $action, 2) + ['', '*'];
			$this->model($model, $type, $listener, $priority);
		}
	}

	public function controller($controller, $listener, $type = 'after', $priority = 0)
	{
		if (strpos($controller, '@') === false) $controller .= '@*';
		$this->execute("controller.{$type}: ", $controller, $listener, $priority);
	}

	public function controllers($controllers, $type = 'after', $priority = 0)
	{
		foreach ($controllers as $controller => $listener)
			$this->controller($controller, $listener, $type, $priority);
	}

	public function group($attributes, Closure $callback)
	{
		$this->updateGroupStack($attributes);

		call_user_func($callback, $this);

		array_pop($this->groupStack);
	}

	/**
	 * Update the group stack with the given attributes.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	protected function updateGroupStack(array $attributes)
	{
		if (! empty($this->groupStack)) {
			$attributes = $this->mergeGroup($attributes, end($this->groupStack));
		}

		$this->groupStack[] = $attributes;
	}

	/**
	 * Merge the given array with the last group stack.
	 *
	 * @param  array  $new
	 * @return array
	 */
	public function mergeWithLastGroup($new)
	{
		return $this->mergeGroup($new, end($this->groupStack));
	}

	/**
	 * Merge the given group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return array
	 */
	public static function mergeGroup($new, $old)
	{
		$new['namespace'] = static::formatUsesPrefix($new, $old);

		return array_merge_recursive(Arr::except($old, ['namespace']), $new);
	}

	/**
	 * Determine if the eventer currently has a group stack.
	 *
	 * @return bool
	 */
	public function hasGroupStack()
	{
		return ! empty($this->groupStack);
	}

	/**
	 * Get the current group stack for the eventer.
	 *
	 * @return array
	 */
	public function getGroupStack()
	{
		return $this->groupStack;
	}

	/**
	 * Format the uses prefix for the new group attributes.
	 *
	 * @param  array  $new
	 * @param  array  $old
	 * @return string|null
	 */
	protected static function formatUsesPrefix($new, $old)
	{
		if (isset($new['namespace'])) {
			return isset($old['namespace'])
					? trim($old['namespace'], '\\').'\\'.trim($new['namespace'], '\\')
					: trim($new['namespace'], '\\');
		}

		return isset($old['namespace']) ? $old['namespace'] : null;
	}

}
