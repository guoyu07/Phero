<?php
namespace Phero\Database\Realize;

use Phero\Database\Enum\DatabaseConfig;
use Phero\Database\PDO;
use Phero\System\Config;
use Phero\System\DI;
use Phero\System\Traits\TInject;

/**
 *pdo链接仓库
 *pdo链接从这里获取
 *这里会对多台mysql机器进行分配
 */
class PdoWarehouse {
	use TInject;

	protected $pdo_hit;
	private static $already_init = false;

	/**
	 * @Inject[di=pdo_instance]
	 * @var [type]
	 */
	protected $pdo;

	CONST read = 0;
	CONST write = 1;

	private function __construct() {}
	public static function getInstance() {
		return new PdoWarehouse();
	}

	//根据数据
	public function getPdo($pattern) {
		$database_config = Config::config("database");
		$hit_classname = Config::config('hit_rule');
		if (empty($hit_classname)) {
			$hit_classname = "Phero\Database\Realize\Hit\RandomSlaveHit";
		}
		$this->pdo_hit = new $hit_classname;
		$this->init($database_config);
		//注入后解析
		$this->inject();
		if (is_array($this->pdo)&&!empty($this->pdo['slave'])&&!empty($this->pdo['master'])) {
			if ($pattern == 0) {
				$pdo = $this->pdo_hit->hit($this->pdo['slave']);
			} else {
				$pdo = $this->pdo['master'];
			}
		} else if(is_array($this->pdo)&&empty($this->pdo['slave'])&&!empty($this->pdo['master'])) {
			$pdo = $this->pdo['master'];
		}else{
			$pdo=$this->pdo;
		}
		$charset = Config::config('hit_rule');
		$charset = empty($charset) ? "utf8" : $charset;
		$pdo->exec("set names $charset");
		$pdo->exec("set character_set_client=$charset");
		$pdo->exec("set character_set_results=$charset");
		//PDO::ATTR_STRINGIFY_FETCHES 提取的时候将数值转换为字符串。 
		//PDO::ATTR_EMULATE_PREPARES 启用或禁用预处理语句的模拟。
		$pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
		$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		return $pdo;
	}
	private function init($config) {
		$pdo_di = DI::get(DatabaseConfig::pdo_instance);
		if ($pdo_di) {
			return;
		}
		if (array_key_exists("dsn", $config)) {
			DI::inj(DatabaseConfig::pdo_instance, new PDO($config['dsn'], $config['user'], $config['password']));
		} elseif (array_key_exists('master', $config)) {
			$master = $config['master'];
			$slave_pdo = [];
			if (!empty($config['slave'])){
				$slave = $config['slave'];
				foreach ($slave as $value) {
					$slave_pdo[] = new PDO($value['dsn'], $value['user'], $value['password']);
				}
			}
			$master_pdo=[];
			if(!empty($config['master'])&&is_array($config['master'])&&!array_key_exists("dsn",$config['master'])){
				foreach ($config['master'] as $key => $value) {
					$master_pdo[]=new PDO($value['dsn'], $value['user'], $value['password']);
				}
			}else if(!empty($config['master'])){
				$value=$config['master'];
				$master_pdo=new PDO($value['dsn'], $value['user'], $value['password']);
			}
			$pdo = [
				"master" => $master_pdo,
				"slave" => $slave_pdo,
			];
			DI::inj(DatabaseConfig::pdo_instance, $pdo);
		}
	}
}