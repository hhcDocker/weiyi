<?php

/**
 * dataModule
 * @author auto create
 */
class SettleAdjustmentResponse
{
	
	/** 
	 * bizOrderId
	 **/
	public $biz_order_id;
	
	/** 
	 * comments
	 **/
	public $comments;
	
	/** 
	 * 费用，单位分
	 **/
	public $cost;
	
	/** 
	 * gmtCreate
	 **/
	public $create_time;
	
	/** 
	 * description
	 **/
	public $description;
	
	/** 
	 * id
	 **/
	public $id;
	
	/** 
	 * gmtModified
	 **/
	public $modified_time;
	
	/** 
	 * parentBizOrderId
	 **/
	public $parent_biz_order_id;
	
	/** 
	 * pictureUrls
	 **/
	public $picture_urls;
	
	/** 
	 * priceFactors
	 **/
	public $price_factors;
	
	/** 
	 * serviceCode
	 **/
	public $service_code;
	
	/** 
	 * serviceOrderId
	 **/
	public $service_order_id;
	
	/** 
	 * 调整单态。 待商家确认:1, 商家已确认:2,  待小二判定:3,  小二判定有效:4,  小二判定无效:5,  小二无法判定:6, 服务商取消:7, 超时确认:8, 完成:9
	 **/
	public $status;
	
	/** 
	 * type
	 **/
	public $type;
	
	/** 
	 * workcardId
	 **/
	public $workcard_id;	
}
?>