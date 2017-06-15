<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: RandBase.php
 * | Description: base class generating random numeric
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-05-08 11:10
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\utils\SN;
use app\common\utils\SN\RandInterface;

/**
 * define SN base class that should be inherited
 */ 
abstract class RandBase implements RandInterface {
    
    /**
     * define type code map
     * len is the corresponding serial number length
     */ 
    static $typecode = [
         "service" => ['code'=>82, 'len'=>20],
         "expense" => ['code'=>72, 'len'=>20],
    ];
    
    /**
     *  generate fixed length random numeric
     *  @param int $min  min value
     *  @param int $max  max value
     *  @return string fixed length string
     */ 
    static protected function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);
        $ret = $min + $rnd;
        $len = strlen("$max");
        $rstring = sprintf("%0{$len}s", $ret);
        return $rstring;
    }

    /**
     *  @param int $min  min value
     *  @param int $max  max value
     *  @param string $prefix lock file or cache key prefix
     *  @param int $duration the duration within which guaranting random numeric is unique
     *  @return string fixed length string
     */ 
    static protected function random_per_duration($min, $max, $prefix, $duration){
        $start = time();
        $lock = fopen("$prefix.lockfile", 'w+');
        $repeat = true;
        while($repeat){
           $repeat = false;
           $rstring = self::crypto_rand_secure($min, $max);
           flock($lock, LOCK_EX);
           $ret = cache("{$prefix}_{$rstring}");
           if($ret !== false) {
              $repeat = true;
           } else {
              cache("{$prefix}_{$rstring}", 1, $duration);
              $repeat = false;
           }
           flock($lock, LOCK_UN);
           if($repeat && time()- $start > 2) {
              fclose($lock);
              throw new \Exception("the elapsed time used to geneate random numeric is too long!");
           }
       }
       fclose($lock);
       return $rstring;
   }
   
   static protected function padLength($str, $len){
       if(strlen($str) < $len) {
           $str = sprintf("%0{$len}s", $str);
       } else if(strlen($str) > $len) {
           $str = substr($str, -$len);
       }
       return $str; 
    }
    
}
