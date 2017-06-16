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
use api\APIController;
use api\APIException;
use think\captcha\Captcha;
use think\Config;

class Index extends APIController
{
    /*public function index()
    {
        return json(array('code'=>1,'msg'=>'<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布666 ]</span></div><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_bd568ce7058a1091"></thinkad>'))
        ;
    }*/

    /**
     * authorize user login
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

        if (($mobilephone !== '' ) && ($password !== '')) {
            $query_manager = model('Managers')->getManagerInfo($mobilephone, $password);
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
            $update_result = model('Managers')->updateManagerLoginInfo($query_manager['uid'], $client_ip);
        } else{
            throw new APIException(10004);
        }
        $authkey = ["mobilephone"=>$mobilephone, "password"=>$password];
        session("authkey", $authkey);
        return $this->format_ret($res);
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
     * send register code
     * @return array
     */ 
    public function getRegisterCode(){
        $mobilephone = noempty_input('mobilephone', '/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/');
        $captcha_code = noempty_input('captcha_code');
        $query_blacklist = model('Users')->getBlackListUserInfo($mobilephone);
        if ($query_blacklist) {// 黑名单
            throw new APIException(10021);
        }
        if (!model("login")->checkMobilephone($mobilephone)) {
               throw new APIException(10005);
        }
        model("login")->getSmsCode($mobilephone, $captcha_code); 
        return $this->format_ret();
    }
    
    /**
     *  send reset password code
     *  @return array
     */ 
    public function getResetCode(){
       $mobilephone = noempty_input('mobilephone', '/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/'); 
       $query_blacklist = model('Users')->getBlackListUserInfo($mobilephone);
       if ($query_blacklist) {// 黑名单
          throw new APIException(10021);
       }
       if (!model("login")->checkExistsMobilephone($mobilephone)) {
          throw new \APIException(10020);
       }
       model("login")->getSmsCode($mobilephone);
       return $this->format_ret();
    }
    
    public function getcaptcha(){
       $captcha = new Captcha((array)Config::get('captcha'));
       $response = $captcha->entry();
       $content = base64_encode($response->getContent());
       return $this->format_ret(["data"=>"data:image/png;base64,{$content}"]);
    }
    
    /**
     * user register
     * @return array
     */ 
    public function register() {
        $client_ip = $this->request->ip();
        $sms_code = noempty_input('sms_code');
        $captcha_code = noempty_input('captcha_code');
        $mobilephone =  noempty_input('mobilephone','/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/');
        $password = noempty_input('password');
        $re_password = noempty_input('re_password');
        $es_agreement = noempty_input('es_agreement');
        $buyer_type = noempty_input('buyer_type_id', '/^[12]$/', 1);
        session('regbuyer_type_id', $buyer_type);
        if (!model("login")->checkMobilephone($mobilephone)) {
            $review_status = model("login")->getStatusByMobilephone($mobilephone);
            if($review_status < 2){
                throw new APIException(10005);
            }
        }
        if(!model("login")->checkSMScode($sms_code, $mobilephone)) {
           throw new APIException(10006);
        }
        if($es_agreement && ($password == $re_password) && $buyer_type){
            $time = date("Y-m-d H:i:s");
            $uid = md5(uniqid(rand(), true));
			$password_md5 = md5($password);
			$regist = model("login")->checkExistsRegisterMobilephone($mobilephone, $buyer_type);
			if(0 == $regist && null !== $regist){
			    throw new APIException(10008, ['buyer_type' => $buyer_type]);
			} else if(1 == $regist){
			    throw new APIException(10009);
			}
            if ($buyer_type == 2) {//电商
				$company_id_main_key = model('EcommerceShop')->insertEcommerceCompanies($uid,$mobilephone,"");

				// 向电商人员表中插入数据
				if($company_id_main_key > 0){
					$manager_id = model('EcommerceShop')->insertEcommerceShopManagers($uid,$mobilephone,$password_md5,$client_ip,$company_id_main_key);
				}else{
					 throw new \Exception();
				}
                session('regbuyer_manager_id', $manager_id);// 当前用户id
                session('regbuyer_manager_uid', $uid);// 当前用户uid
                session('regbuyer_company_id', $company_id_main_key);// 当前用户所在公司id
                return $this->format_ret(['data'=>'2']);
            }else{
                $manager_id = model('BeautyShop')->insertBeautyManagers($uid,$mobilephone,$password_md5,$client_ip);

				//是否需要插入beauty_shop表 @kevin 2017/4/19

                //添加用户和组对应关系
                $auth_id = model('Users')->insertUserGroup($uid);
                session('regbuyer_manager_id', $manager_id);// 当前用户id
                session('regbuyer_manager_uid', $uid);// 当前用户uid
                session('regbuyer_company_id', 0);// 当前用户所在公司id
                return $this->format_ret(['data'=>1]);
           }
        } else if(!$es_agreement) {
            throw new  APIException(10011);
        } else if($password!=$re_password) {
            throw new  APIException(10012);
        } else if (!$buyer_type) {
            throw new  APIException(10013);
        } else {
            throw new  APIException(10014);
        }
    }
    
	//注册第二步（电商）
    public function eregister() {
        $client_ip = $this->request->ip();
        $company_name = noempty_input('company_name');
        $store_link = noempty_input('store_link');
		$shop_name = noempty_input('shop_name');
        $principal = noempty_input('principal');
        $principal_tel = noempty_input('principal_tel');
        
		if (!model("login")->buyerCheckCompanyname($company_name,'2')) {
		    throw new  APIException(10010);
		}
        $principal_tel = preg_replace('/[^0-9]+/', '', $principal_tel);
        $preg_mobilephone ='/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/';

        if (!preg_match($preg_mobilephone, $principal_tel)) {
            throw new APIException(10017);
        }

		//电商注册，修改原数据
		$company_id = session('regbuyer_company_id');
		$boss_uid = session('regbuyer_manager_uid');
		$manager_id = session('regbuyer_manager_id');

        if(anyempty($company_id, $boss_uid, $manager_id)) {
            throw new APIException(10018);
        }
		$company_data = array('company_name' => $company_name);
		$ret = model('Ecommerces')->updateCompanyNameByUidId($company_id,$boss_uid,$company_data);
        if(empty($insertid)) {
           throw new APIException();
        }
		$insertid = model('Ecommerces')->insertEcommerceShops($boss_uid,$company_id,$store_link,$shop_name,$principal,$principal_tel);
        if(empty($insertid)) {
           throw new APIException();
        }
		$ret = model('Ecommerces')->updateManagerNameByUidId($manager_id,$boss_uid,array('is_registered'=>1));
        return $this->format_ret();
	}
    
	//注册第二步（门店）
    public function bregister(){
        if(anyempty(session('regbuyer_manager_uid'), session('regbuyer_type_id'))) {
            throw new APIException(10018);
        }
        $client_ip = $this->request->ip();
        $company_name = noempty_input('company_name');
		$license = noempty_input('license', '/^[0-9A-HJ-NPQRTUWXY]{2}\d{6}[0-9A-HJ-NPQRTUWXY]{10}$|^\d{15}$/');
        $registration_province = noempty_input('registration_province');
		$registration_city = noempty_input('registration_city');
		$registration_district = noempty_input('registration_district');
		$address = noempty_input('address');
        $principal = noempty_input('principal');
        $principal_tel = noempty_input('principal_tel', '/^(1(([35][0-9])|(47)|[78][0-9]))\d{8}$/');
		$photos = json_decode(noempty_input('photos'), true);
        $location = noempty_input("lnglat");
		
        if (!model("login")->buyerCheckRealShopname($company_name,'1')) {
            throw new APIException(10010);
		}
        if(!is_array($photos) || count($photos) != 4) {
            throw new APIException(20002);
        }
		foreach ($photos as $k=>$v){
		    if (!$v){
		        throw new APIException(10019);
            }
        }
		//实体注册，修改原数据
		$manager_id = session('regbuyer_manager_id');
		$boss_uid = session('regbuyer_manager_uid');
		$company_data = array('company_name' => $company_name,'manager_name' => $principal,'business_license_num' => $license,'business_license_photo'=>$photos[1],'legal_person_id_photo'=>($photos[2].','.$photos[3]));

		$ret = model('BeautyShop')->updateManagerById($manager_id,$company_data);
        list($lng,$lat) = explode(",", $location);
        $location_point = "POINT($lng $lat)";
		
		$data = array(
			'real_shopname'=>$company_name,
			'boss_uid'=>$boss_uid,
			'boss_name'=>$principal,
			'mobilephone'=>$principal_tel,
			'province'=>$registration_province,
			'city'=>$registration_city,
			'district'=>$registration_district,
			'address'=>$address,
			'full_address'=>$registration_province.$registration_city.$registration_district.$address,
			'location'=>$location,
			'lng'=>$lng,
			'lat'=>$lat,
			'location_point'=>$location_point,
			'unified_phone'=>'4008330773',
			'money'=>'0.00',
			'photos'=>$photos[0],
			'review_status'=>'待审核',
			'create_time'=>date('Y-m-d H:i:s',time())
		);
		if(session('buyer_boss_uid') && (2 == session('buyer_manager_status'))) {
			//修改原来的数据
			$update_ret = model('BeautyShop')->updateBeautyShops(session('buyer_boss_uid'),$data);
		} else {
			$insertid = model('BeautyShop')->insertBeautyShops($data);
			if(empty($insertid)){
				throw new APIException();
			}
			//修改register为1 @kevin 2017/4/23
			$ret = model('BeautyShop')->updateManagerById($manager_id,array('is_registered'=>1));
		}
		return $this->format_ret();
	}
    
    /**
     * check sms code whether valid
     * @return array
     */ 
    public function checkSMScode() {
        $sms_code = noempty_input('sms_code');
        $mobilephone = noempty_input('mobilephone');
        if(!model("login")->checkSMScode($sms_code, $mobilephone)){
            throw new APIException(10006);
        } else {
            return $this->format_ret();
        }
    }
    
    /**
     * reset user password
     * @return array
     */ 
    public function resetpasswd() {
        $mobilephone = noempty_input('mobilephone');
        $password = noempty_input('password');
        $re_password = noempty_input('re_password');
        if ($password !==$re_password) {
            throw new APIException(10012);
        }
        if(!preg_match('/[0-9a-z]{32}/',$password)) {
           $password = md5($password); 
        }
        if (session('mall_sms_tag')) {
	       $has_manager = model('BeautyShop')->getBeautyUserInfoByMobilephone($mobilephone,' count(1) as num ');
           if (!empty($has_manager['num'])) {
               $update_result = model('BeautyShop')->updateBeautyUserPasswdByMobilephone($mobilephone,$password);
           } else {
	           $has_manager = model('EcommerceShop')->getEcommerceUserInfoByMobilephone($mobilephone,' count(1) as num ');
               if (!empty($has_manager['num'])) {
				   $update_result = model('EcommerceShop')->updateEcommerceUserPasswdByMobilephone($mobilephone,$password);
               }else{
                   throw new APIException(10020);
               }
           }
           session('mall_sms_tag', null);
           return $this->format_ret();
       }else{
           throw new APIException(10006);
       }
    }

}
