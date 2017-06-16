<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Managers.php
 * | Description: 店铺接口数据
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-6-15 15:00 
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
 
namespace app\common\model;
use think\Db;

class Managers extends Base
{ 
    /**
     * 根据手机和密码 查询客户信息
     * @return [type] [description]
     */
    public function getManagerInfo($mobilephone, $password)
    {
        $query_manager = array();
        if(!empty($mobilephone) && !empty($password))
        {
            $query_manager = Db::name('managers')
                            ->where('mobilephone', $mobilephone)
                            ->where('password', $password)
                            ->where('is_deleted', 0)
                            ->find();
        }
        return $query_manager;
    }

    public function updateManagerInfo($uid, $client_ip = '')
    {
        if (!$uid) {
            return 0;
        }
        $has_update = Db::name('managers')
                            ->where('uid', $uid)
                            ->update([
                                    'last_login_time'  => ['exp', 'now()'],
                                    'last_login_ip'    => $client_ip,
                                 ]);
        return $has_update;
    }

}

/*CREATE TABLE `wj_managers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(32) NOT NULL COMMENT 'md5生成的随机定长字符串',
  `mobilephone` char(16) DEFAULT NULL,
  `password` char(64) DEFAULT NULL,
  `is_locked` tinyint(1) unsigned DEFAULT '0',
  `create_time` int(11) unsigned NOT NULL,
  `register_ip` char(16) DEFAULT NULL,
  `last_login_time` datetime DEFAULT NULL,
  `last_login_ip` char(16) DEFAULT NULL,
  `update_time` int(11) unsigned NOT NULL,
  `delete_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微跳客户信息表';
*/
