<?php

namespace Addons\Core\Models;

use DB;
use Illuminate\Support\Arr;

trait TreeTrait{

	public function hasChildren()
	{
		return $this->children()->count() > 0;
	}

	/**
	 * 将二维dataset数组生成tree，必须是ID为KEY的二维数组
	 *
	 * @param mixed $items ID为KEY的二维数组
	 * @param bool 返回的children中，是否以ID为Key
	 */
	public static function datasetToTree(array $items, bool $idAsKey = true)
	{
		$node = new static;
		$ids = [];

		if ($idAsKey)
		{
			foreach ($items as $item)
			{
				if ($item[$node->getKeyName()] == $item[$node->parentKey]) continue; //如果父ID等于自己，避免死循环，跳过

				$ids[] = $item[$node->getKeyName()];
				$items[ ($item[$node->parentKey]) ][ 'children' ][ ($item[$node->getKeyName()]) ] = &$items[ ($item[$node->getKeyName()]) ];
			}
		}
		else
		{
			foreach ($items as $item)
			{
				if ($item[$node->getKeyName()] == $item[$node->parentKey]) continue; //如果父ID等于自己，避免死循环，跳过

				$ids[] = $item[$node->getKeyName()];
				$items[ ($item[$node->parentKey]) ][ 'children' ][] = &$items[ ($item[$node->getKeyName()]) ];
			}
		}

		$result = Arr::except($items, $ids);
		return count($result) === 1 ? Arr::get(array_pop($result), 'children', []) : $result;
	}

	/**
	 * 改变父级，本函数不可再任何情况下调用
	 * $tree->update(['pid' => 'xxx']) 会自动调用本函数
	 *
	 * @return [type]          [description]
	 */
	protected function changeParent()
	{
		$newParent = $this->getNode($this->getParentKey());
		if(empty($newParent) || $this->getParentKey() == $this->getOriginal($this->parentKey)) return NULL;

		if (!empty($this->pathKey))
		{
			$newPath = $newParent->getPathKey() . $this->getKey() . '/';
			static::where($this->pathKey, 'LIKE', '%'.$this->getPathKey().'%')->update([$this->pathKey => DB::raw('REPLACE(`'.$this->pathKey.'`, \''.$this->getPathKey().'\', \''.$newPath.'\')')]);
		}

		if (!empty($this->levelKey))
		{
			$deltaLevel = intval($this->getLevelKey()) - intval($newParent->getLevelKey()) - 1;
			$ids = $this->getLeaves([])->add($this)->modelKeys(); //get id pid
			static::whereIn($this->getKeyName(), $ids)->decrement($this->levelKey, $deltaLevel);
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
