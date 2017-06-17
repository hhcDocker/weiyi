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
                preg_match('/(?:id=)(\d+)/',$url,$m);
                if (empty($m) || !isset($m[1])){
                    throw new APIException(30001,['url'=>$url]);
                }
                $item_id =$m[1];
                $url='https://detail.m.tmall.com/item.htm?abtest=_AB-LR90-PR90&pos=1&abbucket=_AB-M90_B17&acm=03080.1003.1.1287876&id='.$item_id.'&scm=1007.12913.42100.100200300000000';
                vendor('simple_html_dom.simple_html_dom');
                set_time_limit(0); 
                header("Connection:Keep-Alive");
                header("Proxy-Connection:Keep-Alive");
                $html =file_get_html($url);
                //店铺链接
                $shop_url=iconv("GB2312//IGNORE","UTF-8",$html->find('div#s-actionbar',0)->find('div.toshop',0)->find('a',0)->href);
                foreach($html->find('script') as $key => $script){
                    $v = iconv("GB2312//IGNORE","UTF-8",$script->innertext);
                    if (strpos($v,'_DATA_Detail')!==false){
                        preg_match('/(?:"rstShopId":)\d+/',$v,$id_str);// echo $a;"rstShopId":60291124
                        $shop_id = str_replace('"rstShopId":','',$id_str);
                        break;
                    }
                };
                if (!$shop_url || !$shop_id) {
                    throw new APIException(30009);
                }
                $shop_info = model('AliShops')->getShopInfoByIdUrl($shop_url,$shop_id);
                if (empty($shop_info)) { //表示从来没抓取过该店铺数据，即用户未体验过
                    $has_add = model('AliShops')->saveShopInfo($shop_id,1,$shop_url);
                    if (!$has_add) {
                        throw new APIException(30010);
                    }
                    $service_info =array();
                }else{
                    $service_info = model('ShopServices')->getServicesByShopId($shop_id,session('manager_id'));

                    if (!empty($service_info) && $service_info['service_end_time']>time()) { //服务未过期
                        //设置路由，获取链接，生成二维码
                        //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                        throw new APIException(31997);
                    }
                }

                //新增体验服务
                $service_info = $this->AddShopShortUrlInfo($shop_id);//设置路由，获取链接，生成二维码
                        //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                        throw new APIException(31997);
            }elseif (strpos($full_url, 'tmall.com/shop') || preg_match('/\w+[.\w]+tmall.com/',$full_url)){ //店铺
                $full_url = preg_replace('(/.+\w+)[.m]?.tmall.com\/shop\/view_shop\.htm.+/','$1'.'.m.tmall.com',$full_url);
                $full_url = preg_replace('/(.+\w+)[.m]?.tmall.com.+/','$1'.'.m.tmall.com',$full_url);

                $shop_info = model('AliShops')->getShopInfoByShopUrl($full_url);
                if (empty($shop_info)) { //表示从来没抓取过该店铺数据，或者信息不全
                    //抓取店铺信息,调用服务
                    //检查对比url和shopid，没有则添加，新增服务，有则修改，查表是否有服务，没有就新增服务
                    //

                    
                    $has_add = model('AliShops')->saveShopInfo($shop_id,1,$shop_url);
                    if (!$has_add) {
                        throw new APIException(30010);
                    }
                    $service_info =array();
                }
                //汇总，

                //新增体验服务
                //$service_info = $this->AddShopShortUrlInfo($shop_id);//设置路由，获取链接，生成二维码
                        //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                        throw new APIException(31997);
            }else{
                $is_shop=0;
                //待定
                throw new APIException(31998,['url'=>$url]);
                //mc
                //拿到url
            }
            $service_info = $this->getShopShortUrlInfo($full_url);
            return $service_info;
        }elseif (strpos($full_url, 'taobao')) { //淘宝
            if (preg_match('/shop\d+\.taobao/', $full_url)){ //淘宝店铺pc端
                $full_url = preg_replace('/(.+\w+).taobao.com.+/', '$1'.'m.taobao.com',$full_url);
            }elseif (preg_match('/shop\d+\.m\.taobao/', $full_url)) { //淘宝店铺移动端
                $full_url = preg_replace('/(.+\w+).taobao.com.+/', '$1'.'m.taobao.com',$full_url);
            }elseif (strpos($full_url, 'shop.m.taobao.com')) { //淘宝店铺移动端
                $full_url = preg_replace('/(.+\w+).taobao.com.+/', '$1'.'m.taobao.com',$full_url);
            }else{
                $is_shop=0;
                //待定
                throw new APIException(31998);
            }

            $service_info = $this->getShopShortUrlInfo($full_url);
            return $service_info;

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
        $url = 'https://'.$url;
        try
        {
            $url_header = @get_headers($url);
        }
        catch(Exception $e)
        {   
            throw new APIException(30003);
        }
	    if (!is_array($url_header)) {
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
     * 新增记录，体验三天
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    private function AddShopShortUrlInfo($shop_id=0)
    {
        $experience_time = config('ExperienceTime');
        $time_start = time();
        $time_end = strtotime("+".$experience_time." day");

        //mc 改用tp路由
        $o = new ShortUrl($full_url);
        $shop_url_str = $o->getSN();
        //mc
        
        //新增服务
        $service_id = model('ShopServices')->saveServices(session('manager_id'),$shop_id,$shop_url_str,$time_start,$time_end);
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
