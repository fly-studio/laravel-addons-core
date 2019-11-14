<?php

namespace Addons\Core\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection as BaseCollection;

class Collection extends BaseCollection {

	/**
	 * 可以在Collection(Model)转换数组的时候，给Model补充casts字段、appends字段、relations关系，以及修改时间格式
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
	 * 		'only_show_field1',
	 * 		'only_show_field2',
	 * 	],
	 * 	'dataFormat' => DateTime::W3C,
	 * ]);
	 *
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

		if (is_array($options['relations']) && !empty($options['relations']))
			$this->loadMissing($options['relations']);

		return array_map(function ($value) use ($options) {
			return $value instanceof Arrayable ? $value->toArray($options) : $value;
		}, $this->items);
	}

}
