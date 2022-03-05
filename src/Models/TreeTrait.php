<?php

namespace Addons\Core\Models;

use DB;
use Illuminate\Support\Arr;
use Addons\Core\Models\TreeCacheTrait;

trait TreeTrait {

	use TreeCacheTrait;

	//不能批量赋值
	//protected $guarded = ['id', 'level', 'path', 'order']; //这些字段禁止维护


	/* 由于改为了trait方式，所以下面的变量的值请使用下面的函数来实现
	public $parentKey = 'pid'; //必要字段                MySQL需加索引
	public $orderKey = 'order'; //无需此字段请设置null   MySQL需加索引
	public $pathKey = 'path'; //无需此字段请设置null     MySQL需加索引
	public $levelKey = 'level'; //无需此字段请设置null   MySQL需加索引
	*/

	public function getParentKey()
	{
		return $this->{$this->getParentKeyName()};
	}

	public function getParentKeyName()
	{
		return 'pid';
	}

	public function getOrderKey()
	{
		return $this->{$this->getOrderKeyName()};
	}

	public function getOrderKeyName()
	{
		return 'order';
	}

	public function getPathKey()
	{
		return $this->{$this->getPathKeyName()};
	}

	public function getPathKeyName()
	{
		return 'path';
	}

	public function getLevelKey()
	{
		return $this->{$this->getLevelKeyName()};
	}

	public function getLevelKeyName()
	{
		return 'level';
	}

	//relation
	public function children()
	{
		$builder = $this->hasMany(get_class($this), $this->getParentKeyName(), $this->getKeyName());

		if (!empty($this->getOrderKeyName()))
			$builder->orderBy($this->getOrderKeyName(), 'asc');

		return $builder;
	}

	public function parent()
	{
		return $this->hasOne(get_class($this), $this->getKeyName(), $this->getParentKeyName());
	}

	/**
	 * 获取指定ID的Node
	 *
	 * @param  integer $cid 输入ID
	 * @return mixed      输出查询结果
	 */
	public function getNode($id, array $columns = ['*'])
	{
		$columns = $this->formatTreeColumns($columns);

		return empty($id) ? (new static)->newFromBuilder([
				$this->getKeyName() => 0,
				$this->getParentKeyName() => null,
				$this->getPathKeyName() => '/0/',
				$this->getOrderKeyName() => 0,
				$this->getLevelKeyName() => 0,
			]) : static::find($id, $columns);
	}

	/**
	 * 获取所匹配的根节点Node
	 *
	 * @return mixed      得到root
	 */
	public function getRoot(array $columns = ['*'])
	{
		$columns = $this->formatTreeColumns($columns);
		$node = $this;

		if (empty($this->getPathKeyName()))
		{
			while(!empty($node->getParentKey()))
				$node = $this->getNode($node->getParentKey(), $columns);

			return $node;
		} else {
			list(,, $rootid) = explode('/', $node->getPathKey()); // [/0/1/2/3/] => 1

			return $this->getNode($rootid, $columns);
		}
	}

	/**
	 * 获取父级的Node
	 *
	 * @return mixed      得到父级元素
	 */
	public function getParent(array $columns = ['*'])
	{
		$columns = $this->formatTreeColumns($columns);

		return $this->getNode($this->getParentKey(), $columns);
	}

	/**
	 * 获取所匹配的祖先Nodes
	 *
	 * @return array     返回祖先
	 */
	public function getParents(array $columns = ['*'])
	{
		$columns = $this->formatTreeColumns($columns);
		$result = $this->newCollection();
		$node = $this;

		while(!empty($node->getParentKey()))
		{
			$node = $this->getNode($node->getParentKey(), $columns);
			$result[] = $node;
		}

		return $result;
	}

	/**
	 * 获得子集，返回一个Collection
	 *
	 * @return array 返回数据
	 */
	public function getChildren(array $columns = ['*'])
	{
		$columns = $this->formatTreeColumns($columns);
		$builder = static::where($this->getParentKeyName(), $this->getKey());

		if (!empty($this->getOrderKeyName()))
			$builder->orderBy($this->getOrderKeyName());

		return $builder->get();
	}

	/**
	 * 获得所有子(孙)集，不包含自己，返回一个Collection
	 *
	 * @return Collection 返回数据
	 */
	public function getOffspring(array $columns = ['*'])
	{
		return $this->getLeaves($columns);
	}

	/**
	 * getOffspring的别名
	 *
	 * @return Collection 返回数据
	 */
	public function getLeaves(array $columns = ['*'])
	{
		$columns = $this->formatTreeColumns($columns);
		$node = $this;

		if (!empty($this->getPathKeyName())) //使用Path搜索出来的节点，无法通过order进行良好的排序
		{
			$builder = static::where($this->getPathKeyName(), 'LIKE', $node->getPathKey().'%')
				->where($node->getKeyName(), '!=', $node->getKey());

			if (!empty($this->getOrderKeyName()))
				$builder->orderBy($this->getOrderKeyName());

			if (!empty($this->getPathKeyName()))
				$builder->orderBy($this->getPathKeyName());

			return $builder->get($columns);

		} else {

			$result = $this->newCollection();
			$children = $this->getChildren($columns);

			foreach($children as $v)
			{
				$result[] = $v;
				$result = $result->merge($v->getLeaves($columns));
			}
			return $result;
		}
	}

	/**
	 * 通过pid获取树, 注意: 这是一个static方法
	 *
	 * @param int $pid 父级ID, 如果将整个表生成树结构, 传递null，注意：roles表中 0 是一个存在的记录， 所以传递0将无法得到id为0的node
	 * @param array $columns 需要取出的字段名
	 * @param bool 返回的children中，是否以ID为Key
	 * @return array 返回数据
	 */
	public static function getTreeByPid(int $pid = null, array $columns = ['*'], bool $idAsKey = false) {
		if (is_null($pid)) {
			$model = new static();
			$builder = $model->newQuery();
			if (!empty($model->getOrderKeyName()))
				$builder->orderBy($model->getOrderKeyName());

			$nodes = $builder->get($columns)->keyBy($model->getKeyName())->toArray();
		} else {
			$nodes = static::find($pid)->getTree($columns, $idAsKey);
		}

		return static::datasetToTree($nodes, $idAsKey);
	}

	/**
	 * 获得一颗树，返回一个Tree的数组
	 *
	 * @param array $columns 需要取出的字段名
	 * @param bool 返回的children中，是否以ID为Key
	 * @return array 返回数据
	 */
	public function getTree(array $columns = ['*'], bool $idAsKey = false)
	{
		$nodes = $this->getOffspring($columns)->prepend($this)->keyBy($this->getKeyName())->toArray();

		return static::datasetToTree($nodes, $idAsKey);
	}

	private function formatTreeColumns(array $columns)
	{
		if (in_array('*', $columns))
			return $columns;

		if (!in_array($this->getKeyName(), $columns))
			$columns[] = $this->getKeyName();

		if (!in_array($this->getParentKeyName(), $columns))
			$columns[] = $this->getParentKeyName();

		if (!in_array($this->getPathKeyName(), $columns))
			$columns[] = $this->getPathKeyName();

		return $columns;
	}

	public function newOrder()
	{
		if (empty($this->getOrderKeyName())) return null;

		$node = static::where($this->getParentKeyName(), $this->getParentKey())
			->where($this->getKeyName(), '!=', $this->getKey() ?: 0)
			->orderBy($this->getOrderKeyName(), 'desc')
			->first([$this->getOrderKeyName()]);

		return empty($node) ? 1 : intval($node->getOrderKey()) + 1;
	}

	public function moveToLast()
	{
		if (empty($this->getOrderKeyName())) return null;

		return static::where($this->getKeyName(), $this->getKey())
			->update([
				$this->getOrderKeyName() => $this->newOrder()
			]);
	}

	public function movePrev($target_id)
	{
		return $this->move($target_id, 'prev');
	}

	public function moveNext($target_id)
	{
		return $this->move($target_id, 'next');
	}

	public function moveInner($target_id)
	{
		return $this->move($target_id, 'inner');
	}

	/**
	 * 移动节点
	 * @param  integer $target_id   目标CID
	 * @param  string $move_type     [inner, prev, next]分别表示：子集、前一位、后一位
	 * @return boolean
	 */
	public function move($target_id, string $move_type)
	{
		$targetNode = $this->getNode($target_id);
		if (empty($target_id) || empty($targetNode)) return null;

		if ($move_type == 'inner') //成为别人子集，则直接调用,放入子集
			return $this->update([$this->getParentKeyName() => $target_id]); //自动调取changeParent

		if ($targetNode->getParentKey() != $this->getParentKey()) //父级不相同
			$this->update([$this->getParentKeyName() => $targetNode->getParentKey()]); //自动调取changeParent

		if (!empty($this->getOrderKeyName()))
		{
			$order = intval($targetNode->getOrderKey()) + ($move_type == 'prev' ? 0 : 1);
			//更新同父级下所有的顺序
			static::where($this->getParentKeyName(), $targetNode->getParentKey())
				->where($this->getOrderKeyName(), '>=', $order)
				->increment($this->getOrderKeyName());

			$this->update([$this->getOrderKeyName() => $order]); //更新自己的顺序
		}

		return $this;
	}

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
	public static function datasetToTree(array $items, bool $idAsKey = false)
	{
		$node = new static;
		$ids = [];

		if ($idAsKey)
		{
			foreach ($items as $item)
			{
				if ($item[$node->getKeyName()] == $item[$node->getParentKeyName()])
					continue; //如果父ID等于自己，避免死循环，跳过

				$ids[] = $item[$node->getKeyName()];
				$items[ ($item[$node->getParentKeyName()]) ][ 'children' ][ ($item[$node->getKeyName()]) ] = &$items[ ($item[$node->getKeyName()]) ];
			}
		}
		else
		{
			foreach ($items as $item)
			{
				if ($item[$node->getKeyName()] == $item[$node->getParentKeyName()])
					continue; //如果父ID等于自己，避免死循环，跳过

				$ids[] = $item[$node->getKeyName()];
				$items[ ($item[$node->getParentKeyName()]) ][ 'children' ][] = &$items[ ($item[$node->getKeyName()]) ];
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

		if(empty($newParent) || $this->getParentKey() == $this->getOriginal($this->getParentKeyName()))
			return null;

		if (!empty($this->getPathKeyName()))
		{
			$newPath = $newParent->getPathKey() . $this->getKey() . '/';

			static::where($this->getPathKeyName(), 'LIKE', '%'.$this->getPathKey().'%')
				->update([
					$this->getPathKeyName() => DB::raw('REPLACE(`'.$this->getPathKeyName().'`, \''.$this->getPathKey().'\', \''.$newPath.'\')')
				]);
		}

		if (!empty($this->getLevelKeyName()))
		{
			$deltaLevel = intval($this->getLevelKey()) - intval($newParent->getLevelKey()) - 1;

			$ids = $this->getOffspring([])
				->add($this)
				->modelKeys(); //get id pid

			static::whereIn($this->getKeyName(), $ids)
				->decrement($this->getLevelKeyName(), $deltaLevel);
		}

		return $this;
	}

	public static function bootTreeTrait()
	{
		//更新order/level
		static::creating(function($node){
			if (!empty($node->getOrderKeyName()))
				$node[$node->getOrderKeyName()] = $node->newOrder();

			if (!empty($node->getLevelKeyName()))
				$node[$node->getLevelKeyName()] = $node->getParent()->getLevelKey() + 1;
		});

		//更新path
		static::created(function($node){
			if (!empty($node->getPathKeyName()))
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
