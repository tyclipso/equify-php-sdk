<?php
namespace equifySDK;

use Exception;

class Request
{
	const EXCEPTION_CONNECTION_FAILED = 'Connection failed';
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_DELETE = 'DELETE';
	const METHOD_PUT = 'PUT';
	
	protected static $defaultAPIHost;
	protected static $defaultAPIPath;
	protected static $defaultAPIKey;
	protected static $defaultAPISecret;
	protected static $defaultAuthUser;
	protected static $defaultAuthPassword;
	
	protected $apiHost;
	protected $apiPath;
	protected $apiKey;
	protected $apiSecret;
	protected $endpoint;
	protected $method;
	
	private $params = array();
	private $authUser;
	private $authPassword;
	
	public static function setDefaultAPIHost($value)
	{
		static::$defaultAPIHost = $value;
	}
	
	public static function setDefaultAPIPath($value)
	{
		static::$defaultAPIPath = $value;
	}
	
	public static function setDefaultAPIKey($value)
	{
		static::$defaultAPIKey = $value;
	}
	
	public static function setDefaultAPISecret($value)
	{
		static::$defaultAPISecret = $value;
	}
	
	public static function setDefaultAuthData($username, $password)
	{
		static::$defaultAuthUser = $username;
		static::$defaultAuthPassword = $password;
	}
	
	public function __construct($endpoint, $method = self::METHOD_GET, $apiKey = null, $apiSecret = null, $apiHost = null, $apiPath = null)
	{
		$this->endpoint = $endpoint;
		$this->method = $method;
		if ($apiHost) $this->apiHost = $apiHost;
		else $this->apiHost = static::$defaultAPIHost;
		if ($apiPath) $this->apiPath = $apiPath;
		else $this->apiPath = static::$defaultAPIPath;
		if ($apiKey) $this->apiKey = $apiKey;
		else $this->apiKey = static::$defaultAPIKey;
		if ($apiSecret) $this->apiSecret = $apiSecret;
		else $this->apiSecret = static::$defaultAPISecret;
		$this->authUser = static::$defaultAuthUser;
		$this->authPassword = static::$defaultAuthPassword;
	}
	
	public function setAuthData($username, $password)
	{
		$this->authUser = $username;
		$this->authPassword = $password;
	}
	
	public function setParams(array $params)
	{
		$this->params = $params;
	}
	
	public function setParam($param, $value)
	{
		if ($value !== null) $this->params[$param] = $value;
		else unset($this->params[$param]);
	}
	
	public function getParams()
	{
		return $this->params;
	}
	
	protected function getSignature($timestamp)
	{
		$data = $this->apiPath.$this->endpoint.$timestamp.$this->apiSecret;
		return hash('sha256', $data);
	}
	
	protected function getSignedParams()
	{
		$params = $this->params;
		$params['apiKey'] = $this->apiKey;
		$params['timestamp'] = time();
		$params['hash'] = $this->getSignature($params['timestamp']);
		return $params;
	}
	
	/**
	 * Send the request
	 * @return Response
	 * @throws Exception
	 */
	public function send()
	{
		if (!$this->apiHost) throw new Exception(self::EXCEPTION_CONNECTION_FAILED);
		// Send request
		$ch = curl_init();
		$url = $this->apiHost.$this->apiPath.$this->endpoint;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Pass the fields depending on the request method
		if ($this->method == self::METHOD_POST) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->getSignedParams()));
		else $url .= '?'.http_build_query($this->getSignedParams());
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
		curl_setopt($ch, CURLOPT_URL, $url);
		// Add basic auth data if set
		if ($this->authUser) curl_setopt($ch, CURLOPT_USERPWD, $this->authUser . ":" . $this->authPassword);
		$result = curl_exec($ch);
		curl_close($ch);
		$response = new Response($result);
		return $response;
	}
}