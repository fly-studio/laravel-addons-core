<?php
namespace Addons\Core\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\Role;
//Facades
use Addons\Core\Controllers\OutputTrait;
use Addons\Core\Controllers\InitTrait;
use Addons\Core\Controllers\PermissionTrait;
use Addons\Core\Events\BeforeControllerEvent;
use Addons\Core\Events\ControllerEvent;
class Controller extends BaseController {
	use InitTrait, PermissionTrait, OutputTrait;
	protected $addons = true;
	
	public function callAction($method, $parameters)
	{
		//event before
		event(get_class($this).'.before.'.$method);
		event(new BeforeControllerEvent($this, $method));

		if ($this->addons)
		{
			$this->initCommon();
			$this->initMember();
			foreach(['site', 'user'] as $key)
				$this->viewData['_'.$key] = &$this->$key;
			if( !$this->checkPermission($this->user, $method) )
			{
				in_array(app('request')->input('of'), ['csv', 'xls', 'xlsx', 'pdf']) && app('request')->offsetSet('of', '');
				return $this->failure('auth.failure_permission');
			}
		}

		$response = call_user_func_array([$this, $method], $parameters);
		//event after
		event(get_class($this).'.'.$method);
		event(new ControllerEvent($this, $method, $response));
		return $response;
	}

}