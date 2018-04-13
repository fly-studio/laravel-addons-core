<?php

namespace Addons\Core\Controllers;

use Illuminate\Support\Facades\Auth;
use Addons\Entrust\Exception\PermissionException;

use App\User;

trait PermissionTrait {

	/**
	 * RBAC权限表，注意：只有被路由调用的函数才会检查权限
	 *
	 * '函数名' => '权限名'
	 * '函数名1,函数名2,...' => '权限名' 表示这两个函数分别对应此权限
	 * @example  ['index,show' => 'member.view', 'edit,update,create,store' => 'member.edit', 'destroy' => 'member.destroy']
	 * '函数名1,函数名2,...' => ['权限名1', '权限名2'] 表示这两个权限都要满足，权限的数量没有限制
	 * @example ['index' => ['member.view', 'dashborad.view']] index函数，需同时满足这2个权限
	 * @example ['index,show' => ['member.view', 'dashborad.view']] index或show函数，需同时满足这2个权限
	 * '*' => '权限名' 除了已设置的函数，均要检查本权限, * 与其他函数名无前后之分。注意：* 只能单独使用。
	 * @example  ['*' => 'member.view', 'edit,update,create,store' => 'member.edit', 'destroy' => 'member.destroy']
	 * @example  ['*' => ['member.view', 'dashborad.view']] 因为没有定义的函数，所以此例表示所有的函数都要满足这两个权限
	 * 如果没有设置键名，则自动配置RESTful的index show data export print edit update create store destroy
	 * @example  ['member'] 等同于 ['index,show,data' => 'member.view', 'export,print' => 'member.export', 'edit,update' => 'member.edit', 'create,store' => 'member.create', 'destroy' => 'member.destroy']
	 *
	 * @var array
	 */
	protected $permissions = [];

	private function getPermissionTable()
	{
		$permissionTable = [];
		foreach($this->permissions as $k => $v)
		{
			if (is_numeric($k))
				$permissionTable += [
					'index' => $v.'.view',
					'show' => $v.'.view',
					'data' => $v.'.view',
					'export' => $v.'.export',
					'print' => $v.'.export',
					'edit' => $v.'.edit',
					'update' => $v.'.edit',
					'create' => $v.'.create',
					'store' => $v.'.create',
					'destroy' => $v.'.destroy',
				];
			else
				foreach(explode(',', $k) as $key)
					$permissionTable[strtolower($key)] = $v;
		}

		return $permissionTable;
	}

	private function checkPermission($method, $return_result = false)
	{
		$permissionTable = $this->getPermissionTable();

		if (empty($permissionTable)) return true;

		$user = Auth::check() ? Auth::user() : new User;
		$method = strtolower($method);
		!isset($permissionTable[$method]) && $method = '*';

		if (array_key_exists($method, $permissionTable) && !$user->can($permissionTable[$method], true))
		{
			if ($return_result)
				return false;
			else
				throw new PermissionException();
		}

		return true;
	}
}