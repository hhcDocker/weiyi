<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Others.php
 * | Description: 各种配置数据
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-7-26 10:00
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Db;

class Others extends Base
{
    public function getValueByKey($key='')
    {
        if (!$key) {
            return '';
        }
        $res = Db::table('wj_others')->where('wj_key',$key)->value('wj_value');
        return $res;
    }
}
/*CREATE TABLE `wj_others` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wj_key` varchar(255) NOT NULL,
  `wj_value` varchar(255) NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;*/
