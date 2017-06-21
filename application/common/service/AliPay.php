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
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            logResult("fail------notify_alipay Run Seccess !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
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
    
    /**
     * 更新订单状态，更新消费状态
     * @param  string  $out_trade_no      [消费记录编号]
     * @param  integer $total_fee         [消费金额]
     * @param  string  $trade_no          [支付宝交易单号]
     * @param  boolean $comfirm_seller_id [$seller_id是否等于$config['seller_id']]
     */
    private function update_order_expense_status($out_trade_no='',$total_fee=0,$trade_no='',$comfirm_seller_id=false)
    {
        $query_expense_records = model('BuyerExpense')->getExpenseByNum($out_trade_no);
        if (trim($query_expense_records['trade_status']) == 0 && $query_expense_records['payment_amount'] == $total_fee && $comfirm_seller_id) {
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
            } catch (\Exception $e) {
                Db::rollback();
            }
        }
    }

    /**
     * 更新充值状态，更新余额
     * @param  string  $out_trade_no      [消费记录编号]
     * @param  integer $total_fee         [消费金额]
     * @param  string  $trade_no          [支付宝交易单号]
     * @param  boolean $comfirm_seller_id [$seller_id是否等于$config['seller_id']]
     */
    private function update_recharge_status($out_trade_no='',$total_fee=0,$trade_no='',$comfirm_seller_id=false){
        
        $query_recharge_order = model('RechargeRecord')->getRechargeRecordByNum($out_trade_no);
        
        if($query_recharge_order['trade_status'] == 0 && $query_recharge_order['payment_amount'] == $total_fee && $comfirm_seller_id)
        {
            $buyer_type = $query_recharge_order['buyer_type'];
            if ($buyer_type == 1) {
                $query_money = model('BeautyShop')->getShopStatusByUserId($query_recharge_order['buyer_id']);
                $table = 'BeautyShop';
            } else if ($buyer_type == 2) {
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