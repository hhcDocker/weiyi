<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Factory.php
 * | Description: 工厂和工厂管理员所有操作的model类
 * +----------------------------------------------------------------------
 * | Created by equinoxsun at 2017-08-04 11:49
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Db;

class Factory extends Base
{    
    public function getFactoryManagerInfoByMobile($mobilephone)
	{
		$db2 = db('seller_managers', config('_database2'), true);
		$result = $db2
                      ->where('is_deleted', 0)
					  ->where('mobilephone', $mobilephone)
					  ->find();	
		return $result;
	}

    public function getFactoryManagerInfo($mobilephone, $password)
	{
        if (!$mobilephone || !$password) {
            return array();
        }
        $password = md5($password);
		$db2 = db('seller_managers', config('_database2'), true);
		$result = $db2
                    ->where('is_deleted', 0)
					->where('mobilephone', $mobilephone)
                	->where('password', $password)
					->find();
		return $result?$result:array();
	}

	public function updateFactoryPassword($id, $password = '')
	{
        $result = 0;
        if ($id && $password) {
            $password_md5 = md5($password);
			$db2 = db('seller_managers', config('_database2'), true);
			$result = $db2->where('id', $id)->update(array('password' => $password_md5, 'update_time' => time()));
        }
        return $result;
	}
}

/*CREATE TABLE `dm2_seller_managers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `uid` char(32) NOT NULL DEFAULT '' COMMENT 'md5生成的长串id',
  `boss_uid` char(32) NOT NULL DEFAULT '' COMMENT '超级管理员uid',
  `username` char(64) NOT NULL DEFAULT '' COMMENT '用户名（登录名或者昵称）',
  `manager_name` char(64) NOT NULL DEFAULT '' COMMENT 'Manager，真实姓名',
  `role_id` smallint(2) unsigned NOT NULL DEFAULT '0' COMMENT '角色id，每个人只能在同一个超级管理员定义的角色中拥有一个角色',
  `password` char(64) NOT NULL DEFAULT '' COMMENT '密码',
  `headimg` varchar(32) NOT NULL DEFAULT '' COMMENT '头像',
  `mobilephone` char(16) NOT NULL DEFAULT '' COMMENT '管理员手机号码',
  `phone` char(16) NOT NULL DEFAULT '' COMMENT '座机号码',
  `qq` char(16) NOT NULL DEFAULT '' COMMENT 'qq',
  `wechat` char(64) NOT NULL DEFAULT '' COMMENT '管理员wechat',
  `wechat_token_id` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员wechat_token_id',
  `aliwangwang` char(64) NOT NULL DEFAULT '' COMMENT '阿里旺旺',
  `email` char(64) NOT NULL DEFAULT '',
  `is_registered` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否注册完整（第一步是1，第二步是2，完成是3）',
  `islocked` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否锁定',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `manager_gender` char(8) NOT NULL DEFAULT '' COMMENT '负责人身份证性别',
  `manager_nationality` char(16) NOT NULL DEFAULT '' COMMENT '负责人身份证民族',
  `manager_birthday` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '负责人身份证生日',
  `manager_address` char(64) NOT NULL DEFAULT '' COMMENT '负责人身份证地址',
  `manager_id_num` char(32) NOT NULL DEFAULT '' COMMENT '负责人身份证号码',
  `manager_id_photo` varchar(255) NOT NULL DEFAULT '' COMMENT '负责人身份证照片',
  `manager_id_photo2` varchar(255) NOT NULL DEFAULT '' COMMENT '负责人身份证反面照片',
  `manager_id_photo3` varchar(255) NOT NULL DEFAULT '' COMMENT '手持身份证照片',
  `register_ip` char(16) NOT NULL DEFAULT '' COMMENT '注册时所用的ip地址',
  `last_login_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '上次登陆时间',
  `last_login_ip` char(16) NOT NULL DEFAULT '' COMMENT '上次登录的ip地址',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0-未删除，1-已删除）',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`,`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8 COMMENT='卖家（工厂）人员信息';*/