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
        if ($this->checkExistsMobilephone($mobilephone)) {
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
        $this->checkPassword($password);
        $this->checkPassword($re_password);

        if ($this->checkExistsMobilephone($mobilephone)) {
            throw new APIException(10002);
        }
       
        $time_elapsed = time() - session('weitiao_sms_code_time');
        if($sms_code != session('weitiao_sms_code') || $mobilephone != session('weitiao_sms_mobilephone') || $time_elapsed > 600) {
            throw new APIException(10005);
        }

        if($password == $re_password){
            $time = date("Y-m-d H:i:s");
            $uid = md5(uniqid(rand(), true));
            $password_md5 = md5($password);
            $manager_id = model('Managers')->addManagerInfo($uid,$mobilephone,$password_md5,$client_ip);
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
     * 微跳客户登录
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

         // noempty_input('password');
        $this->checkPassword($password);
        if(!preg_match('/[0-9a-z]{32}/',$password)) {
           $password = md5($password); 
        }

        if (($mobilephone != '' ) && ($password != '')) {
            $query_manager = model('Managers')->getManagerInfo($mobilephone, $password);
            if(empty($query_manager)) {
                throw new APIException(10008);
            }
            if($query_manager['is_locked']) {
                throw new APIException(10009);
            }
            if ($query_manager['is_deleted']) {
                throw new APIException(10010);
            }
            // 存储session
            session('manager_id',$query_manager['id']);
            session('manager_uid', $query_manager['uid']);
            session('manager_mobilephone', $query_manager['mobilephone']);
            // 更新用户登录信息
            $update_result = model('Managers')->updateManagerLoginInfo($query_manager['uid'], $client_ip);
        } else{
            throw new APIException(10011);
        }
        $authkey = ["mobilephone"=>$mobilephone, "password"=>$password];
        session("authkey", $authkey);
        $login_info = array('manager_mobilephone'=>session('manager_mobilephone'),'is_login'=>1);
        return $login_info;
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
       if (!$this->checkExistsMobilephone($mobilephone)) {
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

        $this->checkPassword($password);
        $this->checkPassword($re_password);
        if ($password !==$re_password) {
            throw new APIException(10006);
        }
        if(!preg_match('/[0-9a-z]{32}/',$password)) {
           $password = md5($password); 
        }
        if (session('weitiao_sms_tag')) {
            $mobilephone_info =$this->checkMobilephone($mobilephone);
            if ($mobilephone_info==0) {
                throw new APIException(10013);
            }elseif ($mobilephone_info==1) {
                $has_update = model('Managers')->updateManagerPassword($mobilephone,$password);
                if (!$has_update) {
                    throw new APIException(10014);
                }
                session('weitiao_sms_tag', null);
                return $this->format_ret();
            }else{
                return $this->format_ret($mobilephone_info);
            }
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
        if(!preg_match('/[0-9a-z]{32}/',$password)) {
           $password = md5($password); 
        }
        if(!preg_match('/[0-9a-z]{32}/',$old_password)) {
           $old_password = md5($old_password); 
        }
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

        $has_update = model('Managers')->updateManagerPassword($mobilephone,$password);
        if (!$has_update) {
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
        // return $this->format_ret($login_info);
        return $login_info;
    }

    /********************************************* 公用函数 ********************************************/
    /**
     * 检测手机号是否注册过
     * @param  string $mobilephone [description]
     * @return [type]              [description]
     */
    private function checkExistsMobilephone($mobilephone='')
    {
        if (!$mobilephone) {
            throw new APIException(10001);
        }
        $res = model('Managers')->hasManagerMobilephone($mobilephone);

        return $res?true:false;
    }

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

        //微信订单异步通知日志
        Log::init([
            'type'  =>  'File',
            'path'  =>  LOG_PATH.'../paylog/'
        ]);
        if(!$notify_data){
            Log::write("微信回调校验失败",'log');
            exit("微信回调校验失败");
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
            }else{
                Log::write("微信回调校验失败".json($res),'log');
                exit("微信回调校验失败".json($res));
            }
        }else{
            Log::write("微信回调校验失败".json($result),'log');
            exit("微信回调校验失败".json($result));
        }
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

        $has_update = model('ExpenseRecords')->updateExpense($expense_num,$trade_num,$actually_amount,$trade_status);
        if ($has_update) {
            $service_info = model('ShopServices')->getServicesById($expense_info['service_id']);
            $service_start_time = $expense_info['service_start_time'];
            $service_end_time = $expense_info['service_end_time'];

            if ($service_start_time - $service_info['service_end_time'] <24*60*60) { //时间不间断
                $experience_days = config('experience_days');
                if ($service_info['service_end_time'] - $service_info['service_start_time'] <= $experience_days*24*60*60+1) { //体验服务
                    if ($service_start_time <= $service_info['service_end_time']) { //且选择了体验服务时间内
                        $remain_expenience_day = date('d',$service_info['service_end_time']) - date('d', $service_start_time); //剩余的体验服务时间
                        $service_end_time = $service_end_time + $remain_expenience_day*24*60*60;
                    }
                }
                $service_start_time = $service_info['service_start_time'];
            }
            $has_update = model('ShopServices')->updateShopServiceTime($expense_info['service_id'] , $service_start_time ,$service_end_time);
            return array('code'=>1);
        }else{
            return array('code'=>0,'msg'=>'更新消费记录失败');
        }
    }
}
