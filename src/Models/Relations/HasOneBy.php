<?php

namespace Addons\Core\Models\Relations;

use Illuminate\Database\Eloquent\Collection;

class HasOneBy extends HasOneOrManyBy {

    public function getResults()
    {
        if (is_null($this->getParentKey())) {
            return null;
        }

        return $this->getEager()->first();
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
            $model->setRelation($relation, null);
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  array  $models
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @param  string  $relation
     * @return array
     */
    public function match(array $models, Collection $results, $relation)
    {
        return $this->matchOneOrMany($models, $results, $relation, 'one');
    }
}
