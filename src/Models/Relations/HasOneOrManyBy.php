<?php

namespace Addons\Core\Models\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class HasOneOrManyBy extends Relation {

	protected $callable;
	private $parentKeys = [];

	public function __construct(Model $parent, callable $callable, $foreignKey, $localKey)
	{
		$this->callable = $callable;
		$this->parent = $parent;
		$this->foreignKey = $foreignKey;
		$this->localKey = $localKey;

		$this->addConstraints();
	}

	public function setCallable(callable $callable)
	{
		$this->callable = $callable;
	}

	public function addConstraints()
	{
		$this->parentKeys = [$this->getParentKey()];
	}

	public function addEagerConstraints(array $models)
	{
		$this->parentKeys = $this->getKeys($models, $this->localKey);
	}

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function get($columns = ['*'])
	{
		return call_user_func($this->callable, $this->parentKeys, $columns);
	}

	/**
	 * Get the underlying query for the relation.
	 *
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function getQuery()
	{
		throw new \Exception('No database query in HasOneOrManyBy');
	}


	/**
	 * Get the related model of the relation.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getRelated()
	{
		throw new \Exception('No related in HasOneOrManyBy');
	}

	/**
	 * Run a raw update against the base query.
	 *
	 * @param  array  $attributes
	 * @return int
	 */
	public function rawUpdate(array $attributes = [])
	{
		throw new \Exception('No database query in HasOneOrManyBy');
	}

	/**
	 * Get the name of the related model's "updated at" column.
	 *
	 * @return string
	 */
	public function relatedUpdatedAt()
	{
		throw new \Exception('No related in HasOneOrManyBy');
	}

	/**
	 * Match the eagerly loaded results to their many parents.
	 *
	 * @param  array   $models
	 * @param  \Illuminate\Database\Eloquent\Collection  $results
	 * @param  string  $relation
	 * @param  string  $type
	 * @return array
	 */
	protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
	{
		$dictionary = $this->buildDictionary($results);

		// Once we have the dictionary we can simply spin through the parent models to
		// link them up with their children using the keyed dictionary to make the
		// matching very convenient and easy work. Then we'll just return them.
		foreach ($models as $model) {
			if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
				$model->setRelation(
					$relation, $this->getRelationValue($dictionary, $key, $type)
				);
			}
		}

		return $models;
	}

	/**
	 * Get the value of a relationship by one or many type.
	 *
	 * @param  Collection   $dictionary
	 * @param  string  $key
	 * @param  string  $type
	 * @return mixed
	 */
	protected function getRelationValue(Collection $dictionary, $key, $type)
	{
		$value = $dictionary[$key];

		return $type === 'one' ? reset($value) : new $dictionary($value);
	}

	/**
	 * Build model dictionary keyed by the relation's foreign key.
	 *
	 * @param  \Illuminate\Database\Eloquent\Collection  $results
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	protected function buildDictionary(Collection $results)
	{
		$foreign = $this->getForeignKeyName();

		return $results->mapToDictionary(function ($result) use ($foreign) {
			return [$result->{$foreign} => $result];
		});
	}

	/**
	 * Get the key for comparing against the parent key in "has" query.
	 *
	 * @return string
	 */
	public function getExistenceCompareKey()
	{
		return $this->getQualifiedForeignKeyName();
	}

	/**
	 * Get the key value of the parent's local key.
	 *
	 * @return mixed
	 */
	public function getParentKey()
	{
		return $this->parent->getAttribute($this->localKey);
	}

	/**
	 * Get the fully qualified parent key name.
	 *
	 * @return string
	 */
	public function getQualifiedParentKeyName()
	{
		return $this->parent->qualifyColumn($this->localKey);
	}

	/**
	 * Get the plain foreign key.
	 *
	 * @return string
	 */
	public function getForeignKeyName()
	{
		$segments = explode('.', $this->getQualifiedForeignKeyName());

		return end($segments);
	}

	/**
	 * Get the foreign key for the relationship.
	 *
	 * @return string
	 */
	public function getQualifiedForeignKeyName()
	{
		return $this->foreignKey;
	}

	/**
	 * Get the local key for the relationship.
	 *
	 * @return string
	 */
	public function getLocalKeyName()
	{
		return $this->localKey;
	}

}
