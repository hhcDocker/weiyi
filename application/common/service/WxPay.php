<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: WxPay.php
 * +----------------------------------------------------------------------
 * | Created by peteyhuang at 2016-10-01 08:00 
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Updated by peteyhuang at 2016-10-13 16:44
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\service;
use think\Validate;

class WxPay
{
	private function _weixin_config(){//微信支付公共配置函数
		define('WXPAY_APPID', config('wx_config.WXPAY_APPID'));//微信公众号APPID
		define('WXPAY_MCHID', config('wx_config.WXPAY_MCHID'));//微信商户号MCHID
		define('WXPAY_KEY', config('wx_config.WXPAY_KEY'));//微信商户自定义32位KEY
		define('WXPAY_APPSECRET', config('wx_config.WXPAY_APPSECRET'));//微信公众号appsecret
		vendor('wxpay.WxPay_Api');
		vendor('wxpay.WxPay_NativePay');
	}

	public function wxPay($data=[])
	{
		//发起微信支付，如果成功，返回微信支付字符串，否则范围错误信息
		$validate = new Validate([
			['body','require','请输入描述'],
			['attach','require','请输入标题'],
			['out_trade_no','require|alphaNum','消费编号输入错误|消费编号输入错误'],
			['total_fee','require|number|gt:0','金额输入错误|金额输入错误|金额输入错误'],
			['notify_url','require','异步通知地址不为空'],
			['trade_type','require|in:JSAPI,NATIVE,APP','交易类型错误'],
		]);

		if (!$validate->check($data)) 
		{
			return ['code'=>0,'msg'=>$validate->getError()];
		}

		$this->_weixin_config();
		$notify = new \NativePay();
		$input = new \WxPayUnifiedOrder();
		$input->SetBody($data['body']);
		$input->SetAttach($data['attach']);
		$input->SetOut_trade_no($data['out_trade_no']);
		$input->SetTotal_fee($data['total_fee']);
		$input->SetTime_start($data['time_start']);
		$input->SetTime_expire($data['time_expire']);
		$input->SetGoods_tag($data['goods_tag']);
		$input->SetNotify_url($data['notify_url']);
		$input->SetTrade_type($data['trade_type']);
		$input->SetProduct_id($data['product_id']);
		$result = $notify->GetPayUrl($input);

		if($result['return_code'] != 'SUCCESS')
		{
			return ['code'=>0,'msg'=> $result['return_msg']];
		}

		if($result['result_code'] != 'SUCCESS')
		{
			return ['code'=>0,'msg'=> $result['err_code_des']];
		}
		return ['code'=>1,'msg'=>$result["code_url"]];
	}

	public function notify_wxpay($data='', $type=1)
	{
		//微信支付异步通知
		if(!$data)
		{
			return false;
		}

		$this->_weixin_config();
    	$doc = new \DOMDocument();
		$doc->loadXML($data);
		$out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
		$transaction_id = $doc->getElementsByTagName("transaction_id")->item(0)->nodeValue;
		$openid = $doc->getElementsByTagName("openid")->item(0)->nodeValue;
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = \WxPayApi::orderQuery($input);
		if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && array_key_exists("trade_state", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS" && $result["trade_state"] == "SUCCESS")
		{
		    $total_fee = $result['total_fee'] * 0.01;
		        
	        if ($type == 1) {// 支付
	            $this->update_order_expense_status($out_trade_no,$total_fee,$transaction_id);
	        } else { // 充值
	            $this->update_recharge_status($out_trade_no,$total_fee,$transaction_id);
	        }
		    
			// 处理支付成功后的逻辑业务
			Log::init([
				'type'  =>  'File',
				'path'  =>  LOG_PATH.'../paylog/'
			]);
			Log::write($result,'log');
			return 'SUCCESS';
		}
		return false;
	}
	
	public function queryOrder($expense_num, $type =1)
	{
		// 主动查询支付结果
	    $this->_weixin_config();
	    $input = new \WxPayOrderQuery();
	    $input->SetOut_trade_no($expense_num);
	    $result = \WxPayApi::orderQuery($input);
	    
	    if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && array_key_exists("trade_state", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS" && $result["trade_state"] == "SUCCESS")
	    {
	        $total_fee = $result['total_fee'] * 0.01;
	        $transaction_id = $result['transaction_id'];
	       
	        if ($type == 1) {// 支付
	            $this->update_order_expense_status($expense_num,$total_fee,$transaction_id);
	        } else { // 充值
	            $this->update_recharge_status($expense_num,$total_fee,$transaction_id);
	        }
	    
	        // 处理支付成功后的逻辑业务
	        Log::init([
	            'type'  =>  'File',
	            'path'  =>  LOG_PATH.'../paylog/'
	        ]);
	        Log::write($result,'log');
	        return 'SUCCESS';
	    }
	    return false;
	}
	
	/**
	 * 更新订单状态，更新消费状态
	 * @param  string  $out_trade_no      [消费记录编号]
	 * @param  integer $total_fee         [消费金额]
	 * @param  string  $trade_no          [微信交易单号]
	 */
	public function update_order_expense_status($out_trade_no='',$total_fee=0,$trade_no='')
	{
	    $query_expense_records = model('BuyerExpense')->getExpenseByNum($out_trade_no);
	    if (trim($query_expense_records['trade_status']) == 0 && $query_expense_records['payment_amount'] == $total_fee) {
	        $time = time();
	        $expense_data = [
	            'trade_num'=>$trade_no,
	            'trade_status'=> 1,//已支付
	            'update_time' => $time
	        ];
	
	        Db::startTrans();
	        try {
    	        model('BuyerExpense')->updateExpenseByNum($out_trade_no, $expense_data);
    	        
    	        $buyer_id = $query_expense_records['buyer_id'];
    	        $buyer_type = $query_expense_records['buyer_type'];
    	        if(1 == $buyer_type){
    	            $table = 'BeautyShop';
    	            $query_buyer = model('BeautyShop')->getBeautyUserById($buyer_id);
    	        }else{
    	            $table = 'EcommerceShop';
    	            $query_buyer = model('EcommerceShop')->getEcommerceUserById($buyer_id);
    	        }
    	        $total_score = 0;
    	        
    	        $query_buyer_orders = model('Orders')->getOrdersByExpenseNum($out_trade_no);
    	        $orderid_arr = [];
    	        foreach ($query_buyer_orders as $order) {
    	            $orderid_arr[] = $order['id'];
    	            $total_score += $order['score'];
    	        }
    	        $order_ids = implode(',', $orderid_arr);
    	        $sample_num = model('OrderGoods')->getSampleNumByOrderIds($order_ids);
    	        if ($sample_num) {
    	            $sample_num += $query_buyer['used_sample_chance'];
    	            model($table)->updateSampleNum($query_buyer['id'], $query_buyer['uid'], $sample_num);
    	        }
    	        // 更新商品销量
    	        $query_order_goods = model('OrderGoods')->getOrderGoodsByOrderIds($order_ids);
    	        foreach ($query_order_goods as $order_goods) {
    	            model('Goods')->addGoodsCurrentSaleNum($order_goods['id'], $order_goods['purchase_quantity']);
    	        }
    	        
    	        // 拆单
    	        model('Orders')->splitOrder($buyer_id, $buyer_type, $order_ids, $out_trade_no);
    	        
    	        // 支付时使用积分，要扣除积分
    	        if ($total_score) {
    	            $new_score  = $query_buyer['score'] - $total_score;
    	            model($table)->updateManagerNameByUidId($query_buyer['id'], $query_buyer['uid'], ['score'=>$new_score, 'update_time'=>['exp', 'now()']]);
    	        }
    	        Db::commit();
	        } catch(\Exception $e) {
	            Db::rollback();
	        }
	    }
	}
	
	/**
	 * 更新充值状态，更新余额
	 * @param  string  $out_trade_no      [消费记录编号]
	 * @param  integer $total_fee         [消费金额]
	 * @param  string  $trade_no          [微信交易单号]
	 */
	public function update_recharge_status($out_trade_no='',$total_fee=0,$trade_no=''){
	
	    $query_recharge_order = model('RechargeRecord')->getRechargeRecordByNum($out_trade_no);
	
	    if($query_recharge_order['trade_status'] == 0 && $query_recharge_order['payment_amount'] == $total_fee)
	    {
	        $buyer_type = $query_recharge_order['buyer_type'];
	        if ($buyer_type == 1) {
	            $query_money = model('BeautyShop')->getShopStatusByUserId($query_recharge_order['buyer_id']);
	            $table = 'BeautyShop';
	        } elseif ($buyer_type == 2) {
	            $query_money = model('EcommerceShop')->getEcommerceUserCompaniesByUserId($query_recharge_order['buyer_id']);
	            $table = 'EcommerceShop';
	        } else {
	            $query_money = model('SellerShops')->getShopsInfoBySellerId($query_recharge_order['buyer_id']);
	        }
	
	        $new_money = $query_money['money'] + $total_fee;
	        
	        $company_data = [
	            'money' => $new_money,
	            'update_time' => ['exp', 'now()']
	        ];
	        Db::startTrans();
	        try {
    	        if ($buyer_type == 1 || $buyer_type == 2) {
    	            model($table)->updateCompanyNameByUidId($query_money['id'], $query_money['boss_uid'], $company_data);
    	        } else {
    	            model('SellerShops')->updateShopBalance($query_money['id'], $query_money['boss_uid'], $new_money);
    	        }
    	        model('RechargeRecord')->updateRechargeRecord($out_trade_no,$trade_no, $total_fee, 1);
    	        Db::commit();
	        } catch (\Exception $e) {
	            Db::rollback();
	        }
	    }
	}
}
?>