<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: APIController.php
 * | Description: common base class for others which have no need of login
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-04-28 16:44
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace api;
use think\Controller;
use think\Request;
use api\APIException;

/**
 * This class should be inherited by others which aren't required login.
 * Please change this class if you have requirements of adding common 
 * functions for class which subclass it.
 */
abstract class APIController extends Controller{
    public function __construct(){
        parent::__construct();
    }
    
    /**
     * default return value for api
     * @param  array   $ret 
     * @param  string  $message the tips for errcode
     * @return array   return value for api
     */
    public function format_ret($ret = [], $message = ""){
        if(isset($ret['errcode'])) {
            return $ret;
        } else {
            if($ret === null) {
                $ret = [];
            }
            $apiret = array("errcode"=>0, "message"=>$message, "result"=>$ret);
            return $apiret;
        }
    }
    
}