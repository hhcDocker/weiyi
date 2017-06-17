<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: SMS.php
 * +----------------------------------------------------------------------
 * | Created by peteyhuang at 2016-10-01 08:00 
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Updated by peteyhuang at 2016-10-10 15:09
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\index\model;
use think\Model;
use think\Validate;

error_reporting(0);

class SMS extends Model
{
	public static $sms_config = [
		'appkey'		=> '23485661',//阿里大于APPKEY
		'secretKey' 	=> '971955c6d9734418d98a26bfb346f723',//阿里大于secretKey
		'FreeSignName' 	=> '微跳通用验证码',//短信签名，大胖子2
	];

	public function sms($data=[])
	{
		$validate = new Validate([
			['param','require|array','参数必填|参数必须为数组'],
			['mobile','require|/1[34578]{1}\d{9}$/','手机号错误|手机号错误'],
			['template','require','模板id错误'],
		]);

		if (!$validate->check($data)) 
		{
			return $validate->getError();
		}
		
		if (!defined('TOP_SDK_WORK_DIR')){define('TOP_SDK_WORK_DIR', CACHE_PATH.'sms_tmp/');}
		if(!defined('TOP_SDK_DEV_MODE')){define('TOP_SDK_DEV_MODE', false);}
		vendor('taobao.TopSdk');
		$config = self::$sms_config;
		$c = new \TopClient;
		$c->appkey = $config['appkey'];
		$c->secretKey = $config['secretKey'];
		$req = new \AlibabaAliqinFcSmsNumSendRequest;
		$req->setExtend('');
		$req->setSmsType('normal');
		$req->setSmsFreeSignName($config['FreeSignName']);
		$req->setSmsParam(json_encode($data['param']));
		$req->setRecNum($data['mobile']);
		$req->setSmsTemplateCode($data['template']);
		$result = $c->execute($req);
		$result = $this->_simplexml_to_array($result);
		if(isset($result['code']))
		{
			return $result['sub_code'];
		}
		return true;
	}

	private function _simplexml_to_array($obj)
	{
		//该函数用于转化阿里大于返回的数据，将simplexml格式转化为数组，方面后续使用
		if(count($obj) >= 1)
		{
			$result = $keys = [];

			foreach($obj as $key=>$value)
			{
				isset($keys[$key]) ? ($keys[$key] += 1) : ($keys[$key] = 1);

				if( $keys[$key] == 1 )
				{
					$result[$key] = $this->_simplexml_to_array($value);
				}
				elseif( $keys[$key] == 2 )
				{
					$result[$key] = [$result[$key], $this->_simplexml_to_array($value)];
				}
				else if( $keys[$key] > 2 )
				{
					$result[$key][] = $this->_simplexml_to_array($value);
				}
			}

			return $result;
		}
		else if(count($obj) == 0)
		{
			return (string)$obj;
		}
	}
}
?>