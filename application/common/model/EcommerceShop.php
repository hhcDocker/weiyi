<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: EcommerceShop.php
 * | Description: 电商以及电商管理员所有操作的model类
 * +----------------------------------------------------------------------
 * | Created by equinoxsun at 2017-08-04 11:49
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Db;

class EcommerceShop extends Base
{    
    public function getEcommerceManagerByMobile($mobilephone='')
	{
        $db1 = db('ecommerce_managers', config('_database1'), true);
        $result = $db1
					  ->where('mobilephone', $mobilephone)
					  ->where('delete_time', null)
					  ->find();	
		return $result?$result:array();
	}

    public function getEcommerceManager($mobilephone='',$password='')
	{
        if (!$mobilephone || !$password) {
            return array();
        }

		$password = md5($password);
        $db1 = db('ecommerce_managers', config('_database1'), true);
        $result = $db1
					->where('mobilephone', $mobilephone)
                	->where('password', $password)
					->where('delete_time', null)
					->find();	
		return $result?$result:array();
	}

    /**
     * 修改用户password
     * @return [type] [description]
     */
    public function updateEcommerceManagerPassword($id, $password = '')
    {
        $result = 0;
        if ($id && $password) {
            $password_md5 = md5($password);

            $db1 = db('ecommerce_managers', config('_database1'), true);
            $result = $db1
                ->where('id', $id)
                ->update(array('password' => $password_md5, 'update_time' => ['exp', 'now()']));
        }
        return $result;
    }

    /**
     * 通过微跳链接获取信息
     * @param  string $weitiao_link [description]
     * @return [type]               [description]
     */
    public function getLinkInfoByLink($weitiao_link='')
    {
        if (!$weitiao_link) {
            return array();
        }
        $link_info = Db::table('tp5_ecommerce_product_links')->where('review_status','审核通过')->where('weitiao_link',$weitiao_link)->where('delete_time',null)->find();
        return $link_info?$link_info:array();
    }
}
