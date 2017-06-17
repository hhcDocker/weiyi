<?php
/**
 * TOP API: tmall.servicecenter.anomalyrecourse.querybyid request
 * 
 * @author auto create
 * @since 1.0, 2016.08.11
 */
class TmallServicecenterAnomalyrecourseQuerybyidRequest
{
	/** 
	 * 一键求助的id
	 **/
	private $anomalyRecourseId;
	
	private $apiParas = array();
	
	public function setAnomalyRecourseId($anomalyRecourseId)
	{
		$this->anomalyRecourseId = $anomalyRecourseId;
		$this->apiParas["anomaly_recourse_id"] = $anomalyRecourseId;
	}

	public function getAnomalyRecourseId()
	{
		return $this->anomalyRecourseId;
	}

	public function getApiMethodName()
	{
		return "tmall.servicecenter.anomalyrecourse.querybyid";
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
