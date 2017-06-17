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
use app\index\model\SMS;
use api\APIController;
use api\APIException;
use think\Config;
use think\Request;


class Index extends APIController
{
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
        $password = noempty_input('password');
        $re_password = noempty_input('re_password');
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
            $manager_id = model('Managers')->addManagerInfo($uid,$mobilephone,$password,$client_ip);
            if (!$manager_id) {
                throw new APIException(10006);
            }
            session('manager_id', $manager_id);// 当前用户id
            session('manager_uid', $uid);// 当前用户uid
            session('manager_mobilephone',$mobilephone);
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
        $password = noempty_input('password');
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
        return $this->format_ret();
    }
    
    /**
     * logout
     * @return array
     */ 
    public function logout(){
        session_destroy();
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
    public function setnewpasswd()
    {
        $mobilephone = session('find_pwd_mobilephone');
        $password = noempty_input('password');
        $re_password = noempty_input('re_password');
        if ($password !==$re_password) {
            throw new APIException(10006);
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
    public function resetpasswd() {
        $mobilephone = session('manager_mobilephone');
        $old_password = noempty_input('old_password');
        $password = noempty_input('password');
        $re_password = noempty_input('re_password');
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
            throw new APIException(10006);
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
       session('weitiao_sms_tag', null);
       return $this->format_ret();
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
}
