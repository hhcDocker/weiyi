<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: ShopServices.php
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

class ShopServices extends Base
{
    public function getServicesByShopId($shop_id='',$manager_id='')
    {
        if (!$shop_id) {
            return array();
        }
        $res = Db::table('wj_shop_services')->where('shop_id',$shop_id)->where('manager_id',$manager_id)->where('is_deleted',0)->find();
        return $res?$res:array();
    }

    /**
     * [getServicesByShopId description]
     * @param  string $shop_id    [description]
     * @param  string $manager_id [description]
     * @return [type]             [description]
     */
    public function getServicesByAliShopId($ali_shop_id='')
    {
        if (!$ali_shop_id) {
            return array();
        }
        $res = Db::table('wj_shop_services w')
                ->join('wj_ali_shops a','a.id =w.shop_id','left')
                ->where('a.ali_shop_id',$ali_shop_id)
                ->where('w.is_deleted',0)
                ->select();
        return $res?$res:array();
    }

    /**
     * [getServicesById description]
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function getServicesById($id=0)
    {
        if (!$id) {
            return array();
        }
        $res = Db::table('wj_shop_services')->where('id',$id)->where('is_deleted',0)->find();
        return $res?$res:array();
    }

    /**
     * [getServicesByManagerId description]
     * @param  integer $manager_id [description]
     * @return [type]              [description]
     */
    public function getServicesByManagerId($manager_id=0)
    {
        if (!$manager_id) {
            return array();
        }
        $res = Db::table('wj_shop_services')->where('manager_id',$manager_id)->where('is_deleted',0)->select();
        return $res?$res:array();
    }

    /**
     * [getServicesByUrlStr description]
     * @param  integer $url_str [description]
     * @return [type]              [description]
     */
    public function getServicesByUrlStr($url_str='')
    {
        if (!$url_str) {
            return array();
        }
        $res = Db::table('wj_shop_services')->where('transformed_url',$url_str)->where('is_deleted',0)->find();
        return $res?$res:array();
    }

    /**
     * [getServicesExpenseByShopId description]
     * @param  [type] $shop_id    [description]
     * @param  string $manager_id [description]
     * @return [type]             [description]
     */
    public function getServicesExpenseByShopId($shop_id=0,$manager_id=0)
    {
        if (!$shop_id ||!$manager_id) {
            return array();
        }
        $res = Db::table('wj_shop_services w')
                ->join('wj_expense_records r','r.service_id = w.id and r.trade_status=1 and r.is_deleted=0','left')
                ->where('w.shop_id',$shop_id)
                ->where('w.is_deleted',0)
                ->order('r.id desc')
                ->find();
        return $res?$res:array();
    }

    /**
     * [saveServices description]
     * @param  integer $manager_id         [description]
     * @param  string  $shop_id            [description]
     * @param  string  $transformed_url    [description]
     * @param  integer $service_start_time [description]
     * @param  integer $service_end_time   [description]
     * @return [type]                      [description]
     */
    public function saveServices($manager_id=0,$shop_id='',$transformed_url='',$service_start_time=0,$service_end_time=0)
    {
    	if (!$manager_id ||!$shop_id ||!$transformed_url ||!$service_start_time ||!$service_end_time) {
    		return 0;
    	}
    	$add_data = array(
            'manager_id'=>$manager_id,
    		'shop_id'=> $shop_id,
    		'transformed_url'=> $transformed_url,
    		'service_start_time'=> $service_start_time,
            'service_end_time'=>$service_end_time,
    		'is_deleted'=> 0,
    		'create_time'=> time(),
    		'update_time'=> time(),
    	);
    	$has_add = Db::table('wj_shop_services')->insertGetId($add_data);
        return $has_add;
    }

    /**
     * [addServices description]
     * @param  integer $manager_id         [description]
     * @param  string  $shop_id            [description]
     * @param  string  $transformed_url    [description]
     * @param  integer $shop_name          [description]
     * @param  integer $shop_url           [description]
     * @return [type]                      [description]
     */
    public function addServices($manager_id=0,$shop_id='',$transformed_url='',$shop_name='',$shop_url='')
    {
        if (!$manager_id ||!$shop_id ||!$transformed_url ||!$shop_name ||!$shop_url) {
            return 0;
        }
        $add_data = array(
            'manager_id'=>$manager_id,
            'shop_id'=> $shop_id,
            'transformed_url'=> $transformed_url,
            'shop_name'=> $shop_name,
            'shop_url'=>$shop_url,
            'is_deleted'=> 0,
            'create_time'=> time(),
            'update_time'=> time(),
        );
        $has_add = Db::table('wj_shop_services')->insertGetId($add_data);
        return $has_add;
    }

    /**
     * [updateShopNameUrl description]
     * @param  integer $id        [description]
     * @param  string  $shop_name [description]
     * @param  string  $shop_url  [description]
     * @return [type]             [description]
     */
    public function updateShopNameUrl($id=0,$shop_name='',$shop_url='')
    {
        if (!$id) {
            return 0;
        }
        $update_data = array(
            'shop_name' =>$shop_name,
            'shop_url' =>$shop_url,
            'update_time'=>time()
        );
        $has_update = Db::table('wj_shop_services')->where('id',$id)->update($update_data);
        return $has_update;
    }

    /**
     * [updateShopNameUrl description]
     * @param  integer $id        [description]
     * @param  string  $shop_name [description]
     * @param  string  $shop_url  [description]
     * @return [type]             [description]
     */
    public function updateShopServiceTime($id=0,$service_start_time='',$service_end_time='')
    {
        if (!$id ||!$service_start_time ||!$service_end_time) {
            return 0;
        }
        $update_data = array(
            'service_start_time' =>$service_start_time,
            'service_end_time' =>$service_end_time,
            'update_time'=>time()
        );
        $has_update = Db::table('wj_shop_services')->where('id',$id)->update($update_data);
        return $has_update;
    }

    /**
     * [ExistShortUrl description]
     * @param string $short_url [description]
     */
    public function ExistShortUrl($transformed_url='')
    {
        if (!$transformed_url) {
            return -1;
        }
        $res = Db::table('wj_shop_services')->where('transformed_url',$transformed_url)->where('is_deleted',0)->count();
        return $res;
    }

    public function softDeleteShopData($url='')
    {
    	# code...
    }

}
/*CREATE TABLE `wj_shop_services` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `manager_id` int(10) unsigned NOT NULL COMMENT '客户id',
  `shop_id` int(10) unsigned NOT NULL COMMENT '对应店铺表id',
  `transformed_url` varchar(255) DEFAULT NULL COMMENT '本站转换链接',
  `shop_name` varchar(255) DEFAULT NULL COMMENT '客户填写的店铺名称',
  `shop_url` varchar(255) DEFAULT NULL COMMENT '客户填写的店铺首页网址',
  `service_start_time` int(11) unsigned NOT NULL COMMENT '服务开始时间，当前时间所在服务的开始时间',
  `service_end_time` int(11) unsigned NOT NULL COMMENT '服务结束时间',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned DEFAULT NULL,
  `update_time` int(11) unsigned DEFAULT NULL,
  `delete_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8 COMMENT='微跳服务信息';
*/

