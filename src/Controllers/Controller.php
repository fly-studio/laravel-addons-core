<?php
namespace Addons\Core\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\Role;
//Facades
use Addons\Core\Controllers\OutputTrait;
use Addons\Core\Controllers\PermissionTrait;
use Addons\Core\Events\BeforeControllerEvent;
use Addons\Core\Events\ControllerEvent;

class Controller extends BaseController {
	use PermissionTrait, OutputTrait;
	
	public function callAction($method, $parameters)
	{
		//event before
		event(get_class($this).'.before.'.$method);
		event(new BeforeControllerEvent($this, $method));

		// check current user's permissions
		$this->checkPermission($method);
		
		$response = call_user_func_array([$this, $method], $parameters);
		//event successful
		event(get_class($this).'.'.$method);
		event(new ControllerEvent($this, $method, $response));
		return $response;
	}

}