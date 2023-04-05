<?php

namespace Addons\Core\Models;

use Illuminate\Support\Str;
use Addons\Core\Models\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Arrayable;

trait PolyfillTrait {

    public static function insertUpdate(array $attributes)
    {
        $model = new static;
        $model->fill($attributes);

        if ($model->usesTimestamps())
            $model->updateTimestamps();


        $attributes = $model->getAttributes();

        $query = $model->newBaseQueryBuilder();
        $processor = $query->getProcessor();
        $grammar = $query->getGrammar();

        $table = $grammar->wrapTable($model->getTable());
        $keyName = $model->getKeyName();
        $columns = $grammar->columnize(array_keys($attributes));
        $insertValues = $grammar->parameterize($attributes);

        $updateValues = [];

        if ($model->getKeyName() !== null)
            $updateValues[] = "{$grammar->wrap($keyName)} = LAST_INSERT_ID({$keyName})";


        foreach ($attributes as $k => $v)
            $updateValues[] = sprintf("%s = '%s'", $grammar->wrap($k), $v);


        $updateValues = join(',', $updateValues);

        $sql = "insert into {$table} ({$columns}) values ({$insertValues}) on duplicate key update {$updateValues}";

        $id = $processor->processInsertGetId($query, $sql, array_values($attributes));

        $model->setAttribute($keyName, $id);

        return $model;
    }

    /**
     * 可以在Model转换数组的时候，补充casts字段、appends字段、relations关系，以及修改时间格式
     *
     * @example
     * toArray([
     * 	'casts' => [
     * 		'other_cast_field1' => 'datetime'
     * 		'other_cast_field2' => 'datetime'
     * 	],
     * 	'appends' => [
     * 		'other_append_field1', 'other_append_field2'
     * 	],
     * 	'relations' => [
     * 		'other_relation1',
     * 		'other_relation2',
     * 	],
     * 	'dataFormat' => \DateTime::W3C,
     * ])
     * @param  array  $options
     * @return array
     */
    public function toArray(array $options = [])
    {
        $options += [
            'casts' => [],
            'appends' => [],
            'relations' => [],
            'dateFormat' => null,
        ];

        $data = $this->getArrayableAttributes();

        $data = $this->addMutatedAttributesToArray(
            $data, $mutatedAttributes = $this->getMutatedAttributes()
        );

        if ($options['casts'] !== false && is_array($options['casts']))
        {
            foreach ($this->getCasts() + $options['casts'] as $key => $value) {

                if (! isset($data[$key]) || in_array($key, $mutatedAttributes)) {
                    continue;
                }

                $data[$key] = $this->castAttribute(
                    $key, $data[$key]
                );

                if ($data[$key] &&
                    ($value === 'date' || $value === 'datetime')) {
                    $data[$key] = $this->serializeDate($data[$key]);
                }

                if ($data[$key] && $this->isCustomDateTimeCast($value)) {
                    $data[$key] = $data[$key]->format(explode(':', $value, 2)[1]);
                }

                // execute xxToArray
                if (isset($data[$key]) && !empty($value) && !in_array($value, $this->originalCastTypes))
                {
                    $method = Str::camel($value).'ToArray';
                    if (method_exists($this, $method))
                        $data[$key] = call_user_func([$this, $method], $data[$key], $key, $value);
                }

                if ($data[$key] instanceof Arrayable) {
                    $data[$key] = $data[$key]->toArray($options);
                }
            }
        }

        if ($options['appends'] !== false && is_array($options['appends']))
        {
            foreach ($this->getArrayableAppends() + array_combine($options['appends'], $options['appends'])  as $key) {
                $data[$key] = $this->mutateAttributeForArray($key, null);
            }
        }

        $dateFormat = !empty($options['dateFormat']) ? $options['dateFormat'] : $this->getDateFormat();

        foreach ($this->getDates() as $key) {
            if (! isset($data[$key]) || empty($data[$key])) {
                continue;
            }

            $data[$key] = $this->asDateTime($data[$key])->format($dateFormat);
        }

        $attributes = [];

        if ($options['relations'] !== false && is_array($options['relations']))
        {
            if (!empty($options['relations']))
                $this->loadMissing($options['relations']);

            foreach ($this->getArrayableRelations() as $key => $value) {

                if ($value instanceof Model) {
                    $relation = $value->toArray($options);
                } else if ($value instanceof Arrayable) {
                    $relation = $value->toArray($options);
                } else if (is_null($value)) {
                    $relation = $value;
                }

                if (static::$snakeAttributes) {
                    $key = Str::snake($key);
                }

                // If the relation value has been set, we will set it on this attributes
                // list for returning. If it was not arrayable or null, we'll not set
                // the value on the array because it is some type of invalid value.
                if (isset($relation) || is_null($value)) {
                    $attributes[$key] = $relation;
                }

                unset($relation);
            }
        }

        return $data + $attributes;
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

}
