<?php
/**
 * TOP API: tmall.servicecenter.anomalyrecourse.remark.update request
 * 
 * @author auto create
 * @since 1.0, 2016.07.25
 */
class TmallServicecenterAnomalyrecourseRemarkUpdateRequest
{
	/** 
	 * 需要更新的一键求助单id
	 **/
	private $id;
	
	/** 
	 * 需要更新的服务商备注
	 **/
	private $remark;
	
	private $apiParas = array();
	
	public function setId($id)
	{
		$this->id = $id;
		$this->apiParas["id"] = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function setRemark($remark)
	{
		$this->remark = $remark;
		$this->apiParas["remark"] = $remark;
	}

	public function getRemark()
	{
		return $this->remark;
	}

	public function getApiMethodName()
	{
		return "tmall.servicecenter.anomalyrecourse.remark.update";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function check()
	{
		
	}
	
	public function putOtherTextParam($key, $value) {
		$this->apiParas[$key] = $value;
		$this->$key = $value;
	}
}
