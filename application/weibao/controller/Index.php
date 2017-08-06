<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Index.php
 * | Description: 微信端获取微驿数据
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
    /**
     * 微驿首页
     * @return [type] [description]
     */
    public function index()
    {
        header("Location:/frontend/html/index.html");
    }

	/**
     * 商品搜索页
     * @return [type] [description]
     */
	public function weibaoPersonCenter()
    {
        return $this->fetch('weibao_person_center');
    }
	public function weibaoMyFav()
    {
        return $this->fetch('weibao_fav_cmd_shop');
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

    /**
     * 商品搜索页
     * @return [type] [description]
     */
	public function searchCommodity()
    {
        return $this->fetch('search_commodity');
    }

    /**
     * 天猫店铺商品列表
     * @return [type] [description]
     */
	public function tmShopCommodityList()
    {
        if($this->request->method() == 'POST'){
            $page_index = input('post.page_index') ? intval(input('post.page_index')) : 1;

            //session存在则表示服务时间范围内，不再做检测
            $shopId=session('shopId');
            if (!$shopId) {
                return json_encode(array('errcode'=>1,'msg'=>'参数错误','data'=>array()));
            }

            if ($page_index<1 || $page_index>3) {
                return json_encode(array('errcode'=>3,'msg'=>'页码错误','data'=>array()));
            }

            //获取店铺商品统计信息
            $shop_info = model('AliShops')->getShopInfoById($shopId);
            if (empty($shop_info)) {
                return json_encode(array('errcode'=>2,'msg'=>'该店铺未转换过链接','data'=>array()));
            }
            if (is_null($shop_info['total_page']) || is_null($shop_info['total_results'])) { //表示没有获取过商品列表页
                $wei_bao = new WeiBaoData();
                $res = $wei_bao->getShopGoodsDataByUrl($shop_info['shop_url']);
                if ($res['errcode']) {
                    return json_encode($res);
                }else{
                    $total_page = $res['total_page'];
                    $page_size = $res['page_size'];
                    $total_results = $res['total_results'];
                    $goods_data = $res['goods_data'];
                    try{
                        //更新店铺数据
                        $has_update = model('AliShops')->updateShopById($shopId,$total_page,$page_size,$total_results);
                        //存储店铺商品信息
                        $has_add = model('AliShopGoodsList')->batchAddGoodsListData($shopId,$goods_data);
                    }catch(Exception $e){
                        return json_encode(array('errcode'=>2,'msg'=>'操作数据失败'));
                    }
                    if (array_key_exists($page_index-1, $goods_data)) {
                        return json_encode(array('errcode'=>0,'data'=>$goods_data[$page_index-1]['items']));
                    }else{
                        return json_encode(array('errcode'=>3,'msg'=>'页码错误','data'=>array()));
                    }
                }
            }else{
                if ($page_index>$shop_info['total_page']) {
                    return json_encode(array('errcode'=>3,'msg'=>'页码错误','data'=>array()));
                }else{
                    //从表中获取对应页数据
                    $data = model('AliShopGoodsList')->getGoodsListByShopId($shopId,$page_index);
                    return json_encode(array('errcode'=>0,'data'=>$data));
                }
            }
        }else{
            $flag_session=1;
            $shopId=session('shopId');
            if (!$shopId) {
                $flag_session=0;
            }
            return $this->fetch('tm_shop_commodity_list',array('flag_session'=>$flag_session));
        }
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
     * 通过短链接访问，短链接长度为6个字符
     * @return [type] [description]
     */
    public function getShopDataByShortUrl()
    {
        $str_url = input('param.str_url') ? input('param.str_url') : '';
        if (!$str_url) {
            return $this->fetch('service_expired',array('err_msg' => "链接不存在"));
        }
        //查询是否存在且未过期
        $service_info = model('ShopServices')->getServicesByUrlStr($str_url);
        if (empty($service_info)) {
            return $this->fetch('service_expired',array('err_msg' => "链接不存在"));
        }

        if (($service_info['service_start_time']<=time() && $service_info['service_end_time']>=time()) || ($service_info['experience_start_time']<=time() && $service_info['experience_end_time']>=time()) ) {//服务未过期
            //获取接口
            $shop_data = model('ShopApi')->getShopDataByShopId($service_info['shop_id']);
            //获取链接
            $shop_info = model('AliShops')->getShopInfoById($service_info['shop_id']);
            if (empty($shop_info) || !isset($shop_info['shop_url'])) {
                return $this->fetch('service_expired',array('err_msg' => "链接不存在"));
            }

            session('shopId',$service_info['shop_id']);
            if (!$this->is_weixin()) {
                header('Location:'.$shop_info['shop_url']);
                exit;
            }
            if (empty($shop_data)) {
                $wei_bao = new WeiBaoData();
                $res = $wei_bao->getShopDataByUrl($shop_info['shop_url']);

                if ($res['errcode']) {
                    return $this->fetch('service_expired',array('err_msg' => "获取数据失败"));
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
                    return $this->fetch('service_expired',array('err_msg' => "获取数据失败"));
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
            return $this->fetch('service_expired',array('err_msg' => "该店铺不在服务时间范围内"));
        }
    }

    /**
     * 商品详情
     * @return [type] [description]
     */
    public function getGoodsDetail()
    {
        $isTm = input('param.isTm') ? input('param.isTm') : '';
        $item_id = input('param.item_id') ? input('param.item_id') : '';

        if (!$this->is_weixin()) {
            if ($isTm) {
                header('Location:https://detail.m.tmall.com/item.htm?abtest=_AB-LR90-PR90&pos=1&abbucket=_AB-M90_B17&acm=03080.1003.1.1287876&id='.$item_id.'&scm=1007.12913.42100.100200300000000');
                exit;
            }else{
                header('Location:https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22'.$item_id.'%22%7D');
                exit;
            }
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

                if (!$item_id) {
                    return $this->fetch('service_expired',array('err_msg' => "item_id参数错误不存在"));
                }
                $item_info = model('AliTmGoodsDetail')->getGoodsDetailByItemId($item_id);

                $tm_detail_cache_day = config('tm_detail_cache_day') * 24 *60 * 60;
                if (!empty($item_info) && $item_info['update_time'] - time() < $tm_detail_cache_day) {
                    $shop_id =  $item_info['shop_id'];

                    $arr = array(
                        'shop_id' => $item_info['shop_id'],
                        'dataOther'=> json_decode($item_info['data_other'],true),
                        'assess_flag'=> $item_info['assess_flag'],
                        'imgUrl'=> json_decode($item_info['img_url'],true),
                        'score'=> $item_info['score'],
                        'cd_parameter'=> $item_info['cd_parameter'],
                        'shopName'=> $item_info['shop_name'],
                        'delPrice'=> $item_info['del_price'],
                    );
                    //查询服务
                    $service_info = model('ShopServices')->getServicesByAliShopId($shop_id);
                    if (empty($service_info)) {
                        return $this->fetch('service_expired',array('err_msg' => "该店铺无购买服务，请到微驿上购买"));
                    }else{
                        $is_time_out =1;
                        foreach ($service_info as $k => $v) {
                            if (($v['service_start_time']<=time() && $v['service_end_time']>=time()) || ($v['experience_start_time']<=time() && $v['experience_end_time']>=time()) ) {
                                $is_time_out = 0;
                                $arr['shortUrl'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.$v['transformed_url'];
                                session('shopId',$v['shop_id']);
                                break;
                            }
                        }
                        if ($is_time_out) {
                        return $this->fetch('service_expired',array('err_msg' => "该店铺不在服务时间范围内"));
                        }
                    }
                }else{
                    $wei_bao = new WeiBaoData();
                    $tm_res = $wei_bao->getTmGoodsDetail($item_id);
                    if ($tm_res['errcode']) {
                        return $this->fetch('service_expired',array('err_msg' => "获取数据失败"));
                    }else{
                        //存储商品详情数据，获取服务是否在服务时间范围内
                        $shop_id =  $tm_res['data']['shop_id'];
                        $shop_url =  $tm_res['data']['shop_url'];
                        //查询服务
                        $service_info = model('ShopServices')->getServicesByAliShopId($shop_id);
                        if (empty($service_info)) {
                            return $this->fetch('service_expired',array('err_msg' => "该店铺无购买服务，请到微驿上购买"));
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
                                return $this->fetch('service_expired',array('err_msg' => "该店铺不在服务时间范围内"));
                            }
                            if (empty($item_info)) {
                                $has_add = model('AliTmGoodsDetail')->addGoodsDetailData($item_id,$tm_res['data']);
                            }else{
                                $has_update = model('AliTmGoodsDetail')->updateGoodsDetailDataByItemId($item_info['id'],$tm_res['data']);
                            }
                            $arr['shop_id'] = $shop_id;
                            $arr['dataOther'] = json_decode($tm_res['data']['data_other'],true);
                            $arr['assess_flag'] = $tm_res['data']['assess_flag'];
                            $arr['imgUrl'] = json_decode($tm_res['data']['img_url'],true);
                            $arr['score'] = $tm_res['data']['score'];
                            $arr['cd_parameter'] = $tm_res['data']['cd_parameter'];
                            $arr['shopName'] = $tm_res['data']['shop_name'];
                            $arr['delPrice'] = $tm_res['data']['del_price'];
                        }
                    }
                }
                return $this->fetch('tm_commodity_detail',array('data' => json_encode($arr)));
            }else{
                $url='https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22'.$item_id.'%22%7D';
                $data=file_get_contents($url);
                $data=json_decode($data,true);

                if(!$data || $data['ret'][0] !="SUCCESS::调用成功" || !isset($data['data']['seller'])){
                        return $this->fetch('service_expired',array('err_msg' => "获取数据失败"));
                }

                //获取shopid,验证服务是否存在，是否过期
                $seller_info = $data['data']['seller'];
                $shop_id = intval($seller_info['shopId']);
                // $user_id = intval($seller_info['userId']);
                // $shop_url ='https://shop.m.taobao.com/shop/shop_index.htm?user_id='.$user_id;

                if (!$shop_id) {
                        return $this->fetch('service_expired',array('err_msg' => "获取数据失败"));
                }
                $shortUrl = '';
                //查询服务
                $service_info = model('ShopServices')->getServicesByAliShopId($shop_id);
                if (empty($service_info)) {
                    return $this->fetch('service_expired',array('err_msg' => "该店铺无购买服务，请到微驿上购买"));
                }else{
                    $is_time_out =1;
                    foreach ($service_info as $k => $v) {
                        if (($v['service_start_time']<=time() && $v['service_end_time']>=time()) || ($v['experience_start_time']<=time() && $v['experience_end_time']>=time()) ) {
                            $is_time_out =0;
                            $shortUrl = 'http://'.$_SERVER['HTTP_HOST'].'/'.$v['transformed_url'];
                            break;
                        }
                    }
                    if ($is_time_out) {
                        return $this->fetch('service_expired',array('err_msg' => "该店铺不在服务时间范围内"));
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
     * 商品图文详情
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
            try{
                $url='https://item.taobao.com/modulet/v5/wItemDesc.do?id='.$itemId.'&type=0';
                $opts = array('http'=>array('header' => "User-Agent:Mozilla/4.0"));
                $context = stream_context_create($opts);
                $response = file_get_contents($url,false,$context);
                $response_data = json_decode($response,true);
                // var_dump($response_data);
                $des_data = json_encode($response_data['wdescContent']['pages']);

                if (empty($des_info)) {
                    $has_add = model('AliGoodsDes')->addShopData($itemId,$des_data);
                }else{
                    $has_update = model('AliGoodsDes')->updateDesDataById($des_info['id'],$des_data);
                }
                echo $des_data;
            }catch(Exception $e){
                echo '获取数据失败，请刷新重试';
                exit;
            }
        }
    }

    /**
     * 推广商品的商品详情，对接一期
     * @return [type] [description]
     */
    public function getPromotionalGoodsDetail()
    {
        $isTm = input('param.isTm') ? input('param.isTm') : '';
        $item_id = input('param.item_id') ? input('param.item_id') : '';

        if (!$this->is_weixin()) {
            if ($isTm) {
                header('Location:https://detail.m.tmall.com/item.htm?abtest=_AB-LR90-PR90&pos=1&abbucket=_AB-M90_B17&acm=03080.1003.1.1287876&id='.$item_id.'&scm=1007.12913.42100.100200300000000');
                exit;
            }else{
                header('Location:https://item.taobao.com/item.htm?id='.$item_id);
                    // https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22'.$item_id.'%22%7D');
                exit;
            }
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

                if (!$item_id) {
                    return $this->fetch('service_expired',array('err_msg' => "item_id参数错误不存在"));
                }
                $item_info = model('AliTmGoodsDetail')->getGoodsDetailByItemId($item_id);

                $tm_detail_cache_day = config('tm_detail_cache_day') * 24 *60 * 60;
                if (!empty($item_info) && $item_info['update_time'] - time() < $tm_detail_cache_day) {
                    $shop_id =  $item_info['shop_id'];

                    $arr = array(
                        'shop_id' => $item_info['shop_id'],
                        'dataOther'=> json_decode($item_info['data_other'],true),
                        'assess_flag'=> $item_info['assess_flag'],
                        'imgUrl'=> json_decode($item_info['img_url'],true),
                        'score'=> $item_info['score'],
                        'cd_parameter'=> $item_info['cd_parameter'],
                        'shopName'=> $item_info['shop_name'],
                        'delPrice'=> $item_info['del_price'],
                    );
                }else{
                    $wei_bao = new WeiBaoData();
                    $tm_res = $wei_bao->getTmGoodsDetail($item_id);
                    if ($tm_res['errcode']) {
                        return $this->fetch('service_expired',array('err_msg' => "获取数据失败"));
                    }else{
                        //存储商品详情数据
                        $shop_id =  $tm_res['data']['shop_id'];
                        $shop_url =  $tm_res['data']['shop_url'];
                        //查询服务
                        $service_info = model('ShopServices')->getServicesByAliShopId($shop_id);
                        if (empty($item_info)) {
                            $has_add = model('AliTmGoodsDetail')->addGoodsDetailData($item_id,$tm_res['data']);
                        }else{
                            $has_update = model('AliTmGoodsDetail')->updateGoodsDetailDataByItemId($item_info['id'],$tm_res['data']);
                        }
                        $arr['shop_id'] = $shop_id;
                        $arr['dataOther'] = json_decode($tm_res['data']['data_other'],true);
                        $arr['assess_flag'] = $tm_res['data']['assess_flag'];
                        $arr['imgUrl'] = json_decode($tm_res['data']['img_url'],true);
                        $arr['score'] = $tm_res['data']['score'];
                        $arr['cd_parameter'] = $tm_res['data']['cd_parameter'];
                        $arr['shopName'] = $tm_res['data']['shop_name'];
                        $arr['delPrice'] = $tm_res['data']['del_price'];
                    }
                }
                // var_dump($arr);exit;
                return $this->fetch('tm_pro_commodity_detail',array('data' => json_encode($arr)));
            }else{
                return $this->fetch('tb_pro_commodity_detail',array('data' => $item_id));
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
     * [testGoods description]
     * @return [type] [description]
     */
    public function testGoods()
    {
        $item_id = input('param.item_id') ? input('param.item_id') : '';
        $wei_bao = new WeiBaoData();
        $tm_res = $wei_bao->getTmGoodsDetail($item_id);
        if ($tm_res['errcode']) {
            return $this->fetch('service_expired',array('err_msg' => "获取数据失败"));
        }else{
            //存储商品详情数据
            $shop_id =  $tm_res['data']['shop_id'];
            $shop_url =  $tm_res['data']['shop_url'];
            $arr['shop_id'] = $shop_id;
            $arr['dataOther'] = json_decode($tm_res['data']['data_other'],true);
            $arr['assess_flag'] = $tm_res['data']['assess_flag'];
            $arr['imgUrl'] = json_decode($tm_res['data']['img_url'],true);
            $arr['score'] = $tm_res['data']['score'];
            $arr['cd_parameter'] = $tm_res['data']['cd_parameter'];
            $arr['shopName'] = $tm_res['data']['shop_name'];
            $arr['delPrice'] = $tm_res['data']['del_price'];
        }
        return $this->fetch('tm_commodity_detail',array('data' => json_encode($arr)));
    }

    /**
     * @return bool
     */
    public function is_weixin(){
        // return true;
        if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
            return true;  
        }    
        return false;  
    }

}
