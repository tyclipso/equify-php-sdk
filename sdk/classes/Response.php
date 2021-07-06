<?php
namespace equifySDK;

use Exception;

class Response
{
	const EXCEPTION_INVALID_RESPONSE = 'Invalid response';
	
	const STATUS_CODE_SUCCESS = 0;
	
	protected $data;

	public function __construct($jsonResponse)
	{
		$this->data = json_decode($jsonResponse);
		if ($this->data === null || !$this->data->status){
		    throw new Exception(self::EXCEPTION_INVALID_RESPONSE.': '.$jsonResponse);
        }
	}
	
	public function success()
	{
		if ($this->getStatusCode() == self::STATUS_CODE_SUCCESS) return true;
		return false;
	}
	
	public function getStatusCode()
	{
		return $this->data->status->code;
	}
	
	public function getStatusMessage()
	{
		return $this->data->status->message;
	}
	
	public function getStatusDescription()
	{
		return $this->data->status->description;
	}
	
	public function getData($element)
	{
		return $this->data->{$element};
	}
}