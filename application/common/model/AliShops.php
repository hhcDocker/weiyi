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
    public function getShopInfoByUrl($shop_url='')
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
        $res = Db::table('wj_ali_shops')->where('ali_shop_id',$ali_shop_id)->where('is_deleted',0)->find();
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
        $shop_url = trim($shop_url);
        $ali_shop_id = intval($ali_shop_id);
        $res = Db::table('wj_ali_shops')->where('shop_url="'.$shop_url.'" or ali_shop_id='.$ali_shop_id)->where('is_deleted',0)->find();
        return $res?$res:array();
    }

    /**
     * [getShopInfoById description]
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function getShopInfoById($id=0)
    {
        if (!$id) {
            return array();
        }
        $res = Db::table('wj_ali_shops')->where('id',$id)->where('is_deleted',0)->find();
        return $res?$res:array();
    }

    public function getAllShop()
    {
        $res = Db::table('wj_ali_shops')->where('is_deleted',0)->select();
        return $res;
    }

    /**
     * 保存链接信息
     * @param  integer $ali_shop_id [description]
     * @param  string  $shop_url    [description]
     * @return [type]               [description]
     */
    public function saveShopInfo($ali_shop_id=0,$shop_url='')
    {
    	if (!$ali_shop_id  ||!$shop_url ) {
    		return 0;
    	}
    	$add_data = array(
    		'ali_shop_id'=> $ali_shop_id,
            'shop_url'=>$shop_url,
    		'is_deleted'=> 0,
    		'create_time'=> time(),
    		'update_time'=> time(),
    	);
    	$shop_id =Db::table('wj_ali_shops')->insertGetId($add_data);
        return $shop_id;
    }

    /**
     *
     * @param  [type] $ali_shop_id [description]
     * @param  [type] $shop_url    [description]
     * @return [type]              [description]
     */
    public function updateShopUrl($ali_shop_id=0,$shop_url='')
    {
        if (!$ali_shop_id ||!$shop_url ) {
            return 0;
        }
        $update_data = array(
            'shop_url'=>$shop_url,
            'update_time'=> time(),
        );
        $has_update =Db::table('wj_ali_shops')->where('ali_shop_id',$ali_shop_id)->update($update_data);
        return $has_update;
    }

    /**
     * 更新店铺商品信息
     */
    public function updateShopById($id=0,$total_page=0,$page_size=0,$total_results=0)
    {
        if (!$id ||!$page_size) {
            return 0;
        }
        $update_data = array(
            'total_page'=>$total_page,
            'page_size'=>$page_size,
            'total_results'=>$total_results,
            'update_time'=> time(),
        );
        $has_update =Db::table('wj_ali_shops')->where('id',$id)->update($update_data);
        return $has_update;
    }
}
/*CREATE TABLE `wj_ali_shops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `ali_shop_id` int(10) unsigned NOT NULL COMMENT '淘宝天猫店铺id，可根据shopId拼成店铺链接（淘宝以https://shop34189316.taobao.com形式）',
  `shop_url` varchar(255) DEFAULT NULL COMMENT '店铺链接，天猫以https://lanhuqcyp.m.tmall.com形式，淘宝以https://shop.m.taobao.com/shop/shop_index.htm?user_id=2256365969形式',
  `total_page` mediumint(5) unsigned DEFAULT NULL COMMENT '店铺商品列表页数',
  `page_size` tinyint(2) unsigned DEFAULT NULL COMMENT '店铺商品每页商品数量，当前为24个商品',
  `total_results` int(10) unsigned DEFAULT NULL COMMENT '店铺商品总数量',
  `is_deleted` tinyint(1) unsigned DEFAULT '0',
  `create_time` int(10) unsigned DEFAULT NULL,
  `update_time` int(10) unsigned DEFAULT NULL,
  `delete_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ali_shop_id` (`ali_shop_id`),
  KEY `shop_url` (`shop_url`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8;*/
