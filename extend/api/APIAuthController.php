<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: APIAuthController.php
 * | Description: validate user's login status
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-04-28 12:14 
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace api;
use think\Controller;
use api\APIException;

/**
 *  This class should be inherited and it's goal is to guarantee logined.
 *  Please change this class if you have requirements of adding common 
 *  functions for class which subclass it.
 */
abstract class  APIAuthController extends Controller{
    public function __construct(){
         parent::__construct();
        if(!is_login()) {
            throw new APIException(10018);
        }
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
