<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016-2017 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: BeautyShop.php
 * | Description: 门店以及门店管理员所有操作的model类
 * +----------------------------------------------------------------------
 * | Created by equinoxsun at 2017-08-04 11:49
 * | Email: equinoxsun@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace app\common\model;
use think\Db;

class BeautyShop extends Base
{    
    public function getBeautyManagerInfoByMobile($mobilephone)
	{
        $db1 = db('beauty_managers', config('_database1'), true);
        $result = $db1
				  	->where('mobilephone', $mobilephone)
				  	->where('delete_time', null)
				  	->find();
		return $result?$result:array();
	}

    public function getBeautyManagerInfo($mobilephone='',$password='')
	{
        if (!$mobilephone || !$password) {
            return array();
        }
        $password = md5($password);
        $db1 = db('beauty_managers', config('_database1'), true);
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
	public function updateBeautyManagerPassword($id, $password = '')
	{
        $result = 0;
        if ($id && $password) {
            $password_md5 = md5($password);
            $result = Db::table('tp5_beauty_managers')->where('id', $id)->update(array('password' => $password_md5, 'update_time' => ['exp', 'now()']));
        }
        return $result;
	}
}
