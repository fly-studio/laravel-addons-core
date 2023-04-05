<?php

namespace Addons\Core\Models;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder as Base;

class Builder extends Base {

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

            $columns = ['*'];
            if (count($parameters) == count($fields) + 1)
                $columns = array_pop($parameters);

            if (count($fields) != count($parameters))
                throw new InvalidArgumentException("method '%s' needs %d parameters", $method, count($fields));

            foreach($parameters as $key => $param)
                $this->where($fields[$key], $param);

            return $singled ? $this->first($columns) : $this->get($columns);
        }

        return parent::__call($method, $parameters);
    }
}
