<?php
namespace Addons\Core\Models;

use Addons\Core\Models\Model;
use Addons\Core\Models\FieldTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cache;
class UserFinance extends Model {
	use SoftDeletes;

	public $auto_cache = true;
	protected $guarded = [];

	public function user()
	{
		return $this->hasOne(get_namespace($this).'\\User', 'id', 'id');
	}
}