<?php
namespace Addons\Core\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;

use Addons\Core\Models\CacheTrait;
class Model extends BaseModel {
	use CacheTrait;
	
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
}
