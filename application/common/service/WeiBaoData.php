<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Filename: WeiBaoData.php
 * | Description: 调取天猫淘宝数据服务
 * +----------------------------------------------------------------------
 * | Created by equinox at 2017-06-15 13:16
 * | Email: equinox@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */
 
namespace app\common\service;
use api\APIException;

class WeiBaoData {
	
    /**
     * 接收并处理淘宝、天猫等链接
     * 淘宝搜索商品结果列表：https://s.m.taobao.com/h5?q=%E5%A4%B9%E5%85%8B
     * 天猫商品详情：https://detail.m.tmall.com/item.htm?ft=t&id=544094926521
     * @param  integer $isTm          [天猫网址]
     * @param  integer $itemId        [商品id]
     * @param  integer $is_check_shop [只是确认店铺是否付费]
     * @return [type]                 [description]
     */
    public function processUrl($isTm=0,$itemId=0,$is_check_shop=1)
    {
    	if (!$itemId) {
            throw new APIException(10001);
    	}
        vendor('simple_html_dom.simple_html_dom');
        set_time_limit(0); 
        header("Connection:Keep-Alive");
        header("Proxy-Connection:Keep-Alive");
		$arr = array();
        if ($this->request->method() == 'GET') 
        {
        	if($isTm){
//      		$arr['price']=$_GET['price'];
//				$arr['sold']=$_GET['sold'];
//				$arr['area']=$_GET['area'];
				$arr['isTm']=$isTm;
				$url='https://detail.m.tmall.com/item.htm?abtest=_AB-LR90-PR90&pos=1&abbucket=_AB-M90_B17&acm=03080.1003.1.1287876&id='.$itemId.'&scm=1007.12913.42100.100200300000000';
	           	$html =file_get_html($url);
				$assessFlag='https://rate.tmall.com/listTagClouds.htm?itemId='.$itemId;
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
                
				session('shopUrl',$arr['shopUrl']);
				//mc 加上校验
				$arr['delPrice']=$html->find('section#s-price',0)->find('span.mui-price',0)->find('span.mui-price-integer',0)->innertext;
           		return $this->fetch('tm_commodity_detail',array('data' => json_encode($arr)));
        	}else{
        		/*$url='https://acs.m.taobao.com/h5/mtop.taobao.detail.getdetail/6.0/?appKey=12574478&t=1489817645812&sign=c6259cd8b4facd409f04f6878e84ebce&api=mtop.taobao.detail.getdetail&v=6.0&ttid=2016%40taobao_h5_2.0.0&isSec=0&ecode=0&AntiFlood=true&AntiCreep=true&H5Request=true&type=jsonp&dataType=jsonp&data=%7B%22exParams%22%3A%22%7B%5C%22id%5C%22%3A%5C%22521783759898%5C%22%2C%5C%22abtest%5C%22%3A%5C%227%5C%22%2C%5C%22rn%5C%22%3A%5C%22581759dfb5263dad588544aa4ddfc465%5C%22%2C%5C%22sid%5C%22%3A%5C%223f8aaa3191e5bf84a626a5038ed48083%5C%22%7D%22%2C%22itemNumId%22%3A%22'.$itemId.'%22%7D';
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
}