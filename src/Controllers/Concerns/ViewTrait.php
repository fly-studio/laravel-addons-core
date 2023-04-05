<?php

namespace Addons\Core\Controllers\Concerns;

use Auth, URL;

trait ViewTrait {

    protected $viewData = [];

    public function __set($key, $value)
    {
        $this->viewData[$key] = $value;
    }

    public function __get($key)
    {
        return $this->viewData[$key];
    }

    public function __isset($key)
    {
        return isset($this->viewData[$key]);
    }

    public function __unset($key)
    {
        unset($this->viewData[$key]);
    }

    protected function view($filename, $data = [])
    {
        $this->viewData['_url'] = URL::current();
        $this->viewData['_user'] = Auth::user();

        return view($filename, $data)->with($this->viewData);
    }

}
