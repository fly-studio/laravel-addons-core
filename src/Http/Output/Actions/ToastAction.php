<?php

namespace Addons\Core\Http\Output\Actions;

use Addons\Core\Http\Output\ActionFactory;
use Addons\Core\Contracts\Http\Output\Action;

class ToastAction extends Action {

    protected $timeout;

    public function __construct(int $timeout = 1500)
    {
        $this->timeout = $timeout;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return [
            ActionFactory::TOAST, $this->timeout
        ];
    }

}
