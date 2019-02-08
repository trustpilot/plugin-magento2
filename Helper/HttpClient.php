<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use \Psr\Log\LoggerInterface;

class HttpClient extends AbstractHelper
{
    const HTTP_REQUEST_TIMEOUT = 3;
    protected $_logger;

    public function __construct(LoggerInterface $logger) 
    {
        $this->_logger = $logger;  
    }

    public function request($url, $httpRequest, $origin = null, $data = null, $params = array(), $timeout = self::HTTP_REQUEST_TIMEOUT)
    {
        try{
            $ch = curl_init();
            $this->setCurlOptions($ch, $httpRequest, $data, $origin, $timeout);
            $url = $this->buildParams($url, $params);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            $responseData = json_decode($content);
            $responseInfo = curl_getinfo($ch);
            $responseCode = $responseInfo['http_code'];
            curl_close($ch);
            $response = array();
            $response['code'] = $responseCode;
            if (is_object($responseData) || is_array($responseData)) {
                $response['data'] = $responseData;
            }
            return $response;
        } catch (\Exception $e){
            //intentionally empty
        }
    }
    
    private  function jsonEncoder($data)
	{
		if (function_exists('json_encode'))
			return json_encode($data);
		elseif (method_exists('Tools', 'jsonEncode'))
			return Tools::jsonEncode($data);
    }
    
    private function setCurlOptions($ch, $httpRequest, $data, $origin, $timeout)
    { 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($httpRequest == 'POST') {
            $encoded_data = $this->jsonEncoder($data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('content-type: application/json', 'Content-Length: ' . strlen($encoded_data), 'Origin: ' . $origin));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded_data);
            return;
        } elseif ($httpRequest == 'GET') {
            curl_setopt($ch, CURLOPT_POST, false);
            return;
        }
        return;
    }

    private function buildParams($url, $params = array())
    {
        if (!empty($params) && is_array($params)) {
            $url .= '?'.http_build_query($params);
        }
        return $url;
    }
}
