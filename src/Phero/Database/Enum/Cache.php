<?php 

namespace Phero\Database\Enum;
/**
 * @Author: lerko
 * @Date:   2017-06-08 17:24:28
 * @Last Modified by:   lerko
 * @Last Modified time: 2017-06-08 20:55:14
 */
class Cache{
	public $liveTime=null;
	public function __construct($liveTime=null){
		$this->liveTime=$liveTime;
	}
}