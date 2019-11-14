<?php

namespace Addons\Core\Models\Relations;

use Illuminate\Database\Eloquent\Collection;

class HasManyBy extends HasOneOrManyBy {

	public function getResults()
	{
		return ! is_null($this->getParentKey())
				? $this->getEager()
				: $this->parent->newCollection();
	}

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return array
	 */
	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model) {
			$model->setRelation($relation, $this->parent->newCollection());
		}

		return $models;
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  \Illuminate\Database\Eloquent\Collection  $results
	 * @param  string  $relation
	 * @return array
	 */
	public function match(array $models, Collection $results, $relation)
	{
		return $this->matchOneOrMany($models, $results, $relation, 'many');
	}

}
