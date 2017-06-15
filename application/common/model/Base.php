<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Base.php
 * | Description: model的基类 
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-6-15 15:00 
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Model;
use think\Db;

class Base extends Model 
{
	/**
	 * 获取空model
     * @return [type] [description]
	 */
	public function getEModel($tables)
	{
		$rs =  Db::query('show columns FROM `'.config('database.prefix').$tables."`");
		$obj = [];
		if($rs){
			foreach($rs as $key => $v) {
				$obj[$v['Field']] = $v['Default'];
				if($v['Key'] == 'PRI')$obj[$v['Field']] = 0;
			}
		}
		return $obj;
	}
}