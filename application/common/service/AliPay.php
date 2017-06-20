<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: AliPay.php
 * +----------------------------------------------------------------------
 * | Created by peteyhuang at 2016-10-01 08:00 
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Updated by peteyhuang at 2016-10-13 16:44
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Updated by sunminchun at 2017-01-11 16:44
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\service;
use think\Validate;
use think\Log;
use think\Db;

class AliPay
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
		return ['code'=>1,'msg'=>$alipaySubmit->buildRequestForm($parameter,"get", "确认")];
	}

    /**
     * 支付宝支付结果通知
     * @param  integer $type [1-订单支付，2-充值]
     * @return [type]        [description]
     */
	public function notify_alipay($type=1)
	{
		//异步订单结果通知
		$config = self::$alipay_config;
		vendor('alipay.alipay');
		$alipayNotify = new \AlipayNotify($config);
        $verify_result = $alipayNotify->verifyNotify();

        logResult("outside------notify_alipay Run Seccess !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! verify_result = ".serialize($verify_result));

        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代

            logResult("notify_alipay verify_result Seccess !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            
            //商户订单号

            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];

            //交易金额
            $total_fee = $_POST['total_fee'];

            //支付宝partner
            $seller_id = $_POST['seller_id'];


            if($trade_status == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                    //如果有做过处理，不执行商户的业务程序
                        
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                if ($type==1) {
                    $this->update_order_expense_status($out_trade_no,$total_fee,$trade_no,$seller_id==$config['seller_id']);
                }else{
                    $this->update_recharge_status($out_trade_no,$total_fee,$trade_no,$seller_id==$config['seller_id']);
                }
                
                //调试用，写文本函数记录程序运行情况是否正常
                logResult("TRADE_FINISHED------notify_alipay Run Seccess !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
            }
            else if ($trade_status == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                    //如果有做过处理，不执行商户的业务程序
                        
                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                if ($type==1) {
                    $this->update_order_expense_status($out_trade_no,$total_fee,$trade_no,$seller_id==$config['seller_id']);
                }else{
                    $this->update_recharge_status($out_trade_no,$total_fee,$trade_no,$seller_id==$config['seller_id']);
                }
                
                //调试用，写文本函数记录程序运行情况是否正常
                logResult("TRADE_SUCCESS------notify_alipay Run Seccess !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
                
            echo "success";     //请不要修改或删除
            
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            return array('code'=>0);
        }
	}

	public function return_alipay($type=1)
	{
        //https://unitradeprod.alipay.com/acq/cashierReturn.htm?sign=K1iSL1z2omfrPYWYJBvJX2JVKHc5T1JpbgpuiTV0796xZTEcRx1wFZ7ZCLSHaq6TJBIs%252F7r3&outTradeNo=20161020110135845449&pid=2088521090980134&type=1
        
        //http://dm2.com/ecommerce_shop/ecommerce_report/rechargeAlipayReturnUrl?body=%E5%9C%A8%E7%BA%BF%E5%85%85%E5%80%BC%E9%87%91%E9%A2%9D%E5%88%B0%E5%A4%A7%E8%83%96%E5%AD%90%E6%B1%BD%E8%BD%A6%E5%AE%89%E8%A3%85%E8%81%94%E7%9B%9F%E7%94%B5%E5%95%86%E5%8D%96%E5%AE%B6%E8%B4%A6%E6%88%B7%E4%BD%99%E9%A2%9D&buyer_email=hapety2009%40163.com&buyer_id=2088702195059547&exterface=create_direct_pay_by_user&is_success=T&notify_id=RqPnCoPT3K9%252Fvwbh3InWeO1CUnDM8gkVUCBYaawbVjLUeR5cDCKloJczCAQCJs8df5oA&notify_time=2016-10-20+11%3A02%3A02&notify_type=trade_status_sync&out_trade_no=20161020110135845449&payment_type=1&seller_email=3319318506%40qq.com&seller_id=2088521090980134&subject=%E5%A4%A7%E8%83%96%E5%AD%90%E6%B1%BD%E8%BD%A6%E5%AE%89%E8%A3%85%E8%81%94%E7%9B%9F%E7%94%B5%E5%95%86%E5%8D%96%E5%AE%B6%E5%85%85%E5%80%BC&total_fee=0.01&trade_no=2016102021001004540233794108&trade_status=TRADE_SUCCESS&sign=0fcff86442317a3f24cac134b5d435d4&sign_type=MD5

        //异步订单结果通知
		$config = self::$alipay_config;
		vendor('alipay.alipay');
		$alipayNotify = new \AlipayNotify($config);
        $verify_result = $alipayNotify->verifyReturn();

        logResult("outside------return_alipay Run Seccess !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");

        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码
            
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

            //商户订单号

            $out_trade_no = $_GET['out_trade_no'];

            //支付宝交易号

            $trade_no = $_GET['trade_no'];

            //交易状态
            $trade_status = $_GET['trade_status'];

            //交易金额
            $total_fee = $_GET['total_fee'];

            //支付宝partner
            $seller_id = $_GET['seller_id'];


            if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //如果有做过处理，不执行商户的业务程序
                
                logResult("TRADE_FINISHED || TRADE_SUCCESS------return_alipay Run Seccess !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");

                if ($type==1) {
                    $this->update_order_expense_status($out_trade_no,$total_fee,$trade_no,$seller_id==$config['seller_id']);
                }else{
                    $this->update_recharge_status($out_trade_no,$total_fee,$trade_no,$seller_id==$config['seller_id']);
                }
                    echo '<meta charset="utf-8" /><div style="text-align: center;"><img src="/static/images/chongzhichenggong.png" border="0" style="height: 160px; margin-top: 80px;" /></div>';
                }
            else {
                echo '<meta charset="utf-8" /><div style="text-align: center;"><img src="/static/images/queren.png" border="0" style="height: 160px; margin-top: 80px;" /><div style="font-size: x-large; margin-top: 30px;">验证成功！<br />交易状态：'.$_GET['trade_status'].'</div></div>';
            }
            
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            //如要调试，请看alipay_notify.php页面的verifyReturn函数
            echo '<meta charset="utf-8" /><div style="text-align: center;"><img src="/static/images/quxiao.png" border="0" style="height: 160px; margin-top: 80px;" /><div style="font-size: x-large; margin-top: 30px;">验证失败！</div></div>';
        }
	}
}
?>