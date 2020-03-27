<?php

namespace Addons\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Addons\Core\Http\SerializableRequest;
use Addons\Core\Http\SerializableResponse;
use Addons\Core\Contracts\Events\ControllerEvent as ControllerEventContract;

class ControllerEvent implements ControllerEventContract
{
	use InteractsWithSockets;
    use SerializesModels;

    protected $controller;
    protected $method;
    protected $request;
    protected $response;

    /**
     * Create a new controller event instance.
     *
     * @param Illuminate\Routing\Controller $controller
     * @param string $method
     * @param Illuminate\Http\Request $request       default for app('request')
     * @param Illuminate\Http\Response|Illuminate\View\View $response
     *
     * @return void
     */
    public function __construct(Controller $controller, $method, Request $request = null, $response = null)
    {
        $request = is_null($request) ? app('request') : $request;

        $this->controller = $controller;
        $this->method = $method;
        $this->request = (new SerializableRequest($request))->data();
        $this->response = (new SerializableResponse($response))->data();
    }

	public function getController()
    {
        return $this->controller;
    }

    public function getControllerName()
    {
        return get_class($this->controller);
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getRequest()
    {
        return (new SerializableRequest)->invoke($this->request);
    }

    public function getSerializedRequest()
    {
        return $this->request;
    }

    public function getRoute($param = null)
    {
        $request = $this->getRequest();
        return $request->route($param);
    }

    public function getResponse()
    {
        return (new SerializableResponse)->invoke($this->response);
    }

    public function getSerializedResponse()
    {
        return $this->response;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('controller');
    }
}
