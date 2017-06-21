<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: common.php
 * | Description: the common functions 
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-04-29 17:57
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
 
use api\APIException;

/**
 * guarantee the parameter $str isn't emtpy
 * @param string $str
 * @return string return trimed string if not empty
 */
function noemptyvalid($str, $name){
   if(trim($str) === "") {
        if (input($name)){
            throw new APIException(10019, ['name'=>$name]);
        }
        throw new APIException(10001, ['name'=>$name]);
    } else {
        return trim($str);
    } 
}

/**
 * guarantee the input isn't empty
 * @param string $name input name
 * @param mixed[string,array] $filter
 * @param string $default default value
 * @return string return trimed value
 */ 
function noempty_input($name, $filter = "", $default = ""){
    $ret = input($name, $default, $filter);
    return noemptyvalid($ret, $name);
}

/**
 * Determine whether any variable is empty
 * @return boolean
 */ 
function anyempty(){
  $args = func_get_args();
  foreach($args as $v) {
     if(empty($v)) {
        return true;
     }
  }  
  return false;
}

/**
 * Determine whether any variable is not empty
 * @return boolean
 */ 
function notallempty(){
   $args = func_get_args();
   foreach($args as $v) {
      if(!empty($v)) {
         return true;
      }
   }  
   return false; 
}

/**
 * Determine whether user is logined
 * @return boolean
 */ 
function is_login(){
    $authkey = session("authkey");
    if(empty($authkey)) {
        return false;
    } else {
        return true;
    }
}