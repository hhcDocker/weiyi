<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: WeiBaoData.php
 * | Description: 调取天猫淘宝数据服务
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-06-15 13:16
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\service;
use JonnyW\PhantomJs\Client;
use app\common\utils\SN\ShortUrl;
use app\common\utils\SN\ExpenseSN;

class WeiBaoData {

    /**
     * 接收并处理跳转淘宝店铺、天猫店铺等链接
     * @param  string  $url  [链接]
     * @return [type]        [description]
     */
    public function getShopDataByUrl($url='') {
    	if (!$url) {
    		return array('errcode'=>10001);
    	}
        if (!preg_match('/http.+m.taobao.com/', $url) && !preg_match('/http.+m.tmall.com/', $url)) {
        	return array('errcode'=>30007);
        }

        $client = Client::getInstance();
        $client->getEngine()->setPath(config('PhantomjsPath'));
        $client->isLazy();
        /**
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($url, 'GET');
        //$request->setDelay(1000);
        /**
         * @see JonnyW\PhantomJs\Http\Response
         **/
        $request->setTimeout(100000);
        $response = $client->getMessageFactory()->createResponse();

        // Send the request
        $client->send($request, $response);
        dump( $response->getUrls());
        dump($response->getConsole());
        $data=$response->getUrlData();
        var_dump($data);
        //end
        if (!$data ||empty($data)) {
            return array('errcode'=>30012);
            //echo json_encode($response->getConsole());
            //return;
        }
		$shopOtherData=$response->getShopOtherData();

        $shop_data=array();
        $flag_error =0;
        $shopId=0;
        //校验是否全部获取到
        foreach ($data as $k => $v) {
            $v = preg_replace('/^mtopjsonp\d+\(([\s\S]+)\)/','$1', $v);
            $v = json_decode($v,true);
            if ($v['ret'][0]!="SUCCESS::调用成功"){
                $flag_error=1;
                break;
            }else{
                if ($k==0){ //第一个接口
                    $_data=array('data'=>$v['data']['data'],'view'=>$v['data']['view'],'shop_other_data'=>$shopOtherData);
                    if (isset($v['data']['data']['shopId'])){ //taobao
                        $shopId = $v['data']['data']['shopId'];
                        if (isset($v['data']['data']['_we_request']['user_id'])){
                            $userId =$v['data']['data']['_we_request']['user_id'];
                        }elseif(isset($v['data']['data']['_we_system']['userId'])){
                            $userId =$v['data']['data']['_we_system']['userId'];
                        }else{
                            $userId=0;
                        }
                    }else{ //tmall
                        $shopId =$v['data']['data']['_we_request']['shopId'];
                        $userId =0;//mc
                    }

                    if (!$shopId) {
                        $flag_error=1;
                        break;
                    }
                }else{
                    $_data = $v['data'];
                }
                $shop_data[]=array(
                    'shop_id'=> $shopId,
                    'api_url'=> $v['api'],
                    'api_data'=> json_encode($_data)
                );
            }
        }
        if ($flag_error) {
            return array('errcode'=>30012);
        }else{
            return array('errcode'=>0,'shop_id'=>$shopId,'shop_data'=>$shop_data,'user_id'=>$userId);
        }
    }
}
