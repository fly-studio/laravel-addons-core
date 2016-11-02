<?php
namespace Addons\Core\Controllers;
use Illuminate\Support\Facades\Auth;
use App\User;
trait PermissionTrait {

	/**
	 * RBAC权限表，注意：只有被路由调用的函数才会检查权限
	 * '函数名' => '权限名'
	 * '函数名1,函数名2' => '权限名' 表示这两个函数对应此权限
	 * '函数名1,函数名2' => ['权限名1', '权限名2'] 表示这两个权限都要满足，权限的数量没有限制
	 * '*' => '权限名' 所有未配置的函数均要检查本权限，如果函数已经定义，则以定义的权限为准。
	 * @example  ['index,show' => 'member.view', 'edit,update,create,store' => 'member.edit', 'destroy' => 'member.destroy']
	 * @example  ['*' => 'member.view', 'edit,update,create,store' => 'member.edit', 'destroy' => 'member.destroy'] 此配置同上，* 代表所有未配置的函数名
	 * @example  ['*' => ['member.view', 'dashborad.view']] * 代表所有未配置的函数名，此例也就是代表所有函数，所有的函数都要满足这两个权限
	 *  
	 * @var array
	 */
	protected $permissions = [];
	/**
	 * 设置本名称后，将自动为本名称加上一个通用的权限
	 * 查看 initPermissions
	 * $permissions中的函数会优先于RESTful
	 * 
	 * @var string
	 */
	protected $RESTful_permission = NULL;


	private function initPermissions()
	{
		$_permissions = [];
		foreach($this->permissions as $k => $v)
		{
			foreach(explode(',', $k) as $key)
				$_permissions[strtolower($key)] = $v;
		}
		$rest = $this->RESTful_permission;
		if (!empty($rest))
		{
			$_permissions += [
				'index' => $rest.'.view',
				'show' => $rest.'.view',
				'data' => $rest.'.view',
				'export' => $rest.'.export',
				'print' => $rest.'.export',
				'edit' => $rest.'.edit',
				'update' => $rest.'.edit',
				'create' => $rest.'.create',
				'store' => $rest.'.create',
				'destroy' => $rest.'.destroy',
			];
		}
		$this->permissions = $_permissions;
	}

	private function checkPermission(User $user, $method)
	{
		$this->initPermissions();
		$method = strtolower($method);
		!isset($this->permissions[$method]) && $method = '*';
		return array_key_exists($method, $this->permissions) ? $user->can($this->permissions[$method], true) : true;
	}
}