<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Services.php
 * | Description: 店铺服务数据
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-6-15 15:00 
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
 
namespace app\common\model;
use think\Db;

class Services extends Base
{
    public function getServicesByShopUrl($shop_url='')
    {
        if (!$shop_url) {
            return array();
        }
        $res = Db::table('wj_services')->where('shop_url',$shop_url)->where('is_deleted',0)->find();
        return $res?$res:array();
    }

    public function getServicesByManagerId($manager_id=0)
    {
        if (!$manager_id) {
            return array();
        }
        $res = Db::table('wj_services')->where('manager_id',$manager_id)->where('is_deleted',0)->select();
        return $res?$res:array();
    }

    public function saveServices($manager_id=0,$shop_url='',$transformed_url='',$service_start_time=0,$service_end_time=0)
    {
    	if (!$manager_id ||!$shop_url ||!$transformed_url ||!$service_start_time ||!$service_end_time) {
    		return 0;
    	}
    	$add_data = array(
    		'shop_url'=> $shop_url,
    		'transformed_url'=> $transformed_url,
    		'service_start_time'=> $service_start_time,
            'service_end_time'=>$service_end_time,
    		'is_deleted'=> 0,
    		'create_time'=> time(),
    		'update_time'=> time(),
    	);
    	$has_add =Db::table('wj_services')->insertGetId($add_data);
        return $has_add;
    }

    public function softDeleteShopData($url='')
    {
    	# code...
    }
}
/*CREATE TABLE `wj_services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `manager_id` int(10) unsigned NOT NULL COMMENT '客户id',
  `shop_url` varchar(255) NOT NULL COMMENT '店铺首页网址',
  `transformed_url` varchar(255) DEFAULT NULL COMMENT '本站转换链接',
  `service_start_time` int(11) unsigned NOT NULL COMMENT '服务开始时间，当前时间所在服务的开始时间',
  `service_end_time` int(11) unsigned NOT NULL COMMENT '服务结束时间',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned DEFAULT NULL,
  `update_time` int(11) unsigned DEFAULT NULL,
  `delete_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;*/

