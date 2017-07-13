<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: AliShopGoodsList.php
 * | Description: 店铺商品列表接口信息
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-7-13 15:00
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Db;

class AliShopGoodsList extends Base
{
    /**
     * 通过wj_shop_id获取接口数据
     * @param  integer $wj_shop_id [description]
     * @return [type]              [description]
     */
    public function getGoodsListByShopId($wj_shop_id=0,$page_index=1)
    {
        if (!$wj_shop_id || !$page_index) {
            return array();
        }
        $list_info = Db::table('wj_ali_shop_goods_list')->where('wj_shop_id',$wj_shop_id)->where('page_index',$page_index)->find();
        return $list_info;
    }

    /**
     * 保存接口数据
     * @param integer $wj_shop_id [description]
     * @param string  $data    [description]
     */
    public function batchAddGoodsListData($wj_shop_id=0,$data=array())
    {
    	if (!$wj_shop_id ||!$data || empty($data)) {
    		return 0;
    	}
        $add_data = array();
        foreach ($data as $k => $v) {
            $add_data[] = array(
                'wj_shop_id' => $wj_shop_id,
                'items' => $v['items'],
                'page_index' =>$v['page_index']
            );
        }
    	$has_add =Db::table('wj_ali_goods_des')->insertAll($add_data);
        return $has_add;
    }

    /**
     * 根据wj_shop_id删除接口数据
     * @param  string $wj_shop_id [description]
     * @return [type]           [description]
     */
    public function deleteListDataByItemId($wj_shop_id=0)
    {
        if (!$wj_shop_id) {
            return 0;
        }
        $has_delete = Db::table('wj_ali_shop_goods_list')->where('wj_shop_id',$wj_shop_id)->delete();
        return $has_delete;
    }

}

/*CREATE TABLE `wj_ali_shop_goods_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `wj_shop_id` int(10) unsigned NOT NULL COMMENT '对应wj_ali_shops表的主键',
  `items` text NOT NULL COMMENT '对应页码的商品数据',
  `page_index` tinyint(3) unsigned NOT NULL COMMENT '页码',
  PRIMARY KEY (`id`),
  KEY `wj_shop_id` (`wj_shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/
