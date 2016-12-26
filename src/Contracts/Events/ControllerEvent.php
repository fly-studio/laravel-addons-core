<?php

namespace Addons\Core\Contracts\Events;

use ReflectionClass;
use ReflectionProperty;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Addons\Core\Http\SerializableRequest;
use Addons\Core\Http\SerializableResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class ControllerEvent
{
    use InteractsWithSockets;
    use SerializesAndRestoresModelIdentifiers;

    private $controllerObject;
    private $methodName;
    private $request;
    private $response;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($controllerObject, $methodName, $response = null)
    {
        $request = app('request');
        $this->controllerObject = $controllerObject;
        $this->methodName = $methodName;
        $this->request = (new SerializableRequest($request))->data();
        $this->response = (new SerializableResponse($response))->data();
    }

	public function getClass()
    {
        return $this->controllerObject;
    }

    public function getClassName()
    {
        return get_class($this->controllerObject);
    }

    public function getMethod()
    {
        return $this->methodName;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getRequest()
    {
        static $request;
        empty($request) && $request = (new SerializableRequest)->invoke($this->request);

        return $request;
    }

    public function getRoute($param = null)
    {
        $request = $this->getRequest();
        return $request->route($param);
    }

    public function getResponse()
    {
        static $response;
        empty($response) && $response = (new SerializableResponse)->invoke($this->response);
        
        return $response;
    }
}