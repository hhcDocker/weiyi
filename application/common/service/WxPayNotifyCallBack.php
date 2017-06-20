<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: WxPayNotifyCallBack.php
 * | Description: wei xin pay notify
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-05-29 13:16
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
 
namespace app\index\service;
use wxpay\WxPayNotify;
use wxpay\WxPayOrderQuery;
use wxpay\WxPayApi;

class WxPayNotifyCallBack extends WxPayNotify {
	
    //查询订单
	public function Queryorder($transaction_id)	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")	{
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)	{
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		$total_fee = $data['total_fee'];
		$out_trade_no = $data['out_trade_no'];
		$transaction_id = $data['transaction_id'];
        $wxpaymodel = new WxPay();
        $wxpaymodel->update_order_expense_status($out_trade_no,round($total_fee/100,2),$transaction_id);
		return true;
	}
}
