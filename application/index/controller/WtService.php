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
use app\common\utils\SN\ShortUrl;
use app\common\service\WeiBaoData;
use app\common\service\QRCode;
use app\common\service\WxPay;
use app\common\service\AliPay;
use JonnyW\PhantomJs\Client;
use api\APIAuthController;
use api\APIException;
use think\Config;
use think\Db;

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
        /*if(!is_login()) {
            throw new APIException(10018);
        }*/

        if (strpos($full_url,'tmall.com')) { //天猫
            $key_word_arr =array('list.','shouji.','www.tmall.com','pages.tmall.com','chaoshi');//天猫各种列表关键字
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
                }catch (\Exception $e){
                    throw new APIException(30009);
                }
                //店铺链接
                try{
                    $shop_url='https:'.trim(iconv("GB2312//IGNORE","UTF-8",$html->find('div#s-actionbar',0)->find('div.toshop',0)->find('a',0)->href));
                
                    foreach($html->find('script') as $key => $script){
                        $v = iconv("GB2312//IGNORE","UTF-8",$script->innertext);
                        if (strpos($v,'_DATA_Detail')!==false){
                            preg_match('/(?:"rstShopId":)\d+/',$v,$id_str);// echo $a;"rstShopId":60291124
                            $id_str = $id_str[0];
                            $shop_id = str_replace('"rstShopId":','',$id_str);
                            break;
                        }
                    };
                }catch (\Exception $e){
                    throw new APIException(30009);
                }
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
                $qrcode_url ='/detail/1/'.$item_id;
                $res =$this->manageServiceInfo($service_info,$qrcode_url,$wj_shop_id);
                //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                return $this->format_ret($res);
            }elseif (strpos($full_url, 'tmall.com/shop') || preg_match('/\w+[.\w]+tmall.com/',$full_url)){ //店铺
                $full_url = preg_replace('/(\w+)[\.m]*\.tmall.com.*/','$1'.'.m.tmall.com',$full_url);

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
                        Db::startTrans();
                        foreach ($shop_data as $k1 => $v1) {
                            $has_add = model('ShopApi')->saveShopData($wj_shop_id,$v1['api_url'],$v1['api_data']);
                            if (!$has_add) {
                                $flag_error=1;
                                break;
                            }
                        }
                        if ($flag_error) {
                            Db::rollback();
                            throw new APIException(30010);
                        }else{
                             Db::commit();
                        }
                        //表示没有服务
                        $service_info=array();
                    }else{
                        $wj_shop_id =$shop_info['id'];
                        if ($shop_info['shop_url'] && $shop_info['shop_url']!=$full_url) {
                            throw new APIException(30015);
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
                return $this->format_ret($res);
            }
        }elseif (strpos($full_url, 'taobao')) { //淘宝

            /*$key_word_arr =array('markets.','qiang.taobao.com','www.taobao.com','https://m.taobao.com','login.taobao.com');//淘宝各种列表关键字
            foreach ($key_word_arr as $k => $v) {
                if (strpos($full_url, $v)) {
                    throw new APIException(30026);
                }
            }*/
            if (preg_match('/shop[.\d\w]+.taobao.com/',$full_url)){ //店铺链接
                if (strpos($full_url, 'shop.m.taobao.com')) { //淘宝店铺移动端1
                    //获取userid
                    preg_match('/(?:user_(number_)?id=)(\d+)/',$full_url,$m);
                    if (empty($m) || !isset($m[1])){
                        throw new APIException(30001,['url'=>$full_url]);
                    }
                    $user_id =end($m);
                    $shop_url='https://shop.m.taobao.com/shop/shop_index.htm?user_id='.$user_id;
                    //查询店铺
                    $shop_info = model('AliShops')->getShopInfoByUrl($shop_url);
                    if (!empty($shop_info)) {
                        $wj_shop_id =$shop_info['id'];
                        $service_info = model('ShopServices')->getServicesByShopId($wj_shop_id,session('manager_id'));
                    }else{
                        //抓取店铺信息,调用服务
                        $wei_bao = new WeiBaoData();
                        $res = $wei_bao->getShopDataByUrl($shop_url);
                        if ($res['errcode']) {
                            throw new APIException($res['errcode']);
                        }
                        $shop_id = $res['shop_id'];
                        //检查对比url和shopid，没有则添加;有则修改
                        $shop_info = model('AliShops')->getShopInfoByShopId($shop_id);
                        if (empty($shop_info)) {
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
                            $wj_shop_id =$shop_info['id'];
                            if ($shop_info['shop_url'] && $shop_info['shop_url']!=$shop_url) {
                                throw new APIException(30015);
                            }
                            $has_update = model('AliShops')->updateShopUrl($shop_id,$shop_url);
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
                    }
                }else{
                    throw new APIException(30008);
                }

                //链接从$service_info取短链拼接而成，指向店铺数据，路由映射
                $res =$this->manageServiceInfo($service_info,'',$wj_shop_id);
                //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                return $this->format_ret($res);
            }elseif (strpos($full_url,'item.htm') || strpos($full_url,'detail.htm')) { //商品详情
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
                $qrcode_url ='/detail/0/'.$item_id;
                $res =$this->manageServiceInfo($service_info,$qrcode_url,$wj_shop_id);
                //mc 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
                return $this->format_ret($res);
            }else{
                throw new APIException(30014);
            }
        }else{
        	throw new APIException(30004);
        }
    }

    /**
     * [AddShopService description]
     * 购买服务未付费成功，时间保留，付费成功后再修改时间
     * @param string $value [description]
     */
    public function buyShopService()
    {
        $shop_url = noempty_input('shop_url','/^http/'); //客户填写的网址
        $shop_name = noempty_input('shop_name');
        $service_start_time = noempty_input('service_start_time');
        $service_time = noempty_input('service_time','/\d+/');
        $_payment_amount = config('service_cost');//每年支付费用
        $payment_amount = $service_time * $_payment_amount;
        $payment_method = noempty_input('payment_method','/\d/');//1-微信，2-支付宝

        //校验
        //校验网址
        $shop_url = strtolower($shop_url);
        if (strpos($shop_url,'http://')===false && strpos($shop_url,'https://')===false){
            throw new APIException(30001);
        }
        $shop_url = str_replace('http://', '', $shop_url);
        $shop_url = str_replace('https://', '', $shop_url);
        $shop_url = $this->checkUrl($shop_url,0);

        //店铺名称
        
        //服务时长
        if ($service_time>5 || $service_time<1) {
            throw new APIException(30016);
        }

        //服务开始时间，格式yyyy-mm-dd,换算为time
        $_service_start_time = explode('-', $service_start_time);
        $_year = $_service_start_time[0];
        $_month = $_service_start_time[1];
        $_day = $_service_start_time[2];
        if (!$_year || !$_month || !$_day){
            throw new APIException(30017);
        }
        // $service_start_time = mktime(hour, minute, second, month, day, year);
        $service_start_time = mktime(0, 0, 0, $_month, $_day, $_year);
        $service_end_time = mktime(23, 59, 59, $_month, $_day, $_year+$service_time);
        if ($_year <date("Y") || $_month>12 ||$_day>31 || time() - $service_start_time>24*60*60) {
            throw new APIException(30017);
        }

        //支付方式
        if ($payment_method!=1 &&$payment_method!=2) {
            throw new APIException(30018);
        }

        $service_info = array();
        //检测是否在alishop表中
        //天猫店铺
        if (strpos($shop_url, 'tmall.com/shop') || preg_match('/\w+[.\w]+tmall.com/',$shop_url)){ //天猫店铺
            $ali_shop_url = preg_replace('/(\w+)[\.m]*\.tmall.com.*/','$1'.'.m.tmall.com',$shop_url);//获取数据的网址

            $shop_info = model('AliShops')->getShopInfoByUrl($ali_shop_url);
            if (empty($shop_info)) { //表示从来没抓取过该店铺数据，或者信息不全
                //抓取店铺信息,调用服务
                $wei_bao = new WeiBaoData();
                $res = $wei_bao->getShopDataByUrl($ali_shop_url);
                if ($res['errcode']) {
                    throw new APIException($res['errcode']);
                }
                $shop_id = $res['shop_id'];
                //检查对比url和shopid，没有则添加;有则修改
                $shop_info = model('AliShops')->getShopInfoByShopId($shop_id);
                if (empty($shop_info)) {
                    //添加店铺表记录
                    $wj_shop_id = model('AliShops')->saveShopInfo($shop_id,$ali_shop_url);
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
                    if ($shop_info['shop_url'] && $shop_info['shop_url']!=$ali_shop_url) {
                        throw new APIException(30015);
                    }
                    $has_update = model('AliShops')->updateShopUrl($shop_id,$ali_shop_url);
                    if (!$has_update) {
                        throw new APIException(30010);
                    }
                    $service_info = model('ShopServices')->getServicesExpenseByShopId($wj_shop_id,session('manager_id'));
                }
            }else{
                $wj_shop_id = $shop_info['id'];
                $service_info = model('ShopServices')->getServicesExpenseByShopId($wj_shop_id,session('manager_id'));
            }
            if (!empty($service_info)){
                if ($service_info['service_end_time'] - time()>3*60*60*24) { //不是体验服务
                    throw new APIException(30020);
                }
            }
        }elseif (strpos($shop_url, 'shop.m.taobao.com')) { //淘宝店铺移动端1
            //获取userid
            preg_match('/(?:user_(number_)?id=)(\d+)/',$shop_url,$m);
            if (empty($m) || !isset($m[1])){
                throw new APIException(30001,['url'=>$shop_url]);
            }
            $user_id =end($m);
            $ali_shop_url='https://shop.m.taobao.com/shop/shop_index.htm?user_id='.$user_id;
            //查询店铺
            $shop_info = model('AliShops')->getShopInfoByUrl($ali_shop_url);
            if (!empty($shop_info)) {
                $wj_shop_id =$shop_info['id'];
                $service_info = model('ShopServices')->getServicesExpenseByShopId($wj_shop_id,session('manager_id'));
            }else{
                //抓取店铺信息,调用服务
                $wei_bao = new WeiBaoData();
                $res = $wei_bao->getShopDataByUrl($ali_shop_url);
                if ($res['errcode']) {
                    throw new APIException($res['errcode']);
                }
                $shop_id = $res['shop_id'];
                //检查对比url和shopid，没有则添加;有则修改
                $shop_info = model('AliShops')->getShopInfoByShopId($shop_id);
                if (empty($shop_info)) {
                    //添加店铺表记录
                    $wj_shop_id = model('AliShops')->saveShopInfo($shop_id,$ali_shop_url);
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
                    if ($shop_info['shop_url'] && $shop_info['shop_url']!=$ali_shop_url) {
                        throw new APIException(30015);
                    }
                    $has_update = model('AliShops')->updateShopUrl($shop_id,$ali_shop_url);
                    if (!$has_update) {
                        throw new APIException(30010);
                    }
                    $service_info = model('ShopServices')->getServicesExpenseByShopId($wj_shop_id,session('manager_id'));
                }
            }
        }elseif (preg_match('/shop\d+\.taobao/', $shop_url)){
            $shop_id = preg_replace('/.+shop(\d+).+/','\1',$shop_url);
            //根据shopid查表
            $shop_info = model('AliShops')->getShopInfoByShopId($shop_id);
            $ali_shop_url = "https://shop".$shop_id.'.m.taobao.com';
            if (empty($shop_info)) {
                //抓取店铺信息,调用服务
                $wei_bao = new WeiBaoData();
                $res = $wei_bao->getShopDataByUrl($ali_shop_url);
                if ($res['errcode']) {
                    throw new APIException($res['errcode']);
                }
                $user_id = $res['user_id'];
                $ali_shop_url='https://shop.m.taobao.com/shop/shop_index.htm?user_id='.$user_id;

                //添加店铺表记录
                $wj_shop_id = model('AliShops')->saveShopInfo($shop_id,$ali_shop_url);
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
                $service_info =model('ShopServices')->getServicesExpenseByShopId($wj_shop_id,session('manager_id'));
            }
        }else{
            throw new APIException(30008);
        }

        //校验服务，防止重复购买
        if (!empty($service_info) && ($service_info['service_end_time'] - time()>3*60*60*24)){
            throw new APIException(30020);
        }

        //发起微信或支付宝支付，微信则取得链接，生成二维码，支付宝则取得链接
        $expense_model = new ExpenseSN();
        $expense_num = $expense_model->getSN();

        if ($payment_method==1) { //微信
            //发起支付
            $wxPay = new WxPay;
            $result = $wxPay->wxPay([
                'body' => '微跳-购买服务',
                'attach' => '微跳-购买服务',
                'out_trade_no' => $expense_num,
                'total_fee' => $payment_amount*100,//订单金额，单位为分，如果你的订单是100元那么此处应该为 100*100
                'time_start' => date("YmdHis"),//交易开始时间
                'time_expire' => date("YmdHis", time() + 604800),//一周过期
                'goods_tag' => '购买服务',
                'notify_url' => request()->domain().'/index/wt_service/WeixinNotify',
                'trade_type' => 'NATIVE',
                'product_id' => rand(1,999999),
            ]);
            
            if(!$result['code']){
                throw new APIException(30019, ['msg'=>$result['msg']]);
                // return $this->error($result['msg']);
            }else{
                //生成微信支付二维码
                $QRCode = new QRCode;
                $response_data = base64_encode($QRCode->createQRCodeImg($result['msg']));
                
                /*vendor('wxpay.phpqrcode');
                $response_data =\QRcode::png(urldecode($result['msg']));*/
            }
        }elseif ($payment_method==2) { //支付宝
            //发起支付
            $aliPay = new AliPay;
            $result = $aliPay->alipay([
                'notify_url' => request()->domain().'/index/wt_service/AlipayNotifyUrl',
                'return_url' => request()->domain().'/index/wt_service/AlipayReturnUrl',
                'out_trade_no' => $expense_num,
                'subject' => "微跳-购买服务",
                'total_fee' => $payment_amount,//订单金额，单位为元
                'body' => "微跳",
            ]);
            if(!$result['code']){
                throw new APIException(30019, ['msg'=>$result['msg']]);
                // return $this->error($result['msg']);
            }else{
                //生成支付宝支付链接
                $response_data = 'https://mapi.alipay.com/gateway.do?'.$result['msg'];
            }
        }
        //返回支付页面所需参数,微信则微信二维码，支付宝则支付宝
        if (!$response_data){
            throw new APIException(30019);
        }
        Db::startTrans();
        try{
            //生成或修改服务记录
            if (empty($service_info)) {
                //短链接
                $o = new ShortUrl($wj_shop_id,session('manager_id'));
                $shop_url_str = $o->getSN();
                //查询短链接是否存在
                $i=1;
                $res = model('ShopServices')->ExistShortUrl($shop_url_str);
                while (!empty($res) || strlen($shop_url_str)!=6) {
                    //shop_id+manager_id+time()
                    $o = new ShortUrl($wj_shop_id,session('manager_id'),time());
                    $shop_url_str = $o->getSN();
                    //查询短链接是否存在
                    $res = model('ShopServices')->ExistShortUrl($shop_url_str);
                    if ($i>100) {
                        throw new APIException(30010);
                    }
                    $i++;
                }

                $service_id = model('ShopServices')->addServices(session('manager_id'),$wj_shop_id,$shop_url_str,$shop_name,$shop_url);
                if (!$service_id) {
                    throw new APIException(30010);
                }
            }else{
                //更新服务信息
                $service_id = $service_info['id'];
                $has_update = model('ShopServices')->updateShopNameUrl($service_id,$shop_name,$shop_url);
                if (!$has_update) {
                    throw new APIException(30010);
                }
            }
            //增加消费记录
            $expense_id = model('ExpenseRecords')->addExpense($expense_num,$payment_method,$service_id,session('manager_id'),$payment_amount ,$service_start_time,$service_end_time,0);
            Db::commit();
        } catch(\Exception $e){
            Db::rollback();
            throw new APIException(30019);
        }
        $res = array('expense_num'=>$expense_num,'shop_url'=>$shop_url,'shop_name'=>$shop_name,'payment_amount'=>$payment_amount,'service_start_time'=>$service_start_time,'service_end_time'=>$service_end_time,'pay_data'=>$response_data);
        return $this->format_ret($res);
    }

    /**
     * [renewalShopService description]
     * @return [type] [description]
     */
    public function renewalShopService()
    {
        $service_id = noempty_input('service_id','/\d+/');
        $service_start_time = noempty_input('service_start_time');
        $service_time = noempty_input('service_time','/\d+/');
        $_payment_amount = config('service_cost');//每年支付费用
        $payment_amount = $service_time * $_payment_amount;
        $payment_method = noempty_input('payment_method','/\d/');//1-微信，2-支付宝
        
        //服务时长
        if ($service_time>5 || $service_time<1) {
            throw new APIException(30016);
        }

        //支付方式
        if ($payment_method!=1 &&$payment_method!=2) {
            throw new APIException(30018);
        }

        //服务开始时间，格式yyyy-mm-dd,换算为time
        $_service_start_time =explode('-', $service_start_time);
        $_year = $_service_start_time[0];
        $_month = $_service_start_time[1];
        $_day = $_service_start_time[2];

        if (!$_year || !$_month || !$_day){
            throw new APIException(30017);
        }
        // $service_start_time = mktime(hour, minute, second, month, day, year);
        $service_start_time = mktime(0, 0, 0, $_month, $_day, $_year);
        $service_end_time = mktime(23, 59, 59, $_month, $_day, $_year+$service_time);
        if ($_year <date("Y") || $_month>12 ||$_day>31 ||time() - $service_start_time>24*60*60) {
            throw new APIException(30017);
        }

        $service_info = model('ShopServices')->getServicesById($service_id);
        if (empty($service_info)) {
            throw new APIException(30021);
        }
        if ($service_info['service_end_time'] >time()) { //还未过期
            //续费开始时间比之前结束时间多出至少一天
            if ($service_start_time - $service_info['service_end_time'] > 24*60*60) {
                throw new APIException(30022);
            }elseif ($service_end_time - $service_info['service_end_time'] < 364*24*60*60) { //续费结束时间和当前服务结束时间相差不到1年，表示重复付费
                throw new APIException(30022);
            }
        }

        //发起微信或支付宝支付，微信则取得链接，生成二维码，支付宝则取得链接
        $expense_model = new ExpenseSN();
        $expense_num = $expense_model->getSN();

        if ($payment_method==1) { //微信
            //发起支付
            $wxPay = new WxPay;
            $result = $wxPay->wxPay([
                'body' => '微跳-服务续费',
                'attach' => '微跳-服务续费',
                'out_trade_no' => $expense_num,
                'total_fee' => $payment_amount*100,//订单金额，单位为分，如果你的订单是100元那么此处应该为 100*100
                'time_start' => date("YmdHis"),//交易开始时间
                'time_expire' => date("YmdHis", time() + 604800),//一周过期
                'goods_tag' => '服务续费',
                'notify_url' => request()->domain().'/index/wt_service/WeixinNotify',
                'trade_type' => 'NATIVE',
                'product_id' => rand(1,999999),
            ]);
            
            if(!$result['code']){
                throw new APIException(30019, ['msg'=>$result['msg']]);
                // return $this->error($result['msg']);
            }else{
                //生成微信支付二维码
                $QRCode = new QRCode;
                $response_data = base64_encode($QRCode->createQRCodeImg($result['msg']));
                
                /*vendor('wxpay.phpqrcode');
                $response_data =\QRcode::png(urldecode($result['msg']));*/
            }
        }elseif ($payment_method==2) { //支付宝
            //发起支付
            $aliPay = new AliPay;
            $result = $aliPay->alipay([
                'notify_url' => request()->domain().'/index/wt_service/AlipayNotifyUrl',
                'return_url' => request()->domain().'/index/wt_service/AlipayReturnUrl',
                'out_trade_no' => $expense_num,
                'subject' => "微跳-服务续费",
                'total_fee' => $payment_amount,//订单金额，单位为元
                'body' => "微跳",
            ]);
            if(!$result['code']){
                throw new APIException(30019, ['msg'=>$result['msg']]);
                // return $this->error($result['msg']);
            }else{
                //生成支付宝链接
                $response_data = 'https://mapi.alipay.com/gateway.do?'.$result['msg'];
            }
        }
        if (!$response_data){
            throw new APIException(30019);
        }
        Db::startTrans();
        try{
            //无须更新服务信息
            $service_id = $service_info['id'];
            //增加消费记录
            $expense_id = model('ExpenseRecords')->addExpense($expense_num,$payment_method,$service_id,session('manager_id'),$payment_amount ,$service_start_time,$service_end_time,0);
            Db::commit();
        } catch(\Exception $e){
            Db::rollback();
            throw new APIException(30019);
        }

        //返回支付页面所需参数,微信则微信二维码，支付宝则支付宝链接
        $res = array('expense_num'=>$expense_num,'shop_url'=>$service_info['shop_url'],'shop_name'=>$service_info['shop_name'],'payment_amount'=>$payment_amount,'service_start_time'=>$service_start_time,'service_end_time'=>$service_end_time,'pay_data'=>$response_data);
        return $this->format_ret($res);
    }

    /**
     * [getShopService description]
     * @return [type] [description]
     */
    public function getShopService()
    {
        $page_index = input('param.page_index') ? intval(input('param.page_index')):1;
        $page_size = input('param.page_size') ? intval(input('param.page_size')):10;
        $service_type = input('param.service_type') ? intval(input('param.service_type')):0;//0-全部，1-已过期，2-未开始，3-未过期

        $service_list = model('ShopServices')->getServicesByManagerId(session('manager_id'),$page_index, $page_size,$service_type);
        return $this->format_ret($service_list);
    }

    /**
     * 更新店铺数据
     * @return [type] [description]
     */
    public function updateShopApi()
    {
        set_time_limit(0);
        $shop_list = model('AliShops')->getAllShop();
        $log = '';
        if (!empty($shop_list)) {
            foreach ($shop_list as $k => $v) {
                //抓取店铺信息,调用服务
                $wei_bao = new WeiBaoData();
                $res = $wei_bao->getShopDataByUrl($v['shop_url']);
                if ($res['errcode']) {
                    $log .="id为".$v['id']."，链接为（".$v['shop_url'].")的店铺获取数据失败，保留原数据；异常编号:".$res['errcode']."\n\n";
                    continue;
                }
                if ($res['shop_id'] != $v['ali_shop_id']) {
                    $log .="链接为（".$v['shop_url'].")的店铺更新数据失败，保留原数据；异常原因:原始id：".$v['ali_shop_id']."，现id：".$res['shop_id']."\n\n";
                    continue;
                }
                Db::startTrans();
                try{
                    $has_delete =  model('ShopApi')->softDeleteShopDataByShopId($v['id']);
                    if (!$has_delete) {
                        $has_data = model('ShopApi')->getShopDataByShopId($v['id']);
                        if (!empty($has_data)) {
                            throw new \Exception('删除店铺'.$v['id'].'api数据失败', 1);
                        }
                    }

                    //添加店铺数据记录
                    $shop_data = $res['shop_data'];
                    $flag_error=0;
                    foreach ($shop_data as $k1 => $v1) {
                        $has_add = model('ShopApi')->saveShopData($v['id'],$v1['api_url'],$v1['api_data']);
                        if (!$has_add) {
                            $flag_error=1;
                            break;
                        }
                    }
                    if ($flag_error) {
                        Db::rollback();
                        throw new \Exception('店铺'.$v['id'].'添加api数据失败', 1);
                    }else{
                        Db::commit();
                    }
                }catch(\Exception $e){
                    $log .= $e."\n\n";
                    continue;
                }
                if (!$k%100) {
                    usleep(10000);
                }
            }
            $log = $log ? $log : '成功更新所有店铺数据';
            $file = 'new_cron_log/'.date("Ymd").'_update_shop_api_log.txt';
            $content = date("Y-m-d H:i:s")."\n\n**********************更新店铺数据'**************************\n\n".$log."\n\n";
            Log::write($content, $file);
        }
    }
    
    // ******************************************************************************支付相关************************************************************************
    /**
     * 微信订单异步通知
     */
    public function WeixinNotify()
    {
        $notify_data = file_get_contents("php://input");//获取由微信传来的数据
        if(!$notify_data){
            $notify_data = $GLOBALS['HTTP_RAW_POST_DATA'] ?: '';//以防上面函数获取到的内容为空
        }
        if(!$notify_data){
            exit('校验失败');
        }
        $wxPay = new WxPay;
        $wxPay->_weixin_config();
        $doc = new \DOMDocument();
        $doc->loadXML($notify_data);
        $out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
        $transaction_id = $doc->getElementsByTagName("transaction_id")->item(0)->nodeValue;
        $openid = $doc->getElementsByTagName("openid")->item(0)->nodeValue;
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $result = \WxPayApi::orderQuery($input);
        if(array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && array_key_exists("trade_state", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS" && $result["trade_state"] == "SUCCESS")
        {
            $total_fee = $result['total_fee'] * 0.01;
            
            $res = $this->updateServiceExpense($out_trade_no,$transaction_id,$total_fee,1);
            if ($res['code']) {
                // 处理支付成功后的逻辑业务
                Log::init([
                    'type'  =>  'File',
                    'path'  =>  LOG_PATH.'../paylog/'
                ]);
                Log::write($result,'log');
                logResult("TRADE_FINISHED------notify_alipay Run Success");
                exit('支付成功');
            }else{
                exit(json($res));
            }
        }else{
            exit('支付失败');
        }
    }

    /**
     * 查询微信或支付宝订单结果
     * @return \think\response\Json
     */
    public function queryOrderStatus()
    {
        $expense_num = noempty_input('expense_num','/\d+/');
        //查询数据库
        $expense_info = model('ExpenseRecords')->getRecordsByExpenseNum($expense_num);
        if ($expense_info['trade_status']) {
            return $this->format_ret(array('code'=>1));
        }else{
            $payment_method = $expense_info['payment_method'];
            if ($payment_method==1) {
                // 主动查询支付结果
                vendor('alipay.alipay');
                $wxPay = new WxPay;
                $result = $wxPay->queryOrder($expense_num);
                if ($result['code']==1) {
                    $total_fee = $result['data']['total_fee'] * 0.01;
                    $transaction_id = $result['data']['transaction_id'] ;
                    $res = $this->updateServiceExpense($expense_num,$transaction_id,$total_fee,1);
                    
                    return $this->format_ret($res);
                }else{
                    return $this->format_ret($result);
                }
            }else{
                return $this->format_ret(array('code'=>0));
            }

        }
    }

    /**
     * 支付宝支付结果通知
     * @return [type] [description]
     */
    public function AlipayNotifyUrl()
    {
        $aliPay = new AliPay;

        //异步订单结果通知
        $config = $aliPay::$alipay_config;
        vendor('alipay.alipay');
        $alipayNotify = new \AlipayNotify($config);
        $verify_result = $alipayNotify->verifyNotify();
        logResult("outside------notify_alipay Run Success------verify_result = ".serialize($verify_result));
        if($verify_result) {//验证成功
            $out_trade_no = $_POST['out_trade_no'];//商户订单号
            $trade_no = $_POST['trade_no'];//支付宝交易号
            $trade_status = $_POST['trade_status']; //交易状态
            $total_fee = $_POST['total_fee'];//交易金额
            $seller_id = $_POST['seller_id'];//支付宝partner

            if($trade_status == 'TRADE_FINISHED') {
                $res = $this->updateServiceExpense($out_trade_no,$trade_no,$total_fee,$seller_id==$config['seller_id']);
                if ($res) {
                    logResult("TRADE_FINISHED------notify_alipay Run Success");
                }
            }elseif ($trade_status == 'TRADE_SUCCESS') {
                $res = $this->updateServiceExpense($out_trade_no,$trade_no,$total_fee,$seller_id==$config['seller_id']);
                if ($res) {
                    logResult("TRADE_FINISHED------notify_alipay Run Success");
                }
            }
            echo "success";  
        }else {
            //验证失败
            echo "fail";
            //写文本函数记录程序运行情况是否正常
            logResult("fail------notify_alipay Run Success ");
        }
    }
    
    /**
     * 支付宝支付结果通知 return_url
     * @return [type] [<description>]
     */
    public function AlipayReturnUrl()
    {
        $aliPay = new AliPay;
        $config = $aliPay::$alipay_config;
        vendor('alipay.alipay');
        $alipayNotify = new \AlipayNotify($config);
        $verify_result = $alipayNotify->verifyReturn();
        if($verify_result) { //验证成功
            $out_trade_no = $_GET['out_trade_no'];//商户订单号
            $trade_no = $_GET['trade_no'];//支付宝交易号
            $trade_status = $_GET['trade_status']; //交易状态
            $total_fee = $_GET['total_fee'];//交易金额
            $seller_id = $_GET['seller_id'];//支付宝partner

            if($trade_status == 'TRADE_FINISHED') {
                $res = $this->updateServiceExpense($out_trade_no,$trade_no,$total_fee,$seller_id==$config['seller_id']);
                if ($res) {
                    logResult("TRADE_FINISHED------notify_alipay Run Success");
                }
            }elseif ($trade_status == 'TRADE_SUCCESS') {
                $res = $this->updateServiceExpense($out_trade_no,$trade_no,$total_fee,$seller_id==$config['seller_id']);
                if ($res) {
                    logResult("TRADE_FINISHED------notify_alipay Run Success");
                }
            }
            $url = $_SERVER['HTTP_HOST'] . '/frontend/html/service.html';
            echo $url;
            header($url);
            exit;
        }else{
            echo '<meta charset="utf-8" /><div style="text-align: center;"><div style="font-size: x-large; margin-top: 30px;">验证失败！</div></div>';
        }
    }

    // *******************************************************************公有函数*****************************************************************************

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
        catch(\Exception $e)
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
            catch(\Exception $e)
            {
                throw new APIException(30003);
            }
		    if (!is_array($url_header)) {
	        	throw new APIException(30002);
		    }
		}
	    if(!in_array('HTTP/1.1 200 OK',$url_header)){
        	throw new APIException(30003);
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
    private function manageServiceInfo($service_info='',$qrcode_url='',$wj_shop_id=0)
    {
        $service_type =0; //服务类型：1-体验3天，2-已购买，3-服务未开始,4-已过期
        if (!$wj_shop_id) {
            throw new APIException(30010);
        }
        $img ='';
        if (empty($service_info)){
            //新增体验服务
            $experience_days = config('experience_days');
            $time_start = time();
            $time_end = strtotime("+".$experience_days." day");

            //mc 改用shop_id+manager_id
            $o = new ShortUrl($wj_shop_id,session('manager_id'));
            $shop_url_str = $o->getSN();
            //查询短链接是否存在
            $i=1;
            $res = model('ShopServices')->ExistShortUrl($shop_url_str);
            while (!empty($res) || strlen($shop_url_str)!=6) {
                //mc 改用shop_id+manager_id
                $o = new ShortUrl($wj_shop_id,session('manager_id'),time());
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
            $has_add = model('ExpenseRecords')->addExpense($expense_num, 0,$service_id,session('manager_id'),0,$time_start,$time_end,1);
            $service_type =1;
        }

        if ($service_info['service_start_time']<=time() && $service_info['service_end_time']>=time()) { //服务未过期
            //体验期内
            $service_type =2;
            if ($service_info['service_end_time'] - $service_info['service_start_time']== 259200) {
                $service_type =1;
            }
            //设置路由，获取链接，生成二维码
            //mc 路由映射短链
            $qrcode_url = $qrcode_url?$qrcode_url:'/'.$service_info['transformed_url'];
            $qrcode_url = 'http://'.$_SERVER['HTTP_HOST'].$qrcode_url;
            //二维码
            $QRCode = new QRCode;
            $img = base64_encode($QRCode->createQRCodeImg($qrcode_url));
        }elseif ($service_info['service_start_time']>time()) { //服务未开始
            $qrcode_url='';
            $service_type =3;
        }else {//已过期
            $qrcode_url='';
            $service_type =4;
        }
        $res_data =array('service_id'=>$service_info['id'],'service_start_time'=>$service_info['service_start_time'],'service_end_time'=>$service_info['service_end_time'],'service_type'=>$service_type,'qrcode_url'=>$qrcode_url,'qrcode_img'=>$img);
        return $res_data;
    }

    /**
     * [updateServiceExpense description]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    private function updateServiceExpense($expense_num='',$trade_num='',$actually_amount=0,$trade_status=0)
    {
        $expense_info = model('ExpenseRecords')->getRecordsByExpenseNum($expense_num);
        if(empty($expense_info)){
            logResult($expense_num."没有对应的消费记录");
            return array('code'=>0,'msg'=>'没有对应的消费记录');
        }
        //判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id
        if ($expense_info['payment_amount']!=$actually_amount) {
            logResult($expense_num."实际支付金额不对：消费记录金额".$expense_info['payment_amount']."，实际支付金额".$actually_amount);
            return array('code'=>0,'msg'=>'实际支付金额不对');
        }

        $has_update = model('ExpenseRecords')->updateExpense($expense_num,$trade_num,$actually_amount,$trade_status);
        if ($has_update) {
            $service_info = model('ShopServices')->getServicesById($expense_info['service_id']);
            $service_start_time = $expense_info['service_start_time'];
            $service_end_time = $expense_info['service_end_time'];

            if ($service_start_time - $service_info['service_end_time'] <24*60*60) { //时间不间断
                $service_start_time = $service_info['service_start_time'];
            }
            $has_update = model('ShopServices')->updateShopServiceTime($expense_info['service_id'] , $service_start_time ,$service_end_time);
            return array('code'=>1);
        }else{
            return array('code'=>0,'msg'=>'更新消费记录失败');
        }
    }
}
