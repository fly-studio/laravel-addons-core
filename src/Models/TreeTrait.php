<?php
namespace Addons\Core\Models;
use DB;
trait TreeTrait{

	/**
	 * 改变父级，本函数不可再任何情况下调用
	 * $tree->update(['pid' => 'xxx']) 会自动调用本函数
	 * 
	 * @return [type]          [description]
	 */
	protected function changeParent()
	{
		$newParent = $this->getNode($this->getParentKey());
		if(empty($newParent) || $this->getParentKey() == $this->getOriginal($this->getParentKeyName())) return NULL;

		if (!empty($this->getPathKeyName()))
		{
			$newPath = $newParent->getPathKey() . $this->getKey() . '/';
			static::where($this->getPathKeyName(), 'LIKE', '%'.$this->getPathKey().'%')->update([$this->getPathKeyName() => DB::raw('REPLACE(`'.$this->getPathKeyName().'`, \''.$this->getPathKey().'\', \''.$newPath.'\')')]);
		}

		if (!empty($this->getLevelKeyName()))
		{
			$deltaLevel = intval($this->getLevelKey()) - intval($newParent->getLevelKey()) - 1;
			$ids = $this->getDescendant([])->add($this)->fetch($this->getKeyName())->toArray(); //get id pid
			static::whereIn($this->getKeyName(), $ids)->decrement($this->getLevelKeyName(), $deltaLevel);
		}
		
		return $this;
	}

	public static function bootTreeTrait()
	{
		//更新order/level
		static::creating(function($node){
			!empty($node->getOrderKeyName()) && $node[$node->getOrderKeyName()] = $node->newOrder();
			!empty($node->getLevelKeyName()) && $node[$node->getLevelKeyName()] = $node->getParent()->getLevelKey() + 1;
		});
		//更新path
		static::created(function($node){
			if (!empty($node->pathKey))
			{
				$node[$node->getPathKeyName()] = $node->getParent()->getPathKey() . $node->getKey().'/';
				$node->save();
			}
		});

		//切换父级
		static::updated(function($node) {
			if ($node->isDirty($node->getParentKeyName()))
			{
				$node->changeParent();
				//放到新Parent的结尾
				$node->moveToLast();
			}
		});
		//删除子集，会自动递归删除后代
		static::deleted(function($node){
			foreach($node->getChildren() as $v)
				$v->delete();
		});
	}
}