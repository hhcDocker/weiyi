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
use api\APIException;
use JonnyW\PhantomJs\Client;
use app\common\utils\SN\ShortUrl;
use app\common\utils\SN\ExpenseSN;

class WeiBaoData {
	
    /**
     * 接收并处理跳转淘宝店铺、天猫店铺等链接
     * @param  string  $url  [链接]
     * @param  integer $flag [是否获取最新数据，默认否]
     * @return [type]        [description]
     */
    public function getShopData($url='',$flag=0) {
    	if (!$url) {
    		return 1;
    	}
        if (!preg_match('/http.+m.taobao.com/', $url) && !preg_match('/http.+m.tmall.com/', $url)) {

        }
        //mc 暂时
        //加上判断是否过期
        //考虑短链接情况，查表获取真实链接
        $url_info = $this->getShopShortUrlInfo($url);
        if (empty($url_info)){
            echo json_encode(array('code'=>0,'msg'=>'获取真实链接失败'));
            return;
        }
        $service_id = $url_info['id'];
        $api_info= array();
        //不获取最新数据
        if (!$flag) {
            //判断服务是否过期
            $api_info = model('ShopApi')->getShopDataByShopUrl($url);
            if (!empty($api_info)) {
                $shop_data = $api_info;
            }
        }
        if (empty($api_info)){
            //把这块剥离为服务层，私有化
            $client = Client::getInstance();
            $client->getProcedureCompiler()->clearCache();
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
            //dump( $response->getUrls());
            //dump($response->getConsole());
            $data=$response->getUrlData();
            //end
			$shopOtherData=$response->getShopOtherData();
            if (!$data) {
                echo json_encode($response->getConsole());
                return;
            }

            $shop_data=array();
            $flag_error =0;
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
                    }else{
                        $_data = $v['data'];
                    }
                    $shop_data[]=array(
                        'shop_url'=> $url,
                        'api_url'=> $v['api'],
                        'api_data'=> json_encode($_data),
                        'is_deleted'=> 0,
                        'create_time'=> time(),
                        'update_time'=> time(),
                    );
                }
            }
            if ($flag_error) {
                echo json_encode(array('code'=>0,'msg'=>'获取数据失败'));
                return;
            }else{
                //软删除之前记录
                model('ShopApi')->softDeleteShopDataByShopUrl($url);
                foreach ($shop_data as $k1 => $v1) {
                    $has_add = model('ShopApi')->saveShopData($v1['shop_url'],$v1['api_url'],$v1['api_data']);
                    if (!$has_add) {
                        //mc 记录日志或者发送警报，不推送给前端
                    }
                }
            }
        }
		return $this->fetch('tm_shop',array('data' => json_encode($shop_data)));
    }
}