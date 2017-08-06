<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: Index.php
 * | Description: user login, logout, register, reset
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-06-13 14:57
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\index\controller;
use app\common\service\SMS;
use api\APIController;
use api\APIException;
use think\Config;
use think\Request;
use think\Log;
use app\index\AlertMail;
use app\common\service\WxPay;
use app\common\service\AliPay;


class Index extends APIController
{
    /**
     * [index description]
     * @return [type] [description]
     */
    public function index()
    {
        header("Location:/frontend/html/index.html");
    }

    /**
     * 发送注册短信验证码
     * @return array
     */ 
    public function getRegisterCode(){
        $mobilephone = noempty_input('mobilephone', '/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/');

        $user_info = $this->ergodicSearchMobilephone($mobilephone); //手机是否注册过
        if ($user_info['code']>0) { //1-电商，2-网点，3-工厂，4-微驿，5-官网，0-无
            throw new APIException(10002);
        }

        $this->getSmsCode($mobilephone); 
        return $this->format_ret();
    }

    /**
     * 注册
     * 请求参数手机号，短信验证码，密码，确认密码
     * @return array
     */ 
    public function register() {
        $client_ip = $this->request->ip();
        $sms_code = noempty_input('sms_code');
        $mobilephone =  noempty_input('mobilephone','/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/');
        $password = input('password');
        $re_password =input('re_password');

        //校验
        $this->checkPassword($password);
        $this->checkPassword($re_password);

        $user_info = $this->ergodicSearchMobilephone($mobilephone); //手机是否注册过
       
        if ($user_info['code']>0) { //1-电商，2-网点，3-工厂，4-微驿，5-官网，0-无
            throw new APIException(10002);
        }

        $time_elapsed = time() - session('weitiao_sms_code_time');
        if($sms_code != session('weitiao_sms_code') || $mobilephone != session('weitiao_sms_mobilephone') || $time_elapsed > 600) {
            throw new APIException(10005);
        }

        if($password == $re_password){
            $time = date("Y-m-d H:i:s");
            $uid = md5(uniqid(rand(), true));
            // $password_md5 = md5($password);
            $manager_id = model('Managers')->addManagerInfo($uid,$mobilephone,$password,$client_ip,4,0);
            if (!$manager_id) {
                throw new APIException(10006);
            }
            session('manager_id', $manager_id);// 当前用户id
            session('manager_uid', $uid);// 当前用户uid
            session('manager_mobilephone',$mobilephone);
            session('weitiao_sms_code',null);
            return $this->format_ret();
        } else {
            throw new APIException(10006);
        }
    }

    /**
     * 微驿客户登录
     * @return array
     */ 
    public function login(){
        $authkey = session("authkey");
        if(!empty($authkey)) {
            throw new APIException(10007);
        }
        $client_ip = request()->ip();
        $mobilephone = noempty_input('mobilephone', '/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/');
        $password = input('password');

        $this->checkPassword($password);
        /*if(!preg_match('/[0-9a-z]{32}/',$password)) {
           $password = md5($password); 
        }*/

        if (($mobilephone != '' ) && ($password != '')) {
            $query_manager = model('Managers')->getManagerInfo($mobilephone, $password);
            $query_blacklist = model('OsManager')->getBlackListByMobile($mobilephone);

            if (!empty($query_manager) && !$query_manager['is_deleted'] && !$query_manager['is_locked'] && !$query_blacklist) {
                // 存储session
                session('manager_id',$query_manager['id']);
                session('manager_uid', $query_manager['uid']);
                session('manager_mobilephone', $query_manager['mobilephone']);
                // 更新用户登录信息
                $update_result = model('Managers')->updateManagerLoginInfo($query_manager['uid'], $client_ip);
            }elseif ($query_manager['is_deleted']) {
                throw new APIException(10010);
            }elseif($query_manager['is_locked']) {
                throw new APIException(10009);
            }elseif(empty($query_manager)) {
                //是否当前系统密码错误
                $manager_info = model('Managers')->hasManagerMobilephone($mobilephone);
                if ($manager_info) {
                    throw new APIException(10008);
                }

                //是否存在于其他系统
                $user_info = $this->ergodicSearchOtherManager($mobilephone,$password);
                if (!$user_info['code']) {
                    throw new APIException(10008);
                }else{
                    //将账号保存到当前账号，资金分开
                    $origin_role = $user_info['code'];//1-电商，2-网点，3-工厂，5-官网，
                    $origin_info = $user_info['manager_info'];
                    $origin_id = $origin_info['id'];
                    //添加
                    $uid = md5(uniqid(rand(), true));
                    $manager_id = model('Managers')->addManagerInfo($uid,$mobilephone,$password,$client_ip,$origin_role,$origin_id);
                    if (!$manager_id) {
                        throw new APIException(10008);
                    }

                    // 存储session
                    session('manager_id',$manager_id);
                    session('manager_uid', $uid);
                    session('manager_mobilephone', $mobilephone);// 当前用户mobilephone
                    // 更新用户登录信息
                    $update_result = model('Managers')->updateManagerLoginInfo($uid, $client_ip);
                }
            }
        } else{
            throw new APIException(10011);
        }
        $authkey = ["mobilephone"=>$mobilephone, "password"=>$password];
        session("authkey", $authkey);
        $login_info = array('manager_mobilephone'=>session('manager_mobilephone'),'is_login'=>1);
        return $this->format_ret($login_info);
    }
    
    /**
     * logout
     * @return array
     */ 
    public function logout(){
        session(null);
        return $this->format_ret();
    }
    
    /**
     *  找回密码验证身份短信
     *  @return array
     */ 
    public function getResetCode(){
       $mobilephone = noempty_input('mobilephone', '/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/'); 

        $user_info = $this->ergodicSearchMobilephone($mobilephone); //手机是否注册过
        
        if ($user_info['code']==0) { //1-电商，2-网点，3-工厂，4-微驿，5-官网，0-无
            throw new APIException(10013);
        }

        $this->getSmsCode($mobilephone);
        session('find_pwd_mobilephone',$mobilephone);
        return $this->format_ret();
    }
    
    /**
     * 找回密码
     * @return [type] [description]
     */
    public function setNewPasswd()
    {
        $mobilephone = session('find_pwd_mobilephone');
        $password = input('password');
        $re_password =input('re_password');

        if (!$mobilephone) {
            throw new APIException(10023);
        }

        $this->checkPassword($password);
        $this->checkPassword($re_password);
        if ($password !==$re_password) {
            throw new APIException(10006);
        }
        /*if(!preg_match('/[0-9a-z]{32}/',$password)) {
           $password = md5($password); 
        }*/
        if (session('weitiao_sms_tag')) {
            $user_info = $this->ergodicSearchMobilephone($mobilephone); //手机是否注册过
            
            if ($user_info['code']==0) { //1-电商，2-网点，3-工厂，4-微驿，5-官网，0-无
                throw new APIException(10013);
            }
            if ($user_info['code']==4) {
                Db::startTrans();
                try{
                    $has_update = model('Managers')->updateManagerPassword($mobilephone,$password);
                    if (!$has_update) {
                        Db::rollback();
                        throw new APIException(10014);
                    }

                    //修改原系统密码暂时不做
                    Db::commit();
                }catch(Exception $e){
                    Db::rollback();
                    throw new APIException(10014);
                }
            }else{
                $client_ip = $this->request->ip();
                //将账号保存到当前账号，资金分开
                $origin_role = $user_info['code'];//1-电商，2-网点，3-工厂，5-官网
                $origin_info = $user_info['manager_info'];
                $origin_id = $origin_info['id'];
                Db::startTrans();
                try{
                    //添加
                    $uid = md5(uniqid(rand(), true));
                    $manager_id = model('Managers')->addManagerInfo($uid,$mobilephone,$password,$client_ip,$origin_role,$origin_id);
                    if (!$manager_id) {
                        Db::rollback();
                        throw new APIException(10014);
                    }

                    //修改原系统密码暂时不做
                    Db::commit();
                }catch(Exception $e){
                    Db::rollback();
                    throw new APIException(10014);
                }
            }
            session(null);
            return $this->format_ret();
       }else{
            throw new APIException(10005);
       }
    }

    /**
     * 修改密码
     * @return array
     */ 
    public function resetPasswd() {
        $mobilephone = session('manager_mobilephone');
        $old_password = input('old_password');
        $password = input('password');
        $re_password =input('re_password');

        $this->checkPassword($old_password);
        $this->checkPassword($password);
        $this->checkPassword($re_password);
        
        if ($password !==$re_password) {
            throw new APIException(10006);
        }
        /*if(!preg_match('/[0-9a-z]{32}/',$password)) {
           $password = md5($password); 
        }
        if(!preg_match('/[0-9a-z]{32}/',$old_password)) {
           $old_password = md5($old_password); 
        }*/
        if ($old_password==$password) {
            throw new APIException(10017);
        }
        $query_manager = model('Managers')->getManagerInfo($mobilephone, $old_password);
        if (empty($query_manager)) {
            throw new APIException(10015);
            
        }
        if($query_manager['is_locked']) {
            throw new APIException(10008);
        }
        if ($query_manager['is_deleted']) {
            throw new APIException(10010);
        }

        Db::startTrans();
        try{
            $has_update = model('Managers')->updateManagerPassword($mobilephone,$password);
            if (!$has_update) {
                throw new APIException(10014);
            }

            //修改原系统密码暂时不做
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
            throw new APIException(10014);
        }
               
        session(null);
        return $this->format_ret();
    }

    /**
     * 获取用户的登录信息,用户账号,登录状态
     * @return [type] [description]
     */
    public function getLoginInfo()
    {
        if(!is_login()) {
            throw new APIException(10018);
        }
        $login_info = array('manager_mobilephone'=>session('manager_mobilephone'),'is_login'=>1);
        return $this->format_ret($login_info);
    }

    /**
     * 获取服务费用
     * @return [type] [description]
     */
    public function getServicePayAmount()
    {
        $service_pay_amount = model('Others')-> getValueByKey('service_pay_amount');
        $service_pay_array = json_decode($service_pay_amount,true);
        return $this->format_ret($service_pay_array);
    }

    /**
     * 获取体验时间
     * @return [type] [description]
     */
    public function getExperienceDays()
    {
        $experience_days = model('Others')-> getValueByKey('experience_time');
        return $this->format_ret($experience_days);
    }

    /********************************************* 公用函数 ********************************************/

    /**
     * 获取短信验证码
     * @param  [type] $mobilephone  [description]
     * @return [type]               [description]
     */
    public function getSmsCode($mobilephone='') {
        if (!$mobilephone) {
            throw new \Exception("mobilephone参数错误");
        }
        $time_elapsed = time() - session('weitiao_sms_code_time');
        if ($time_elapsed >= 60) {
            $sms = new SMS();
            $code = (string)rand(100000,999999);
            $sms_result = $sms->sms([
                    'param'  => ['code'=>$code],
                    'mobile'  => $mobilephone,
                    'template'  => 'SMS_71215766',
            ]);

            /*$sms_result = $sms->sms([
                'param'  => ['code'=>$code, 'product'=>'大胖子车装联盟'],
                'mobile'  => $mobilephone,
                'template'  => 'SMS_6215201',
            ]);*/
            if($sms_result !== true){
                throw new APIException(10003);
            }
            session('weitiao_sms_mobilephone', $mobilephone);
            session('weitiao_sms_code', $code);
            session('weitiao_sms_code_time', time());
        } else {
            throw new APIException(10004);
        }
    }

    /**
     * 检查短信验证码
     * @return array
     */ 
    public function checkSMScode() {
        $sms_code = noempty_input('sms_code');
        $mobilephone = noempty_input('mobilephone');

        $time_elapsed = time() - session('weitiao_sms_code_time');
        if($sms_code == session('weitiao_sms_code') && $mobilephone == session('weitiao_sms_mobilephone') && $time_elapsed < 600) {
            session('weitiao_sms_tag',1);
            session('weitiao_sms_code',null);
            return $this->format_ret();
        } else {
            throw new APIException(10005);
        }
    }
    
    /**
     * 检查手机
     * @param  [type] $mobilephone [description]
     * @return [type]              [description]
     */
    public function checkMobilephone($mobilephone='')
    {
        if (!$mobilephone) {
            throw new \Exception("mobilephone参数错误");
        }
        $query_manager = model('Managers')->getManagerInfoByMobilephone($mobilephone);
        if (empty($query_manager)) {
            return 0;
        }
        if($query_manager['is_locked']) {
            throw new APIException(10009);
        }
        if ($query_manager['is_deleted']) {
            throw new APIException(10010);
        }
        return 1;
    }

    /**
     * [checkPassword description]
     * @param  string $password [description]
     * @return [type]           [description]
     */
    public function checkPassword($password='')
    {
        if (!$password) {
            throw new APIException(10001);
        }
        if (strlen($password)<6) {
            throw new APIException(10020);
        }
        if (strlen($password)>16) {
            throw new APIException(10021);
        }
        if (preg_match('/[^0-9A-Za-z]/', $password)) {
            throw new APIException(10022);
        }
    }

    /**
     * 微信订单异步通知
     */
    public function WeixinNotify()
    {
        $notify_data = file_get_contents("php://input");//获取由微信传来的数据
        if(!$notify_data){
            $notify_data = $GLOBALS['HTTP_RAW_POST_DATA'] ?: '';//以防上面函数获取到的内容为空
        }

        if(!$notify_data){
            exit("微信订单异步通知校验失败");
        }
        $wxPay = new WxPay;
        $wxPay->_weixin_config();
        $doc = new \DOMDocument();
        $doc->loadXML($notify_data);
        $out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
        $transaction_id = $doc->getElementsByTagName("transaction_id")->item(0)->nodeValue;
        $openid = $doc->getElementsByTagName("openid")->item(0)->nodeValue;
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && array_key_exists("trade_state", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS" && $result["trade_state"] == "SUCCESS")
        {
            $total_fee = $result['total_fee'] * 0.01;
            
            $res = $this->updateServiceExpense($out_trade_no,$transaction_id,$total_fee,1);

            if ($res['code']) {
                Log::write($result,'log');
                exit('SUCCESS');
            }else{
                exit('');
            }
        }else{
            exit('');
        }
    }

    public function testPay()
    {
        $out_trade_no = '72170807023451722674';
        $transaction_id = '2017080721001004530285082170';
        $total_fee = 0.01;
        $this->updateServiceExpense($out_trade_no,$transaction_id,$total_fee,1);
        echo "1";
    }

    /**
     * [updateServiceExpense description]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    private function updateServiceExpense($expense_num='',$trade_num='',$actually_amount=0,$trade_status=0)
    {
        $expense_info = model('ExpenseRecords')->getRecordsByExpenseNum($expense_num);
        if(empty($expense_info)){
            return array('code'=>0,'msg'=>'没有对应的消费记录');
        }
        //判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id
        if ($expense_info['payment_amount']!=$actually_amount) {
            return array('code'=>0,'msg'=>'实际支付金额不对');
        }
        if ($expense_info['trade_status']==1) {
            $file = LOG_PATH.'../paylog/'.date("Ymd").'_repeat_pay_log.txt';
            $content = date("Y-m-d H:i:s")."订单异步通知重复发送请求\n\n";
            Log::write($content, $file);

            return array('code'=>1,'msg'=>'已于'.date("Y-m-d H:i:s",$expense_info['update_time']).'更新过');
        }
        $has_update = model('ExpenseRecords')->updateExpense($expense_num,$trade_num,$actually_amount,$trade_status);

        if ($has_update) {
            //消费对应的服务
            $service_info = model('ShopServices')->getServicesById($expense_info['service_id']);
            //此订单购买的服务时间
            $service_start_time = $expense_info['service_start_time'];
            $service_end_time = $expense_info['service_end_time'];

            if ($service_start_time <= $service_info['service_end_time']+1) { //时间不间断
                if ($service_info['service_end_time'] - $service_info['service_start_time'] <= 364*24*60*60) { //体验服务
                    $remain_expenience_time = $service_info['service_end_time']- $service_start_time; //剩余的体验服务时间
                    $remain_expenience_time = $remain_expenience_time>0 ? $remain_expenience_time :0;
                    $service_end_time = $service_end_time + $remain_expenience_time;
                }
                $service_start_time = $service_info['service_start_time'];
            }
            $has_update = model('ShopServices')->updateShopServiceTime($expense_info['service_id'] , $service_start_time ,$service_end_time);
            if ($has_update) {
                return array('code'=>1,'msg'=>'更新店铺服务'.$expense_info['service_id'].'消费记录成功');
            }else{
            return array('code'=>0,'msg'=>'更新店铺服务'.$expense_info['service_id'].'消费记录失败');
            }
        }else{
            return array('code'=>0,'msg'=>'更新店铺服务'.$expense_info['service_id'].'消费记录失败');
        }
    }


    /**
     * 支付宝支付结果通知
     * @return [type] [description]
     */
    public function AlipayNotifyUrl()
    {
        $aliPay = new AliPay;

        //异步订单结果通知
        $config = $aliPay::$alipay_config;
        vendor('alipay.alipay');
        $alipayNotify = new \AlipayNotify($config);
        $verify_result = $alipayNotify->verifyNotify();
        logResult(date("y-m-d H:i:s")."支付宝支付结果通知:verify_result = ".serialize($verify_result));
        if($verify_result) {//验证成功
            $out_trade_no = $_POST['out_trade_no'];//商户订单号
            $trade_no = $_POST['trade_no'];//支付宝交易号
            $trade_status = $_POST['trade_status']; //交易状态
            $total_fee = $_POST['total_fee'];//交易金额
            $seller_id = $_POST['seller_id'];//支付宝partner

            if($trade_status == 'TRADE_FINISHED') {
                $res = $this->updateServiceExpense($out_trade_no,$trade_no,$total_fee,$seller_id==$config['seller_id']);
                if ($res) {
                    logResult(date("y-m-d H:i:s")."支付宝支付结果通知:TRADE_FINISHED------notify_alipay Run Success\n编号为".$out_trade_no."的消费记录更新结果：".json_encode($res));
                }
            }elseif ($trade_status == 'TRADE_SUCCESS') {
                $res = $this->updateServiceExpense($out_trade_no,$trade_no,$total_fee,$seller_id==$config['seller_id']);
                if ($res) {
                    logResult(date("y-m-d H:i:s")."支付宝支付结果通知:TRADE_FINISHED------notify_alipay Run Success\n编号为".$out_trade_no."的消费记录更新结果：".json_encode($res));
                }
            }
            echo "success";  
        }else {
            //验证失败
            echo "fail";
            //写文本函数记录程序运行情况是否正常
            logResult(date("y-m-d H:i:s")."支付宝支付结果通知:fail------notify_alipay Run Success ");
        }
    }
    
    /**
     * 支付宝支付结果通知 return_url
     * @return [type] [<description>]
     */
    public function AlipayReturnUrl()
    {
        $aliPay = new AliPay;
        $config = $aliPay::$alipay_config;
        vendor('alipay.alipay');
        $alipayNotify = new \AlipayNotify($config);
        $verify_result = $alipayNotify->verifyReturn();
        if($verify_result) { //验证成功
            $out_trade_no = $_GET['out_trade_no'];//商户订单号
            $trade_no = $_GET['trade_no'];//支付宝交易号
            $trade_status = $_GET['trade_status']; //交易状态
            $total_fee = $_GET['total_fee'];//交易金额
            $seller_id = $_GET['seller_id'];//支付宝partner

            if($trade_status == 'TRADE_FINISHED') {
                $res = $this->updateServiceExpense($out_trade_no,$trade_no,$total_fee,$seller_id==$config['seller_id']);
                if ($res) {
                    logResult(date("y-m-d H:i:s")."支付宝支付结果通知:TRADE_FINISHED------notify_alipay Run Success\n编号为".$out_trade_no."的消费记录更新结果：".json_encode($res));
                }
            }elseif ($trade_status == 'TRADE_SUCCESS') {
                $res = $this->updateServiceExpense($out_trade_no,$trade_no,$total_fee,$seller_id==$config['seller_id']);
                if ($res) {
                    logResult(date("y-m-d H:i:s")."支付宝支付结果通知:TRADE_FINISHED------notify_alipay Run Success\n编号为".$out_trade_no."的消费记录更新结果：".json_encode($res));
                }
            }
            $url = $_SERVER['HTTP_HOST'] . '/frontend/html/service.html';

            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache");
            header("Pragma: no-cache");
            $url = 'http://'.$_SERVER['HTTP_HOST'] . '/frontend/html/service.html';
            header("Location:".$url);
            exit;
        }else{
            echo '<meta charset="utf-8" /><div style="text-align: center;"><div style="font-size: x-large; margin-top: 30px;">验证失败！</div></div>';
        }
    }
    /*********************************************账号体系********************************************/

    /**
     * 号码是否存在于系统中
     * 1-电商，2-网点，3-工厂，4-微驿，5-官网，
     * @param string $mobilephone [description]
     */
    public function ExistMobilephone($mobilephone='',$type=0)
    {
        if (!$mobilephone || !$type) {
            return array();
        }
        switch ($type) {
            case 1:
                $manager_info = model('EcommerceShop')->getEcommerceManagerByMobile($mobilephone);
                break;
            case 2:
                $manager_info = model('BeautyShop')->getBeautyManagerInfoByMobile($mobilephone);
                break;
            case 3:
                $manager_info = model('Factory')->getFactoryManagerInfoByMobile($mobilephone);
                break;
            case 4:
                $manager_info = model('Managers')->getManagerInfoByMobilephone($mobilephone);
                break;
            case 5:
                $manager_info = model('OsManager')->getOsManagerInfoByMobile($mobilephone);
                break;
            default:
                $manager_info = array();
                break;
        }

        return $manager_info;
    }

    /**
     * 遍历是否存在账号，存在则放回数据和相应系统编号，否则返回0和空数组，错误返回-1
     * 顺序：微驿-电商-网点-工厂-官网
     * 1-电商，2-网点，3-工厂，4-微驿，5-官网，
     * @param string $value [description]
     */
    public function ergodicSearchMobilephone($mobilephone='')
    {
        if (!$mobilephone) {
            return array('code'=>-1,'manager_info'=>array());
        }
        //微驿
        $manager_info = model('Managers')->getManagerInfoByMobilephone($mobilephone);
        if (!empty($manager_info)) {
            return array('code'=>4,'manager_info'=>$manager_info);
        }

        //电商
        $manager_info = model('EcommerceShop')->getEcommerceManagerByMobile($mobilephone);
        if (!empty($manager_info)) {
            return array('code'=>1,'manager_info'=>$manager_info);
        }

        //网点
        $manager_info = model('BeautyShop')->getBeautyManagerInfoByMobile($mobilephone);
        if (!empty($manager_info)) {
            return array('code'=>2,'manager_info'=>$manager_info);
        }
        
        //工厂
        $manager_info = model('Factory')->getFactoryManagerInfoByMobile($mobilephone);
        if (!empty($manager_info)) {
            return array('code'=>3,'manager_info'=>$manager_info);
        }

        //官网
        $manager_info = model('OsManager')->getOsManagerInfoByMobile($mobilephone);
        if (!empty($manager_info)) {
            return array('code'=>5,'manager_info'=>$manager_info);
        }
        
        //从未注册过
        return array('code'=>0,'manager_info'=>array());
    }

    /**
     * 遍历是否存在手机号码密码吻合的账号，存在则放回数据和相应系统编号，否则返回0和空数组，错误返回-1
     * 顺序：电商-网点-工厂-官网
     * 1-电商，2-网点，3-工厂，5-官网，
     * @param string $value [description]
     */
    public function ergodicSearchOtherManager($mobilephone='',$password='')
    {
        if (!$mobilephone || !$password) {
            return array('code'=>-1,'manager_info'=>array());
        }

        //电商
        $manager_info = model('EcommerceShop')->getEcommerceManager($mobilephone,$password);
        if (!empty($manager_info)) {
            return array('code'=>1,'manager_info'=>$manager_info);
        }

        //网点
        $manager_info = model('BeautyShop')->getBeautyManagerInfo($mobilephone,$password);
        if (!empty($manager_info)) {
            return array('code'=>2,'manager_info'=>$manager_info);
        }
        
        //工厂
        $manager_info = model('Factory')->getFactoryManagerInfo($mobilephone,$password);
        if (!empty($manager_info)) {
            return array('code'=>3,'manager_info'=>$manager_info);
        }

        //官网
        $manager_info = model('OsManager')->getOsManagerInfo($mobilephone,$password);
        if (!empty($manager_info)) {
            return array('code'=>4,'manager_info'=>$manager_info);
        }
        
        //没有刚好吻合账号
        return array('code'=>0,'manager_info'=>array());
    }

    /**
     * [updateOtherMangerPwd description]
     * @param  integer $origin_role [description]
     * @param  integer $origin_id   [description]
     * @param  [type]  $password    [description]
     * @return [type]               [description]
     */
    public function updateOtherMangerPwd($origin_role=0,$origin_id=0,$password='')
    {
        if (!$origin_role || !$origin_id || !$password) {
            return 0;
        }
        $has_update=0;
        switch ($origin_role) {
            case 1:
                $has_update = model('EcommerceShop')->updateEcommerceManagerPassword($origin_id, $password);
                break;
            case 2:
                $has_update = model('BeautyShop')->updateBeautyManagerPassword($origin_id, $password);
                break;
            case 3:
                $has_update = model('Factory')->updateFactoryPassword($origin_id, $password);
                break;
            case 5:
                $has_update = model('OsManager')->updateOsManagerPassword($origin_id, $password);
                break;
        }

        return $has_update;
    }

}
