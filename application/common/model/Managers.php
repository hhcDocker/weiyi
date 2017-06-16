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
     * 通过shop_url获取api接口数据
     * @param  integer $shop_url [description]
     * @return [type]            [description]
     */
    public function getShopDataByShopUrl($shop_url='')
    {
        if (!$shop_url) {
            return array();
        }
        $shop_url = trim($shop_url);
        $api_info = Db::table('wj_shop_api')->where('shop_url',$shop_url)->where('is_deleted',0)->select();
        return $api_info;
    }

    /**
     * 保存店铺api店铺数据
     * @param  integer $shop_url [description]
     * @param  string  $api_url  [description]
     * @param  string  $api_data [description]
     * @param  string  $api_view [description]
     * @return [type]            [description]
     */
    public function saveShopData($shop_url='',$api_url='',$api_data='',$api_view='')
    {
    	if (!$shop_url ||!$api_url ||!$api_data) {
    		return 0;
    	}
        $shop_url = trim($shop_url);
    	$add_data = array(
    		'shop_url'=> $shop_url,
    		'api_url'=> $api_url,
    		'api_data'=> $api_data,
    		'api_view'=> $api_view,
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
     * @param  string $shop_url [description]
     * @return [type]           [description]
     */
    public function softDeleteShopDataByShopUrl($shop_url='')
    {
        if (!$shop_url) {
            return 0;
        }
        $shop_url = trim($shop_url);
        $has_delete = Db::table('wj_shop_api')->where('shop_url',$shop_url)->update(array('is_deleted'=>1,'delete_time'=>time()));
        return $has_delete;
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
