<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Index.php
 * | Description: 微信端获取微跳数据
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-06-15 11:00 
 * | Email: equinoxsun@purplethunder.cn
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

class Index1 extends Controller
{
    /**
     * 接收并处理淘宝、天猫等链接,获取并显示数据
     * 淘宝搜索商品结果列表：https://s.m.taobao.com/h5?q=%E5%A4%B9%E5%85%8B
     * 天猫商品详情：https://detail.m.tmall.com/item.htm?ft=t&id=544094926521
     * @return [type]
     */
    public function getGoodsDetail()
    {
        vendor('simple_html_dom.simple_html_dom');
        set_time_limit(0); 
        header("Connection:Keep-Alive");
        header("Proxy-Connection:Keep-Alive");
		$arr = array();
        if ($this->request->method() == 'GET') 
        {
        	if($_GET['isTm']){
                //$arr['price']=$_GET['price'];
                //$arr['sold']=$_GET['sold'];
                //$arr['area']=$_GET['area'];
                $wei_bao_data = new WeiBaoData();
                $arr =$wei_bao_data->processUrl(1,$_GET["itemId"],0);
				session('shopUrl',$arr['shopUrl']);
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
        $shopUrl=session('shopUrl');
        return $this->fetch('tm_shop_commodity_list',array('shopUrl'=>$shopUrl));
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
		session('shopUrl',$url);
        $flag = input('param.flag') ? input('param.flag'):1;
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

    private function getShopShortUrlInfo($url)
    {
        //mc
        session('manager_id',1);
        $service_info = model('ShopServices')->getServicesByShopUrl($url,session('manager_id'));
        //没有服务则表示体验
        if (empty($service_info)) {
            $experience_days = config('experience_days');
            $time_start = time();
            $time_end = strtotime("+".$experience_days." day");
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
