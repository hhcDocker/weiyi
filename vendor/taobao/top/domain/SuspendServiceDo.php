<?php

/**
 * 入参对象
 * @author auto create
 */
class SuspendServiceDo
{
	
	/** 
	 * 买家user_id
	 **/
	public $buyer_id;
	
	/** 
	 * 无需安装的原因
	 **/
	public $comments;
	
	/** 
	 * 扩展字段
	 **/
	public $extension;
	
	/** 
	 * 工单类型，固定值1
	 **/
	public $type;
	
	/** 
	 * 更新时间得long值
	 **/
	public $update_date;
	
	/** 
	 * api调用者
	 **/
	public $updater;
	
	/** 
	 * 工单id
	 **/
	public $workcard_id;	
}
?>