<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: AliTmGoodsDetail.php
 * | Description: 淘宝商品图文详情数据
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-6-26 15:00
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Db;

class AliTmGoodsDetail extends Base
{
    /**
     * 通过item_id获取天猫数据
     * @param  integer $shop_id [description]
     * @return [type]            [description]
     */
    public function getGoodsDetailByItemId($item_id=0)
    {
        if (!$item_id) {
            return array();
        }
        $goods_info = Db::table('wj_ali_tm_goods_detail')->where('item_id',$item_id)->find();
        return $goods_info;
    }

    /**
     * 保存接口数据
     * @param integer $item_id [description]
     * @param string  $data    [description]
     */
    public function addGoodsDetailData($item_id=0,$data=array())
    {
    	if (!$item_id || empty($data) || !$data['shop_url'] || !$data['shop_id'] || !$data['data_other'] || !$data['shop_name']) {
    		return 0;
    	}

    	$add_data = array(
    		'item_id' => $item_id,
    		'shop_url' => $data['shop_url'],
            'shop_id' => $data['shop_id'],
            'data_other' => $data['data_other'],
            'assess_flag' => $data['assess_flag'],
            'img_url' => $data['img_url'],
            'score' => $data['score'],
            'cd_parameter' => $data['cd_parameter'],
            'shop_name' => $data['shop_name'],
            'del_price' => $data['del_price'],
    		'create_time'=> time(),
    		'update_time'=> time(),
    	);
    	$goods_id = Db::table('wj_ali_tm_goods_detail')->insertGetId($add_data);
        return $goods_id;
    }


    /**
     * 更新数据
     */
    public function updateGoodsDetailDataByItemId($id=0,$data=array())
    {
        if (!$id || empty($data) || !$data['shop_url'] || !$data['shop_id'] || !$data['data_other'] || !$data['shop_name']) {
            return 0;
        }
        $update_data = array(
            'shop_url' => $data['shop_url'],
            'shop_id' => $data['shop_id'],
            'data_other' => $data['data_other'],
            'assess_flag' => $data['assess_flag'],
            'img_url' => $data['img_url'],
            'score' => $data['score'],
            'cd_parameter' => $data['cd_parameter'],
            'shop_name' => $data['shop_name'],
            'del_price' => $data['del_price'],
            'update_time'=> time(),
        );
        $has_update = Db::table('wj_ali_tm_goods_detail')->where('id',$id)->update($update_data);
        return $has_update;
    }
}

/*CREATE TABLE `wj_ali_tm_goods_detail` (
  `item_id` int(11) unsigned NOT NULL COMMENT '天猫商品id',
  `shop_url` varchar(255) NOT NULL COMMENT '店铺实际url',
  `shop_id` int(11) unsigned NOT NULL COMMENT '阿里店铺id',
  `data_other` text NOT NULL,
  `assess_flag` text COMMENT 'https://rate.tmall.com/listTagClouds.htm?itemId=''商品id获取到的内容',
  `img_url` varchar(255) DEFAULT NULL COMMENT '商品图片url数组',
  `score` varchar(255) DEFAULT NULL COMMENT '店铺score数组',
  `cd_parameter` text COMMENT '商品信息',
  `shop_name` varchar(50) DEFAULT NULL COMMENT '店铺名称',
  `del_price` decimal(10,2) unsigned DEFAULT NULL COMMENT '原价',
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/
