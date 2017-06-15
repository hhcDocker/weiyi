<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: APIBase.php
 * | Description: The top base class of other api classes
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-04-28 14:12
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace api;
use think\Controller;
use api\APIException;
use think\Request;

/**
 *  This class is the top base class, so please change it if you have need of adding common
 *  functions for all api class.
 *  This class has the following functions:
 *  1. guarantee the api calls following the rules that should be applyed.
 *  2. Restful API's version manage
 */ 
abstract class APIBase extends Controller {
    
    private $version = null;
    
    /*public function __construct(){
        parent::__construct();
        $sname = config('session.var_session_id');
        $token = $this->request->header("$sname");
        /* guarantee api token */
        /*if(empty($sname) || !$token){
            throw new APIException(40002);
        }
        $_REQUEST[$sname] = $token;
        /* guarantee api token is valid */
        /*$validorigin = config('session.validorigin');
        if(session('validorigin') !== $validorigin) {
            session_destroy();
            throw new APIException(40002);
        }
        $this->version = $this->request->header("X-Version");
        if (strpos($this->request->header('USER_AGENT'), 'MicroMessenger') === false) {
			//throw new APIException(10057);
	    }
    }*/
    
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
    
    /**
     * compare two versions
     * @param  array $e1  version array [major,minor,release] 
     * @param  array $e2  version array [major,minor,release] 
     * @return integer  -1 if $e1 < $e2, 0 if $e1 == $e2, 1 if $e1 > $e2
     */
    private static function version_cmp($e1, $e2){
        if($e1[0] > $e2[0]) {
            return 1;
        } else if($e1[0] < $e2[0]) {
            return -1;
        } else if($e1[1] > $e2[1]) {
            return 1;
        } else if($e1[1] < $e2[1]) {
            return -1;
        } else if($e1[2] > $e2[2]) {
            return 1;
        } else if($e1[2] < $e2[2]) {
            return -1;
        } else {
            return 0;
        }
    }
    
    /**
     * fill version array to make it meet [major, minor, release] format
     * @param  array  $v_3  version array to be formated
     * @return array  formated version array
     */
    private static function format_v3($v_3){
        if(count($v_3) == 1) {
            $v_3[1] = "0";
            $v_3[2] = "0";
        }
        if(count($v_3) == 2) {
            $v_3[2] = "0";
        }
        foreach($v_3 as $k=>$v) {
            $v_3[$k] = intval($v);
        }
        return $v_3;
    }
    
    /**
     * find the most suitable api version according to the following two rules:
     * 1. return the api that is matched with the parameter $version
     * 2. return the api that is closest to the parameter $version
     * @param  string  $name  api name
     * @param  string  $version  the api version
     * @return \ReflectMethod  the most suitable ReflectMethod object
     */
    private function find_method($name, $version=PHP_INT_MAX) {
        $name = strtolower($name);
        $v_3 = array();
        if(preg_match('/^[0-9]+(?:\.[0-9]+){0,2}$/', $version)) {
            $v_3 = explode(".", $version);
        } else if(preg_match('/^[0-9]+(?:_[0-9]+){0,2}$/', $version)) {
            $v_3 = explode("_", $version);
        } else {
            throw new APIException(10002);
        }
        $version = implode("_", $v_3);
        $reflection = new \ReflectionClass(get_class($this));
        if($reflection->hasMethod($name."_v".$version)) {
            return $reflection->getMethod($name."_v".$version);
        } else {
            $v_3 = self::format_v3($v_3);
            $v_3_array = array();
            $method_array = array();
            $methods = $reflection->getMethods();
            foreach($methods as $v) {
                $mname = strtolower($v->getName());
                $pattern = '/^'.preg_quote($name, '/').'_v([0-9]+(?:_[0-9]+){0,2})$/';
                $matches = null;
                $cnt = preg_match($pattern, $mname, $matches);
                if($cnt == 1) {
                    $tmp = self::format_v3(explode("_", $matches[1]));
                    $v_3_array[] = $tmp;
                    $method_array[serialize($tmp)] = $v;
                }
            }
            if(in_array($v_3, $v_3_array)) {
                return $method_array[serialize($v_3)];
            } else {
                $v_3_array[] = $v_3;
                usort($v_3_array, array(get_class($this), "version_cmp"));
                $index = array_search($v_3, $v_3_array);
                if ($index === false || $index === 0) {
                    throw new APIException(20001);
                } else {
                    return $method_array[serialize($v_3_array[$index-1])];
                }
            }
        }
    }
    
    /**
     * the restful api for resource list
     * @return array
     */
    final public function index() {
        if($this->version){
            $method = $this->find_method('index',$this->version);
        } else {
            $method = $this->find_method('index');
        }
        $args = func_get_args();
        return $this->format_ret($method->invokeArgs($this, $args));
    }

    /**
     * 
     * @return array
     */
    final public function create() {
        if($this->version){
            $method = $this->find_method('create',$this->version);
        } else {
            $method = $this->find_method('create');
        }
        $args = func_get_args();
        return $this->format_ret($method->invokeArgs($this, $args));
    }

    /**
     * the restful api for adding resource 
     * @param  \think\Request  $request
     * @return array
     */
    final public function save(Request $request) {
        if($this->version){
            $method = $this->find_method('save',$this->version);
        } else {
            $method = $this->find_method('save');
        }
        $args = func_get_args();
        return $this->format_ret($method->invokeArgs($this, $args));
    }

    /**
     * the restful api for specified resource detail
     * @param  int  $id  resource id
     * @return array
     */
    final public function read($id) {
        if($this->version){
            $method = $this->find_method('read',$this->version);
        } else {
            $method = $this->find_method('read');
        }
        $args = func_get_args();
        return $this->format_ret($method->invokeArgs($this, $args));
    }

    /**
     * the restful api for editing specified resource
     * @param  int  $id  resource id
     * @return array
     */
    final public function edit($id) {
        if($this->version){
            $method = $this->find_method('edit',$this->version);
        } else {
            $method = $this->find_method('edit');
        }
        $args = func_get_args();
        return $this->format_ret($method->invokeArgs($this, $args));
    }

    /**
     * the restful api for updating specified resource
     * @param  \think\Request  $request
     * @param  int  $id  resource id
     * @return array
     */
    final public function update(Request $request, $id) {
        if($this->version){
            $method = $this->find_method('update',$this->version);
        } else {
            $method = $this->find_method('update');
        }
        $args = func_get_args();
        return $this->format_ret($method->invokeArgs($this, $args));
    }

    /**
     * the restful api for deleting specified resource
     * @param  int  $id resource id
     * @return array
     */
    final public function delete($id) {
        if($this->version){
            $method = $this->find_method('delete',$this->version);
        } else {
            $method = $this->find_method('delete');
        }
        $args = func_get_args();
        return $this->format_ret($method->invokeArgs($this, $args));
    }
}