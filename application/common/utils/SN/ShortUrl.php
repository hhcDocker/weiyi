<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: ShortUrl.php
 * | Description: generate service serial number
 * +----------------------------------------------------------------------
 * | Created by equinoxsun at 2017-07-26 16:16
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\utils\SN;
use app\common\utils\SN\RandBase;

/**
 *  generate service serial number
 *  the use case is following:
 *    $o = new ShortUrl();
 *    $sn = $o->getSN();
 */
class ShortUrl extends RandBase {
    
    const PREFIX = "ShortUrl";
    const DURATION = 1;
    
    public function getSN(){
        $rand = self::random_per_duration(100000000, 999999999, self::PREFIX, self::DURATION);
        $rand =  base_convert($rand, 10, 36);
        $rand = strtoupper($rand);
        return $rand;
    }
}
