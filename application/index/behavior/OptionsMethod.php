<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: PersonalLog.php
 * | Description: record personal log
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-05-13 09:49
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
namespace app\index\behavior;
use think\Request;

class OptionsMethod {
    public function run(){
        if(Request::instance()->method() == "OPTIONS") {
           header('Access-Control-Allow-Origin: *');
           header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT');
           // header('Access-Control-Allow-Headers: X-Token, Origin, X-Requested-With, Content-Type, Accept, Connection, User-Agent, Cookie');
           exit;
        }
    }
}