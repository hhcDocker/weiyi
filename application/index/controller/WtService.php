<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: WtService.php
 * | Description: user login, logout, register, reset
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-06-13 14:57
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\index\controller;
use api\APIAuthController;
use api\APIException;
use think\Config;

class WtService extends APIAuthController
{
    /**
     * post请求
     * 首页转换网址,验证登录与否
     * 验证店铺，判断地址合法性，获取店铺地址（非店铺地址则爬取数据，得到店铺地址）
     * 查表获取该链接是否已购买服务，是否已过期
     * 如果从未购买，则生成体验记录，默认3天，生成服务记录，返回短链接
     * 返回值：链接二维码，短链接，有效期（不返回具体数据，只返回链接）
     * 暂时先验证，之后再迁移到账号体系，index模块下，改前后分离
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
        $checkUrl = $this->checkUrl($url,0);

        //mc 判断是否登录
        if (!session('manager_id')) {
            
        }
        if (strpos($url,'tmall.com')) { //天猫
            if (strpos($url, 'tmall.com/shop')) { //天猫店铺
                if (!strpos($url, '.m.')) { //PC转移动端
                    $url = preg_replace('/.+(\w+).tmall.com\/shop\/view_shop\.htm.+/','$1'.'.m.tmall.com',$url);
                }
                $service_info = $this->getShopShortUrlInfo($url);
                return $this->format_ret($service_info);
            }else{
                //待定
                // return array('code'=>0,'msg'=>'非天猫店铺网址');
                dump(array('code'=>0,'msg'=>'非天猫店铺网址'));
            }
        }elseif (strpos($url, 'taobao')) { //淘宝
            if (preg_match('/shop\d+\.taobao/', $url)){ //淘宝店铺pc端
                $url = str_replace('taobao.com', 'm.taobao.com', $url);
                $service_info = $this->getShopShortUrlInfo($url);
                return $this->format_ret($service_info);
            }elseif (preg_match('/shop\d+\.m\.taobao/', $url)) { //淘宝店铺移动端
                $service_info = $this->getShopShortUrlInfo($url);
                return $this->format_ret($service_info);
            }else{
                //待定
                // return array('code'=>0,'msg'=>'非淘宝店铺网址');
                dump(array('code'=>0,'msg'=>'非淘宝店铺网址'));
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
    }

    /**
     * 检查客户是否已购买服务或者体验，没有记录则新增记录，体验三天
     * @param  [type] $url [description]
     * @return [type]      [description]
     */
    private function getShopShortUrlInfo($url='')
    {
        $url = strtolower($url);
    	$this->checkUrl($url,1);
        //mc
        session('manager_id',1);
        $service_info = model('ShopServices')->getServicesByShopUrl($url,session('manager_id'));
        //没有服务则表示体验
        if (empty($service_info)) {
            $experience_time = config('ExperienceTime');
            $time_start = time();
            $time_end = strtotime("+".$experience_time." day");
            //mc 改用tp路由
            $o = new ShortUrl($url);
            $shop_url_str = $o->getSN();
            //mc
            $service_id = model('ShopServices')->saveServices(session('manager_id'),$url,$shop_url_str,$time_start,$time_end);
            $service_info = model('ShopServices')->getServicesByShopUrl($url,session('manager_id'));
            //添加消费记录，体验3天
            $expense_model = new ExpenseSN();
            $expense_num = $expense_model->getSN();
            $has_add = model('ExpenseRecords')->addExpense($expense_num, 0,'',$service_id,session('manager_id'),0,$time_start,$time_end,1);
        }
        return $service_info;
    }
}