<?php
/**
 * TOP API: tmall.service.code.consume request
 * 
 * @author auto create
 * @since 1.0, 2016.07.19
 */
class TmallServiceCodeConsumeRequest
{
	/** 
	 * 核销码
	 **/
	private $consumeCode;
	
	/** 
	 * 核销帐号
	 **/
	private $operatorNick;
	
	/** 
	 * 门店id
	 **/
	private $shopId;
	
	private $apiParas = array();
	
	public function setConsumeCode($consumeCode)
	{
		$this->consumeCode = $consumeCode;
		$this->apiParas["consume_code"] = $consumeCode;
	}

	public function getConsumeCode()
	{
		return $this->consumeCode;
	}

	public function setOperatorNick($operatorNick)
	{
		$this->operatorNick = $operatorNick;
		$this->apiParas["operator_nick"] = $operatorNick;
	}

	public function getOperatorNick()
	{
		return $this->operatorNick;
	}

	public function setShopId($shopId)
	{
		$this->shopId = $shopId;
		$this->apiParas["shop_id"] = $shopId;
	}

	public function getShopId()
	{
		return $this->shopId;
	}

	public function getApiMethodName()
	{
		return "tmall.service.code.consume";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
		RequestCheckUtil::checkNotNull($this->consumeCode,"consumeCode");
		RequestCheckUtil::checkNotNull($this->operatorNick,"operatorNick");
		RequestCheckUtil::checkNotNull($this->shopId,"shopId");
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
