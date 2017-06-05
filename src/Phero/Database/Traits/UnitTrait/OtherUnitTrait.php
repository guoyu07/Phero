<?php 

namespace Phero\Database\Traits\UnitTrait;

use Phero\Database\Model;
/**
 * @Author: lerko
 * @Date:   2017-06-02 17:21:00
 * @Last Modified by:   ‘chenyingqiao’
 * @Last Modified time: 2017-06-04 12:51:42
 */

trait OtherUnitTrait{
	//分组
	protected $groupBy;
	//范围
	protected $limit;
	//排序
	protected $order;
	//having的分组
	protected $havingGroup = false;
	protected $datasourse = [];
	protected $distinct = false;
	protected $values_cache, $inifalse;
	private $model;
	/**
	 * 列是否需要as
	 * @var [type]
	 */
	public $have_as = true;

	public function getGroup() {return $this->groupBy;}
	public function getLimit() {return $this->limit;}
	public function getOrder() {return $this->order;}
	public function getDistinct() {return $this->distinct;}

	/**
	 * 分组列 group by
	 * @param  [type] $field [description]
	 * @return [type]        [description]
	 */
	public function group($field) {
		$this->groupBy = $field;
		return $this;
	}

	/**
	 * 查询范围
	 * @param  [type] $start [description]
	 * @param  [type] $end   [description]
	 * @return [type]        [description]
	 */
	public function limit($start, $end = null) {
		$this->limit = [$start, $end];
		return $this;
	}

	/**
	 * 设置排序
	 * @param  [type] $field      [description]
	 * @param  [type] $order_type [description]
	 * @return [type]             [description]
	 */
	public function order($field, $order_type = null) {
		$this->order = [$field, $order_type];
		return $this;
	}

	public function distinct() {
		$this->distinct = true;
	}

    /**
     * 初始化实体 从values_cache恢复数据  这个是为了entity复用的时候进行数据缓存处理  保留原本的数据但是不保留原本的查询动作
     */
	protected function unit_new($reloadFieldValueFormCache=true) {
		$this->errormsg=$this->model->getError();
		$this->model = new Model();
		$this->where = [];
		$this->having = [];
		$this->join = [];
		$this->datasourseJoinType = null;
		$this->fieldTemp = [];
		$this->groupBy = null;
		$this->limit = null;
		$this->order = null;
		$this->whereGroup = false;
		$this->havingGroup = false;
		$this->field = [];
		$this->datasourse = [];
		$this->distinct = false;
		//不管是查询还是插入都会把字段值全部重置成null
		//select存储的field描述存储在一个values_cache变量中
		$this->allNull();
		//从values_cache恢复数据  这个是为了entity复用的时候进行数据缓存处理  保留原本的数据但是不保留原本的查询动作
		if($reloadFieldValueFormCache){
			$this->initField($this->values_cache);
		}
	}
}