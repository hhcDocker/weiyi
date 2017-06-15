<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: CrossDomain.php
 * | Description: resolve cross domain issues for api calls
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-05-04 20:30
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
namespace app\index\behavior;

class CrossDomain {
    public function run(){
        header('Access-Control-Allow-Origin: *');
    }
}