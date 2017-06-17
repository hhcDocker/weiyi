<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: AliShops.php
 * | Description: 淘宝天猫店铺链接数据
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-6-15 15:00 
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
 
namespace app\common\model;
use think\Db;

class AliShops extends Base
{
    /**
     * [getShopInfoByShopUrl description]
     * @param  string $shop_url [description]
     * @return [type]           [description]
     */
    public function getShopInfoByShopUrl($shop_url='')
    {
        if (!$shop_url) {
            return array();
        }
        $res = Db::table('wj_ali_shops')->where('shop_url',$shop_url)->where('is_deleted',0)->find();
        return $res?$res:array();
    }

    /**
     * [getShopInfoByShopId description]
     * @param  integer $ali_shop_id [description]
     * @return [type]               [description]
     */
    public function getShopInfoByShopId($ali_shop_id=0)
    {
        if (!$ali_shop_id) {
            return array();
        }
        $res = Db::table('wj_shop_services')->where('ali_shop_id',$ali_shop_id)->where('is_deleted',0)->find();
        return $res?$res:array();
    }

    /**
     * [getShopInfoByIdUrl description]
     * @param  string  $shop_url    [description]
     * @param  integer $ali_shop_id [description]
     * @return [type]               [description]
     */
    public function getShopInfoByIdUrl($shop_url='',$ali_shop_id=0)
    {
        if (!$ali_shop_id || !$shop_url) {
            return array();
        }
        $res = Db::table('wj_shop_services')->where('ali_shop_id',$ali_shop_id)->whereOr('ali_shop_id',$ali_shop_id)->find();
        return $res?$res:array();
    }
    /**
     * 保存链接信息
     * @param  integer $manager_id         [description]
     * @param  string  $shop_url           [description]
     * @param  string  $transformed_url    [description]
     * @param  integer $service_start_time [description]
     * @param  integer $service_end_time   [description]
     * @return [type]                      [description]
     */
    public function saveShopInfo($ali_shop_id=0,$is_tmall=0,$shop_url='')
    {
    	if (!$ali_shop_id ||!$is_tmall ||!$shop_url ) {
    		return 0;
    	}
    	$add_data = array(
    		'ali_shop_id'=> $ali_shop_id,
    		'is_tmall'=> $is_tmall,
            'shop_url'=>$shop_url,
    		'is_deleted'=> 0,
    		'create_time'=> time(),
    		'update_time'=> time(),
    	);
    	$has_add =Db::table('wj_shop_services')->insertGetId($add_data);
        return $has_add;
    }

}
/*CREATE TABLE `wj_ali_shops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `ali_shop_id` int(10) unsigned NOT NULL COMMENT '淘宝天猫店铺id，可根据shopId拼成店铺链接（淘宝以https://shop34189316.taobao.com形式）',
  `is_tmall` tinyint(1) unsigned NOT NULL COMMENT '淘宝天猫标识位，0-淘宝，1-天猫',
  `shop_url` varchar(255) DEFAULT NULL COMMENT '店铺链接，天猫以https://lanhuqcyp.m.tmall.com形式，淘宝以https://shop.m.taobao.com/shop/shop_index.htm?user_id=2256365969形式',
  `is_deleted` tinyint(1) unsigned DEFAULT '0',
  `create_time` int(10) unsigned DEFAULT NULL,
  `update_time` int(10) unsigned DEFAULT NULL,
  `delete_-time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;*/

