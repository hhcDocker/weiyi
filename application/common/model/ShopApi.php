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
    public function getShopData()
    {
        # code...
    }

    public function saveShopData($shop_service_id=0,$api_url='',$api_data='',$api_view='')
    {
    	if (!$shop_service_id ||!$api_url ||!$api_data) {
    		return 0;
    	}
    	$add_data = array(
    		'shop_service_id'=> $shop_service_id,
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

    public function softDeleteShopData($url='')
    {
    	# code...
    }
}
