<?php
namespace Addons\Core\Http;
use Illuminate\Http\Request;

class SerializableRequest implements \Serializable {

	private $request;

	public function __construct(Request $request = null)
	{
		$this->request = $request;
	}

	/**
     * Serializes the request
     *
     * @return string|null
     * @link http://php.net/manual/en/serializable.serialize.php
     */
    public function serialize()
    {
    	return serialize($this->data());
    }

    /**
     * Unserializes the request.
     *
     *
     * @param string $serialized
     *
     * @throws Exception
     * @link http://php.net/manual/en/serializable.unserialize.php
     */
    public function unserialize($serialized)
    {
    	$data = unserialize($serialized);
    	return $this->invoke($data);
    }

    public function data()
    {
    	return [
    		'query' => $this->request->query->all(),
            'request' => $this->request->request->all(),
            'attributes' => $this->request->attributes->all(),
            'cookies' => $this->request->cookies->all(),
            'files' => $this->request->files->all(),
            'server' => $this->request->server->all(),
            'content' => $this->request->content,
    	];
    }

    public function invoke($data)
    {
    	$request = Request::createFromBase(new SymfonyRequest($data['query'], $data['request'], $data['attributes'], $data['cookies'], $data['files'], $data['server'], $data['content']));

        $router = app(\Illuminate\Routing\Router::class);
        $routes = $router->getRoutes();
        $route = $routes->match($request);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return $request;
    }
}