<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: ShopApi.php
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

class ShopApi extends Base
{
    /**
     * 通过shop_id获取api接口数据
     * @param  integer $shop_id [description]
     * @return [type]            [description]
     */
    public function getShopDataByShopId($shop_id='')
    {
        if (!$shop_id) {
            return array();
        }
        $api_info = Db::table('wj_shop_api')->where('shop_id',$shop_id)->where('is_deleted',0)->select();
        return $api_info;
    }

    /**
     * 保存店铺api店铺数据
     * @param  integer $shop_id [description]
     * @param  string  $api_url  [description]
     * @param  string  $api_data [description]
     * @param  string  $api_view [description]
     * @return [type]            [description]
     */
    public function saveShopData($shop_id='',$api_url='',$api_data='')
    {
    	if (!$shop_id ||!$api_url ||!$api_data) {
    		return 0;
    	}
    	$add_data = array(
    		'shop_id'=> $shop_id,
    		'api_url'=> $api_url,
    		'api_data'=> $api_data,
    		'is_deleted'=> 0,
    		'create_time'=> time(),
    		'update_time'=> time(),
    	);
    	$has_add =Db::table('wj_shop_api')->insertGetId($add_data);
        return $has_add;
    }

    /**
     * 批量添加店铺接口数据
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function batchAddShopData($data=array())
    {
        if (empty($data)) {
            return 0;
        }
        $has_add = Db::table('wj_shop_api')->insertAll($data);
        return $has_add;
    }

    /**
     * 根据链接软删除接口数据
     * @param  string $shop_id [description]
     * @return [type]           [description]
     */
    public function softDeleteShopDataByShopId($shop_id='')
    {
        if (!$shop_id) {
            return 0;
        }
        $has_delete = Db::table('wj_shop_api')->where('shop_id',$shop_id)->update(array('is_deleted'=>1,'delete_time'=>time()));
        return $has_delete;
    }
}

/*CREATE TABLE `wj_shop_api` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `shop_id` int(10) unsigned NOT NULL COMMENT '对应店铺表主键id',
  `api_url` varchar(255) NOT NULL COMMENT '请求的api接口链接',
  `api_data` mediumtext NOT NULL COMMENT 'api请求到的data串',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned DEFAULT NULL,
  `update_time` int(11) unsigned DEFAULT NULL,
  `delete_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB AUTO_INCREMENT=424 DEFAULT CHARSET=utf8 COMMENT='淘宝天猫店铺主页接口数据';
*/
