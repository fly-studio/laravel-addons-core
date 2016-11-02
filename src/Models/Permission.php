<?php namespace Addons\Core\Models;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Addons\Entrust
 */

use Addons\Entrust\Contracts\PermissionInterface;
use Addons\Entrust\Traits\PermissionTrait as EntrustPermissionTrait;
use Addons\Core\Models\Model;
use Illuminate\Support\Facades\Config;

class Permission extends Model implements PermissionInterface
{
	use EntrustPermissionTrait;

	public $auto_cache = true;
	public $fire_caches = ['roles'];
	public $guarded = ['id'];
	
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
		$this->table = Config::get('entrust.permissions_table');
	}

}
