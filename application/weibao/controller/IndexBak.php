<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Index.php
 * +----------------------------------------------------------------------
 * | Created by peteyhuang at 2017-03-06 11:00 
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Updated by peteyhuang at 2016-03-06 11:33
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\weibao\controller;
use JonnyW\PhantomJs\Client;
use think\Controller;
use think\Request;
use think\Db;

class IndexBak extends Controller
{
    /**
     * 接收并处理淘宝、天猫等链接
     * 淘宝搜索商品结果列表：https://s.m.taobao.com/h5?q=%E5%A4%B9%E5%85%8B
     * 天猫商品详情：https://detail.m.tmall.com/item.htm?ft=t&id=544094926521
     * @return [type]
     */
    public function processUrl()
    {
        vendor('simple_html_dom.simple_html_dom');
        set_time_limit(0); 
        header("Connection:Keep-Alive");
        header("Proxy-Connection:Keep-Alive");
		$arr = array();
        if ($this->request->method() == 'GET') 
        {
        	if($_GET['isTm']){
        		$arr['price']=$_GET['price'];
				$arr['sold']=$_GET['sold'];
				$arr['area']=$_GET['area'];
				$arr['isTm']=$_GET['isTm'];
				$url='https://detail.m.tmall.com/item.htm?abtest=_AB-LR90-PR90&pos=1&abbucket=_AB-M90_B17&acm=03080.1003.1.1287876&id='.$_GET["itemId"].'&scm=1007.12913.42100.100200300000000';
	           	$html =file_get_html($url);
				$assessFlag='https://rate.tmall.com/listTagClouds.htm?itemId='.$_GET["itemId"];
				$assessFlag='{'.file_get_contents($assessFlag).'}';
				$arr['assessFlag'] = iconv("GB2312//IGNORE","UTF-8",$assessFlag);
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
	            
	            //得到dataDetail对象
				foreach($html->find('script') as $key => $script){
//					if($key==6){
//						$arr['dataDetail']=iconv("GB2312//IGNORE","UTF-8",$script->innertext);;
//					}else{
						$arr['dataOther'][]=iconv("GB2312//IGNORE","UTF-8",$script->innertext);;
//					}
				};
				//得到店铺score
				
				foreach ($html ->find('ul.score') as  $score) {
					foreach($score->find('li') as $key => $li){
						$arr['score'][$key]['className']=$li->find('b',0)->class;
						$arr['score'][$key]['text']=$li->find('b',0)->innertext;
					}
				}
				
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
					
				}catch(Exception  $e){
					
				}

				
				//得到店铺名
				$arr['shopName']=iconv("GB2312//IGNORE","UTF-8",$html->find('section#s-shop',0)->find('div.shop-t',0)->innertext);
				
				$arr['shopUrl']=iconv("GB2312//IGNORE","UTF-8",$html->find('div#s-actionbar',0)->find('div.toshop',0)->find('a',0)->href);
				
				$arr['delPrice']=$html->find('section#s-price',0)->find('span.mui-price',0)->find('span.mui-price-integer',0)->innertext;
           		return $this->fetch('tm_commodity_detail',array('data' => json_encode($arr)));
        	}else{
        		/*$url='https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22'.$_GET["itemId"].'%22%7D';
        		//$data=file_get_contents($url);*/
        		$data=$_GET['itemId'];
           		return $this->fetch('tb_commodity_detail',array('data' => $data ));
        	}

        }
        else
        {
            $result = [
                'code' => 400,
                'msg'  => '请求参数错误！',
                'data' => '{}',
            ];
            return json($result, 400);
        }
    }
    /**
     * 展示商品详情
     * @return [type]
     */
    public function showCommodityDetail()
    {
        if ($this->request->method() == 'POST') 
        {

        }
        else if ($this->request->method() == 'GET') {
            return $this->fetch('commodity_detail');
        }
        else
        {
            $result = [
                'code' => 400,
                'msg'  => '请求参数错误！',
                'data' => '{}',
            ];
            return json($result, 400);
        }
    }
	
	/*
	 * 搜索商品
	 * 
	 * 
	 */
	public function GetCommodityData(){
			if(isset($_POST["commodityName"])){
			
			if(!isset($_POST["sortType"])){
				$url='https://s.m.taobao.com/search?event_submit_do_new_search_auction=1&_input_charset=utf-8&topSearch=1&atype=b&searchfrom=1&action=home%3Aredirect_app_action&from=1&q='.$_POST['commodityName'].'&sst=1&n=20&buying=buyitnow&m=api4h5&abtest=22&wlsort=22&page='.$_POST['page'];
			}else{
				$url='https://s.m.taobao.com/search?q='.$_POST["commodityName"].'&search=%E6%8F%90%E4%BA%A4&tab=all&sst=1&n=20&buying=buyitnow&m=api4h5&abtest=2&wlsort=2&style=list&closeModues=nav%2Cselecthot%2Conesearch&sort='.$_POST["sortType"].'&page='.$_POST['page'];
			}
	        $html = file_get_contents($url);
			echo $html;
		}
	}
	public function searchCommodity()
    {
        return $this->fetch('search_commodity');
    }
	public function tm_shop()
    {
        return $this->fetch('tm_shop');
    }
	public function tmShopCommodityList()
    {
        return $this->fetch('tm_shop_commodity_list');
    }
	/**
     * 获取天猫评价数据
     * @return [data]
     */
	public function GetAccessData(){
		
		$url='https://rate.tmall.com/list_detail_rate.htm?itemId='.$_POST['itemID'].'&sellerId='.$_POST['sellerID'].'&order=3&append=0&content=0&currentPage='.$_POST['page'].'&pageSize=10&tagId=&_ksTS=1489809936348_512&callback=jsonp';
		$data = file_get_contents($url);
		$data = str_replace('jsonp(','',$data);
		$data = str_replace(')','',$data);
		$data=mb_convert_encoding($data, 'utf-8', 'gbk');
		echo $data;
	}
	
	/**
     * 获取天猫看了又看，其他数据
     * @return [data]
     */
	public function GetTMRecommend(){
		$url='https://aldcdn.tmall.com/recommend.htm?appId=03080&itemId='.$_GET['itemId'].'&categoryId=110206&sellerId='.$_GET['sellerId'].'&resultSize=12&_ksTS=1489806690121_444';
		$url2='https://detailskip.taobao.com/json/wap/tmallH5Desc.do?_ksTS=1489820826429_496&callback=setMdskip&itemId='.$_GET['itemId'].'&sellerId='.$_GET['sellerId'].'&isPreview=false&isg=AuDgXeh1rO_KJxDslDcORQ7Gse4-_2Aoq6eCMlrxrPuOVYB_AvmUQ7Zjn0eq&isg2=AsjIpAa3XVo%2FHf0FqOwc%2FzrXGC3acSx7';
		
		$str=file_get_contents($url);
		$str2=file_get_contents($url2);
		$str = mb_convert_encoding($str, 'utf-8', 'gbk');
		$str = str_replace('jsonp(','',$str);
		$str = str_replace(')','',$str);
		$str2 = mb_convert_encoding($str2, 'utf-8', 'gbk');
		$str2 = str_replace('setMdskip(','',$str2);
		$str2 = str_replace(')','',$str2);
		$arr[]=$str;
		$arr[]=$str2;
		echo json_encode($arr);
	}
	 
	 /**
     * 接收并处理跳转淘宝店铺、天猫店铺等链接
     * @return [shop_data]
     */
    public function getShopData() {
		$url = input('param.shopURL');
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
		$request->setTimeout(80000);
        $response = $client->getMessageFactory()->createResponse();
    
         // Send the request
        $client->send($request, $response);
        //dump( $response->getUrls());
        //dump($response->getConsole());
        $data=$response->getUrlData();
        if (!$data || empty($data)) {
            echo "获取数据失败";
            return;
        }
        $is_tmall=1;
		foreach ($data as $key => &$value) {
            $value=preg_replace('/^mtopjsonp\d+\(/','', $value);
            $value= trim($value,')');
            $value_array = json_decode($value,true);
            if (strpos($value_array['api'],'taobao')) {
                $is_tmall=0;
                break;
            }elseif (strpos($value_array['api'],'tmall')) {
                $is_tmall=1;
                break;
            }
		}
        if ($is_tmall) {
            return $this->fetch('tm_shop',array('data' => json_encode($data)));
        }else{
            return $this->fetch('tb_shop',array('data' => json_encode($data)));
        }

    }

	/**
     * 通过天猫店铺URL获取店铺首页数据
     * @return [shop_data]
     */
	public function getTMShopData($url='') {
        // $url = 'https://xuanzhimengklm.m.tmall.com/';
        $client = Client::getInstance();
         $client->getProcedureCompiler()->clearCache();
        $client->getEngine()->setPath($_SERVER['DOCUMENT_ROOT'].'/../vendor/bin/phantomjs.exe');
        $client->isLazy();
        /** 
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($url, 'GET');
        //$request->setDelay(1000);
        /** 
         * @see JonnyW\PhantomJs\Http\Response 
         **/
		$request->setTimeout(80000);
        $response = $client->getMessageFactory()->createResponse();
    
         // Send the request
        $client->send($request, $response);
        $data =$response->getUrlData();
        if (!$data) {
            echo "获取数据失败";
            return;
        }
        $res = $this->saveShopData($data);
        var_dump($res);
        exit;
    }

	/**
     * 通过淘宝店铺URL获取店铺首页数据
     * @return [shop_data]
     */
	public function getTBShopData($url='') {
        // $url = 'https://shop.m.taobao.com/shop/shop_index.htm?item_id=521783759898&shopId=129896371';
        $client = Client::getInstance();
        $client->getProcedureCompiler()->clearCache();
        $client->getEngine()->setPath($_SERVER['DOCUMENT_ROOT'].'/../vendor/bin/phantomjs.exe');
        $client->isLazy();
        /** 
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($url, 'GET');
        //$request->setDelay(1000);
        /** 
         * @see JonnyW\PhantomJs\Http\Response 
         **/
		$request->setTimeout(80000);
        $response = $client->getMessageFactory()->createResponse();
    
         // Send the request
        $client->send($request, $response);
        //dump($response->getUrls());
        //dump($response->getConsole());
        $data =$response->getUrlData();
        if (!$data) {
            echo "获取数据失败";
            return;
        }
        $res = $this->saveShopData($data);
        var_dump($res);
        exit;
    }

    public function getTMShopDataByUrl()
    {
        if ($this->request->method() == 'POST')
        {
            $shop_url = input('post.shop_url')?input('post.shop_url'):'';
            
            /*$is_avaiable = $this->testUrl($shop_url);

            if($is_avaiable['code']){
                echo $is_avaiable['url'];
                $shop_url = $is_avaiable['url'];
            }*/
            $arr = $this->getApiDateByUrl($shop_url);
            exit;
        }else{
            return $this->fetch('test_shop');
        }
    }

    private function getApiDateByUrl($shop_url='')
    {
        
        /*$is_avaiable = $this->testUrl($shop_url);
        if($is_avaiable['code']){
            echo $is_avaiable['url'];
            $shop_url = $is_avaiable['url'];
        }*/
        echo $shop_url;
        $client = Client::getInstance();
        $client->getProcedureCompiler()->clearCache();
        $client->getEngine()->setPath($_SERVER['DOCUMENT_ROOT'].'/../vendor/bin/phantomjs.exe');
        $client->isLazy();
        /**
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($shop_url, 'GET');
        //$request->setDelay(1000);
        /**
         * @see JonnyW\PhantomJs\Http\Response
         **/
        $request->setTimeout(80000);
        $response = $client->getMessageFactory()->createResponse();

        // Send the request
        $client->send($request, $response);
        dump($response->getUrls());
        dump($response->getConsole());

        $data=$response->getUrlData();
        $return_arr = array();
        if (!empty($data)){
            foreach ($data as $key => &$value) {
                $value=preg_replace('/^mtopjsonp\d\(/','', $value);
                $value= trim($value,')');
                echo mb_strlen($value).'</br></br>';
                $value_array = json_decode($value,true);
                var_dump($value_array);
            }
        }
        return $return_arr;
    }

    private function getApiData($shop_url='')
    {
        
        /*$is_avaiable = $this->testUrl($shop_url);
        if($is_avaiable['code']){
            echo $is_avaiable['url'];
            $shop_url = $is_avaiable['url'];
        }*/
        echo $shop_url.'<br/>';
        $client = Client::getInstance();
        $client->getProcedureCompiler()->clearCache();
        $client->getEngine()->setPath($_SERVER['DOCUMENT_ROOT'].'/../vendor/bin/phantomjs.exe');
        $client->isLazy();
        /**
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($shop_url, 'GET');
        //$request->setDelay(1000);
        /**
         * @see JonnyW\PhantomJs\Http\Response
         **/
        $request->setTimeout(80000);
        $response = $client->getMessageFactory()->createResponse();

        // Send the request
        $client->send($request, $response);
        /*dump($response->getUrls());
        dump($response->getConsole());*/

        $data=$response->getUrlData();
        dump($data);
        return $data;
    }

    public function showApiFirstData()
    {
        $shop_url = input('post.shop_url')?input('post.shop_url'):'';
        
        /*$is_avaiable = $this->testUrl($shop_url);
        if($is_avaiable['code']){
            echo $is_avaiable['url'];
            $shop_url = $is_avaiable['url'];
        }*/
        $data = $this->getApiData($shop_url);
        if (!empty($data)){
            echo($data[0]);
            $value_array = json_decode($data[0],true);
            print_r(array_keys($value_array));
            print_r($value_array);
        }else{
            echo "error";
        }
    }

    public function showApiUrl()
    {
        $shop_url = input('post.shop_url')?input('post.shop_url'):'';
        
        $is_avaiable = $this->testUrl($shop_url);
        if($is_avaiable['code']){
            echo $is_avaiable['url'].'<br/>';
        }
        $data = $this->getApiData($shop_url);
        if (!empty($data)){
            foreach ($data as $key => &$value) {
                $value=preg_replace('/^mtopjsonp\d\(/','', $value);
                $value= trim($value,')');
                $value_array = json_decode($value,true);
                echo($value_array['api']);
                echo '<br/><br/>';
            }
        }else{
            echo "error";
        }
    }

    public function showApiEveryData()
    {
        $shop_url = input('post.shop_url')?input('post.shop_url'):'';
        
        /*$is_avaiable = $this->testUrl($shop_url);
        if($is_avaiable['code']){
            echo $is_avaiable['url'];
            $shop_url = $is_avaiable['url'];
        }*/
        $data = $this->getApiData($shop_url);
        if (!empty($data)){
            foreach ($data as $key => &$value) {
                $value=preg_replace('/^mtopjsonp\d\(/','', $value);
                $value= trim($value,')');
                $value_array = json_decode($value,true);
                var_dump($value_array['data']);
                echo '<br/><br/>';
            }
        }else{
            echo "error";
        }
    }

    public function getDataByUrl()
    {
        $url = input('post.shop_url')?input('post.shop_url'):'';
        echo $url;
        if (!$url) { //非空检验
            // return array('code'=>0,'url'=>'','msg'=>'网址不为空');
            dump(array('code'=>0,'url'=>'','msg'=>'网址不为空'));
            return;
        }
        if (strstr($url,'tmall.com')) { //天猫
            if (strstr($url, 'tmall.com/shop')) { //天猫店铺
                // echo "天猫网址<br/><br/>";
                if (!strstr($url, '.m.')) { //PC转移动端
                    $url = preg_replace('/.+(\w+).tmall.com\/shop\/view_shop\.htm.+/','https://'.'$1'.'.m.tmall.com',$url);
                }
                $this->getTMShopData($url);
                dump(array('code'=>1,'url'=>$url));
                // return array('code'=>1,'url'=>$url);
            }else{
                //待定
                // return array('code'=>0,'msg'=>'非天猫店铺网址');
                dump(array('code'=>0,'msg'=>'非天猫店铺网址'));
            }
        }elseif (strstr($url, 'taobao')) { //淘宝
            if (preg_match('/shop\d+\.taobao/', $url)){ //淘宝店铺pc端
                $url = str_replace('taobao.com', 'm.taobao.com', $url);
                $this->getTBShopData($url);
            }elseif (preg_match('/shop\d+\.m\.taobao/', $url)) { //淘宝店铺移动端
                $this->getTBShopData($url);
            }else{
                //待定
                // return array('code'=>0,'msg'=>'非淘宝店铺网址');
                dump(array('code'=>0,'msg'=>'非淘宝店铺网址'));
            }
        }else{
            // return array('code'=>0,'msg'=>'非淘宝天猫网址');
            dump(array('code'=>0,'msg'=>'非淘宝天猫网址'));
        }
    }

    private function saveShopData($data=array()){
        dump($data);
        $err_msg = '';
        if (empty($data)) {
            return array('code'=>0,'msg'=>'无数据');
        }
        foreach ($data as $k => $v) {
            $v = preg_replace('/^mtopjsonp\d\(([\s\S]+)\)/','$1', $v);
            $v = json_decode($v,true);
            var_dump($v);
            if ($v['ret'][0]=="SUCCESS::调用成功"){
                if ($k==0){ //第一个接口
                    $data=$v['data']['data'];
                    $view=$v['data']['view'];
                }else{
                    $data = $v['data'];
                    $view = array();
                }

                $has_add = model('ShopApi')->saveShopData(1,$v['api'],json_encode($data),json_encode($view));
                if (!$has_add) {
                    $err_msg .='添加数据失败；';
                }
            }else{
                $err_msg .='获取数据失败；';
            }
        }
        if ($err_msg) {
            return array('code'=>0,'msg'=>$err_msg);
        }else{
            return array('code'=>1,'msg'=>'获取数据成功');
        }
    }

    private function getServiceInfo($url)
    {
        $service_info = model('Services')->getServicesByShopUrl($url);
        if (empty($service_info)) {
            $o = new ShortUrl();
            $shop_url_str = $o->getShortUrl();
            $service_id = model('Services')->saveServices(session('manager_id'),$url,$shop_url_str,time(),strtotime("+3 day"));
            $service_info = model('Services')->getServicesByShopUrl($service_id);
        }

        return $service_info;
    }
}
