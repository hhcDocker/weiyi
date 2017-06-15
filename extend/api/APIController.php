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
use api\APIBase;

/**
 * This class should be inherited by others which aren't required login.
 * Please change this class if you have requirements of adding common 
 * functions for class which subclass it.
 */
abstract class APIController extends APIBase {
    public function __construct(){
        parent::__construct();
    }
}