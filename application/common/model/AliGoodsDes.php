<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: AliGoodsDes.php
 * | Description: 淘宝商品图文详情数据
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-6-21 15:00
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Db;

class AliGoodsDes extends Base
{
    /**
     * 通过item_id获取api接口数据
     * @param  integer $shop_id [description]
     * @return [type]            [description]
     */
    public function getDesDataByItemId($item_id=0)
    {
        if (!$item_id) {
            return array();
        }
        $des_info = Db::table('wj_ali_goods_des')->where('item_id',$item_id)->find();
        return $des_info;
    }

    /**
     * 保存接口数据
     * @param integer $item_id [description]
     * @param string  $data    [description]
     */
    public function addShopData($item_id=0,$data='')
    {
    	if (!$item_id ||!$data) {
    		return 0;
    	}
    	$add_data = array(
    		'item_id'=> $item_id,
    		'data'=> $data,
    		'create_time'=> time(),
    		'update_time'=> time(),
    	);
    	$des_id =Db::table('wj_ali_goods_des')->insertGetId($add_data);
        return $des_id;
    }

    /**
     * 根据item_id软删除接口数据
     * 不用软删除
     * @param  string $item_id [description]
     * @return [type]           [description]
     */
    public function softDeleteDesDataByItemId($item_id=0)
    {
        if (!$item_id) {
            return 0;
        }
        $has_delete = Db::table('wj_ali_goods_des')->where('item_id',$item_id)->update(array('is_deleted'=>1,'delete_time'=>time()));
        return $has_delete;
    }

    /**
     * 更新数据
     */
    public function updateDesDataById($id=0,$data='')
    {
        if (!$id || !$data) {
            return 0;
        }
        $update_data = array(
            'data'=> $data,
            'update_time'=> time(),
        );
        $has_update = Db::table('wj_ali_goods_des')->where('id',$id)->update($update_data);
        return $has_update;
    }
}

/*CREATE TABLE `wj_ali_goods_des` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `data` text NOT NULL,
  `create_time` int(11) unsigned NOT NULL,
  `update_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='淘宝商品图文详情数据';
*/
