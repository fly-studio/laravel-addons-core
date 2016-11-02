<?php
namespace Addons\Core\Models;

trait FieldTrait{

	public static function bootFieldTrait()
	{
		//order_index自动加1
		static::creating(function($field){
			$_field = static::where('field_class', $field->field_class)->orderBy('order_index','DESC')->first();
			$field->order_index  = empty($_field) ? 1 : $_field->order_index + 1;
		});
	}
}