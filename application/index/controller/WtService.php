<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: WtService.php
 * | Description: 微跳端转换地址以及服务
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-06-13 14:57
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\index\controller;

use app\common\utils\SN\ExpenseSN;
use app\common\service\WeiBaoData;
use app\common\service\QRCode;
use app\common\utils\SN\ShortUrl;
use JonnyW\PhantomJs\Client;
use api\APIAuthController;
use api\APIException;
use think\Config;

class WtService extends APIAuthController
{
    /**
     * 首页转换网址
     * 1.判断链接合法性与否
     * 2.验证登录与否
     * 3.判断天猫or淘宝，店铺则转换地址查表；商品详情则调用爬取函数，天猫取得shopId，淘宝取得userId
     * 4.查表获取该链接所属店铺是否已购买服务，是否已过期
     * 5.如果从未购买，则爬取店铺数据，生成体验记录，默认3天，生成服务记录，返回短链接
     * 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
     * 短链接全部由路由定义
     * @return [type] [description]
     */
    public function getShortUrl()
    {
        $url = noempty_input('url');
        $url = strtolower($url);
        if (strpos($url,'http://')===false && strpos($url,'https://')===false){
            throw new APIException(30001);
        }
        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);
        $full_url = $this->checkUrl($url,0);
        $is_shop = 1;//店铺链接

        //mc 判断是否登录
        if(!is_login()) {
            throw new APIException(10018);
        }

        if (strpos($full_url,'tmall.com')) { //天猫
            $key_word_arr =array('list.','shouji.','www.tmall.com','pages.tmall.com');//天猫各种列表关键字
            foreach ($key_word_arr as $k => $v) {
                if (strpos($full_url, $v)) {
                    throw new APIException(30011);
                }
            }

            if (preg_match('/detail.[m.]*tmall.com/', $full_url)) { //详情
                preg_match('/[?&](?:id=)(\d+)/',$url,$m);
                if (empty($m) || !isset($m[1])){
                    throw new APIException(30001,['url'=>$url]);
                }
                $item_id =$m[1];
                $url='https://detail.m.tmall.com/item.htm?abtest=_AB-LR90-PR90&pos=1&abbucket=_AB-M90_B17&acm=03080.1003.1.1287876&id='.$item_id.'&scm=1007.12913.42100.100200300000000';
                vendor('simple_html_dom.simple_html_dom');
                set_time_limit(0);
                header("Connection:Keep-Alive");
                header("Proxy-Connection:Keep-Alive");
                try{
                    $html = file_get_html($url);
                }catch (Exception $e){
                    throw new APIException(30009);
                }
                //店铺链接
                $shop_url='https://'.trim(iconv("GB2312//IGNORE","UTF-8",$html->find('div#s-actionbar',0)->find('div.toshop',0)->find('a',0)->href));
                foreach($html->find('script') as $key => $script){
                    $v = iconv("GB2312//IGNORE","UTF-8",$script->innertext);
                    if (strpos($v,'_DATA_Detail')!==false){
                        preg_match('/(?:"rstShopId":)\d+/',$v,$id_str);// echo $a;"rstShopId":60291124
                        $id_str = $id_str[0];
                        $shop_id = str_replace('"rstShopId":','',$id_str);
                        break;
                    }
                };
                if (!$shop_url || !$shop_id) {
                    throw new APIException(30009);
                }
                $shop_info = model('AliShops')->getShopInfoByIdUrl($shop_url,$shop_id);
                if (empty($shop_info)) { //表示从来没抓取过该店铺数据，即用户未体验过
                    $wj_shop_id = model('AliShops')->saveShopInfo($shop_id,$shop_url);
                    if (!$wj_shop_id) {
                        throw new APIException(30010);
                    }
                    $service_info =array();
                }else{
                    $wj_shop_id = $shop_info['id'];
                    //mc 测试是否有shop_url无shop_id的情况，或者有shop_if无shop_url的情况
                    $service_info = model('ShopServices')->getServicesByShopId($wj_shop_id,session('manager_id'));
                }
                //要生成二维码的链接，指向爬取详情函数，路由缩短，携带参数：商品id、是否天猫商品
                $qrcode_url ='/weibao/index/processUrl/isTm/1/itemId/'.$item_id;
                $res =$this->manageServiceInfo($service_info,$qr_code_url,$wj_shop_id);
                //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                return $res;
            }elseif (strpos($full_url, 'tmall.com/shop') || preg_match('/\w+[.\w]+tmall.com/',$full_url)){ //店铺
                $full_url = preg_replace('/(.+\w+)[.m]?.tmall.com.+/','$1'.'.m.tmall.com',$full_url);

                $shop_info = model('AliShops')->getShopInfoByUrl($full_url);
                if (empty($shop_info)) { //表示从来没抓取过该店铺数据，或者信息不全
                    //抓取店铺信息,调用服务
                    $wei_bao = new WeiBaoData();
                    $res = $wei_bao->getShopDataByUrl($full_url);
                    if ($res['errcode']) {
                        throw new APIException($res['errcode']);
                    }
                    $shop_id = $res['shop_id'];
                    //检查对比url和shopid，没有则添加;有则修改
                    $shop_info = model('AliShops')->getShopInfoByShopId($shop_id);
                    if (empty($shop_info)) {
                        //添加店铺表记录
                        $wj_shop_id = model('AliShops')->saveShopInfo($shop_id,$full_url);
                        if (!$wj_shop_id) {
                            throw new APIException(30010);
                        }
                        //添加店铺数据记录
                        $shop_data = $res['shop_data'];
                        $flag_error=0;
                        foreach ($shop_data as $k1 => $v1) {
                            $has_add = model('ShopApi')->saveShopData($wj_shop_id,$v1['api_url'],$v1['api_data']);
                            if (!$has_add) {
                                $flag_error=1;
                                break;
                            }
                        }
                        if ($flag_error) {
                            throw new APIException(30010);
                        }
                        //表示没有服务
                        $service_info=array();
                    }else{
                        $wj_shop_id =$shop_info['id'];
                        if ($shop_info['shop_url'] && $shop_info['shop_url']!=$full_url) {
                            throw new APIException(30013);
                        }
                        $has_update = model('AliShops')->updateShopUrl($shop_id,$full_url);
                        if (!$has_update) {
                            throw new APIException(30010);
                        }
                        $service_info = model('ShopServices')->getServicesByShopId($wj_shop_id,session('manager_id'));
                    }
                }else{
                    $wj_shop_id =$shop_info['id'];
                    $service_info = model('ShopServices')->getServicesByShopId($wj_shop_id,session('manager_id'));
                }

                //链接从$service_info取短链拼接而成，指向店铺数据，路由映射
                $res =$this->manageServiceInfo($service_info,'',$wj_shop_id);
                //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                return $res;
            }
        }elseif (strpos($full_url, 'taobao')) { //淘宝
            if (preg_match('/shop[.\d\w]+.taobao.com/',$full_url)){ //店铺链接
                if (strpos($full_url, 'shop.m.taobao.com')) { //淘宝店铺移动端1
                    //获取userid
                    preg_match('/(?:user_id=)(\d+)/',$full_url,$m);
                    if (empty($m) || !isset($m[1])){
                        throw new APIException(30001,['url'=>$full_url]);
                    }
                    $user_id =$m[1];
                    $shop_url='https://shop.m.taobao.com/shop/shop_index.htm?user_id='.$user_id;
                    //查询店铺
                    $shop_info = model('AliShops')->getShopInfoByUrl($shop_url);
                    if (!empty($shop_info)) {
                        $wj_shop_id =$shop_info['id'];
                        $service_info = model('ShopServices')->getServicesByShopId($wj_shop_id,session('manager_id'));
                    }else{
                        //抓取店铺信息,调用服务
                        $wei_bao = new WeiBaoData();
                        $res = $wei_bao->getShopDataByUrl($full_url);
                        if ($res['errcode']) {
                            throw new APIException($res['errcode']);
                        }
                        $shop_id = $res['shop_id'];
                        //检查对比url和shopid，没有则添加;有则修改
                        $shop_info = model('AliShops')->getShopInfoByShopId($shop_id);
                        if (empty($shop_info)) {
                            //添加店铺表记录
                            $wj_shop_id = model('AliShops')->saveShopInfo($shop_id,$full_url);
                            if (!$wj_shop_id) {
                                throw new APIException(30010);
                            }
                            //添加店铺数据记录
                            $shop_data = $res['shop_data'];
                            $flag_error=0;
                            foreach ($shop_data as $k1 => $v1) {
                                $has_add = model('ShopApi')->saveShopData($wj_shop_id,$v1['api_url'],$v1['api_data']);
                                if (!$has_add) {
                                    $flag_error=1;
                                    break;
                                }
                            }
                            if ($flag_error) {
                                throw new APIException(30010);
                            }
                            //表示没有服务
                            $service_info=array();
                        }else{
                            $wj_shop_id =$shop_info['id'];
                            if ($shop_info['shop_url'] && $shop_info['shop_url']!=$full_url) {
                                throw new APIException(30013);
                            }
                            $has_update = model('AliShops')->updateShopUrl($shop_id,$full_url);
                            if (!$has_update) {
                                throw new APIException(30010);
                            }
                            $service_info = model('ShopServices')->getServicesByShopId($wj_shop_id,session('manager_id'));
                        }
                    }
                }elseif (preg_match('/shop\d+\.taobao/', $full_url)){
                    $shop_id = preg_replace('/.+shop(\d+).+/','\1',$full_url);
                    //根据shopid查表
                    $shop_info = model('AliShops')->getShopInfoByShopId($shop_id);
                    $full_url = "https://shop".$shop_id.'.m.taobao.com';
                    if (empty($shop_info)) {
                        //抓取店铺信息,调用服务
                        $wei_bao = new WeiBaoData();
                        $res = $wei_bao->getShopDataByUrl($full_url);
                        if ($res['errcode']) {
                            throw new APIException($res['errcode']);
                        }
                        $user_id = $res['user_id'];
                        $shop_url='https://shop.m.taobao.com/shop/shop_index.htm?user_id='.$user_id;

                        //添加店铺表记录
                        $wj_shop_id = model('AliShops')->saveShopInfo($shop_id,$shop_url);
                        if (!$wj_shop_id) {
                            throw new APIException(30010);
                        }
                        //添加店铺数据记录
                        $shop_data = $res['shop_data'];
                        $flag_error=0;
                        foreach ($shop_data as $k1 => $v1) {
                            $has_add = model('ShopApi')->saveShopData($wj_shop_id,$v1['api_url'],$v1['api_data']);
                            if (!$has_add) {
                                $flag_error=1;
                                break;
                            }
                        }
                        if ($flag_error) {
                            throw new APIException(30010);
                        }
                        //表示没有服务
                        $service_info=array();
                    }else{
                        $wj_shop_id = $shop_info['id'];
                        $service_info =model('ShopServices')->getServicesByShopId($wj_shop_id,session('manager_id'));
                        if (!empty($service_info)) { //存在服务记录
                            //设置路由，获取服务对应的链接，生成二维码
                            //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                            throw new APIException(31997);
                        }
                        //表示无服务记录
                    }
                }else{
                    throw new APIException(30001);
                }

                //链接从$service_info取短链拼接而成，指向店铺数据，路由映射
                $res =$this->manageServiceInfo($service_info,'',$wj_shop_id);
                //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                return $res;
            }elseif (strpos($full_url,'item.htm') || strpos($full_url,'detail.html')) { //商品详情
                preg_match('/[?&](?:id=)(\d+)/',$full_url,$m);
                if (empty($m) || !isset($m[1])){
                    throw new APIException(30001,['url'=>$full_url]);
                }
                $item_id =$m[1];
                $url='https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22'.$item_id.'%22%7D';
                $data=file_get_contents($url);
                $data=json_decode($data,true);
                if(!$data || $data['ret'][0] !="SUCCESS::调用成功" || !isset($data['data']['seller'])){
                    throw new APIException(30009);
                }
                $seller_info = $data['data']['seller'];
                $shop_id = intval($seller_info['shopId']);
                $user_id = intval($seller_info['userId']);
                $shop_url ='https://shop.m.taobao.com/shop/shop_index.htm?user_id='.$user_id;
                $shop_info = model('AliShops')->getShopInfoByIdUrl($shop_url,$shop_id);
                if (empty($shop_info)) { //表示从来没抓取过该店铺数据，即用户未体验过
                    $wj_shop_id = model('AliShops')->saveShopInfo($shop_id,$shop_url);
                    if (!$wj_shop_id) {
                        throw new APIException(30010);
                    }
                    $service_info =array();
                }else{
                    $wj_shop_id = $shop_info['id'];
                    //mc 测试是否有shop_url无shop_id的情况，或者有shop_if无shop_url的情况
                    $service_info = model('ShopServices')->getServicesByShopId($wj_shop_id,session('manager_id'));
                }
                //要生成二维码的链接，指向爬取详情函数，路由缩短，携带参数：商品id、是否天猫商品
                $qrcode_url ='/weibao/index/processUrl/isTm/0/itemId/'.$item_id;
                $res =$this->manageServiceInfo($service_info,$qr_code_url,$wj_shop_id);
                //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                return $res;
            }else{
                throw new APIException(30014);
            }
        }else{
        	throw new APIException(30004);
        }
    }

    // **********************************公有函数******************************************************

    /**
     * 检查链接
     * @param  string $url [description]
     * @return [type]      [description]
     */
    private function checkUrl($url='',$is_shop=0)
    {
        if (!$url) { //非空检验
        	throw new APIException(10001);
        }
        $url = strtolower($url);
        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);
        try
        {
            $url = 'https://'.$url;
            $url_header = @get_headers($url);
        }
        catch(Exception $e)
        {
            throw new APIException(30003);
        }
	    if (!is_array($url_header)) {
            $url = str_replace('https://', '', $url);
	        $url = 'http://'.$url;
            try
            {
                $url_header = @get_headers($url);
            }
            catch(Exception $e)
            {
                throw new APIException(30003);
            }
		    if (!is_array($url_header)) {
	        	throw new APIException(30002);
		    }
		}
	    if(!in_array('HTTP/1.1 200 OK',$url_header)){
        	throw new APIException(30006);
		}

        if(!preg_match('/[\w.]+[\w\/]*[\w.]*[\w=&\+\%.\-\_?]*/is',$url)){
        	throw new APIException(30002);
        }
        if (!strpos($url, 'taobao') && !strpos($url, 'tmall')) {
        	throw new APIException(30004);
        }
        if ($is_shop) {
        	if (!strpos($url, 'm.taobao.com') && !strpos($url, 'm.tmall.com')) {
        		throw new APIException(30005);
	    	}
        }
        return $url;
    }

    /**
     * [manageServiceInfo description]
     * @param  string $service_info [description]
     * @param  string $qrcode_url   [description]
     * @return [type]               [链接二维码，短链接，有效期（不返回具体数据，只返回链接）]
     */
    public function manageServiceInfo($service_info='',$qrcode_url='',$wj_shop_id=0)
    {
        if (!$wj_shop_id) {
            throw new APIException(30010);
        }
        $img ='';
        if (empty($service_info)){
            //新增体验服务
            //$experience_time = config('ExperienceTime');
            $time_start = time();
            $time_end = strtotime("+".$experience_time." day");

            //mc 改用shop_id+manager_id
            $o = new ShortUrl($shop_id,session('manager_id'));
            $shop_url_str = $o->getSN();
            //查询短链接是否存在
            $i=1;
            $res = model('ShopServices')->ExistShortUrl($shop_url_str);
            while (!empty($res) || str_len($shop_url_str)!=6) {
                //mc 改用shop_id+manager_id
                $o = new ShortUrl($shop_id,session('manager_id'),time());
                $shop_url_str = $o->getSN();
                //查询短链接是否存在
                $res = model('ShopServices')->ExistShortUrl($shop_url_str);
                if ($i>100) {
                    throw new APIException(30010);
                }
                $i++;
            }

            //新增服务
            $service_id = model('ShopServices')->saveServices(session('manager_id'),$wj_shop_id,$shop_url_str,$time_start,$time_end);
            $service_info = model('ShopServices')->getServicesById($service_id);
            if (empty($service_info)) {
                throw new APIException(30010);
            }

            //添加消费记录，体验3天
            $expense_model = new ExpenseSN();
            $expense_num = $expense_model->getSN();
            $has_add = model('ExpenseRecords')->addExpense($expense_num, 0,'',$service_id,session('manager_id'),0,$time_start,$time_end,1);
        }

        if ($service_info['service_end_time']>=time()) { //服务未过期
            //设置路由，获取链接，生成二维码
            //mc 路由映射短链
            $qrcode_url = $qrcode_url?$qrcode_url:'/weibao/index/getShopDataByShortUrl/str_url/'.$service_info['transformed_url'];
            //二维码
            $QRCode = new QRCode;
            $img = base64_encode($QRCode->createQRCodeImg($qrcode_url));
        }else {//已过期
             $qrcode_url='';
        }
        $res_data =array('service_info'=>$service_info,'qrcode_url'=>$qrcode_url,'qrcode_img'=>$img);
        return $res_data;
    }

    /**
     * 新增记录，体验三天
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    private function AddShopShortUrlInfo($wj_shop_id=0,$shop_id='')
    {
        if (!$wj_shop_id || !$shop_id) {
            throw new APIException(30010);
        }

        $experience_time = config('ExperienceTime');
        $time_start = time();
        $time_end = strtotime("+".$experience_time." day");

        //mc 改用shop_id+manager_id
        $o = new ShortUrl($shop_id,session('manager_id'));
        $shop_url_str = $o->getSN();
        //查询短链接是否存在
        $i=1;
        $res = model('ShopServices')->ExistShortUrl($shop_url_str);
        while (!empty($res) || str_len($shop_url_str)!=6) {
            //mc 改用shop_id+manager_id
            $o = new ShortUrl($shop_id,session('manager_id'),time());
            $shop_url_str = $o->getSN();
            //查询短链接是否存在
            $res = model('ShopServices')->ExistShortUrl($shop_url_str);
            if ($i>100) {
                throw new APIException(30010);
            }
            $i++;
        }

        //新增服务
        $service_id = model('ShopServices')->saveServices(session('manager_id'),$wj_shop_id,$shop_url_str,$time_start,$time_end);
        $service_info = model('ShopServices')->getServicesById($service_id);
        if (empty($service_info)) {
            throw new APIException(30010);
        }

        //添加消费记录，体验3天
        $expense_model = new ExpenseSN();
        $expense_num = $expense_model->getSN();
        $has_add = model('ExpenseRecords')->addExpense($expense_num, 0,'',$service_id,session('manager_id'),0,$time_start,$time_end,1);
        return $service_info;
    }
}
