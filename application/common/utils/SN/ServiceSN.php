<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: ServiceSN.php
 * | Description: generate service serial number
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-05-08 16:16
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\utils\SN;
use app\common\utils\SN\RandBase;

/**
 *  generate service serial number
 *  the use case is following:
 *    $o = new ServiceSN();
 *    $sn = $o->getSN();
 */
class ServiceSN extends RandBase {
    
    const PREFIX = "ServiceSN";
    const DURATION = 1;
    
    public function getSN(){
        $date = date("ymdHis");
        $rand = self::random_per_duration(0, 999999, self::PREFIX, self::DURATION);
        return self::$typecode['service']['code'].$date.$rand;
    }
}
