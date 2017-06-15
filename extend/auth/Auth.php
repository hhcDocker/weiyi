<?php
/**
 * +----------------------------------------------------------------------
 * | Copyright (C) 2016 深圳市紫雷科技有限公司 All rights reserved.
 * +----------------------------------------------------------------------
 * | Internal procedure is strictly prohibited.
 * +----------------------------------------------------------------------
 * | Filename: Auth.php
 * +----------------------------------------------------------------------
 * | Created by peteyhuang at 2016-10-01 08:00 
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Updated by peteyhuang at 2016-10-10 15:09
 * | Email: peteyhuang@purplethunder.cn
 * +----------------------------------------------------------------------
 * | Version 1.0
 * +----------------------------------------------------------------------
 */

namespace auth;
use think\Db;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。 当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(think_auth_group_access表 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(think_auth_group 定义了用户组权限)
 *
 * 4，支持规则表达式。
 *      在think_auth_rule 表中定义一条规则时，如果type为1， condition字段就可以定义规则表达式。 如定义{score}>5  and {score}<100  表示用户的分数在5-100之间时这条规则才会通过。
 */

//数据库
/*
-- ----------------------------
-- think_auth_rule，规则表，
-- id:主键，name：规则唯一标识, title：规则中文名称 status 状态：为1正常，为0禁用，condition：规则表达式，为空表示存在就验证，不为空表示按照条件验证
-- ----------------------------
DROP TABLE IF EXISTS `think_auth_rule`;
CREATE TABLE `think_auth_rule` (
    `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
    `name` char(80) NOT NULL DEFAULT '',
    `title` char(20) NOT NULL DEFAULT '',
    `type` tinyint(1) NOT NULL DEFAULT '1',
    `status` tinyint(1) NOT NULL DEFAULT '1',
    `condition` char(100) NOT NULL DEFAULT '',  # 规则附件条件,满足附加条件的规则,才认为是有效的规则
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
-- ----------------------------
-- think_auth_group 用户组表，
-- id：主键， title:用户组中文名称， rules：用户组拥有的规则id， 多个规则","隔开，status 状态：为1正常，为0禁用
-- ----------------------------
DROP TABLE IF EXISTS `think_auth_group`;
CREATE TABLE `think_auth_group` (
    `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
    `title` char(100) NOT NULL DEFAULT '',
    `status` tinyint(1) NOT NULL DEFAULT '1',
    `rules` char(80) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
-- ----------------------------
-- think_auth_group_access 用户组明细表
-- uid:用户uid，group_id：用户组id
-- ----------------------------
DROP TABLE IF EXISTS `think_auth_group_access`;
CREATE TABLE `think_auth_group_access` (
    `uid` mediumint(8) unsigned NOT NULL,
    `group_id` mediumint(8) unsigned NOT NULL,
    UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
    KEY `uid` (`uid`),
    KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 */

class Auth
{

    //默认配置
    protected $_config = array(
        'auth_on'           => true, // 认证开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'auth_group_rule', // 用户组数据表名
        'auth_group_access' => 'auth_user_group', // 用户-用户组关系表
        'auth_rule'         => 'auth_rule_condition', // 权限规则表
        'auth_user1'        => 'bg_administrators', // 用户信息表
        'auth_user2'        => 'ecommerce_managers', // 用户信息表
        'auth_user3'        => 'beauty_managers', // 用户信息表
    );

    public function __construct()
    {
        $prefix                             =  '';//config('PREFIX');
        
        $this->_config['auth_group']        = $prefix . $this->_config['auth_group'];
        $this->_config['auth_rule']         = $prefix . $this->_config['auth_rule'];
        $this->_config['auth_group_access'] = $prefix . $this->_config['auth_group_access'];
        $this->_config['auth_user1']         = $prefix . $this->_config['auth_user1'];
        $this->_config['auth_user2']         = $prefix . $this->_config['auth_user2'];
        $this->_config['auth_user3']         = $prefix . $this->_config['auth_user3'];
        if (config('AUTH_CONFIG')) {
            //可设置配置项 AUTH_CONFIG, 此配置项为数组。
            $this->_config = array_merge($this->_config, config('AUTH_CONFIG'));
        }
    }

    /**
     * 检查权限
     * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param uid  char           认证用户的uid
     * @param string mode        执行check的模式
     * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     */
    public function check($name, $uid, $type = 1, $mode = 'url', $relation = 'or')
    {
        if (!$this->_config['auth_on']) {
            return true;
        }
        $user = $this->getUserInfo($uid); //获取用户信息,一维数组
        if ($user['islocked']) { // 锁定的用户没有操作权限
            return false;
        } else if ($user['role_id'] == 101 || $user['role_id'] == 301) { //超级管理员不用判断权限
            return true;
        }

        $authList = $this->getAuthList($uid, $type); //获取用户需要验证的所有有效规则列表

        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }

        $list = array(); //保存验证通过的规则名

        if ($mode == 'url') {
            $REQUEST = unserialize(strtolower(serialize($_REQUEST)));
        }

        foreach ($authList as $auth) {
            $query = preg_replace('/^.+\?/U', '', $auth);
            
            if ($mode == 'url' && $query != $auth) {
                parse_str($query, $param); //解析规则中的param
                $intersect = array_intersect_assoc($REQUEST, $param);
                $auth      = preg_replace('/\?.*$/U', '', $auth);
                if (in_array($auth, $name) && $intersect == $param) {
                    //如果节点相符且url参数满足
                    $list[] = $auth;
                }
            } else if (in_array($auth, $name)) {
                $list[] = $auth;
            }
        }

        if ($relation == 'or' and !empty($list)) {
            return true;
        }

        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }

        return false;
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  uid     char     用户uid
     * @return array   用户所属的用户组 array(
     *     array('uid'=>'用户uid', 'group_id'=>'用户组id', 'title'=>'用户组名称', 'rules'=>'用户组拥有的规则id,多个,号隔开'),
     *     ...)
     */
    public function getGroups($uid)
    {
        
        static $groups = array();
        if (isset($groups[$uid])) {
            return $groups[$uid];
        }

        $dgroups = db($this->_config['auth_group_access'] . ' a', config('_database'), true)
                  ->where("a.uid='$uid' and g.status='1'")
                  ->join($this->_config['auth_group'] . " g", " a.group_id=g.group_id")
                  ->field('uid, a.group_id, title, rules')
                  ->select();
        $groups[$uid] = $dgroups ?: array();
        return $groups[$uid];
    }

    /**
     * 获得权限列表
     * @param char    $uid   用户uid
     * @param int     $type  如果type为1，condition字段就可以定义规则表达式
     * @return array  用户所属的用户组 array(
     *     array('uid'=>'用户uid', 'group_id'=>'用户组id', 'title'=>'用户组名称', 'rules'=>'用户组拥有的规则id,多个,号隔开'),
     *     ...)
     */
    protected function getAuthList($uid, $type)
    {
        static $_authList = array(); //保存用户验证通过的权限列表
        $t = implode(',', (array)$type);
        if (isset($_authList[$uid.$t])) {
            return $_authList[$uid.$t];
        }
        
        if( $this->_config['auth_type'] == 2 && isset($_SESSION['_AUTH_LIST_' . $uid . $t])) {
            //如果是登录认证，则直接返回session中的规则列表
            return $_SESSION['_AUTH_LIST_' . $uid . $t];
        }

        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array(); //保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid . $t] = array();
            return array();
        }

        $map = array(
            'id'     => array('in', $ids),
            'type'   => $type,
            'status' => 1,
        );

        // 读取用户组所有权限规则
        $rules = db($this->_config['auth_rule'], config('_database'), true)
                 ->where($map)
                 ->field('condition, name')
                 ->select();

        // 循环规则，判断结果。
        $authList = array(); //
        foreach ($rules as $rule) {
            if (!empty($rule['condition'])) {
                //根据condition进行验证
                $user = $this->getUserInfo($uid); //获取用户信息,一维数组

                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                //dump($command);//debug
                @(eval('$condition=(' . $command . ');'));
                if ($condition) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                //只要存在就记录
                $authList[] = strtolower($rule['name']);
            }
        }

        $_authList[$uid . $t] = $authList;

        if ($this->_config['auth_type'] == 2) {
            //如果是登录认证，则将规则列表结果保存到session
            $_SESSION['_AUTH_LIST_' . $uid . $t] = $authList;
        }

        return array_unique($authList);
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    protected function getUserInfo($uid)
    {
        static $userinfo = array();
        if (!isset($userinfo[$uid])) {
            $userinfo[$uid] = db($this->_config['auth_user1'], config('_database'), true)->where('uid', $uid)->find();
            if(!$userinfo[$uid])
            {
                $userinfo[$uid] = db($this->_config['auth_user2'], config('_database'), true)->where('uid', $uid)->find();
            }
            if(!$userinfo[$uid])
            {
                $userinfo[$uid] = db($this->_config['auth_user3'], config('_database'), true)->where('uid', $uid)->find();
            }
        }
        return $userinfo[$uid];
    }
}
