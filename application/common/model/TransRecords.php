<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: TransRecords.php
 * | Description: 转换记录
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-7-26 15:00
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Db;

class TransRecords extends Base
{
    /**
     * 转换记录
     * @param  integer $manager_id   [description]
     * @param  integer $pageIndex    [description]
     * @param  integer $pageSize     [description]
     * @param  integer $service_type [description]
     * @return [type]                [description]
     */
    public function getRecordsByManagerId($manager_id=0,$pageIndex=1, $pageSize=10,$service_type=0)
    {
        if (!$manager_id) {
            return array();
        }
        $experience_days = model('Others')-> getValueByKey('experience_days');
        $where = '';
        if ($service_type==1) { //未支付
            $where ='s.service_end_time-s.service_start_time<='.$experience_days.'*24*60*60+1';
        }elseif ($service_type==2) { //生效中
            $where ='s.service_start_time<UNIX_TIMESTAMP(NOW()) and s.service_end_time>unix_timestamp(now())';
        }elseif ($service_type==3) { //已到期
            $where ='s.service_start_time>UNIX_TIMESTAMP(NOW()) or s.service_end_time<unix_timestamp(now())';
        }
        $offset = ($pageIndex-1)*$pageSize;
        $res = Db::table('wj_trans_records r')
                ->field('r.id,r.transnum,r.type_id,r.object_id,r.original_url,s.id,s.shop_url,s.transformed_url,s.service_start_time,s.service_end_time,a.ali_shop_id')
                ->join('wj_shop_services s','r.wj_shop_id=s.shop_id')
                ->join('wj_ali_shops a','a.id =s.shop_id')
                ->where('s.manager_id',$manager_id)
                ->where('r.is_deleted',0)
                ->where('s.is_deleted',0)
                ->where($where)
                ->limit($offset, $pageSize)
                ->select();
        return $res;
    }

    /**
     * [countRecordsByManagerId description]
     * @param  integer $manager_id   [description]
     * @param  [type]  $service_type [description]
     * @return [type]                [description]
     */
    public function countRecordsByManagerId($manager_id=0,$service_type=0)
    {
        if (!$manager_id) {
            return 0;
        }
        $experience_days = model('Others')-> getValueByKey('experience_days');
        $where = '';
        if ($service_type==1) { //未支付
            $where ='s.service_end_time-s.service_start_time<='.$experience_days.'*24*60*60+1';
        }elseif ($service_type==2) { //生效中
            $where ='s.service_start_time<UNIX_TIMESTAMP(NOW()) and s.service_end_time>unix_timestamp(now())';
        }elseif ($service_type==3) { //已到期
            $where ='s.service_start_time>UNIX_TIMESTAMP(NOW()) or s.service_end_time<unix_timestamp(now())';
        }
        $res = Db::table('wj_trans_records r')
                ->join('wj_shop_services s','r.wj_shop_id=s.shop_id')
                ->where('s.manager_id',$manager_id)
                ->where('r.is_deleted',0)
                ->where('s.is_deleted',0)
                ->where($where)
                ->count();
        return $res;
    }

    /**
     * 根据objectid，类型，manager_id查询服务
     * @param  integer $object_id  [description]
     * @param  integer $type_id    [类型：1-店铺，2-天猫商品，3-淘宝商品]
     * @param  integer $manager_id [description]
     * @return [type]              [description]
     */
    public function getServiceByRecord($object_id=0,$type_id=0,$manager_id=0)
    {
        if (!$object_id || !$type_id ||!$manager_id) {
            return array();
        }

        $res = Db::table('wj_trans_records r')
                ->field('s.*,a.ali_shop_id')
                ->join('wj_shop_services s','r.wj_shop_id=s.shop_id')
                ->join('wj_ali_shops a','a.id =s.shop_id')
                ->where('s.manager_id',$manager_id)
                ->where('r.is_deleted',0)
                ->where('s.is_deleted',0)
                ->where('object_id',$object_id)
                ->where('type_id',$type_id)
                ->find();
        return $res?$res:array();
    }

    /**
     * 添加记录
     * @param  string  $transnum     [description]
     * @param  integer $wj_shop_id   [description]
     * @param  integer $object_id    [description]
     * @param  integer $type_id      [类型：1-店铺，2-天猫商品，3-淘宝商品]
     * @param  string  $original_url [description]
     * @return [type]                [description]
     */
    public function addRecords($transnum='',$object_id=0,$type_id=0,$wj_shop_id=0,$original_url='')
    {
    	if (!$transnum ||!$wj_shop_id ||!$object_id ||!$type_id ||!$original_url) {
    		return 0;
    	}
    	$add_data = array(
            'transnum'=>$transnum,
    		'wj_shop_id'=> $wj_shop_id,
    		'object_id'=> $object_id,
    		'type_id'=> $type_id,
            'original_url'=>$original_url,
    		'is_deleted'=> 0,
    		'create_time'=> time(),
    		'update_time'=> time(),
    	);
    	$has_add = Db::table('wj_trans_records')->insertGetId($add_data);
        return $has_add;
    }

}
/*CREATE TABLE `wj_trans_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `transnum` char(19) NOT NULL COMMENT '转换编号',
  `object_id` bigint(18) unsigned NOT NULL COMMENT '商品或店铺id',
  `type_id` tinyint(1) unsigned NOT NULL COMMENT '类型：1-店铺，2-天猫商品，3-淘宝商品',
  `wj_shop_id` int(10) unsigned NOT NULL COMMENT '对应店铺表id',
  `original_url` varchar(255) NOT NULL COMMENT '转换的链接',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_time` int(11) unsigned DEFAULT NULL,
  `update_time` int(11) unsigned DEFAULT NULL,
  `delete_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_id` (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微跳转换记录';
*/

