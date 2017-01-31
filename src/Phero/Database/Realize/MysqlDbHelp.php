<?php
namespace Phero\Database\Realize;

use Phero\Database as database;
use Phero\Database\Enum\RelType;
use Phero\Database\Interfaces as interfaces;
use Phero\Database\Interfaces\IRelation;
use Phero\Database\Realize\PdoWarehouse;
use Phero\Database\Traint\TRelation;

/**
 * 数据库
 */
class MysqlDbHelp implements interfaces\IDbHelp {
	use TRelation;

	protected $pdo;

	private $mode, $classname;

	private $error;

	private $entiy;

	public function __construct() {
		$this->pdo = PdoWarehouse::getInstance()->getPdo(PdoWarehouse::write);
		$this->mode = database\Model::fetch_arr_key;
	}

	/**
	 * 返回影响的行数
	 * @param  [type] $sql  [PDOStatement对象或者是sql语句]
	 * @param  array  $data [绑定的数据]
	 * @return [type]       [返回影响的行数]
	 */
	public function exec($sql, $data = []) {
		$this->pdo = PdoWarehouse::getInstance()->getPdo(PdoWarehouse::write);
		$data = $data == null ? [] : $data;
		if (is_string($sql)) {
			$sql = $this->pdo->prepare($sql);
			$this->sql_bind_execute($sql, $data);
		} else {
			if ($sql instanceof \PDOStatement) {
				$this->sql_bind_execute($sql, $data);
			} else {
				return 0;
			}
		}
		return $sql->rowCount();
	}

	/**
	 * 返回结果集
	 * @param  [type] $sql  [PDOStatement对象或者是sql语句]
	 * @param  array  $data [绑定的数据]
	 * @return array [返回结果集]
	 */
	public function queryResultArray($sql, $data = []) {
		$this->pdo = PdoWarehouse::getInstance()->getPdo(PdoWarehouse::read);
		$data = $data == null ? [] : $data;
		if (is_string($sql)) {
			$sql = $this->pdo->prepare($sql);
			$this->sql_bind_execute($sql, $data);
		} else {
			if ($sql instanceof \PDOStatement) {
				$this->sql_bind_execute($sql, $data);
			} else {
				return array();
			}
		}
		$result_data = [];
		while ($result = $sql->fetch($this->mode)) {
			$result_data[] = $this->select_relation($result);
		}
		return $result_data;
	}

	/**
	 * 返回结果集
	 * @param  [type] $sql  [PDOStatement对象或者是sql语句]
	 * @param  array  $data [绑定的数据]
	 * @return array [返回结果集]
	 */
	public function query($sql, $data = []) {
		$this->pdo = PdoWarehouse::getInstance()->getPdo(PdoWarehouse::read);
		$data = $data == null ? [] : $data;
		if (is_string($sql)) {
			$sql = $this->pdo->prepare($sql);
			$this->sql_bind_execute($sql, $data);
		} else {
			if ($sql instanceof \PDOStatement) {
				$this->sql_bind_execute($sql, $data);
			} else {
				yield null;
			}
		}
		while ($result = $sql->fetch($this->mode)) {
			yield $this->select_relation($result);
		}
		yield null;
	}

	private function bindData(&$sql, $data = []) {
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				$sql->bindValue($value[0], $value[1], $value[2]);
			} else {
				if (isset($data[2])) {
					$sql->bindValue($data[0], $data[1], $data[2]);
				} else {
					$sql->bindValue($data[0], $data[1]);
				}
				return;
			}
		}
	}

	public function PDOStatementFactory(&$PDOStatement) {
		if ($this->mode != database\Model::fetch_obj) {
			$PDOStatement->setFetchMode($this->mode);
		}
		if (!empty($this->mode) && !empty($this->classname) && $this->mode == database\Model::fetch_obj) {
			$PDOStatement->setFetchMode($this->mode, $this->classname);
		}
		$this->mode = database\Model::fetch_arr_key;
		$this->classname = null;
	}
	/**
	 * 设置遍历模式
	 * @param [type] $mode      [description]
	 * @param [type] $classname [指定FETCH_CLASS遍历模型对应的生成类]
	 */
	public function setFetchMode($mode, $classname = null) {
		$this->mode = $mode;
		$this->classname = $classname;
		return $this;
	}
	public function error() {
		return $this->error;
	}
	private function errorMessage($state) {
		$info = $state->errorInfo();
		$this->error = "[error:code]:" . $state->errorCode() . "[error:info]:";
		foreach ($info as $key => $value) {
			$this->error .= $value . "	";
		}
	}
	public function setEntiy($entiy) {
		$this->entiy = $entiy;
	}

	public function getDbConn() {
		return $this->pdo;
	}

	/**
	 * 解析获取关联的数据
	 * 并且合并到原始数据中
	 * @param  [type] $result [可以是数组也可以是对象]
	 * @return [type]         [description]
	 */
	private function select_relation($result) {
		$entiy_fill = $this->fillEntiy($this->entiy, $result);
		if ($entiy_fill instanceof IRelation) {
			$entiy_fill->rel(RelType::select, $entiy_fill);
		}
		$relation_data = $this->relation_select($entiy_fill);
		if (is_array($result)) {
			$result = array_merge($result, $relation_data);
		} else if (is_object($result)) {
			foreach ($relation_data as $key => $value) {
				$result->$key = $value;
			}
		}
		return $result;
	}

    /**
     * 更新
     */
	private function update(){
        if(is_object($result)){
            //取出实体类中的数据
            $result_arr[]=$result;
        }
    }

	/**
	 * 绑定sql数据并且执行sql
	 * @param  string $value [description]
	 * @return [type]        [description]
	 */
	private function sql_bind_execute(&$sql, $data) {
		$this->PDOStatementFactory($sql);
		$this->bindData($sql, $data);
		$sql->execute();
		$this->errorMessage($sql);
	}
}