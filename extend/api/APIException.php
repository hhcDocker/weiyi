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
          10003 => '短信发送失败，请稍后重试！',
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
          10019 => '格式不正确',
          10020 => '密码长度不得少于6个字符',
          10021 => '密码长度不得多于16个字符',
          10022 => '密码只能为字母或数字',
      /*===================================================================*/
          20001 => "没有此接口",
          20002 => "协议不合法",
      /*===================================================================*/
          30001 => "请输入完整链接！",
          30002 => '链接不合法，请检查链接',
          30003 => '链接不可访问，请检查链接',
          30004 => '非淘宝天猫链接，请检查链接',
          30005 => '非淘宝天猫店铺链接，请检查链接',
          30006 => '请求被拒绝，请检查链接',
          30007 => '非淘宝天猫店铺网址，请检查链接',
          30008 => '非淘宝店铺网址，请检查链接',
          30009 => '获取商品详情信息失败，稍后再试',
          30010 => '数据操作出错，请稍后再试',
          30011 => '天猫官方链接',
          30012 => '获取店铺信息失败，请稍后再试',
          30013 => '非天猫店铺网址，请检查链接',
          30014 => '此类型链接暂不支持转换',
          30015 => '多个网址类型',
          30016 => '错误的服务时长',
          30017 => '请选择正确的服务开始时间',
          30018 => '错误的支付方式',
          30019 => '支付失败，请稍后重试',
          30020 => '已购买服务，无须重复购买',
          30021 => '该店铺服务不存在',
          30022 => '请选择正确的服务续费开始时间',
          30023 => '日期格式错误',
          30024 => '支付失败，编号有误',
          30025 => '支付失败，金额有误',
          30026 => '淘宝官方链接',
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
