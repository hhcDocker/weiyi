<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: TransSN.php
 * | Description: generate expense serial number
 * +----------------------------------------------------------------------
 * | Created by equinoxsun at 2017-07-26 11:20
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\utils\SN;
use app\common\utils\SN\RandBase;

/**
 *  generate expense serial number
 *  the use case is following:
 *    $o = new TransSN($type_id,$object_id);
 *    $sn = $o->getSN();
 */ 
class TransSN extends RandBase {
    const PREFIX = "TransSN";
    const DURATION = 86400;
    private $type_id = 1;
    private $object_id = null;
    
    public function __construct($type_id,$object_id){
        $this->object_id = self::padLength($object_id, 4);
    }
    
    public function getSN(){
        $date = date("ymd");
        $rand = self::random_per_duration(0, 999999, self::PREFIX, self::DURATION);
        return self::$typecode['trans']['code'].$date.$this->type_id.$this->object_id.$rand;
    }

    // 8位数字+字母
    /*public function getSN(){
        $rand = self::random_per_duration(100000000000, 999999999999, self::PREFIX, self::DURATION);
        $rand =  base_convert($rand, 10, 36);
        $rand = strtoupper($rand);
        return $rand;
    }*/
}
