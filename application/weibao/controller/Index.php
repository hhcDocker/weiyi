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
use Symfony\Component\Config\Definition\Exception\Exception;
use think\Controller;
use think\Request;
use think\Db;

class Index extends Controller
{
	public function getTBCmdPicWordDetail()
    {
    	$itemId=$_GET['itemId'];
    	$cmd_url='https://h5.m.taobao.com/awp/core/detail.htm?id='.$itemId;
    	$client = Client::getInstance();
        $client->getEngine()->setPath(config('PhantomjsPath'));
        /**
         * @see JonnyW\PhantomJs\Http\Request
         **/
        $request = $client->getMessageFactory()->createRequest($cmd_url, 'GET');
        //$request->setDelay(1000);
        /**
         * @see JonnyW\PhantomJs\Http\Response
         **/
        $response = $client->getMessageFactory()->createResponse();

        // Send the request
        $client->send($request, $response);
        /*dump($response->getUrls());
        dump($response->getConsole());*/

        $data=$response->getUrlData();
		$value=preg_replace('/^mtopjsonp\d\(/','', $data[0]);
        $value= trim($value,')');
		$value=json_decode($value,true);
		echo json_encode($value);
	}
    /**
     * 接收并处理淘宝、天猫等链接,获取并显示数据
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
      		    //$arr['price']=$_GET['price'];
				//$arr['sold']=$_GET['sold'];
				//$arr['area']=$_GET['area'];
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
						$arr['dataOther'][]=iconv("GB2312//IGNORE","UTF-8",$script->innertext);

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

				$arr['delPrice']=$html->find('section#s-price',0)->find('span.mui-price',0)->find('span.mui-price-integer',0)->innertext;
           		return $this->fetch('tm_commodity_detail',array('data' => json_encode($arr)));
        	}else{
//      		$url='https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22'.$_GET["itemId"].'%22%7D';
//      		$data=file_get_contents($url);
//              $data=json_decode($data);
//              echo "<pre>";
//              var_dump($data);
//              echo "<pre/>";
//              exit;
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
     * 测试时保留，最后要删掉，微信端只能通过短链接查看效果
     * @param  string  $url  [链接]
     * @param  integer $flag [是否获取最新数据，默认否]
     * @return [type]        [description]
     */
    public function getShopData() {
        $url = input('param.url') ? input('param.url'):'';
		session('shopUrl',$url);
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
            $request->setTimeout(10000);
            $response = $client->getMessageFactory()->createResponse();

            // Send the request 
            /*
			 *大概耗费10秒时间
			 * */
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
            /*if ($flag_error) {
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
            }*/
        }

		//return $this->fetch('tb_shop',array('data' => json_encode($shop_data)));
		return $this->fetch('tm_shop',array('data' => json_encode($shop_data)));
    }

    /**
     *
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    private function getShopShortUrlInfo($url)
    {
        //mc
        session('manager_id',1);
        echo "error";
        exit;

        // $has_info = model('')
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



    /*********************************************************************正式使用******************************************************************************/

    /**
     * 通过短链接访问，短链接长度为6个字符
     * @return [type] [description]
     */
    public function getShopDataByShortUrl()
    {
        $str_url = input('param.str_url') ? input('param.str_url') : '';
        if (!$str_url) {
            echo "链接不存在";
            exit;
        }
        //查询是否存在且未过期
        $service_info = model('ShopServices')->getServicesByUrlStr($str_url);
        if (empty($service_info)) {
            echo "链接不存在";
            exit;
        }

        if ($service_info['service_start_time']<=time() && $service_info['service_end_time']>=time()) {//服务未过期
            //获取接口
            $shop_data = model('ShopApi')->getShopDataByShopId($service_info['shop_id']);
            //获取链接
            $shop_info = model('AliShops')->getShopInfoById($service_info['shop_id']);
            if (empty($shop_info) || !isset($shop_info['shop_url'])) {
                echo "链接不存在";
                exit;
            }
            session('shopUrl',$shop_info['shop_url']);
            if (empty($shop_data)) {
                $wei_bao = new WeiBaoData();
                $res = $wei_bao->getShopDataByUrl($shop_info['shop_url']);

                if ($res['errcode']) {
                    var_dump($res);
                    exit;
                }

                //添加店铺数据记录
                $shop_data = $res['shop_data'];
                $flag_error=0;
                foreach ($shop_data as $k1 => $v1) {
                    $has_add = model('ShopApi')->saveShopData($service_info['shop_id'],$v1['api_url'],$v1['api_data']);
                    if (!$has_add) {
                        $flag_error=1;
                        break;
                    }
                }
                if ($flag_error) {
                    echo "获取数据失败";
                    exit;
                }
            }

            $is_tmall =0;
            foreach ($shop_data as $k => $v) {
                if (strpos($v['api_url'],'taobao')) {
                    $is_tmall =0;
                    break;
                }elseif (strpos($v['api_url'],'tmall')) {
                    $is_tmall =1;
                    break;
                }
            }
            if ($is_tmall) {
                return $this->fetch('tm_shop',array('data' => json_encode($shop_data)));
            }else{
                return $this->fetch('tb_shop',array('data' => json_encode($shop_data)));
            }
        }else {//已过期
            echo "该服务不在服务时间范围内，请联系管理员";
            exit;
        }
    }

    /**
     * [getGoodsDetail description]
     * @return [type] [description]
     */
    public function getGoodsDetail()
    {
        $isTm = input('param.isTm') ? input('param.isTm') : '';
        $item_id = input('param.item_id') ? input('param.item_id') : '';
        if (!$item_id) {
            echo "item_id参数错误不存在";
            exit;
        }

        vendor('simple_html_dom.simple_html_dom');
        set_time_limit(0);
        header("Connection:Keep-Alive");
        header("Proxy-Connection:Keep-Alive");
        $arr = array();
        if ($this->request->method() == 'GET')
        {
            if($isTm){
                //$arr['price']=$_GET['price'];
                //$arr['sold']=$_GET['sold'];
                //$arr['area']=$_GET['area'];
                $arr['isTm']=$isTm;
                $url='https://detail.m.tmall.com/item.htm?abtest=_AB-LR90-PR90&pos=1&abbucket=_AB-M90_B17&acm=03080.1003.1.1287876&id='.$item_id.'&scm=1007.12913.42100.100200300000000';
                $html = file_get_html($url);
                try{
                    $shop_href_str = $html->find('div#s-actionbar',0)->find('div.toshop',0)->find('a',0)->href;
                }catch (Exception $e) {
                    echo "获取数据失败";
                    exit;
                }
                //店铺链接
                $shop_url=trim(iconv("GB2312//IGNORE","UTF-8",$shop_href_str));
                //先获取shopid,验证服务是否存在，是否过期
                $shop_id = 0;
                try{
                    //得到dataDetail对象
                    foreach($html->find('script') as $key => $script){
                    //if($key==6){
                    //  $arr['dataDetail']=iconv("GB2312//IGNORE","UTF-8",$script->innertext);
                    //}else{
                            $v = iconv("GB2312//IGNORE","UTF-8",$script->innertext);
                            $arr['dataOther'][]=$v;
                            if (strpos($v,'_DATA_Detail')!==false){
                                preg_match('/(?:"rstShopId":)\d+/',$v,$id_str);// echo $a;"rstShopId":60291124
                                $id_str = $id_str[0];
                                $shop_id = str_replace('"rstShopId":','',$id_str);
                            }
                    //}
                    };
                }catch (Exception $e){
                    echo "获取数据失败";
                    exit;
                }
                if (!$shop_url || !$shop_id) {
                    echo "获取数据失败";
                    exit;
                }
                //查询服务
                $service_info = model('ShopServices')->getServicesByAliShopId($shop_id);
                if (empty($service_info)) {
                    echo "该店铺无购买服务，请到微跳上购买";
                    exit;
                }else{
                    $is_time_out =1;
                    foreach ($service_info as $k => $v) {
                        if ($v['service_end_time'] > time()) {
                            $is_time_out =0;
                            $arr['shortUrl'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$v['transformed_url'];
                            break;
                        }
                    }
                    if ($is_time_out) {
                        echo "该店铺所购买服务已过期，请到微跳上续费";
                        exit;
                    }
                }

                session('shopUrl',$shop_url);
                $arr['shopUrl'] = $shop_url;
                $assessFlag='https://rate.tmall.com/listTagClouds.htm?itemId='.$item_id;
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
                    //得到店铺名
                    $arr['shopName']=iconv("GB2312//IGNORE","UTF-8",$html->find('section#s-shop',0)->find('div.shop-t',0)->innertext);

                    $arr['delPrice']=$html->find('section#s-price',0)->find('span.mui-price',0)->find('span.mui-price-integer',0)->innertext;
                }catch(Exception  $e){
                    echo "获取数据失败";
                    exit;
                }
                return $this->fetch('tm_commodity_detail',array('data' => json_encode($arr)));
            }else{
                $url='https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22'.$item_id.'%22%7D';
                $data=file_get_contents($url);
                $data=json_decode($data,true);

                if(!$data || $data['ret'][0] !="SUCCESS::调用成功" || !isset($data['data']['seller'])){
                    echo "获取数据失败";
                    exit;
                }

                //获取shopid,验证服务是否存在，是否过期
                $seller_info = $data['data']['seller'];
                $shop_id = intval($seller_info['shopId']);
                // $user_id = intval($seller_info['userId']);
                // $shop_url ='https://shop.m.taobao.com/shop/shop_index.htm?user_id='.$user_id;

                if (!$shop_id) {
                    echo "获取数据失败";
                    exit;
                }
                $shortUrl = '';
                //查询服务
                $service_info = model('ShopServices')->getServicesByAliShopId($shop_id);
                if (empty($service_info)) {
                    echo "该店铺无购买服务，请到微跳上购买";
                    exit;
                }else{
                    $is_time_out =1;
                    foreach ($service_info as $k => $v) {
                        if ($v['service_end_time'] > time()) {
                            $is_time_out =0;
                            $shortUrl = 'http://'.$_SERVER['HTTP_HOST'].'/'.$v['transformed_url'];
                            break;
                        }
                    }
                    if ($is_time_out) {
                        echo "该店铺所购买服务已过期，请到微跳上续费";
                        exit;
                    }
                }
                return $this->fetch('tb_commodity_detail',array('data' => $item_id,'shortUrl'=>$shortUrl));
            }

        }
        else
        {
            $result = [
                'code' => 400,
                'msg'  => '请求参数错误！',
                'data' => '{}',
            ];
            echo json($result, 400);
            exit;
        }
    }

    /**
     * [getTbGoodsDescription description]
     * @return [type] [description]
     */
    public function getTbGoodsDescription()
    {
        $itemId=$_GET['itemId'];
        if (!$itemId) {
            echo '参数错误';
            exit;
        }
        //查询是否有数据
        $des_info = model('AliGoodsDes')->getDesDataByItemId($itemId);
        $description_cache_time = config('description_cache_day') * 24 *60 * 60;
        if (!empty($des_info) && $des_info['update_time'] - time() < $description_cache_time) {
            echo $des_info['data'];
        }else{
            //获取数据
            $cmd_url='https://h5.m.taobao.com/awp/core/detail.htm?id='.$itemId;
            $client = Client::getInstance();
            $client->getEngine()->setPath(config('PhantomjsPath'));
            /**
             * @see JonnyW\PhantomJs\Http\Request
             **/
            $request = $client->getMessageFactory()->createRequest($cmd_url, 'GET');
            //$request->setDelay(1000);
            /**
             * @see JonnyW\PhantomJs\Http\Response
             **/
            $response = $client->getMessageFactory()->createResponse();

            // Send the request
            $client->send($request, $response);
           /* $urls = $response->getUrls();
            var_dump($urls);
            $console = $response->getConsole();
            var_dump($console);*/

            $data=$response->getUrlData();
            if (empty($data)){
                echo "获取数据失败";
            }else{
                $des_data = preg_replace('/^mtopjsonp\d+\(([\s\S]+)\)/','$1', $data[0]);

                if (empty($des_info)) {
                    $has_add = model('AliGoodsDes')->addShopData($itemId,$des_data);
                }else{
                    $has_update = model('AliGoodsDes')->updateDesDataById($des_info['id'],$des_data);
                }
                echo $des_data;
            }
        }
    }
    public function testHeader()
    {
        $url  = $_SERVER['HTTP_HOST'] . 'frontend/html/service.html';
        header($url);
        exit;
    }
}
