<?php
namespace Addons\Core\Models;

use Addons\Core\Models\Tree;
//use Addons\Core\Models\FieldTrait;
use Cache;
class Field extends Tree {
	//use FieldTrait;
	//不能批量赋值
	public $fire_caches = ['fields'];
	public $orderKey = 'order_index';
	public $pathKey = NULL;
	public $levelKey = NULL;

	public $casts = [
		'extra' => 'array',
	];

	protected $guarded = [];

	public function exists($id, $field_class)
	{
		return $this->where('id', $id)->where('field_class', $field_class)->count() > 0;
	}

	public function exists_name($name, $field_class)
	{
		return $this->where('name', $name)->where('field_class', $field_class)->count() > 0;
	}

	public function get($name, $field_class)
	{
		return $this->where('name', $name)->where('field_class', $field_class)->first();
	}

	public function getFields()
	{
		return Cache::remember('fields', 7 * 24 * 60, function(){
			$result = [];
			$all = $this->orderBy('order_index','ASC')->get();
			$lines = $all->pluck('name', 'id')->toArray();
			foreach($all as $v)
				$result[$v['field_class']][$v['name']] = array_keyfilter($v->toArray(), 'id,name,title,extra,pid');
			foreach ($result as $k => $v)
				foreach($v as $k1 => $v1)
					if (!empty($v1['pid']))
						$result[$k][ $lines[$v1['pid']] ]['children'][$k1] = &$result[$k][$k1];
						//此处不能unset();

			//删除在根目录下有pid的，上面不能删除，因为如果删除了，将无法做到第三层
			foreach ($result as $k => $v)
				foreach($v as $k1 => $v1)
					if (!empty($v1['pid']))
						unset($result[$k][$k1]);

			return $result;
		});
	}
}
