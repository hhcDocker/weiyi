<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: AliPay.php
 * +----------------------------------------------------------------------
 * | Created by sunminchun at 2017-01-11 16:44
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\service;
use think\Model;
use think\Validate;
use think\Log;
use think\Db;

class AliPay extends Model
{
	public static $alipay_config = [
		//'partner' 		=> '2088521090980134',//支付宝partner，2088开头数字-----新支付宝
		//'seller_id' 		=> '2088521090980134',//支付宝partner，2088开头数字-----新支付宝
		//'key' 			=> 'rk22816b976b9sk0adruf7hqfskqpj53',// MD5密钥，安全检验码，由数字和字母组成的32位字符串-----新支付宝
        'partner'           => '2088121738118082',//支付宝partner，2088开头数字-----旧支付宝
        'seller_id'         => '2088121738118082',//支付宝partner，2088开头数字-----旧支付宝
        'key'               => '805pvb3bkzyxqi35rur7fr9erfwghwcp',// MD5密钥，安全检验码，由数字和字母组成的32位字符串-----旧支付宝
		'sign_type' 		=> 'MD5',
		'input_charset' 	=> 'utf-8',
		'cacert' 			=> '',
		'transport' 		=> 'http',
		'payment_type' 		=> '1',
		'service' 			=> 'create_direct_pay_by_user',
		'anti_phishing_key'	=> '',
		'exter_invoke_ip' 	=> '',
	];

	public function alipay($data=[])
	{
		//发起支付宝支付
		$validate = new Validate([
			['out_trade_no','require|alphaNum','订单编号输入错误|订单编号输入错误'],
			['total_fee','require|number|gt:0','金额输入错误|金额输入错误|金额输入错误'],
			['subject','require','请输入标题'],
			['body','require','请输入描述'],
			['notify_url','require','异步通知地址不为空'],
		]);

		if (!$validate->check($data)) 
		{
			return ['code'=>0,'msg'=>$validate->getError()];
		}

		$config = self::$alipay_config;
		vendor('alipay.alipay');
		$parameter = [
			"service"       	=> $config['service'],
			"partner"       	=> $config['partner'],
			"seller_id"  		=> $config['seller_id'],
			"payment_type"		=> $config['payment_type'],
			"notify_url"		=> $data['notify_url'],
			"return_url"		=> $data['return_url'],
			"anti_phishing_key"	=> $config['anti_phishing_key'],
			"exter_invoke_ip"	=> $config['exter_invoke_ip'],
			"out_trade_no"		=> $data['out_trade_no'],
			"subject"			=> $data['subject'],
			"total_fee"			=> $data['total_fee'],
			"body"				=> $data['body'],
			"_input_charset"	=> $config['input_charset']
		];
		$alipaySubmit = new \AlipaySubmit($config);
//        $pay_form = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        $pay_url = $alipaySubmit->buildRequestParaToString($parameter);
        
		return ['code'=>1,'msg'=>$pay_url];
	}
}
?>