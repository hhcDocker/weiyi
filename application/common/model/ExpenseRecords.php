<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: ExpenseRecords.php
 * | Description: 客户消费记录
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-6-15 15:00 
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
 
namespace app\common\model;
use think\Db;

class ExpenseRecords extends Base
{
   /**
    * 添加消费记录
    * @param string  $expense_num        [消费记录编号]
    * @param integer $payment_method     [支付方式]
    * @param integer $service_id         [与此消费对应的服务id]
    * @param integer $manager_id         [客户主键id]
    * @param integer $payment_amount     [消费金额]
    * @param integer $service_start_time [购买服务开始时间]
    * @param integer $service_end_time   [购买服务结束时间]
    * @param integer $trade_status       [交易状态，0待支付、1已支付]
    */
    public function addExpense($expense_num='', $payment_method=0,$service_id=0,$manager_id=0,$payment_amount=0,$service_start_time=0,$service_end_time=0,$trade_status=0)
    {
        if (!$expense_num ||!$service_id ||!$manager_id ||!$service_start_time ||!$service_end_time) {
          return 0;
        }
        $expense_data = [
            'expense_num' => $expense_num, //消费记录编号
            'payment_method'=>$payment_method, //支付方式，0-免费体验，1-微信，2-支付宝，
            // 'trade_num'=>$trade_num, //支付宝交易单号、银行转账单号等
            'service_id'=>$service_id, //与此消费对应的服务id
            'manager_id' => $manager_id, //客户主键id
            'payment_amount' => $payment_amount, //消费金额
            'service_start_time' => $service_start_time, //购买服务开始时间
            'service_end_time' => $service_end_time, //购买服务结束时间
            'trade_status' => $trade_status, //交易状态，0待支付、1已支付
            'create_time' => time(),
            'update_time' => time()
        ];

        $expense_id = Db::table('wj_expense_records')->insertGetId($expense_data);
        return $expense_id;
    }

    /**
     * 更新消费记录
     * @param  [type] $expense_num [description]
     * @param  [type] $data        [description]
     * @return [type]              [description]
     */
    public function updateExpense($expense_num,$trade_num,$actually_amount,$trade_status)
    {
        if (!$expense_num) {
            return 0;
        }
        $update_data = array(
            'trade_num'=> $trade_num,
            'actually_amount'=> $actually_amount,
            'trade_status'=> $trade_status,
            'update_time' =>time()
        );
        $has_update = Db::table('wj_expense_records')->where('expense_num' , $expense_num)->update($update_data);
        return $has_update;
    }
    
    /**
     * [getRecordsByExpenseNum description]
     * @param  string $expense_num [description]
     * @return [type]              [description]
     */
    public function getRecordsByExpenseNum($expense_num='')
    {
        if (!$expense_num) {
            return array();
        }

        $has_update = Db::table('wj_expense_records')->where('expense_num' , $expense_num)->find();
        return $has_update;
    }

    /**
     * 获取体验信息
     */
    public function getExperienceInfoByService($service_id=0)
    {
        if (!$service_id) {
            return array();
        }
        $experience_info = Db::table('wj_expense_records')->where('service_id',$service_id)->where('payment_method',0)->find();
        return $experience_info;
    }
}
/*CREATE TABLE `wj_expense_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `expense_num` varchar(64) NOT NULL DEFAULT '' COMMENT '消费记录编号，单独生成',
  `payment_method` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '支付方式，1-微信，2-支付宝',
  `trade_num` varchar(64) NOT NULL DEFAULT '' COMMENT '支付宝交易单号等',
  `service_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '与此消费对应的服务id',
  `manager_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '客户主键id',
  `payment_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '消费金额',
  `actually_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
  `service_start_time` int(11) unsigned NOT NULL COMMENT '购买服务开始时间',
  `service_end_time` int(11) unsigned NOT NULL COMMENT '购买服务结束时间',
  `trade_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '交易状态，0待支付、1已支付',
  `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已删除（0-未删除，1-已删除）',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `trade_num` (`trade_num`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客户在我们平台购买服务的消费记录';*/

