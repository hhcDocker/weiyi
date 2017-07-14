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
use JonnyW\PhantomJs\DependencyInjection\ServiceContainer;
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
		$client->getEngine()->addOption('--web-security=no');
        $client->getProcedureCompiler()->clearCache();
        $client->getEngine()->setPath(config('PhantomjsPath'));
        $client->isLazy();
        /**
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($url, 'GET');
        $request->setDelay(20000);
        /**
         * @see JonnyW\PhantomJs\Http\Response
         **/
        //$request->setTimeout(10000);
        $response = $client->getMessageFactory()->createResponse();

        // Send the request
        $client->send($request, $response);
        // dump( $response->getUrls());
        // dump($response->getConsole());
        $data=$response->getUrlData();
        // var_dump($data);exit;
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

    /**
     * 获取商品列表
     * @param  string $url [description]
     * @return [type]      [description]
     */
	public function getShopGoodsDataByUrl($url='') {
     	if (!$url) {
     		return array('errcode'=>10001,'msg'=>'参数错误');
     	}
        if (!preg_match('/http.+m.taobao.com/', $url) && !preg_match('/http.+m.tmall.com/', $url)) {
         	return array('errcode'=>30007,'msg'=>'非淘宝天猫链接');
        }

		$location = $_SERVER['DOCUMENT_ROOT'].'/../vendor/jonnyw/php-phantomjs/src/JonnyW/PhantomJs/Resources/procedures';
		$serviceContainer = ServiceContainer::getInstance();
		$procedureLoader = $serviceContainer->get('procedure_loader_factory')->createProcedureLoader($location);
        $client = Client::getInstance();
		$client->setProcedure('my_procedure');
		$client->getProcedureLoader()->addLoader($procedureLoader);
		
		$client->getEngine()->addOption('--web-security=no');
        $client->getProcedureCompiler()->clearCache();
        $client->getEngine()->setPath(config('PhantomjsPath'));
        //$client->isLazy();
        /**
        * @see JonnyW\PhantomJs\Http\Request
        **/
        $request = $client->getMessageFactory()->createRequest($url, 'GET');
        //$request->setDelay(30000);
        /**
          * @see JonnyW\PhantomJs\Http\Response
          **/
        $request->setTimeout(10000);
        $response = $client->getMessageFactory()->createResponse();

        // Send the request
        $client->send($request, $response);
        $data=$response->getUrlData();
        if (!$data ||empty($data)) {
            return array('errcode'=>30012,'msg'=>'获取数据失败');
            //echo json_encode($response->getConsole());
            //return;
        }

        $goods_data =array();
        $total_page = 0;
        $page_size = 0;
        $total_results = 0;
        foreach ($data as $k => $v) {
            $v = preg_replace('/jsonp\((.+)\)/','\1',$v);
            $v = json_decode($v,true);
            if ($k==0) {
                $total_page = $v['total_page'];
                $page_size = $v['page_size'];
                $total_results = $v['total_results'];
            }
            $goods_data[] = array('items'=>json_encode($v['items']),'page_index'=>$v['current_page']);
        }
        if (empty($goods_data)) {
            return json_encode(array('errcode'=>30012,'msg'=>'获取数据失败'));
        }

        return array('errcode'=>0,'goods_data'=>$goods_data,'total_page'=>$total_page,'page_size'=>$page_size,'total_results'=>$total_results);
    }
}
