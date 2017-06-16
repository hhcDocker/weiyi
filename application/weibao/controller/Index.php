<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Index.php
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-06-15 11:00 
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\weibao\controller;
use app\common\service\WeiBaoData;
use app\common\utils\SN\ShortUrl;
use app\common\utils\SN\ExpenseSN;
use JonnyW\PhantomJs\Client;
use think\Controller;
use think\Request;
use think\Db;

class Index extends Controller
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
                //if($key==6){
                //	$arr['dataDetail']=iconv("GB2312//IGNORE","UTF-8",$script->innertext);;
                //}else{
						$arr['dataOther'][]=iconv("GB2312//IGNORE","UTF-8",$script->innertext);;
                //}
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
				//mc 加上校验
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

	/*
	 * 搜索商品
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
     * 之后考虑短链接情况
     * @param  string  $url  [链接]
     * @param  integer $flag [是否获取最新数据，默认否]
     * @return [type]        [description]
     */
    public function getShopData() {
        $url = input('param.url') ? input('param.url'):'';
        $flag = input('param.flag') ? input('param.flag'):0;
        $checkUrl = $this->checkUrl($url);
        if (!$checkUrl['code']) {
            echo json_encode($checkUrl);
            return;
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
            //dump( $response->getUrls());
            //dump($response->getConsole());
            $data=$response->getUrlData();
            //end

            if (!$data) {
                echo json_encode($response->getConsole());
                return;
            }

            $shop_data=array();
            $flag_error =0;
            //校验是否全部获取到
            foreach ($data as $k => $v) {
                $v = preg_replace('/^mtopjsonp\d\(([\s\S]+)\)/','$1', $v);
                $v = json_decode($v,true);
                if ($v['ret'][0]!="SUCCESS::调用成功"){
                    $flag_error=1;
                    break;
                }else{
                    if ($k==0){ //第一个接口
                        $data=$v['data']['data'];
                        $view=$v['data']['view'];
                    }else{
                        $data = $v['data'];
                        $view = array();
                    }
                    $shop_data[]=array(
                        'shop_url'=> $url,
                        'api_url'=> $v['api'],
                        'api_data'=> json_encode($data),
                        'api_view'=> json_encode($view),
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
                $has_add = model('ShopApi')->batchAddShopData($shop_data);
                if (!$has_add) {
                    //mc 记录日志或者发送警报，不推送给前端
                }
            }
        }

		return $this->fetch('tm_shop',array('data' => json_encode($shop_data)));
    }

    private function getShopShortUrlInfo($url)
    {
        //mc
        session('manager_id',1);
        $service_info = model('ShopServices')->getServicesByShopUrl($url,session('manager_id'));
        //没有服务则表示体验
        if (empty($service_info)) {
            $experience_time = config('ExperienceTime');
            $time_start = time();
            $time_end = strtotime("+".$experience_time." day");
            $o = new ShortUrl($url);
            $shop_url_str = $o->getSN();
            $service_id = model('ShopServices')->saveServices(session('manager_id'),$url,$shop_url_str,$time_start,$time_end);
            $service_info = model('ShopServices')->getServicesByShopUrl($url,session('manager_id'));
            //添加消费记录，体验3天
            $expense_model = new ExpenseSN();
            $expense_num = $expense_model->getSN();
            $has_add = model('ExpenseRecords')->addExpense($expense_num, 0,'',$service_id,session('manager_id'),0,$time_start,$time_end,1);
        }
        return $service_info;
    }

    /**
     * 转换链接入口
     * error
     * @return [type] [description]
     */
    /* public function getDataByUrl()
    {
        $url = input('post.shop_url')?input('post.shop_url'):'';
        echo $url;
        if (!$url) { //非空检验
            // return array('code'=>0,'url'=>'','msg'=>'网址不为空');
            dump(array('code'=>0,'url'=>'','msg'=>'网址不为空'));
            return;
        }

        //mc 判断是否登录
        
        if (strstr($url,'tmall.com')) { //天猫
            if (strstr($url, 'tmall.com/shop')) { //天猫店铺
                // echo "天猫网址<br/><br/>";
                if (!strstr($url, '.m.')) { //PC转移动端
                    $url = preg_replace('/.+(\w+).tmall.com\/shop\/view_shop\.htm.+/','https://'.'$1'.'.m.tmall.com',$url);
                }
                $this->getShopData($url);
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
                $this->getShopData($url);
            }elseif (preg_match('/shop\d+\.m\.taobao/', $url)) { //淘宝店铺移动端
                $this->getShopData($url);
            }else{
                //待定
                // return array('code'=>0,'msg'=>'非淘宝店铺网址');
                dump(array('code'=>0,'msg'=>'非淘宝店铺网址'));
            }
        }else{
            // return array('code'=>0,'msg'=>'非淘宝天猫网址');
            dump(array('code'=>0,'msg'=>'非淘宝天猫网址'));
        }
    }*/

    /**
     * 首页转换网址,验证登录与否
     * 验证店铺，判断地址合法性，获取店铺地址（非店铺地址则爬取数据，得到店铺地址）
     * 查表获取该链接是否已购买服务，是否已过期
     * 如果从未购买，则生成体验记录，默认3天，生成服务记录，返回短链接
     * 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
     * 暂时先验证，之后再迁移到账号体系，index模块下，改前后分离
     * @return [type] [description]
     */
    public function getShortUrl()
    {
        $url = input('post.url')?input('post.url'):'';
        // echo $url;
        $checkUrl = $this->checkUrl($url);
        if (!$checkUrl['code']) {
            dump($checkUrl);
            return;
        }
        //mc 判断是否登录
        echo config('ExperienceTime');
        if (strstr($url,'tmall.com')) { //天猫
            if (strstr($url, 'tmall.com/shop')) { //天猫店铺
                // echo "天猫网址<br/><br/>";
                if (!strstr($url, '.m.')) { //PC转移动端
                    $url = preg_replace('/.+(\w+).tmall.com\/shop\/view_shop\.htm.+/','https://'.'$1'.'.m.tmall.com',$url);
                }
                $this->getShopShortUrlInfo($url);
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
                $this->getShopShortUrlInfo($url);
            }elseif (preg_match('/shop\d+\.m\.taobao/', $url)) { //淘宝店铺移动端
                $this->getShopShortUrlInfo($url);
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

    private function checkUrl($url='')
    {
        if (!$url) { //非空检验
            // return array('code'=>0,'url'=>'','msg'=>'网址不为空');
            return array('code'=>0,'url'=>'','msg'=>'网址不为空');
        }

        if(!preg_match('/https?:\/\/[\w.]+[\w\/]*[\w.]*[\w=&\+\%.\-\_?]*/is',$url)){
            return array('code'=>0,'url'=>'','msg'=>'网址不合法');
        }
        if (!strstr($url, 'taobao') && !strstr($url, 'tmall')) {
            return array('code'=>0,'url'=>'','msg'=>'非淘宝天猫链接');
        }
        return array('code'=>1,'url'=>$url,'msg'=>'');
    }
}
