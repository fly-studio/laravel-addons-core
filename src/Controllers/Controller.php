<?php

namespace Addons\Core\Controllers;

use Addons\Core\Events\ControllerEvent;
use Addons\Core\Controllers\OutputTrait;
use Illuminate\Routing\Controller as BaseController;
use Addons\Censor\Validation\ValidatesRequests;
use Addons\Entrust\Controllers\PermissionTrait;


class Controller extends BaseController {

	use PermissionTrait, OutputTrait, ValidatesRequests;

	protected $disableUser = false;

	public function callAction($method, $parameters)
	{
		//event before
		event('controller.before: '.get_class($this).'@'.$method, [new ControllerEvent($this, $method)]);

		// check current user's permissions
		if (!$this->disableUser)
			$this->checkPermission($method);

		$this->viewData['_permissionTable'] = $this->permissionTable;
		$this->viewData['_method'] = $method;

		$response = $this->{$method}(...array_values($parameters));

		//event successful
		event('controller.after: '.get_class($this).'@'.$method, [new ControllerEvent($this, $method, null, $response)]);
		return $response;
	}

}
