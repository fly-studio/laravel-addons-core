<?php

namespace Addons\Core\Contracts\Events;

interface ControllerEvent
{
    public function getController();
    public function getControllerName();
    public function getMethod();
    public function getRequest();
    public function getResponse();
    public function getRoute($param = null);

}