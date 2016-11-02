<?php
namespace Addons\Core\Models;
use DB;
use Addons\Core\Models\UserFinance;
trait UserTrait{

	public static function bootUserTrait()
	{
		//自动创建extra等数据
		static::created(function($user){
			UserFinance::create([
				'id' => $user->getKey(),
			]);
		});
	}
}