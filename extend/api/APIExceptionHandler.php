<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: APIExceptionHandler.php
 * | Description: The default handler for api exception
 * +----------------------------------------------------------------------
 * | Created by junweiqu at 2017-04-28 14:23
 * | Email: junweiqu@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace api;
use  think\exception\Handle;
use  think\Log;
use  api\APIException;
use  app\index\AlertMail;

class ExceptionProcess {
    private $object = null;
    private $errmap = null;
    public function __construct(\Exception $e){
        $this->object = $e;
        if(isset($e->errmap) && !empty($e->errmap)) {
            $this->errmap = $e->errmap;
            if(!isset($this->errmap['errcode'])) {
                $this->errmap['errcode'] = 9999;
            }
            if(!isset($this->errmap['message'])) {
                $this->errmap['message'] = '内部错误';
            }
            if(!isset($this->errmap['result'])) {
                $this->errmap['result'] = new \stdClass;
            }
        } else {
            $this->errmap = array('errcode'=>9999, 'message'=>'内部错误', 'result'=>new \stdClass);
        }
    }

    // send exception to client
    public function send(){
        header("Content-Type: application/json; charset=utf-8");
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode($this->errmap);
    }
}

class APIExceptionHandler extends Handle {

    public function report(\Exception $exception) {
        $data = [
            'file'    => $exception->getFile(),
            'line'    => $exception->getLine(),
            'message' => $this->getMessage($exception),
            'code'    => $this->getCode($exception),
        ];
        $log = "[{$data['code']}]{$data['message']}[{$data['file']}:{$data['line']}]";
        Log::record($log, 'error');
        if(!($exception instanceof APIException)) {
            $timestr = date("Y-m-d H:i:s");
            $server = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '0.0.0.0';
            $remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
            $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI';
            $uri    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $info = [];
            $info["$timestr"] = "{$server} {$remote} {$method} {$uri}";
            $info['ROUTE'] = request()->dispatch();
            $info['HEADER'] = request()->header();
            $info['PARAM'] = request()->param();
            $info['error'] = [
                "code" => $data['code'],
                "message" => $data['message'],
                "file" => $data['file'],
                "line" => $data['line']
            ];
            $cachekey = serialize($info['error']);
            if(cache($cachekey) === false) {
                cache($cachekey, 1, 600);
                $message = print_r($info, true);
                $mail = new AlertMail("<pre>{$message}</pre>");
                $mail->send();
            }
        }
    }

    public function render(\Exception $e) {
        return new ExceptionProcess($e);
    }
}
