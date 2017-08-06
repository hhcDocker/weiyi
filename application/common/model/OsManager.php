<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: OsManager.php
 * +----------------------------------------------------------------------
 * | Created by equinoxsun at 2017-08-05 01:49
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;

use think\Db;

class OsManager extends Base
{
    /**
     * 根据手机和密码 客户信息
     * @return [type] [description]
     */
    public function getOsManagerInfo($mobilephone, $password)
    {
        $result = array();
        if ($mobilephone && $password) {
            $password = md5($password);
            $db1 = db('os_managers', config('_database1'), true);
            $result = $db1
                ->where('is_deleted', 0)
                ->where('mobilephone', $mobilephone)
                ->where('password', $password)
                ->find();
        }
        return $result?$result:array();
    }

    /**
     * 根据手机查询信息
     * @return [type] [description]
     */
    public function getOsManagerInfoByMobile($mobilephone)
    {
        $result = array();
        if (!empty($mobilephone)) {
            $db1 = db('os_managers', config('_database1'), true);
            $result = $db1
                ->where('is_deleted', 0)
                ->where('mobilephone', $mobilephone)
                ->find();
        }
        return $result?$result:array();
    }

    /**
     * 根据手机查询是否是黑名单的人@flag (1电商,2实体,3admin,4工厂)
     * @return [type] [description]
     */
    public function getBlackListByMobile($mobilephone, $flag = 0)
    {
        $result = array();
        if (!empty($mobilephone)) {
            switch ($flag) {
                case 1:
                    $flag = 'is_ecommerce_forbidden';
                    break;
                case 2:
                    $flag = 'is_beauty_forbidden';
                    break;
                case 3:
                    $flag = 'is_bg_admin_forbidden';
                    break;
                case 4:
                    $flag = 'is_factory_forbidden';
                    break;
                default:
                    $flag = 'is_beauty_forbidden|is_ecommerce_forbidden|is_factory_forbidden|is_bg_admin_forbidden';
                    break;
            }

            $db1 = db('bg_blacklist', config('_database1'), true);
            $result = $db1
                ->where('mobilephone', $mobilephone)
                ->where($flag, 1)
                ->where('delete_time', null)
                ->find();
        }
        return $result;
    }

    /**
     * 修改用户password
     * @return [type] [description]
     */
    public function updateOsManagerPassword($id, $password = '')
    {
        $result = 0;
        if ($id && $password) {
            $password_md5 = md5($password);
            $result = Db::table('tp5_os_managers')->where('id', $id)->update(array('password' => $password_md5, 'update_time' => time()));
        }
        return $result;
    }
}

/*CREATE TABLE `tp5_os_managers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mobilephone` char(16) NOT NULL,
  `password` char(64) NOT NULL COMMENT '密码',
  `money` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '余额',
  `frozen_money` decimal(10,2) unsigned DEFAULT '0.00',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `origin_role` tinyint(1) unsigned NOT NULL DEFAULT '5' COMMENT '账号原先所在系统：1-电商，2-网点，3-工厂，4-微驿，5-官网',
  `origin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '账号在原先系统对应的id',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已删除',
  `register_ip` char(16) DEFAULT '' COMMENT '注册时所用的ip地址',
  `last_login_time` int(11) DEFAULT NULL,
  `last_login_ip` char(16) DEFAULT '' COMMENT '上次登录的ip地址',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  `update_time` int(10) unsigned DEFAULT NULL,
  `delete_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `msg` (`mobilephone`,`password`),
  KEY `phone` (`is_deleted`,`mobilephone`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='官网账号';


*/

