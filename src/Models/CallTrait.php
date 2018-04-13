<?php
namespace Addons\Core\Models;

use Illuminate\Support\Str;
use InvalidArgumentException;

trait CallTrait {

	/**
	 * Handle dynamic method calls into the model.
	 *
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (Str::startsWith($method, ['findBy', 'findManyBy']))
		{
			$singled = $method[5] === 'y';
			$fields = explode('-', Str::snake(substr($method, $singled ? 6 : 10), '-'));
			if (count($fields) != count($parameters))
				throw new InvalidArgumentException("method '%s' needs %d parameters", $method, count($fields));

			$query = $this->newQuery();
			foreach($parameters as $key => $param)
				$query->where($fields[$key], $param);
			return $singled ? $query->first() : $query->get();
		}

		return parent::__call($method, $parameters);
	}
}
