<?php

namespace Trustpilot\Reviews\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Trustpilot\Reviews\Helper\HttpClient;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\UrlInterface;

class TrustpilotHttpClient extends AbstractHelper
{
    protected $_httpClient;
    protected $_apiUrl;
    protected $_storeManager;

    public function __construct(
        HttpClient $httpClient, 
        StoreManagerInterface $storeManager)
    {
        $this->_httpClient = $httpClient;
        $this->_storeManager = $storeManager;
        $this->_apiUrl = \Trustpilot\Reviews\Model\Config::TRUSTPILOT_API_URL;
    }

    public function post($url, $origin, $data)
    {
        $httpRequest = "POST";
        return $this->_httpClient->request(
            $url,
            $httpRequest,
            $origin,
            $data
        );
    }

    public function buildUrl($key, $endpoint)
    {
        return $this->_apiUrl . $key . $endpoint;
    }

    public function postInvitation($key, $storeId, $data = array())
    {
        $origin = $this->_storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        return $this->post($this->buildUrl($key, '/invitation'), $origin, $data);
    }

    public function postSettings($key, $data)
    {
        $origin = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        return $this->post($this->buildUrl($key, '/settings'), $origin, $data);
    }

    public function postBatchInvitations($key, $storeId, $data = array())
    {
        $origin = $this->_storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        return $this->post($this->buildUrl($key, '/batchinvitations'), $origin, $data);
    }

    public function postLog($data, $storeId = null)
    {
        try {
            $origin = $storeId ? $this->_storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_WEB) : '';
            return $this->post($this->_apiUrl . 'log',  $origin, $data);
        } catch (\Exception $e) {
            return false;
        }
    }
}
