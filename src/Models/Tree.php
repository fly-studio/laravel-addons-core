<?php
namespace Addons\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Addons\Core\Models\TreeTrait;
use Addons\Core\Models\CacheTrait;
use Addons\Core\Models\CallTrait;
use Addons\Core\Models\PolyfillTrait;

class Tree extends Model {
	use CacheTrait, CallTrait, PolyfillTrait;
	use TreeTrait;

	//不能批量赋值
	protected $guarded = ['id', 'level', 'path', 'order']; //这些字段禁止维护

	public $parentKey = 'pid'; //必要字段                MySQL需加索引
	public $orderKey = 'order'; //无需此字段请设置NULL   MySQL需加索引
	public $pathKey = 'path'; //无需此字段请设置NULL     MySQL需加索引
	public $levelKey = 'level'; //无需此字段请设置NULL   MySQL需加索引

	//relation
	public function children()
	{
		$builder = $this->hasMany(get_class($this), $this->parentKey, $this->getKeyName());
		!empty($this->orderKey) && $builder->orderBy($this->orderKey, 'ASC');
		return $builder;
	}

	public function parent()
	{
		return $this->hasOne(get_class($this), $this->getKeyName(), $this->parentKey);
	}

	/**
	 * 获取指定ID的Node
	 *
	 * @param  integer $cid 输入ID
	 * @return mixed      输出查询结果
	 */
	public function getNode($id, $columns = ['*'])
	{
		$columns = $this->formatColumns($columns);
		return empty($id) ? (new static)->newFromBuilder([
				$this->getKeyName() => 0,
				$this->parentKey => NULL,
				$this->pathKey => '/0/',
				$this->orderKey => 0,
				$this->levelKey => 0,
			]) : static::find($id, $columns);
	}

	/**
	 * 获取所匹配的根节点Node
	 *
	 * @return mixed      得到root
	 */
	public function getRoot($columns = ['*'])
	{
		$columns = $this->formatColumns($columns);
		$node = $this;
		if (empty($this->pathKey))
		{
			while(!empty($node->getParentKey()))
				$node = $this->getNode($node->getParentKey(), $columns);
			return $node;
		} else {
			list(,,$rootid) = explode('/', $node->getPathKey()); // [/0/1/2/3/] => 1
			return $this->getNode($rootid, $columns);
		}
	}

	/**
	 * 获取父级的Node
	 *
	 * @return mixed      得到父级元素
	 */
	public function getParent($columns = ['*'])
	{
		$columns = $this->formatColumns($columns);
		return $this->getNode($this->getParentKey(), $columns);
	}

	/**
	 * 获取所匹配的祖先Nodes
	 *
	 * @return array     返回祖先
	 */
	public function getParents($columns = ['*'])
	{
		$columns = $this->formatColumns($columns);
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
	public function getChildren($columns = ['*'])
	{
		$columns = $this->formatColumns($columns);
		$builder = static::where($this->parentKey, $this->getKey());
		!empty($this->orderKey) && $builder->orderBy($this->orderKey);
		return $builder->get();
	}

	/**
	 * 获得所有子(孙)集，返回一个Collection
	 *
	 * @return array 返回数据
	 */
	public function getDescendant($columns = ['*'])
	{
		$columns = $this->formatColumns($columns);
		$node = $this;
		if (!empty($this->pathKey)) //使用Path搜索出来的节点，无法通过order进行良好的排序
		{
			$builder = static::where($this->pathKey, 'LIKE', $node->getPathKey().'%')->where($node->getKeyName(), '!=', $node->getKey());
			!empty($this->orderKey) && $builder->orderBy($this->orderKey);
			!empty($this->pathKey) && $builder->orderBy($this->pathKey);
			return $builder->get($columns);
		} else {
			$result = $this->newCollection();
			$children = $this->getChildren($columns);
			foreach($children as $v)
			{
				$result[] = $v;
				$result = $result->merge($v->getDescendant($columns));
			}
			return $result;
		}
	}
	/**
	 * 获得所有子(孙)集，返回一个tree数组
	 *
	 * @return array 返回数据
	 */
	public function getTree($columns = ['*'], $with_id = TRUE)
	{
		$nodes = $this->getDescendant($columns)->prepend($this)->keyBy($this->getKeyName())->toArray();
		return static::datasetToTree($nodes, $this->getParentKey(), $with_id);
	}

	/**
	 * 将上面的二维class数组生成tree，必须是设置with_id的二维数组
	 *
	 * @param mixed $items 通过getData获得的二维数组(with_id)
	 * @param integer $topid 提供此二维数组中的顶级节点topid
	 */
	public static function datasetToTree($items, $topid = 0, $with_id = TRUE)
	{
		$node = new static;
		foreach ($items as $item)
		{
			if ($item[$node->getKeyName()] == $item[$node->parentKey]) continue; //如果父ID等于自己，避免死循环，跳过
			if ($with_id)
				$items[ ($item[$node->parentKey]) ][ 'children' ][ ($item[$node->getKeyName()]) ] = &$items[ ($item[$node->getKeyName()]) ];
			else
				$items[ ($item[$node->parentKey]) ][ 'children' ][] = &$items[ ($item[$node->getKeyName()]) ];
		}
	 	return isset($items[ $topid ][ 'children' ]) ? $items[ $topid ][ 'children' ] : [];
	}

	private function formatColumns($columns)
	{
		if (in_array('*', $columns)) return $columns;
		!in_array($this->getKeyName(), $columns) && $columns[] = $this->getKeyName();
		!in_array($this->parentKey, $columns) && $columns[] = $this->parentKey;
		!in_array($this->pathKey, $columns) && $columns[] = $this->pathKey;

		return $columns;
	}


	public function newOrder()
	{
		if (empty($this->orderKey)) return null;

		$node = static::where($this->parentKey, $this->getParentKey())->where($this->getKeyName(), '!=', $this->getKey() ?: 0)->orderBy($this->orderKey, 'DESC')->first([$this->orderKey]);
		return empty($node) ? 1 : intval($node->getOrderKey()) + 1;
	}

	public function moveToLast()
	{
		if (empty($this->orderKey)) return null;

		return static::where($this->getKeyName(), $this->getKey())->update([$this->orderKey => $this->newOrder()]);
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
	public function move($target_id, $move_type)
	{
		$targetNode = $this->getNode($target_id);
		if (empty($target_id) || empty($targetNode)) return NULL;

		if ($move_type == 'inner') //成为别人子集，则直接调用,放入子集
			return $this->update([$this->parentKey => $target_id]); //自动调取changeParent

		if ($targetNode->getParentKey() != $this->getParentKey()) //父级不相同
			$this->update([$this->parentKey => $targetNode->getParentKey()]); //自动调取changeParent
		if (!empty($this->orderKey))
		{
			$order = intval($targetNode->getOrderKey()) + ($move_type == 'prev' ? 0 : 1);
			//更新同父级下所有的顺序
			static::where($this->parentKey, $targetNode->getParentKey())->where($this->orderKey, '>=', $order)->increment($this->orderKey);
			$this->update([$this->orderKey => $order]); //更新自己的顺序
		}

		return $this;
	}

	public function getParentKey()
	{
		return $this->{$this->parentKey};
	}

	public function getParentKeyName()
	{
		return $this->parentKey;
	}

	public function getOrderKey()
	{
		return $this->{$this->orderKey};
	}

	public function getOrderKeyName()
	{
		return $this->orderKey;
	}

	public function getPathKey()
	{
		return $this->{$this->pathKey};
	}

	public function getPathKeyName()
	{
		return $this->pathKey;
	}

	public function getLevelKey()
	{
		return $this->{$this->levelKey};
	}

	public function getLevelKeyName()
	{
		return $this->levelKey;
	}

}
