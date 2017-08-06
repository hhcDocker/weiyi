<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Managers.php
 * | Description: 微驿用户数据
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
    public function getManagerInfo($mobilephone='', $password='')
    {
        $query_manager = array();
        if($mobilephone && $password)
        {
            $password = md5($password);
            $query_manager = Db::table('wj_managers')
                            ->where('mobilephone', $mobilephone)
                            ->where('password', $password)
                            // ->where('is_deleted', 0)
                            ->find();
        }
        return $query_manager;
    }

    /**
     * 根据手机和密码 查询客户信息
     * @return [type] [description]
     */
    public function getManagerInfoByMobilephone($mobilephone='')
    {
        $query_manager = array();
        if($mobilephone)
        {
            $query_manager = Db::table('wj_managers')
                            ->where('mobilephone', $mobilephone)
                            // ->where('is_deleted', 0)
                            ->find();
        }
        return $query_manager;
    }

    /**
     * 更新登录信息
     * @param  [type] $uid       [description]
     * @param  string $client_ip [description]
     * @return [type]            [description]
     */
    public function updateManagerLoginInfo($uid, $client_ip = '')
    {
        if (!$uid) {
            return 0;
        }
        $has_update = Db::table('wj_managers')
                            ->where('uid', $uid)
                            ->update([
                                    'last_login_time'  => time(),
                                    'last_login_ip'    => $client_ip,
                                 ]);
        return $has_update;
    }

    /**
     * 查询该手机号是否注册过
     * @param  [type]  $mobilephone [description]
     * @return boolean              [description]
     */
    public function hasManagerMobilephone($mobilephone='')
    {
        $count = -1;
        if($mobilephone)
        {
            $count = Db::table('wj_managers')
                            ->where('mobilephone', $mobilephone)
                            ->where('is_deleted', 0)
                            ->count();
        }
        return $count;
    }
    
    /**
     * 添加信息
     * @param string  $uid         [description]
     * @param string  $mobilephone [description]
     * @param string  $password    [description]
     * @param string  $register_ip [description]
     * @param integer $origin_role [1-电商，2-网点，3-工厂，4-微驿，5-官网]
     * @param integer $origin_id   [账号在原先系统对应的id]
     */
    public function addManagerInfo($uid='',$mobilephone='',$password='',$register_ip='',$origin_role=0,$origin_id=0)
    {
        if (!$uid || !$mobilephone || !$password || !$register_ip || !$origin_role) {
            return 0;
        }
        $password = md5($password);
        $manager_id = Db::table('wj_managers')->insertGetId([
                    'uid' => $uid,
                    'mobilephone' => $mobilephone,
                    'password' => $password,
                    'origin_role' => $origin_role,
                    'origin_id' => $origin_id,
                    'is_locked' =>0,
                    'create_time' => time(),
                    'register_ip' => $register_ip,
                    'update_time' => time()
            ]);
        return $manager_id;
    }

    /**
     * 更新密码
     * @param  string $mobilephone [description]
     * @param  string $password    [description]
     * @return [type]              [description]
     */
    public function updateManagerPassword($mobilephone='',$password='')
    {
        if (!$mobilephone || !$password) {
            return 0;
        }
        $password = md5($password);
        $has_update = Db::table('wj_managers')
                            ->where('mobilephone', $mobilephone)
                            ->update([
                                    'password'  => $password,
                                    'update_time'    => time(),
                                 ]);
        return $has_update;
    }
}

/*CREATE TABLE `wj_managers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(32) NOT NULL DEFAULT '' COMMENT 'md5生成的随机定长字符串',
  `mobilephone` char(16) NOT NULL DEFAULT '',
  `password` char(64) NOT NULL DEFAULT '',
  `origin_role` tinyint(1) unsigned NOT NULL DEFAULT '4' COMMENT '账号原先所在系统：1-电商，2-网点，3-工厂，4-微驿，5-官网',
  `origin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '账号在原先系统对应的id',
  `is_locked` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `register_ip` char(16) NOT NULL DEFAULT '',
  `last_login_ip` char(16) NOT NULL DEFAULT '',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `info` (`is_deleted`,`mobilephone`,`password`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COMMENT='微驿客户信息表';
*/
