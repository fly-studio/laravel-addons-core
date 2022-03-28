<?php

namespace Addons\Core\Http\Output\Actions;

use Addons\Core\Http\Output\ActionFactory;
use Addons\Core\Contracts\Http\Output\Action;

class BackAction extends Action {

    public function __construct()
    {

    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return [
            ActionFactory::BACK,
        ];
    }
}
