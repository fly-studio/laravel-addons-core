<?php
namespace Addons\Core\Models;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Addons\Entrust
 */

use Addons\Entrust\Contracts\RoleInterface;
use Addons\Entrust\Traits\RoleTrait as EntrustRoleTrait;
use Addons\Core\Models\Model;
use Illuminate\Support\Facades\Config;
use Addons\Core\Models\CacheTrait;
class Role extends Model implements RoleInterface
{
	use EntrustRoleTrait;

	public $auto_cache = true;
	public $fire_caches = ['roles'];

	const ADMIN = 'admin';
	const MANGER = 'manger';
	const OWNER = 'owner';
	const LEADER = 'leader';
	const VIEWER = 'viewer';
	const WECHATER = 'wechater';

	//不能批量赋值
	public $guarded = [];

	 /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Creates a new instance of the model.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('entrust.roles_table');
    }

    public static function findByName($name, $columns = ['*'])
    {
    	return static::where('name', $name)->first($columns);
    }

	public function getRoles()
	{
		$roles = [];
		$_roles = $this->rememberCache('roles', function() {return $this->with('perms')->orderBy('id', 'ASC')->get();});
		foreach ($_roles->toArray() as $role) {
			$role['prems'] = array_map(function($v) { return $v['name'];}, $role['perms']);
			$roles[$role['name']] = $role;
		}
		return $roles;
	}
}
