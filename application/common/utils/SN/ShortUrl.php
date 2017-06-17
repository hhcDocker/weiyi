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
    
    public function __construct($url=''){
    	if (!$url) {
            throw new APIException(9999);
    	}
    	if (!strstr($url, 'm.taobao.com') && !strstr($url, 'm.tmall.com')) {
            throw new APIException(30007);
    	}
        $this->url = $url;
    }

    public function getSN()
    {
	    $url=crc32($this->url); 
	    $result=sprintf("%u",$url);
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
