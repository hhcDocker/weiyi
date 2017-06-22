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
use think\Log;

class WxPay
{
	public function _weixin_config(){//微信支付公共配置函数
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


	public function queryOrder($expense_num, $type =1)
	{
		// 主动查询支付结果
	    $this->_weixin_config();
	    $input = new \WxPayOrderQuery();
	    $input->SetOut_trade_no($expense_num);
	    $result = \WxPayApi::orderQuery($input);
	    
	    if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && array_key_exists("trade_state", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS" && $result["trade_state"] == "SUCCESS")
	    {
	        // 处理支付成功后的逻辑业务
	        Log::init([
	            'type'  =>  'File',
	            'path'  =>  LOG_PATH.'../paylog/'
	        ]);
	        Log::write($result,'log');
	        return array('code'=>1,'data'=>$result);
	    }
	    elseif(!array_key_exists("return_code", $result)){
	    	return ['code'=>0,'msg'=> '参数错误','data'=>array()];
	    }
	    elseif($result['return_code'] != 'SUCCESS'){
	    	return ['code'=>0,'msg'=> $result['return_msg'],'data'=>array()];
	    }
	    elseif (! array_key_exists("result_code", $result)) {
	    	return ['code'=>0,'msg'=> '参数错误','data'=>array()];
	    }
	    elseif($result['result_code'] != 'SUCCESS')
		{
			return ['code'=>0,'msg'=> $result['err_code_des'],'data'=>array()];
		}elseif($result['trade_state'] != 'SUCCESS')
		{
			return ['code'=>0,'msg'=> $result['trade_state_desc'],'data'=>array()];
		}
		else {
	    	return ['code'=>0,'msg'=> '查询失败','data'=>array()];
		}
	}
}
?>