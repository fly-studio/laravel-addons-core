<?php
namespace Addons\Core\Controllers;

use Addons\Core\Controllers\OutputTrait;
use Addons\Core\Controllers\PermissionTrait;
use Addons\Core\Events\ControllerEvent;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
	use PermissionTrait, OutputTrait;
	
	public function callAction($method, $parameters)
	{
		//event before
		event('controller.before: '.get_class($this).'@'.$method, [new ControllerEvent($this, $method)]);
		// check current user's permissions
		if ($this->addons) $this->checkPermission($method);
		
		$response = call_user_func_array([$this, $method], $parameters);
		//event successful
		event('controller.after: '.get_class($this).'@'.$method, [new ControllerEvent($this, $method, null, $response)]);
		return $response;
	}

}