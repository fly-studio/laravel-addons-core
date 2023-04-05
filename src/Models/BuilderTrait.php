<?php

namespace Addons\Core\Models;

use Addons\Core\Models\Builder;
use Addons\Core\Models\Relations\HasOneBy;
use Addons\Core\Models\Relations\HasManyBy;
use Illuminate\Database\ConnectionInterface;

trait BuilderTrait {

    protected $connectionInstance = null;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function setConnectionInstance(ConnectionInterface $connection)
    {
        $this->connectionInstance = $connection;
        return $this;
    }

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return $this->connectionInstance ?? static::resolveConnection($this->getConnectionName());
    }

    /**
     * Define a one-to-one relationship by a callable.
     *
     * @param  callable  $callable
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Addons\Core\Models\Relations\HasOneBy\HasOneBy
     */
    public function hasOneBy(callable $callable, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?: $this->getKeyName();
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        return new HasOneBy($this, $callable, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship by a callable.
     *
     * @param  callable  $callable
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return \Addons\Core\Models\Relations\HasOneBy\HasManyBy
     */
    public function hasManyBy(callable $callable, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?: $this->getKeyName();
        $foreignKey = $foreignKey ?: $this->getForeignKey();

        return new HasManyBy($this, $callable, $foreignKey, $localKey);
    }

}
