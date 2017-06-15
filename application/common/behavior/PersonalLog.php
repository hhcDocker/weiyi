<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: PersonalLog.php
 * | Description: record personal log
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-05-13 09:49
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
namespace app\common\behavior;

class PersonalLog {
    public $size = 2097152;
    public function run(&$log){
        foreach($log as $k=>$v) {
           if(strpos($k, "/") !== false) {
               $path = dirname($k);
               !is_dir($path) && mkdir($path, 0755, true);
                //检测日志文件大小，超过配置大小则备份日志文件重新生成
               if (is_file($k) && floor($this->size) <= filesize($k)) {
                  rename($k, dirname($k) . DS . $_SERVER['REQUEST_TIME'] . '-' . basename($k));
               }
               $info = "";
               foreach ($v as $msg) {
                  if (!is_string($msg)) {
                     $msg = var_export($msg, true);
                  }
                  $info .= $msg . "\r\n";
               }
               if($info) {
                   $info = date("Y-m-d H:i:s\t").$info;
                   error_log("{$info}\r\n", 3, $k);
               }
               unset($log[$k]);
           }
        }
    }
}