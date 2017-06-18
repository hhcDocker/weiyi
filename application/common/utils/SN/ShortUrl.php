<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: ShortUrl.php
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
 *    $o = new ShortUrl();
 *    $sn = $o->getSN();
 */
class ShortUrl extends RandBase {
    
    public function __construct($shop_id=0,$manager_id=0,$time=''){
    	if (!$shop_id || !$manager_id) {
            throw new APIException(9999);
    	}
        $this->shop_id = $shop_id;
		$this->manager_id = $manager_id;
		$this->time = $time;
    }

    public function getSN()
    {
		$string =$this->shop_id . $this->manager_id .$this->time;
		$string=crc32($string);
	    $result=sprintf("%u",$string);
	    return $this->code62($result);
    }

    private function code62($x){
	    $show=''; 
	    while($x>0){
	        $s=$x % 62; 
	        if ($s>35){ 
	            $s=chr($s+61); 
	        }elseif($s>9&&$s<=35){ 
	            $s=chr($s+55); 
	        } 
	        $show.=$s; 
	        $x=floor($x/62); 
	    } 
	    return $show; 
	}
}
