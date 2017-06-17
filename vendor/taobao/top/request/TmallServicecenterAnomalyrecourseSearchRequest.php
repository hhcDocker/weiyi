<?php
/**
 * TOP API: tmall.servicecenter.anomalyrecourse.search request
 * 
 * @author auto create
 * @since 1.0, 2016.08.10
 */
class TmallServicecenterAnomalyrecourseSearchRequest
{
	/** 
	 * 结束时间
	 **/
	private $end;
	
	/** 
	 * 开始时间
	 **/
	private $start;
	
	private $apiParas = array();
	
	public function setEnd($end)
	{
		$this->end = $end;
		$this->apiParas["end"] = $end;
	}

	public function getEnd()
	{
		return $this->end;
	}

	public function setStart($start)
	{
		$this->start = $start;
		$this->apiParas["start"] = $start;
	}

	public function getStart()
	{
		return $this->start;
	}

	public function getApiMethodName()
	{
		return "tmall.servicecenter.anomalyrecourse.search";
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
