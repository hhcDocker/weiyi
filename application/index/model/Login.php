<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: Login.php
 * | Description: operations about login
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-04-29 17:57
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
 
namespace app\index\model;
use app\index\model\SMS;
use think\Model;
use think\Db;
use api\APIException;

class Login extends Model {
    
    /**
     * return user information according to mobilephone, password, buyer_type
     * @param string $mobilephone user's mobile
     * @param string @password  user's password
     * @param int  @buyer_type buyer type defalut 0
     * @return array
     */ 
    public function getUserInfo($mobilephone='', $password='') {

        if (($mobilephone !== '' ) && ($password !== '')) {
            $query_manager = Db::name('managers')
                            ->where('mobilephone', $mobilephone)
                            ->where('password', $password)
                            ->where('is_deleted', 0)
                            ->find();
			if(empty($query_manager)) {
			    throw new APIException(10003);
			}
			if($query_manager['is_locked']) {
                throw new APIException(10008);
            }
			// 存储session
			session('manager_id',$query_manager['id']);
			session('manager_uid', $query_manager['uid']);
			session('manager_mobilephone', $query_manager['mobilephone']);
			// 更新用户登录信息
			$update_result = model('Users')->updateUserInfo($query_manager['uid'], $client_ip, $flag);
        } else{
            throw new APIException(10004);
        }
    }
    
    /**
     * check whether mobile phone has been registered
     * @param string $mobilephone mobile phone
     * @param int $flag seller or buyer
     * @return boolean
     */ 
    public function checkMobilephone($mobilephone, $flag = 1) {
        if(4 == $flag){
            $query_seller_mobilephone = model('Users')->getFactoryUserInfoByMobile($mobilephone,'uid,boss_uid,is_registered');
            if (!empty($query_seller_mobilephone)) {
                return $query_seller_mobilephone;
            }
        } else {
            $query_ecommerce_mobilephone1 = model('EcommerceShop')->getEcommerceUserInfoByMobilephone($mobilephone);
            if ($query_ecommerce_mobilephone1['count(1)']) {
                return false;
            }
            $query_ecommerce_mobilephone15 = model('EcommerceShop')->getEcommerceCompanyByCompany(1,'临时公司');
			if (!$query_ecommerce_mobilephone15['count(1)']){
				$query_ecommerce_mobilephone2 = model('EcommerceShop')->getEcommerceCompanyByCompany(2,$mobilephone);
				if ($query_ecommerce_mobilephone2['count(1)']) {
				    return false;
                }
			}
			$query_beauty_mobilephone = model('BeautyShop')->getBeautyUserInfoByMobilephone($mobilephone);
			if ($query_beauty_mobilephone['count(1)']) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * return user review status
     * @param string $mobilephone user mobile phone
     * @param int $flag seller or buyer
     * @param int review status
     */ 
    public function getStatusByMobilephone($mobilephone, $flag = 1) {
        if(4 == $flag){
			$query_seller_mobilephone = model('SellerShops')->getShopsInfoByMobilePhone($mobilephone,'review_status');
			if (!empty($query_seller_mobilephone)) {
				if(2 == $query_seller_mobilephone['review_status']){
					session('regseller_manager_mobilephone',$mobilephone);
				}
				return $query_seller_mobilephone['review_status'];
			}
		} else {
			$query_ecommerce_mobilephone1 = model('EcommerceShop')->getShopStatusByMobilephone($mobilephone,'review_status');
			if ($query_ecommerce_mobilephone1) {
			     return $query_ecommerce_mobilephone1['review_status'];
            }
			$query_beauty_mobilephone2 = model('BeautyShop')->getShopStatusByMobilephone($mobilephone,'review_status');
			if ($query_beauty_mobilephone2) {
		       return $query_beauty_mobilephone2['review_status'];
            }
		}
		return false;
    }
    
    /**
     * check whether sms code is valid
     * @param string $sms_code sms code
     * @param string $mobilephone mobile phone
     * @return boolean
     */ 
    public function checkSMScode($sms_code, $mobilephone) {
        $time_elapsed = time() - session('mall_sms_code_time');
        if($sms_code == session('mall_sms_code') && $mobilephone == session('mall_sms_mobilephone') && $time_elapsed < 600) {
            session('mall_sms_tag',1);
            return true;
        } else {
            return false;
        }
    }

    /**
     * check exists register mobile phone (who names it? it is very difficult to understand.)
     * @param string $mobilephone mobile phone
     * @param int $type default 2
     * @return int
     */ 
    public function checkExistsRegisterMobilephone($mobilephone, $type=2) {
		$table = (2 == $type)?'EcommerceShop':'BeautyShop';
		//如果type=2 or 1 查询电商的和实体的公司表和用户表信息，
		$is_registered = model($table)->getRegisterByMobilephone($mobilephone);
		if(isset($is_registered['is_registered']) && (0 == $is_registered['is_registered'])){
			$field = (2 == $type)?'id,company_id,boss_uid':'id,boss_uid';
			$ret = model($table)->getRegisterByMobilephone($mobilephone,$field);
			$ret['company_id'] = isset($ret['company_id'])?$ret['company_id']:0;
			session('regbuyer_manager_id', $ret['id']);// 当前用户id
			session('regbuyer_manager_uid', $ret['boss_uid']);// 当前用户uid
			session('regbuyer_company_id', $ret['company_id']);// 当前用户所在公司id
		}
        return $is_registered['is_registered'];
    }
    
    /**
     * check whether one company has been registered for ecommerce shop
     * @param string $company_name company name
     * @return boolean
     */ 
    public function checkCompanyName($company_name) {
        $query_company_name = model('EcommerceShop')->getEcommerceCompanyByCompany(1,$company_name);
        return $query_company_name ? false : true;
    }
    
    /**
     * check whether one company has been registered for buyer by company name
     * @param string $company_name company name
     * @param int $buyer_type  default 2
     * @return boolean
     */ 
    public function buyerCheckCompanyname($company_name, $buyer_type = 2) {
		$table = (1 == $buyer_type) ? "BeautyShop":"EcommerceShop";
		$query_real_shopname = model($table)->getShopInfoByCompanyName($company_name);
		return $query_real_shopname ? false : true;
    }
    
    /**
     * check whether one shop has been registered for buyer by real shop name
     * @param string $real_shopname real shop name
     * @param int $buyer_type  default 2
     * @return boolean
     */ 
    public function buyerCheckRealShopname($real_shopname,$buyer_type = 2) {
		$table = (1 == $buyer_type) ? "BeautyShop":"EcommerceShop";
		$query_real_shopname = model($table)->getShopInfoByShopName($real_shopname);
		return $query_real_shopname ? false : true;
    }
    
    /**
     * check whether one mobile has been registered for buyer
     * @param string $mobilephone mobile phone
     * @return boolean
     */ 
    public function checkExistsMobilephone($mobilephone) {
        $query_ecommerce_mobilephone = model('EcommerceShop')->getEcommerceUserInfoByMobilephone($mobilephone);
        if ($query_ecommerce_mobilephone) {
            return true;
        }                                                   
        $query_beauty_mobilephone = model('BeautyShop')->getBeautyUserInfoByMobilephone($mobilephone);
        if ($query_beauty_mobilephone) {
            return true;
        }
        return false;
    }
    
    /**
     * send sms code
     * @param string $mobilephone mobile phone
     * @param string $captcha_code default null indicates no check captcha code
     * @return void
     */ 
    public function getSmsCode($mobilephone, $captcha_code = null) {
        $time_elapsed = time() - session('mall_sms_code_time');
        if($captcha_code !== null) {
            $captcha_check_result = captcha_check($captcha_code);
            if (!$captcha_check_result) {
               throw new APIException(10022);
            }  
        }
        if ($time_elapsed >= 60) {
            $sms = new SMS();
            $code = (string)rand(100000,999999);
            $sms_result = $sms->sms([
                    'param'  => ['code'=>$code, 'product'=>'大胖子车装联盟'],
                    'mobile'  => $mobilephone,
                    'template'  => 'SMS_6215201',
            ]);
            if($sms_result !== true){
                throw new APIException(10023);
            }
            session('mall_sms_mobilephone', $mobilephone);
            session('mall_sms_code', $code);
            session('mall_sms_code_time', time());
        } else {
            throw new APIException(10024);
        }
    }
    
    /**
     * return value:
     * 0: success
     * 1：data occurs error
     * 2: not manager
     * 3: has no shop
     * 4: review no pass
     * 5: within black list
     */ 
    public function authUserByMobile($mobilephone, $buyer_type=1) {
        $query_manager = array();
		$flag = 1;
        $table = "BeautyShop";
        $client_ip = request()->ip();
        if ($buyer_type == 1) {
            $query_manager = model('BeautyShop')->getBeautyUserInfoByMobile($mobilephone);
        }elseif ($buyer_type == 2) {
            $query_manager = model('EcommerceShop')->getEcommerceUserInfoByMobile($mobilephone);
			$flag = 2;
            $table = "EcommerceShop";
        }else{
            $query_manager = model('BeautyShop')->getBeautyUserInfoByMobile($mobilephone);
            if (!empty($query_manager)) {
                $buyer_type=1;
            }else{
                $query_manager = model('EcommerceShop')->getEcommerceUserInfoByMobile($mobilephone);
                if (!empty($query_manager)) {
                    $buyer_type=2;
					$flag = 2;
                    $table = "EcommerceShop";
                }
            }
        }
        if(empty($query_manager)) {
           return 1; 
        }
        if($query_manager['role_id'] != "201") {
            return 2;
        }
        $result = model($table)->getShopStatusByBossuid($query_manager['boss_uid']);
        if(empty($result)) {
            return 3;
        }
        $allow_login = false;
        foreach($result as $row){
            if($row['review_status'] == "审核通过") {
                $allow_login = true;
                break;
            }
        }
        if(!$allow_login) {
            return 4;
        }
        $query_blacklist = model('Users')->getBlackListUserInfo($mobilephone);
        if(!empty($query_blacklist)) {
            return 5;
        }
        session("buyer_manager_status", 1);
        session('buyer_manager_id',$query_manager['id']);
        session('buyer_manager_uid', $query_manager['uid']);
        session('buyer_boss_uid', $query_manager['boss_uid']);
        session('buyer_company_id', $query_manager['company_id']);
        session('buyer_manager_username', $query_manager['username']);
        session('buyer_manager_headimg', $query_manager['headimg']);
        session('buyer_manager_mobilephone', $query_manager['mobilephone']);
        session('buyer_manager_name', $query_manager['manager_name']);
        session('buyer_manager_role_id',$query_manager['role_id']);
        session('buyer_type',$buyer_type);
        // 更新用户登录信息
		$update_result = model('Users')->updateUserInfo($query_manager['uid'], $client_ip, $flag);
        return 0;
    }
}


/*CREATE TABLE `wj_managers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(32) NOT NULL COMMENT 'md5生成的随机定长字符串',
  `mobilephone` char(16) DEFAULT NULL,
  `password` char(64) DEFAULT NULL,
  `create_time` int(11) unsigned NOT NULL,
  `register_ip` char(16) DEFAULT NULL,
  `last_login_time` datetime DEFAULT NULL,
  `last_login_ip` char(16) DEFAULT NULL,
  `update_time` int(11) unsigned NOT NULL,
  `delete_time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微跳客户信息表';*/

