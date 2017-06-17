<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: APIException.php
 * | Description: The exception for api calls
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-04-27 19:57 
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace api;

class APIException extends \Exception {
    
    /* the errcode and message map array */
    public static $code_array = [
       /*===================================================================*/
          10001 => '参数为空或不正确！',
          10002 => '此手机号已在本平台注册过！',
          10003 => '短信发送失败！',
          10004 => '验证码发送过于频繁，请稍后再试！',
          10005 => '短信验证码填写错误！',
          10006 => '两次输入的密码不一致！',
          10007 => '请勿重复登录！',
          10008 => '登录失败，手机号或密码不正确！',
          10009 => '账号被锁定！',
          10010 => '手机号码被禁用，请联系客服！',
          10011 => '登录失败，手机号和密码必填！',
          10012 => '您输入的手机号码未注册，请先注册，然后再登录！',
          10013 => '此手机号未在本平台注册过！',
          10014 => '重置密码失败!',
          10015 => '旧密码错误',
          10016 => '注册失败，请稍后重试！',
          10017 => '新旧密码相同',
          10018 => '未登录',
          10020 => '手机号格式不正确',


          10032 => '数据操作出错，请稍后再试',
          10033 => '订单不存在，或已删除！',
          10040 => '支付密码错误',
          10045 => '',
          10046 => '',
          11999 => '自定义提示',
      /*===================================================================*/ 
          20001 => "没有此接口",
          20002 => "协议不合法",
      /*===================================================================*/ 
          30001 => "请输入完整链接！",
          30002 => '链接不合法',
          30003 => '链接不可访问',
          30004 => '非淘宝天猫链接',
          30005 => '非淘宝天猫店铺链接链接',
          30006 => '请求被拒绝',
          31999 => '检查链接',
      /*===================================================================*/    
          40001 => "用户ID不能为空",
          40002 => "凭据不合法或者已过期",
          40003 => "凭据不能为空",
          40004 => "数据不符合要求",
          40005 => "数据必须是数组",
          40007 => "未登录",
          40008 => '数据被修改',
      /*===================================================================*/
    ];
    public $errmap = null;
    public function __construct($code = 10001, $ret = []) {
        if(is_array($code)) {
            $this->errmap = $code;
            if(isset($code['errcode'], $code['message'])) {
                parent::__construct($code['message'], $code['errcode']); 
            } else {
                parent::__construct(); 
            }
            return;
        }
        if(in_array($code, array_keys(self::$code_array))) {
            parent::__construct(self::$code_array[$code], $code); 
            $this->errmap = array("errcode"=>$code, "message"=>self::$code_array[$code], "result"=>$ret);
        } else {
            parent::__construct("oh, message is empty", $code);
            $this->errmap = array("errcode"=>$code, "message"=>"","result"=>$ret);
        }
    }
}
