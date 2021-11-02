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
	protected static $defaultCurlOptions = [];

	protected $apiHost;
	protected $apiPath;
	protected $apiKey;
	protected $apiSecret;
	protected $endpoint;
	protected $method;
	
	private $params = [];
    private $curlOptions = [];
	private $authUser;
	private $authPassword;

    /**
     * @param ?string $value
     */
	public static function setDefaultAPIHost(?string $value)
	{
		static::$defaultAPIHost = $value;
	}

    /**
     * @param ?string $value
     */
	public static function setDefaultAPIPath(?string $value)
	{
		static::$defaultAPIPath = $value;
	}

    /**
     * @param ?string $value
     */
	public static function setDefaultAPIKey(?string $value)
	{
		static::$defaultAPIKey = $value;
	}

    /**
     * @param ?string $value
     */
	public static function setDefaultAPISecret(?string $value)
	{
		static::$defaultAPISecret = $value;
	}

    /**
     * @param ?string $username
     * @param ?string $password
     */
	public static function setDefaultAuthData(?string $username, ?string $password)
	{
		static::$defaultAuthUser = $username;
		static::$defaultAuthPassword = $password;
	}

    /**
     * You can set an array with default curl options that are used on every request.
     * They can be overridden for individual requests with the setCurlOption() method
     * @param array $options
     */
    public static function setDefaultCurlOptions(array $options)
    {
        static::$defaultCurlOptions = $options;
    }

    /**
     * Get the currently set default curl options
     * @return array
     */
    public static function getDefaultCurlOptions(): array
    {
        return static::$defaultCurlOptions;
    }

    /**
     * The default connection timeout.
     * @param ?int $timeout
     */
    public static function setDefaultConnectionTimeout(?int $timeout)
    {
        static::$defaultCurlOptions[CURLOPT_CONNECTTIMEOUT] = $timeout;
    }

    /**
     * The default timeout for the whole curl transaction.
     * @param ?int $timeout
     */
    public static function setDefaultTimeout(?int $timeout)
    {
        static::$defaultCurlOptions[CURLOPT_TIMEOUT] = $timeout;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param ?string $apiKey
     * @param ?string $apiSecret
     * @param ?string $apiHost
     * @param ?string $apiPath
     */
	public function __construct(string $endpoint, string $method = self::METHOD_GET, ?string $apiKey = null, ?string $apiSecret = null, ?string $apiHost = null, ?string $apiPath = null)
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
        $this->curlOptions = static::$defaultCurlOptions;
	}

    /**
     * @param ?string $username
     * @param ?string $password
     */
	public function setAuthData(?string $username, ?string $password)
	{
		$this->authUser = $username;
		$this->authPassword = $password;
	}

    /**
     * @param array $params
     */
	public function setParams(array $params)
	{
		$this->params = $params;
	}

    /**
     * @param mixed $param
     * @param mixed $value
     */
	public function setParam($param, $value)
	{
		if ($value !== null) $this->params[$param] = $value;
		else unset($this->params[$param]);
	}

    /**
     * @return array
     */
	public function getParams(): array
	{
		return $this->params;
	}

    /**
     * @param mixed $key The curl option, something like CURLOPT_TIMEOUT
     * @param mixed $value The value of the option
     */
    public function setCurlOption($key, $value)
    {
        $this->curlOptions[$key] = $value;
    }

    /**
     * @param mixed $key The curl option, something like CURLOPT_TIMEOUT
     * @return mixed|null
     */
    public function getCurlOption($key)
    {
        if (isset($this->curlOptions[$key])) return $this->curlOptions[$key];
        return null;
    }

    /**
     * Sets the timeout for this specific request
     * @param ?int $timeout
     */
    public function setTimeout(?int $timeout)
    {
        $this->setCurlOption(CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * Sets the connection timeout for this specific request
     * @param ?int $timeout
     */
    public function setConnectionTimeout(?int $timeout)
    {
        $this->setCurlOption(CURLOPT_CONNECTTIMEOUT, $timeout);
    }

    /**
     * @param $timestamp
     * @return string
     */
	protected function getSignature($timestamp): string
	{
		$data = $this->apiPath.$this->endpoint.$timestamp.$this->apiSecret;
		return (string)hash('sha256', $data);
	}

    /**
     * @return array
     */
	protected function getSignedParams(): array
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
	public function send(): Response
	{
		if (!$this->apiHost) throw new Exception(self::EXCEPTION_CONNECTION_FAILED);
		// Send request
		$ch = curl_init();
		$url = $this->apiHost.$this->apiPath.$this->endpoint;
        // Add user defined curl options
        foreach ($this->curlOptions as $cKey => $cValue) {
            curl_setopt($ch, $cKey, $cValue);
        }
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