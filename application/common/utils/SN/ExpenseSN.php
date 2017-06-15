<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: ExpenseSN.php
 * | Description: generate expense serial number
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-05-08 16:20
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\utils\SN;
use app\common\utils\SN\RandBase;

/**
 *  generate expense serial number
 *  the use case is following:
 *    $o = new ExpenseSN();
 *    $sn = $o->getSN();
 */ 
class ExpenseSN extends RandBase {
    
    const PREFIX = "EXPENSESN";
    const DURATION = 1;
    
    public function getSN(){
        $date = date("ymdHis");
        $rand = self::random_per_duration(0, 999999, self::PREFIX, self::DURATION);
        return self::$typecode['expense']['code'].$date.$rand;
    }
}
