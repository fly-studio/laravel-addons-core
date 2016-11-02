<?php

namespace Addons\Core\Facades;

use Illuminate\Support\Facades\Facade;
/**
 * @see \Addons\Core\Core
 */
class Core extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'core';
    }
}
