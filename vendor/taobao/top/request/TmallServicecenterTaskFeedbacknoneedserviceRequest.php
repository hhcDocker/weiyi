<?php
/**
 * TOP API: tmall.servicecenter.task.feedbacknoneedservice request
 * 
 * @author auto create
 * @since 1.0, 2016.08.12
 */
class TmallServicecenterTaskFeedbacknoneedserviceRequest
{
	/** 
	 * 入参对象
	 **/
	private $param;
	
	private $apiParas = array();
	
	public function setParam($param)
	{
		$this->param = $param;
		$this->apiParas["param"] = $param;
	}

	public function getParam()
	{
		return $this->param;
	}

	public function getApiMethodName()
	{
		return "tmall.servicecenter.task.feedbacknoneedservice";
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
