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
		$client->getEngine()->addOption('--ssl-protocol=any')->addOption('--web-security=no');
        $client->getProcedureCompiler()->clearCache();
        $client->getEngine()->setPath(config('PhantomjsPath'));
        $client->isLazy();
        /**
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($url, 'GET');
        //$request->setDelay(20000);
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
		$client->getEngine()->addOption('--ssl-protocol=any')->addOption('--web-security=no');
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

    /**
     * 获取天猫商品详情
     * @param  string  $item_id [商品id]
     * @return [type]           [description]
     */
    public function getTmGoodsDetail($item_id=0)
    {
        if (!$item_id ) {
            return array('errcode'=>10001);
        }

        $url='https://detail.m.tmall.com/item.htm?abtest=_AB-LR90-PR90&pos=1&abbucket=_AB-M90_B17&acm=03080.1003.1.1287876&id='.$item_id.'&scm=1007.12913.42100.100200300000000';
        vendor('simple_html_dom.simple_html_dom');
        header("Connection:Keep-Alive");
        header("Proxy-Connection:Keep-Alive");
        $add_data = array();
        $flag_error=0;
        try{
            $html = file_get_html($url);
        }catch (\Exception $e){
            return array('errcode'=>30009);
        }
        //店铺链接,shopid
        try{
            if($html->find('div#s-actionbar',0)){
                $shop_href_str = $html->find('div#s-actionbar',0)->find('div.toshop',0)->find('a',0)->href;
            }else{
                return array('errcode'=>30009);
            }
            
            $shop_url='https:'.trim(iconv("GB2312//IGNORE","UTF-8",$shop_href_str));
            $add_data['shop_url'] = $shop_url;
            //得到dataDetail对象
            //获取shopid
            foreach($html->find('script') as $key => $script){
                $v = iconv("GB2312//IGNORE","UTF-8",$script->innertext);
                if (strpos($v,'_DATA_Detail')!==false){
                    preg_match('/(?:"rstShopId":)\d+/',$v,$id_str);// echo $a;"rstShopId":60291124
                    $id_str = $id_str[0];
                    $shop_id = str_replace('"rstShopId":','',$id_str);
                }
                if (strpos($v, '_DATA_Mdskip')!==false && strpos($v,'window.location.href')) {
                    $flag_error=1;//反爬
                    break;
                }
                $arr['dataOther'][]=$v;
            };
        }catch (\Exception $e){
            return array('errcode'=>30009);
        }
        $add_data['data_other'] = json_encode($arr['dataOther']);
        $add_data['shop_id'] = $shop_id;
        if (!$shop_url || !$shop_id || $flag_error) {
            return array('errcode'=>30009);
        }

        //查询服务
        // $service_info = model('ShopServices')->getServicesByAliShopId($shop_id);
        /*if (empty($service_info)) {
            echo "该店铺无购买服务，请到微驿上购买";
            exit;
        }else{
            $is_time_out =1;
            foreach ($service_info as $k => $v) {
                if (($v['service_start_time']<=time() && $v['service_end_time']>=time()) || ($v['experience_start_time']<=time() && $v['experience_end_time']>=time()) ) {
                    $is_time_out =0;
                    $arr['shortUrl'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$v['transformed_url'];
                    session('shopId',$v['shop_id']);
                    break;
                }
            }
            if ($is_time_out) {
                echo "该店铺所购买服务已过期，请到微驿上续费";
                exit;
            }
        }*/

        $arr['shopUrl'] = $shop_url;
        $assessFlag='https://rate.tmall.com/listTagClouds.htm?itemId='.$item_id;
        $assessFlag='{'.file_get_contents($assessFlag).'}';
        $arr['assessFlag'] = iconv("GB2312//IGNORE","UTF-8",$assessFlag);

        $add_data['assess_flag'] = $arr['assessFlag'];
        //得到商品图片url
        foreach($html->find('section#s-showcase') as $pic_contain)
        {

            foreach($pic_contain->find('div.scroller') as $itembox)
            {
                $imgflag=1;
                foreach ($itembox ->find('div.itbox') as $item) {
                    if($imgflag==1){
                        $arr['imgUrl'][]=$item->find('img',0)->src;
                    }else{
                        $arr['imgUrl'][]=$item->find('img',0)->attr['data-src'];
                    }
                    $imgflag++;
                }
            }
        };

        $add_data['img_url'] = json_encode($arr['imgUrl']);
        //得到店铺score
        foreach ($html ->find('ul.score') as  $score) {
            foreach($score->find('li') as $key => $li){
                $arr['score'][$key]['className']=$li->find('b',0)->class;
                $arr['score'][$key]['text']=$li->find('b',0)->innertext;
            }
        }

        $add_data['score'] = json_encode($arr['score']);
        //得到商品信息
        try{
            if($html ->find('div.mdv-standardItemProps',0)){
                $string=$html ->find('div.mdv-standardItemProps',0)->attr['mdv-cfg'];
                if($string){
                    $arr['cd_parameter']=mb_convert_encoding($string, 'utf-8', 'gbk');
                }
            }else{
                $arr['cd_parameter']="";
            }
            $add_data['cd_parameter'] =$arr['cd_parameter'];

            //得到店铺名
            $arr['shopName']=iconv("GB2312//IGNORE","UTF-8",$html->find('section#s-shop',0)->find('div.shop-t',0)->innertext);
            $add_data['shop_name'] =$arr['shopName'];

            $arr['delPrice']=$html->find('section#s-price',0)->find('span.mui-price',0)->find('span.mui-price-integer',0)->innertext;
            $add_data['del_price'] =$arr['delPrice'];
            return array('errcode'=>0,'data'=>$add_data);
        }catch(Exception  $e){
            return array('errcode'=>30009);
        }
    }
}
