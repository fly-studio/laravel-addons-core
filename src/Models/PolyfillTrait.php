<?php
namespace Addons\Core\Models;

use Illuminate\Support\Str;
trait PolyfillTrait{

	protected $originalCastTypes = ['int','integer','real','float','double','string','bool','boolean','object','array','json','collection','date','datetime','timestamp'];

	public function insertUpdate(array $attributes)
	{
		$this->fill($attributes);

		if ($this->usesTimestamps()) {
			$this->updateTimestamps();
		}

		$attributes = $this->getAttributes();

		$query = $this->newBaseQueryBuilder();
		$processor = $query->getProcessor();
		$grammar = $query->getGrammar();

		$table = $grammar->wrapTable($this->getTable());
		$keyName = $this->getKeyName();
		$columns = $grammar->columnize(array_keys($attributes));
		$insertValues = $grammar->parameterize($attributes);

		$updateValues = [];

		if ($this->primaryKey !== null) {
			$updateValues[] = "{$grammar->wrap($keyName)} = LAST_INSERT_ID({$keyName})";
		}

		foreach ($attributes as $k => $v) {
			$updateValues[] = sprintf("%s = '%s'", $grammar->wrap($k), $v);
		}

		$updateValues = join(',', $updateValues);

		$sql = "insert into {$table} ({$columns}) values ({$insertValues}) on duplicate key update {$updateValues}";

		$id = $processor->processInsertGetId($query, $sql, array_values($attributes));

		$this->setAttribute($keyName, $id);

		return $this;
	}

	/**
	 * Cast an attribute to a native PHP type.
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	protected function castAttribute($key, $value)
	{
		$type = $this->getCastType($key);
		if (!empty($type) && !in_array($type, $this->originalCastTypes))
		{
			$method = 'as'.Str::studly($type);
			if (method_exists($this, $method))
				return call_user_func([$this, $method], $value);
		}
		return parent::castAttribute($key, $value);
	}

	/**
	 * Convert the model's attributes to an array.
	 *
	 * @return array
	 */
	public function attributesToArray()
	{
		$data = parent::attributesToArray();
		foreach ($this->getCasts() as $key => $type)
		{
			if (!empty($type) && !in_array($type, $this->originalCastTypes) && isset($data[$type]))
			{
				$method = Str::camel($type).'ToArray';
				if (method_exists($this, $method))
					$data[$key] = call_user_func([$this, $method], $data[$key]);
			}
		}
		return $data;
	}

	/**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
		$type = $this->hasCast($key) ? $this->getCastType($key) : null;
		if (!empty($type) && !$this->hasSetMutator($key)  && !in_array($type, $this->originalCastTypes))
		{
			$method = 'from'.Str::studly($type);
			if (method_exists($this, $method))
				$value = call_user_func([$this, $method], $value);
		}
		return parent::setAttribute($key, $value);
    }

}