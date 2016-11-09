<?php
namespace Addons\Core\Controllers;

use Illuminate\Routing\Controller as BaseController;
use App\Role;
//Facades
use Addons\Core\Controllers\OutputTrait;
use Addons\Core\Controllers\InitTrait;
use Addons\Core\Controllers\PermissionTrait;
class Controller extends BaseController {
	use InitTrait, PermissionTrait, OutputTrait;
	protected $addons = true;
	
	public function callAction($method, $parameters)
	{
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
		//todo:
		return $response;
	}

}