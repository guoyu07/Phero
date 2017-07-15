<?php
namespace Phero\Database\Realize;

use Phero\Database\Interfaces\IDbHelp;
use Phero\Database\Realize\MysqlDbHelp;
use Phero\System\Config;
/**
 *
 */
class SwooleMysqlDbHelp extends MysqlDbHelp implements IDbHelp
{
    /**
     * @hit
     */
	public function queryResultArray($sql, $data=[]){
        $client = $this->_get_swoole_client();
        $client->send(serialize([$sql,$data]));
        $data=unserialize($client->recv());
        $client->close();
        return $data;
    }

    /**
     * 获取一个根据配置的swoole客户端
     * @method _get_swoole_client
     * @param  string             $value [description]
     * @return [type]                    [description]
     */
    private function _get_swoole_client()
    {
        $swoole_config=Config::config("swoole");
        $swoole_client = new \swoole_client(SWOOLE_SOCK_TCP);
        if(empty($swoole_config))
            $connect=$swoole_client->connect('127.0.0.1',54288,-1);
        else
            $connect=$swoole_client->connect($swoole_config['ip'],$swoole_config['port'],-1);
        if(!$connect){
            throw new \Exception("swoole connection exception", 1);

        }
        return $swoole_client;
    }
}