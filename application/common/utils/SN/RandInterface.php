<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: RandInterface.php
 * | Description: generate random numeric interface
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-05-08 10:33
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

/**
 * define interface that SN class should implements
 */ 
namespace app\common\utils\SN;

interface RandInterface {
    public function getSN();
}
